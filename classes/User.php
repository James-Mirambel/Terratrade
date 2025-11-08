<?php
/**
 * User Class - TerraTrade Full-Stack Implementation
 * Handles all user-related operations
 */

class User {
    private $conn;
    private $table = 'users';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Register new user (simplified without roles)
    public function register($name, $email, $password, $kyc = false) {
        // Check if user already exists
        $query = "SELECT id FROM " . $this->table . " WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            return false; // User already exists
        }
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new user
        $query = "INSERT INTO " . $this->table . " 
                  (name, email, password_hash, kyc_verified, created_at) 
                  VALUES (:name, :email, :password_hash, :kyc_verified, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':kyc_verified', $kyc, PDO::PARAM_BOOL);
        
        if ($stmt->execute()) {
            $userId = $this->conn->lastInsertId();
            
            // Log activity
            $this->logActivity($userId, 'user_registered', 'user', $userId);
            
            return $this->getById($userId);
        }
        
        return false;
    }
    
    // Login user
    public function login($email, $password) {
        // Find user by email
        $query = "SELECT * FROM " . $this->table . " WHERE email = :email AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch();
            
            // For demo purposes, allow 'demo' password or verify actual password
            if ($password === 'demo' || password_verify($password, $user['password_hash'])) {
                // Update last login
                $updateQuery = "UPDATE " . $this->table . " SET last_login = NOW() WHERE id = :id";
                $updateStmt = $this->conn->prepare($updateQuery);
                $updateStmt->bindParam(':id', $user['id']);
                $updateStmt->execute();
                
                // Log activity
                $this->logActivity($user['id'], 'user_login', 'user', $user['id']);
                
                // Remove sensitive data
                unset($user['password_hash']);
                
                return $user;
            }
        }
        
        return false;
    }
    
    // Get user by ID
    public function getById($id) {
        $query = "SELECT id, name, email, kyc_verified, signed_docs, created_at, last_login 
                  FROM " . $this->table . " WHERE id = :id AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch();
            
            // Parse JSON fields
            $user['signed_docs'] = json_decode($user['signed_docs'] ?? '[]', true);
            $user['kyc'] = (bool)$user['kyc_verified']; // For frontend compatibility
            
            return $user;
        }
        
        return false;
    }
    
    // Update user profile
    public function updateProfile($id, $data) {
        $allowedFields = ['name', 'kyc_verified'];
        $updateFields = [];
        $params = [':id' => $id];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        $query = "UPDATE " . $this->table . " SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute($params)) {
            $this->logActivity($id, 'profile_updated', 'user', $id);
            return true;
        }
        
        return false;
    }
    
    // Add signed document
    public function addSignedDocument($userId, $document) {
        $user = $this->getById($userId);
        if (!$user) return false;
        
        $signedDocs = $user['signed_docs'];
        $signedDocs[] = $document;
        
        $query = "UPDATE " . $this->table . " SET signed_docs = :signed_docs WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':signed_docs', json_encode($signedDocs));
        $stmt->bindParam(':id', $userId);
        
        if ($stmt->execute()) {
            $this->logActivity($userId, 'document_signed', 'document', null, $document);
            return true;
        }
        
        return false;
    }
    
    // Get all users (admin function)
    public function getAll($limit = 50, $offset = 0) {
        $query = "SELECT id, name, email, kyc_verified, created_at, last_login 
                  FROM " . $this->table . " 
                  WHERE is_active = 1 
                  ORDER BY created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // Search users
    public function search($query, $limit = 20) {
        $searchQuery = "SELECT id, name, email, kyc_verified, created_at 
                        FROM " . $this->table . " 
                        WHERE is_active = 1 AND (name LIKE :query OR email LIKE :query)
                        ORDER BY name ASC 
                        LIMIT :limit";
        
        $stmt = $this->conn->prepare($searchQuery);
        $searchTerm = "%$query%";
        $stmt->bindParam(':query', $searchTerm);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // Deactivate user account
    public function deactivate($id) {
        $query = "UPDATE " . $this->table . " SET is_active = 0, updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $this->logActivity($id, 'account_deactivated', 'user', $id);
            return true;
        }
        
        return false;
    }
    
    // Log user activity
    private function logActivity($userId, $action, $entityType = null, $entityId = null, $details = null) {
        $query = "INSERT INTO activity_log 
                  (user_id, action, entity_type, entity_id, details, ip_address, user_agent, created_at) 
                  VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address, :user_agent, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':entity_type', $entityType);
        $stmt->bindParam(':entity_id', $entityId);
        $stmt->bindParam(':details', json_encode($details));
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'] ?? null);
        $stmt->bindParam(':user_agent', $_SERVER['HTTP_USER_AGENT'] ?? null);
        
        $stmt->execute();
    }
    
    // Get user activity log
    public function getActivityLog($userId, $limit = 50) {
        $query = "SELECT * FROM activity_log 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $activities = $stmt->fetchAll();
        
        // Parse JSON details
        foreach ($activities as &$activity) {
            $activity['details'] = json_decode($activity['details'] ?? '{}', true);
        }
        
        return $activities;
    }
    
    // Get user statistics
    public function getStats($userId) {
        $stats = [];
        
        // Get listing count
        $query = "SELECT COUNT(*) as count FROM listings WHERE owner_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $stats['listings_count'] = $stmt->fetch()['count'];
        
        // Get offers made count
        $query = "SELECT COUNT(*) as count FROM offers WHERE buyer_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $stats['offers_made'] = $stmt->fetch()['count'];
        
        // Get offers received count
        $query = "SELECT COUNT(*) as count FROM offers o 
                  JOIN listings l ON o.listing_id = l.id 
                  WHERE l.owner_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $stats['offers_received'] = $stmt->fetch()['count'];
        
        // Get contracts count
        $query = "SELECT COUNT(*) as count FROM contracts 
                  WHERE buyer_id = :user_id OR seller_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $stats['contracts_count'] = $stmt->fetch()['count'];
        
        // Get favorites count
        $query = "SELECT COUNT(*) as count FROM favorites WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $stats['favorites_count'] = $stmt->fetch()['count'];
        
        return $stats;
    }
}
?>
