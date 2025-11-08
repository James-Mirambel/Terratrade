<?php
/**
 * Contract Class - TerraTrade Full-Stack Implementation
 * Handles all PSA contract-related operations
 */

class Contract {
    private $conn;
    private $table = 'contracts';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new contract from accepted offer
    public function create($data) {
        // Required fields validation
        $required = ['offer_id', 'listing_id', 'buyer_id', 'seller_id', 'purchase_price'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        // Get listing details for property information
        $listingQuery = "SELECT * FROM listings WHERE id = :listing_id";
        $listingStmt = $this->conn->prepare($listingQuery);
        $listingStmt->bindParam(':listing_id', $data['listing_id']);
        $listingStmt->execute();
        $listing = $listingStmt->fetch();
        
        if (!$listing) {
            return false;
        }
        
        // Prepare property details
        $propertyDetails = [
            'title' => $listing['title'],
            'description' => $listing['description'],
            'area' => $listing['area_sqm'],
            'zoning' => $listing['zoning'],
            'location' => $listing['city'] . ', ' . $listing['province'],
            'coordinates' => $listing['coordinates']
        ];
        
        $query = "INSERT INTO " . $this->table . " 
                  (offer_id, listing_id, buyer_id, seller_id, purchase_price, earnest_money, 
                   closing_date, property_details, contingencies, inclusions, exclusions, 
                   special_terms, generated_at) 
                  VALUES (:offer_id, :listing_id, :buyer_id, :seller_id, :purchase_price, 
                          :earnest_money, :closing_date, :property_details, :contingencies, 
                          :inclusions, :exclusions, :special_terms, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':offer_id', $data['offer_id']);
        $stmt->bindParam(':listing_id', $data['listing_id']);
        $stmt->bindParam(':buyer_id', $data['buyer_id']);
        $stmt->bindParam(':seller_id', $data['seller_id']);
        $stmt->bindParam(':purchase_price', $data['purchase_price']);
        $stmt->bindParam(':earnest_money', $data['earnest_money'] ?? null);
        $stmt->bindParam(':closing_date', $data['closing_date'] ?? null);
        $stmt->bindParam(':property_details', json_encode($propertyDetails));
        $stmt->bindParam(':contingencies', json_encode($data['contingencies'] ?? []));
        $stmt->bindParam(':inclusions', $data['inclusions'] ?? '');
        $stmt->bindParam(':exclusions', $data['exclusions'] ?? '');
        $stmt->bindParam(':special_terms', $data['special_terms'] ?? '');
        
        if ($stmt->execute()) {
            $contractId = $this->conn->lastInsertId();
            
            // Log activity
            $this->logActivity($data['buyer_id'], 'contract_generated', 'contract', $contractId);
            $this->logActivity($data['seller_id'], 'contract_generated', 'contract', $contractId);
            
            // Create notifications
            $this->createNotification($data['buyer_id'], 'PSA Contract Generated', 
                'Your Purchase and Sale Agreement has been generated and is ready for signature.', 
                'contract', $contractId, 'contract');
            
            $this->createNotification($data['seller_id'], 'PSA Contract Generated', 
                'A Purchase and Sale Agreement has been generated and is ready for signature.', 
                'contract', $contractId, 'contract');
            
            return $this->getById($contractId);
        }
        
        return false;
    }
    
    // Get contract by ID
    public function getById($id, $userId = null) {
        $query = "SELECT c.*, 
                         b.name as buyer_name, b.email as buyer_email,
                         s.name as seller_name, s.email as seller_email,
                         l.title as listing_title
                  FROM " . $this->table . " c
                  JOIN users b ON c.buyer_id = b.id
                  JOIN users s ON c.seller_id = s.id
                  JOIN listings l ON c.listing_id = l.id
                  WHERE c.id = :id";
        
        // If userId provided, verify access
        if ($userId) {
            $query .= " AND (c.buyer_id = :user_id OR c.seller_id = :user_id)";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        if ($userId) {
            $stmt->bindParam(':user_id', $userId);
        }
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $contract = $stmt->fetch();
            
            // Get signatures
            $contract['signatures'] = $this->getSignatures($id);
            
            return $this->processContractForFrontend($contract);
        }
        
        return false;
    }
    
    // Get contracts by user (buyer or seller)
    public function getByUser($userId) {
        $query = "SELECT c.*, 
                         b.name as buyer_name, b.email as buyer_email,
                         s.name as seller_name, s.email as seller_email,
                         l.title as listing_title, l.city, l.province
                  FROM " . $this->table . " c
                  JOIN users b ON c.buyer_id = b.id
                  JOIN users s ON c.seller_id = s.id
                  JOIN listings l ON c.listing_id = l.id
                  WHERE c.buyer_id = :user_id OR c.seller_id = :user_id
                  ORDER BY c.generated_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $contracts = $stmt->fetchAll();
        
        foreach ($contracts as &$contract) {
            // Get signatures for each contract
            $contract['signatures'] = $this->getSignatures($contract['id']);
            $contract = $this->processContractForFrontend($contract);
        }
        
        return $contracts;
    }
    
    // Sign contract
    public function sign($contractId, $userId) {
        // Get contract details
        $contract = $this->getById($contractId, $userId);
        if (!$contract) {
            return false; // Contract not found or no access
        }
        
        // Check if user already signed
        $existingSignature = $this->getUserSignature($contractId, $userId);
        if ($existingSignature) {
            return false; // Already signed
        }
        
        // Determine role
        $role = ($contract['buyer_id'] == $userId) ? 'buyer' : 'seller';
        
        // Get user details
        $userQuery = "SELECT email FROM users WHERE id = :user_id";
        $userStmt = $this->conn->prepare($userQuery);
        $userStmt->bindParam(':user_id', $userId);
        $userStmt->execute();
        $user = $userStmt->fetch();
        
        // Create signature
        $signatureHash = hash('sha256', $contractId . $userId . time() . rand());
        
        $signQuery = "INSERT INTO contract_signatures 
                      (contract_id, signer_id, signer_email, role, signature_hash, ip_address, signed_at) 
                      VALUES (:contract_id, :signer_id, :signer_email, :role, :signature_hash, :ip_address, NOW())";
        
        $signStmt = $this->conn->prepare($signQuery);
        $signStmt->bindParam(':contract_id', $contractId);
        $signStmt->bindParam(':signer_id', $userId);
        $signStmt->bindParam(':signer_email', $user['email']);
        $signStmt->bindParam(':role', $role);
        $signStmt->bindParam(':signature_hash', $signatureHash);
        $signStmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'] ?? null);
        
        if ($signStmt->execute()) {
            // Update contract signatures count
            $updateQuery = "UPDATE " . $this->table . " SET signatures_collected = signatures_collected + 1 WHERE id = :contract_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':contract_id', $contractId);
            $updateStmt->execute();
            
            // Check if contract is fully executed
            $this->checkFullyExecuted($contractId);
            
            // Log activity
            $this->logActivity($userId, 'contract_signed', 'contract', $contractId);
            
            // Create notification for other party
            $otherPartyId = ($role === 'buyer') ? $contract['seller_id'] : $contract['buyer_id'];
            $this->createNotification($otherPartyId, 'Contract Signed', 
                "The other party has signed the PSA contract.", 'contract', $contractId, 'contract');
            
            return true;
        }
        
        return false;
    }
    
    // Get contract signatures
    private function getSignatures($contractId) {
        $query = "SELECT * FROM contract_signatures WHERE contract_id = :contract_id ORDER BY signed_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':contract_id', $contractId);
        $stmt->execute();
        
        $signatures = $stmt->fetchAll();
        
        // Process for frontend compatibility
        foreach ($signatures as &$signature) {
            $signature['signer'] = $signature['signer_email'];
            $signature['signedAt'] = $signature['signed_at'];
            $signature['signatureHash'] = $signature['signature_hash'];
            $signature['ipAddress'] = $signature['ip_address'];
        }
        
        return $signatures;
    }
    
    // Get user's signature for a contract
    private function getUserSignature($contractId, $userId) {
        $query = "SELECT * FROM contract_signatures WHERE contract_id = :contract_id AND signer_id = :signer_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':contract_id', $contractId);
        $stmt->bindParam(':signer_id', $userId);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    // Check if contract is fully executed
    private function checkFullyExecuted($contractId) {
        $query = "SELECT signatures_collected, signatures_required FROM " . $this->table . " WHERE id = :contract_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':contract_id', $contractId);
        $stmt->execute();
        $contract = $stmt->fetch();
        
        if ($contract && $contract['signatures_collected'] >= $contract['signatures_required']) {
            // Update status to fully executed
            $updateQuery = "UPDATE " . $this->table . " SET status = 'fully_executed', executed_at = NOW() WHERE id = :contract_id";
            $updateStmt = $this->conn->prepare($updateQuery);
            $updateStmt->bindParam(':contract_id', $contractId);
            $updateStmt->execute();
            
            // Create escrow account
            $this->createEscrowAccount($contractId);
            
            // Get contract details for notifications
            $contractDetails = $this->getById($contractId);
            if ($contractDetails) {
                $this->createNotification($contractDetails['buyer_id'], 'Contract Fully Executed', 
                    'The PSA contract has been fully executed. Escrow account has been created.', 
                    'contract', $contractId, 'contract');
                
                $this->createNotification($contractDetails['seller_id'], 'Contract Fully Executed', 
                    'The PSA contract has been fully executed. Escrow account has been created.', 
                    'contract', $contractId, 'contract');
            }
        }
    }
    
    // Create escrow account for fully executed contract
    private function createEscrowAccount($contractId) {
        $contract = $this->getById($contractId);
        if (!$contract) return false;
        
        $escrowQuery = "INSERT INTO escrow_accounts 
                        (offer_id, listing_id, buyer_id, seller_id, amount, created_at) 
                        VALUES (:offer_id, :listing_id, :buyer_id, :seller_id, :amount, NOW())";
        
        $escrowStmt = $this->conn->prepare($escrowQuery);
        $escrowStmt->bindParam(':offer_id', $contract['offer_id']);
        $escrowStmt->bindParam(':listing_id', $contract['listing_id']);
        $escrowStmt->bindParam(':buyer_id', $contract['buyer_id']);
        $escrowStmt->bindParam(':seller_id', $contract['seller_id']);
        $escrowStmt->bindParam(':amount', $contract['earnest_money'] ?? $contract['purchase_price']);
        
        return $escrowStmt->execute();
    }
    
    // Cancel contract
    public function cancel($contractId, $userId, $reason = '') {
        $contract = $this->getById($contractId, $userId);
        if (!$contract) {
            return false;
        }
        
        $query = "UPDATE " . $this->table . " SET status = 'cancelled', cancelled_at = NOW() WHERE id = :contract_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':contract_id', $contractId);
        
        if ($stmt->execute()) {
            $this->logActivity($userId, 'contract_cancelled', 'contract', $contractId, ['reason' => $reason]);
            
            // Notify other party
            $otherPartyId = ($contract['buyer_id'] == $userId) ? $contract['seller_id'] : $contract['buyer_id'];
            $this->createNotification($otherPartyId, 'Contract Cancelled', 
                'The PSA contract has been cancelled.', 'contract', $contractId, 'contract');
            
            return true;
        }
        
        return false;
    }
    
    // Process contract for frontend compatibility
    private function processContractForFrontend($contract) {
        // Parse JSON fields
        $contract['property_details'] = json_decode($contract['property_details'] ?? '[]', true);
        $contract['contingencies'] = json_decode($contract['contingencies'] ?? '[]', true);
        
        // Frontend compatibility fields
        $contract['buyer'] = $contract['buyer_email'];
        $contract['seller'] = $contract['seller_email'];
        $contract['generatedAt'] = $contract['generated_at'];
        $contract['executedAt'] = $contract['executed_at'];
        $contract['signaturesCollected'] = $contract['signatures_collected'];
        $contract['signaturesRequired'] = $contract['signatures_required'];
        
        // Property details for frontend
        if ($contract['property_details']) {
            $contract['property'] = $contract['property_details'];
        }
        
        return $contract;
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
