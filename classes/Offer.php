<?php
/**
 * Offer Class - TerraTrade Full-Stack Implementation
 * Handles all offer-related operations
 */

class Offer {
    private $conn;
    private $table = 'offers';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new offer
    public function create($data) {
        // Required fields validation
        $required = ['listing_id', 'buyer_id', 'price'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        // Check if listing exists and is active
        $listingQuery = "SELECT id, owner_id, title FROM listings WHERE id = :listing_id AND status = 'active'";
        $listingStmt = $this->conn->prepare($listingQuery);
        $listingStmt->bindParam(':listing_id', $data['listing_id']);
        $listingStmt->execute();
        
        if ($listingStmt->rowCount() === 0) {
            return false; // Listing not found or not active
        }
        
        $listing = $listingStmt->fetch();
        
        // Prevent self-offers
        if ($listing['owner_id'] == $data['buyer_id']) {
            return false;
        }
        
        $query = "INSERT INTO " . $this->table . " 
                  (listing_id, buyer_id, price, earnest_money, closing_date, buyer_comments,
                   contingencies, inclusions, exclusions, special_terms, financing_contingency,
                   financing_days, survey_contingency, survey_days, title_contingency, title_days,
                   environmental_contingency, environmental_days, history, submitted_at) 
                  VALUES (:listing_id, :buyer_id, :price, :earnest_money, :closing_date, :buyer_comments,
                          :contingencies, :inclusions, :exclusions, :special_terms, :financing_contingency,
                          :financing_days, :survey_contingency, :survey_days, :title_contingency, :title_days,
                          :environmental_contingency, :environmental_days, :history, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        // Prepare history
        $history = [
            [
                'action' => 'submitted',
                'timestamp' => time() * 1000, // JavaScript timestamp format
                'actor' => $data['buyer_email'] ?? '',
                'details' => 'Offer submitted'
            ]
        ];
        
        // Bind parameters
        $stmt->bindParam(':listing_id', $data['listing_id']);
        $stmt->bindParam(':buyer_id', $data['buyer_id']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':earnest_money', $data['earnest_money'] ?? null);
        $stmt->bindParam(':closing_date', $data['closing_date'] ?? null);
        $stmt->bindParam(':buyer_comments', $data['buyer_comments'] ?? '');
        $stmt->bindParam(':contingencies', json_encode($data['contingencies'] ?? []));
        $stmt->bindParam(':inclusions', $data['inclusions'] ?? '');
        $stmt->bindParam(':exclusions', $data['exclusions'] ?? '');
        $stmt->bindParam(':special_terms', $data['special_terms'] ?? '');
        $stmt->bindParam(':financing_contingency', $data['financing_contingency'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindParam(':financing_days', $data['financing_days'] ?? 30);
        $stmt->bindParam(':survey_contingency', $data['survey_contingency'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindParam(':survey_days', $data['survey_days'] ?? 10);
        $stmt->bindParam(':title_contingency', $data['title_contingency'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindParam(':title_days', $data['title_days'] ?? 15);
        $stmt->bindParam(':environmental_contingency', $data['environmental_contingency'] ?? false, PDO::PARAM_BOOL);
        $stmt->bindParam(':environmental_days', $data['environmental_days'] ?? 20);
        $stmt->bindParam(':history', json_encode($history));
        
        if ($stmt->execute()) {
            $offerId = $this->conn->lastInsertId();
            
            // Log activity
            $this->logActivity($data['buyer_id'], 'offer_submitted', 'offer', $offerId);
            
            // Create notification for seller
            $this->createNotification($listing['owner_id'], 'New Offer Received', 
                "You received a new offer of ₱" . number_format($data['price']) . " for '{$listing['title']}'", 
                'offer', $offerId, 'offer');
            
            return $this->getById($offerId);
        }
        
        return false;
    }
    
    // Get offer by ID
    public function getById($id) {
        $query = "SELECT o.*, l.title as listing_title, l.owner_id as seller_id,
                         u.name as buyer_name, u.email as buyer_email,
                         s.name as seller_name, s.email as seller_email
                  FROM " . $this->table . " o
                  JOIN listings l ON o.listing_id = l.id
                  JOIN users u ON o.buyer_id = u.id
                  JOIN users s ON l.owner_id = s.id
                  WHERE o.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $offer = $stmt->fetch();
            return $this->processOfferForFrontend($offer);
        }
        
        return false;
    }
    
    // Get offers by buyer
    public function getByBuyer($buyerId) {
        $query = "SELECT o.*, l.title as listing_title, l.city, l.province, l.owner_id as seller_id,
                         u.name as buyer_name, u.email as buyer_email,
                         s.name as seller_name, s.email as seller_email
                  FROM " . $this->table . " o
                  JOIN listings l ON o.listing_id = l.id
                  JOIN users u ON o.buyer_id = u.id
                  JOIN users s ON l.owner_id = s.id
                  WHERE o.buyer_id = :buyer_id
                  ORDER BY o.submitted_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':buyer_id', $buyerId);
        $stmt->execute();
        
        $offers = $stmt->fetchAll();
        
        foreach ($offers as &$offer) {
            $offer = $this->processOfferForFrontend($offer);
        }
        
        return $offers;
    }
    
    // Get offers for seller's listings
    public function getBySellerListings($sellerId) {
        $query = "SELECT o.*, l.title as listing_title, l.city, l.province, l.owner_id as seller_id,
                         u.name as buyer_name, u.email as buyer_email,
                         s.name as seller_name, s.email as seller_email
                  FROM " . $this->table . " o
                  JOIN listings l ON o.listing_id = l.id
                  JOIN users u ON o.buyer_id = u.id
                  JOIN users s ON l.owner_id = s.id
                  WHERE l.owner_id = :seller_id
                  ORDER BY o.submitted_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':seller_id', $sellerId);
        $stmt->execute();
        
        $offers = $stmt->fetchAll();
        
        foreach ($offers as &$offer) {
            $offer = $this->processOfferForFrontend($offer);
        }
        
        return $offers;
    }
    
    // Get offers by listing
    public function getByListing($listingId) {
        $query = "SELECT o.*, l.title as listing_title, l.owner_id as seller_id,
                         u.name as buyer_name, u.email as buyer_email
                  FROM " . $this->table . " o
                  JOIN listings l ON o.listing_id = l.id
                  JOIN users u ON o.buyer_id = u.id
                  WHERE o.listing_id = :listing_id
                  ORDER BY o.submitted_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':listing_id', $listingId);
        $stmt->execute();
        
        $offers = $stmt->fetchAll();
        
        foreach ($offers as &$offer) {
            $offer = $this->processOfferForFrontend($offer);
        }
        
        return $offers;
    }
    
    // Respond to offer (accept, reject, counter)
    public function respond($offerId, $sellerId, $response, $counterPrice = null, $sellerResponse = '') {
        // Verify seller owns the listing
        $verifyQuery = "SELECT o.*, l.owner_id, l.title FROM " . $this->table . " o
                        JOIN listings l ON o.listing_id = l.id
                        WHERE o.id = :offer_id AND l.owner_id = :seller_id";
        
        $verifyStmt = $this->conn->prepare($verifyQuery);
        $verifyStmt->bindParam(':offer_id', $offerId);
        $verifyStmt->bindParam(':seller_id', $sellerId);
        $verifyStmt->execute();
        
        if ($verifyStmt->rowCount() === 0) {
            return false; // Not authorized
        }
        
        $offer = $verifyStmt->fetch();
        
        // Update offer based on response
        $updateFields = ['responded_at = NOW()', 'seller_response = :seller_response'];
        $params = [':offer_id' => $offerId, ':seller_response' => $sellerResponse];
        
        switch ($response) {
            case 'accept':
                $updateFields[] = 'status = "accepted"';
                $historyAction = 'accepted';
                $notificationMessage = "Your offer has been accepted!";
                
                // Create contract when offer is accepted
                $this->createContract($offer);
                break;
                
            case 'reject':
                $updateFields[] = 'status = "rejected"';
                $historyAction = 'rejected';
                $notificationMessage = "Your offer has been rejected.";
                break;
                
            case 'counter':
                if (!$counterPrice) return false;
                $updateFields[] = 'status = "countered"';
                $updateFields[] = 'counter_price = :counter_price';
                $params[':counter_price'] = $counterPrice;
                $historyAction = 'countered';
                $notificationMessage = "Seller made a counter-offer of ₱" . number_format($counterPrice);
                break;
                
            default:
                return false;
        }
        
        // Update history
        $currentHistory = json_decode($offer['history'] ?? '[]', true);
        $currentHistory[] = [
            'action' => $historyAction,
            'timestamp' => time() * 1000,
            'actor' => $offer['seller_email'] ?? '',
            'details' => $response === 'counter' ? "Counter-offer: ₱" . number_format($counterPrice) : ucfirst($historyAction) . " offer"
        ];
        
        $updateFields[] = 'history = :history';
        $params[':history'] = json_encode($currentHistory);
        
        $query = "UPDATE " . $this->table . " SET " . implode(', ', $updateFields) . " WHERE id = :offer_id";
        $stmt = $this->conn->prepare($query);
        
        if ($stmt->execute($params)) {
            // Log activity
            $this->logActivity($sellerId, "offer_$historyAction", 'offer', $offerId);
            
            // Create notification for buyer
            $this->createNotification($offer['buyer_id'], 'Offer Response', $notificationMessage, 'offer', $offerId, 'offer');
            
            return true;
        }
        
        return false;
    }
    
    // Withdraw offer
    public function withdraw($offerId, $buyerId) {
        // Verify buyer owns the offer
        $verifyQuery = "SELECT * FROM " . $this->table . " WHERE id = :offer_id AND buyer_id = :buyer_id";
        $verifyStmt = $this->conn->prepare($verifyQuery);
        $verifyStmt->bindParam(':offer_id', $offerId);
        $verifyStmt->bindParam(':buyer_id', $buyerId);
        $verifyStmt->execute();
        
        if ($verifyStmt->rowCount() === 0) {
            return false; // Not authorized
        }
        
        $offer = $verifyStmt->fetch();
        
        // Update offer status
        $currentHistory = json_decode($offer['history'] ?? '[]', true);
        $currentHistory[] = [
            'action' => 'withdrawn',
            'timestamp' => time() * 1000,
            'actor' => $offer['buyer_email'] ?? '',
            'details' => 'Offer withdrawn by buyer'
        ];
        
        $query = "UPDATE " . $this->table . " SET status = 'withdrawn', withdrawn_at = NOW(), history = :history WHERE id = :offer_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':history', json_encode($currentHistory));
        $stmt->bindParam(':offer_id', $offerId);
        
        if ($stmt->execute()) {
            $this->logActivity($buyerId, 'offer_withdrawn', 'offer', $offerId);
            return true;
        }
        
        return false;
    }
    
    // Create contract from accepted offer
    private function createContract($offer) {
        require_once 'Contract.php';
        $contract = new Contract($this->conn);
        
        $contractData = [
            'offer_id' => $offer['id'],
            'listing_id' => $offer['listing_id'],
            'buyer_id' => $offer['buyer_id'],
            'seller_id' => $offer['owner_id'],
            'purchase_price' => $offer['price'],
            'earnest_money' => $offer['earnest_money'],
            'closing_date' => $offer['closing_date'],
            'contingencies' => json_decode($offer['contingencies'] ?? '[]', true),
            'inclusions' => $offer['inclusions'],
            'exclusions' => $offer['exclusions'],
            'special_terms' => $offer['special_terms']
        ];
        
        return $contract->create($contractData);
    }
    
    // Process offer for frontend compatibility
    private function processOfferForFrontend($offer) {
        // Parse JSON fields
        $offer['contingencies'] = json_decode($offer['contingencies'] ?? '[]', true);
        $offer['history'] = json_decode($offer['history'] ?? '[]', true);
        
        // Frontend compatibility fields
        $offer['listingId'] = $offer['listing_id'];
        $offer['buyer'] = $offer['buyer_email'];
        $offer['submittedAt'] = $offer['submitted_at'];
        
        return $offer;
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
