<?php
/**
 * Message Class - TerraTrade Full-Stack Implementation
 * Handles all messaging operations
 */

class Message {
    private $conn;
    private $table = 'messages';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Send message
    public function send($data) {
        // Required fields validation
        $required = ['sender_id', 'recipient_id', 'message'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        $query = "INSERT INTO " . $this->table . " 
                  (sender_id, recipient_id, listing_id, offer_id, subject, message, message_type, sent_at) 
                  VALUES (:sender_id, :recipient_id, :listing_id, :offer_id, :subject, :message, :message_type, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':sender_id', $data['sender_id']);
        $stmt->bindParam(':recipient_id', $data['recipient_id']);
        $stmt->bindParam(':listing_id', $data['listing_id'] ?? null);
        $stmt->bindParam(':offer_id', $data['offer_id'] ?? null);
        $stmt->bindParam(':subject', $data['subject'] ?? '');
        $stmt->bindParam(':message', $data['message']);
        $stmt->bindParam(':message_type', $data['message_type'] ?? 'general');
        
        if ($stmt->execute()) {
            $messageId = $this->conn->lastInsertId();
            
            // Log activity
            $this->logActivity($data['sender_id'], 'message_sent', 'message', $messageId);
            
            // Create notification for recipient
            $this->createNotification($data['recipient_id'], 'New Message', 
                'You have received a new message.', 'message', $messageId, 'message');
            
            return $this->getById($messageId);
        }
        
        return false;
    }
    
    // Get message by ID
    public function getById($id) {
        $query = "SELECT m.*, 
                         s.name as sender_name, s.email as sender_email,
                         r.name as recipient_name, r.email as recipient_email,
                         l.title as listing_title
                  FROM " . $this->table . " m
                  JOIN users s ON m.sender_id = s.id
                  JOIN users r ON m.recipient_id = r.id
                  LEFT JOIN listings l ON m.listing_id = l.id
                  WHERE m.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            return $stmt->fetch();
        }
        
        return false;
    }
    
    // Get user's inbox
    public function getInbox($userId, $limit = 50) {
        $query = "SELECT m.*, 
                         s.name as sender_name, s.email as sender_email,
                         l.title as listing_title
                  FROM " . $this->table . " m
                  JOIN users s ON m.sender_id = s.id
                  LEFT JOIN listings l ON m.listing_id = l.id
                  WHERE m.recipient_id = :user_id
                  ORDER BY m.sent_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // Get user's sent messages
    public function getSent($userId, $limit = 50) {
        $query = "SELECT m.*, 
                         r.name as recipient_name, r.email as recipient_email,
                         l.title as listing_title
                  FROM " . $this->table . " m
                  JOIN users r ON m.recipient_id = r.id
                  LEFT JOIN listings l ON m.listing_id = l.id
                  WHERE m.sender_id = :user_id
                  ORDER BY m.sent_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // Get conversation between two users
    public function getConversation($userId1, $userId2, $limit = 50) {
        $query = "SELECT m.*, 
                         s.name as sender_name, s.email as sender_email,
                         r.name as recipient_name, r.email as recipient_email,
                         l.title as listing_title
                  FROM " . $this->table . " m
                  JOIN users s ON m.sender_id = s.id
                  JOIN users r ON m.recipient_id = r.id
                  LEFT JOIN listings l ON m.listing_id = l.id
                  WHERE (m.sender_id = :user1 AND m.recipient_id = :user2) 
                     OR (m.sender_id = :user2 AND m.recipient_id = :user1)
                  ORDER BY m.sent_at ASC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user1', $userId1);
        $stmt->bindParam(':user2', $userId2);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // Get messages related to a listing
    public function getByListing($listingId, $userId = null) {
        $query = "SELECT m.*, 
                         s.name as sender_name, s.email as sender_email,
                         r.name as recipient_name, r.email as recipient_email
                  FROM " . $this->table . " m
                  JOIN users s ON m.sender_id = s.id
                  JOIN users r ON m.recipient_id = r.id
                  WHERE m.listing_id = :listing_id";
        
        if ($userId) {
            $query .= " AND (m.sender_id = :user_id OR m.recipient_id = :user_id)";
        }
        
        $query .= " ORDER BY m.sent_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':listing_id', $listingId);
        if ($userId) {
            $stmt->bindParam(':user_id', $userId);
        }
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // Mark message as read
    public function markAsRead($messageId, $userId) {
        // Verify user is the recipient
        $query = "UPDATE " . $this->table . " 
                  SET is_read = 1, read_at = NOW() 
                  WHERE id = :message_id AND recipient_id = :user_id AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':message_id', $messageId);
        $stmt->bindParam(':user_id', $userId);
        
        if ($stmt->execute() && $stmt->rowCount() > 0) {
            $this->logActivity($userId, 'message_read', 'message', $messageId);
            return true;
        }
        
        return false;
    }
    
    // Mark all messages as read for a user
    public function markAllAsRead($userId) {
        $query = "UPDATE " . $this->table . " 
                  SET is_read = 1, read_at = NOW() 
                  WHERE recipient_id = :user_id AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        
        if ($stmt->execute()) {
            $this->logActivity($userId, 'messages_read_all', 'message', null);
            return $stmt->rowCount();
        }
        
        return false;
    }
    
    // Get unread message count
    public function getUnreadCount($userId) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " 
                  WHERE recipient_id = :user_id AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $result = $stmt->fetch();
        return $result ? $result['count'] : 0;
    }
    
    // Delete message (soft delete by marking as deleted)
    public function delete($messageId, $userId) {
        // For now, we'll just mark as read instead of actual deletion
        // In a full implementation, you might add a deleted_by field
        return $this->markAsRead($messageId, $userId);
    }
    
    // Search messages
    public function search($userId, $query, $limit = 20) {
        $searchQuery = "SELECT m.*, 
                               s.name as sender_name, s.email as sender_email,
                               r.name as recipient_name, r.email as recipient_email,
                               l.title as listing_title
                        FROM " . $this->table . " m
                        JOIN users s ON m.sender_id = s.id
                        JOIN users r ON m.recipient_id = r.id
                        LEFT JOIN listings l ON m.listing_id = l.id
                        WHERE (m.sender_id = :user_id OR m.recipient_id = :user_id)
                          AND (m.subject LIKE :query OR m.message LIKE :query 
                               OR s.name LIKE :query OR r.name LIKE :query)
                        ORDER BY m.sent_at DESC
                        LIMIT :limit";
        
        $stmt = $this->conn->prepare($searchQuery);
        $searchTerm = "%$query%";
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':query', $searchTerm);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    // Get message statistics for user
    public function getStats($userId) {
        $stats = [];
        
        // Total received
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE recipient_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $stats['received'] = $stmt->fetch()['count'];
        
        // Total sent
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE sender_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $stats['sent'] = $stmt->fetch()['count'];
        
        // Unread
        $stats['unread'] = $this->getUnreadCount($userId);
        
        return $stats;
    }
    
    // Log activity
    private function logActivity($userId, $action, $entityType, $entityId, $details = null) {
        $query = "INSERT INTO activity_log 
                  (user_id, action, entity_type, entity_id, details, ip_address, created_at) 
                  VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':entity_type', $entityType);
        $stmt->bindParam(':entity_id', $entityId);
        $stmt->bindParam(':details', json_encode($details));
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'] ?? null);
        
        $stmt->execute();
    }
    
    // Create notification
    private function createNotification($userId, $title, $message, $type, $relatedId = null, $relatedType = null) {
        $query = "INSERT INTO notifications 
                  (user_id, title, message, type, related_id, related_type, created_at) 
                  VALUES (:user_id, :title, :message, :type, :related_id, :related_type, NOW())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':related_id', $relatedId);
        $stmt->bindParam(':related_type', $relatedType);
        
        $stmt->execute();
    }
}
?>
