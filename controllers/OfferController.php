<?php
/**
 * Offer Controller
 * TerraTrade Land Trading System
 */

class OfferController {
    
    /**
     * Create new offer
     */
    public function createOffer($data) {
        Auth::requireLogin();
        Auth::requireKYC(); // Require KYC verification for offers
        
        $user = Auth::getCurrentUser();
        
        // Validate required fields
        $required = ['property_id', 'offer_amount'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                jsonResponse(['success' => false, 'error' => "Field {$field} is required"], 400);
            }
        }
        
        // Verify CSRF token
        if (!verifyCSRFToken($data['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        
        try {
            // Get property details
            $property = fetchOne("
                SELECT p.*, u.full_name as seller_name 
                FROM properties p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.id = ? AND p.status = 'active'
            ", [$data['property_id']]);
            
            if (!$property) {
                jsonResponse(['success' => false, 'error' => 'Property not found or not available'], 404);
            }
            
            // Check if user is trying to make offer on their own property
            if ($property['user_id'] == $user['id']) {
                jsonResponse(['success' => false, 'error' => 'Cannot make offer on your own property'], 400);
            }
            
            // Validate offer amount
            $offerAmount = (float)$data['offer_amount'];
            $minOfferAmount = $property['price'] * getSetting('min_offer_percentage', 0.5);
            
            if ($offerAmount < $minOfferAmount) {
                jsonResponse(['success' => false, 'error' => 'Offer amount too low'], 400);
            }
            
            // Check for existing pending offers from same user
            $existingOffer = fetchOne("
                SELECT id FROM offers 
                WHERE property_id = ? AND buyer_id = ? AND status IN ('pending', 'countered')
            ", [$data['property_id'], $user['id']]);
            
            if ($existingOffer) {
                jsonResponse(['success' => false, 'error' => 'You already have a pending offer on this property'], 400);
            }
            
            // Calculate expiry date
            $expiryDays = getSetting('default_offer_expiry_days', 7);
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryDays} days"));
            
            // Create offer
            $sql = "
                INSERT INTO offers (
                    property_id, buyer_id, seller_id, offer_amount, earnest_money,
                    contingencies, inclusions, exclusions, special_terms, buyer_comments,
                    closing_date, financing_contingency_days, survey_contingency_days,
                    title_contingency_days, environmental_contingency_days, expires_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $params = [
                $data['property_id'],
                $user['id'],
                $property['user_id'],
                $offerAmount,
                isset($data['earnest_money']) ? (float)$data['earnest_money'] : null,
                isset($data['contingencies']) ? json_encode($data['contingencies']) : null,
                sanitize($data['inclusions'] ?? ''),
                sanitize($data['exclusions'] ?? ''),
                sanitize($data['special_terms'] ?? ''),
                sanitize($data['buyer_comments'] ?? ''),
                $data['closing_date'] ?? null,
                (int)($data['financing_contingency_days'] ?? 30),
                (int)($data['survey_contingency_days'] ?? 10),
                (int)($data['title_contingency_days'] ?? 15),
                (int)($data['environmental_contingency_days'] ?? 20),
                $expiresAt
            ];
            
            executeQuery($sql, $params);
            $offerId = lastInsertId();
            
            // Log audit
            logAudit($user['id'], 'offer_create', 'offers', $offerId, null, $data);
            
            // Send notification to seller
            sendNotification(
                $property['user_id'],
                'offer_received',
                'New Offer Received',
                "You received a new offer of " . formatCurrency($offerAmount) . " for your property '{$property['title']}'",
                ['offer_id' => $offerId, 'property_id' => $data['property_id']]
            );
            
            // Send confirmation to buyer
            sendNotification(
                $user['id'],
                'system',
                'Offer Submitted',
                "Your offer of " . formatCurrency($offerAmount) . " for '{$property['title']}' has been submitted successfully",
                ['offer_id' => $offerId, 'property_id' => $data['property_id']]
            );
            
            jsonResponse([
                'success' => true,
                'message' => 'Offer submitted successfully',
                'offer_id' => $offerId,
                'expires_at' => $expiresAt
            ], 201);
            
        } catch (Exception $e) {
            error_log("Create offer error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to create offer'], 500);
        }
    }
    
    /**
     * Get offers (for buyer or seller)
     */
    public function getOffers($params = []) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            $page = max(1, (int)($params['page'] ?? 1));
            $pageSize = min(MAX_PAGE_SIZE, max(1, (int)($params['page_size'] ?? DEFAULT_PAGE_SIZE)));
            $offset = ($page - 1) * $pageSize;
            
            // Build WHERE clause based on user role and filters
            $whereConditions = [];
            $queryParams = [];
            
            if ($params['type'] === 'received') {
                // Offers received (for sellers)
                $whereConditions[] = "o.seller_id = ?";
                $queryParams[] = $user['id'];
            } elseif ($params['type'] === 'sent') {
                // Offers sent (for buyers)
                $whereConditions[] = "o.buyer_id = ?";
                $queryParams[] = $user['id'];
            } else {
                // All offers for the user
                $whereConditions[] = "(o.buyer_id = ? OR o.seller_id = ?)";
                $queryParams[] = $user['id'];
                $queryParams[] = $user['id'];
            }
            
            // Status filter
            if (!empty($params['status'])) {
                $whereConditions[] = "o.status = ?";
                $queryParams[] = $params['status'];
            }
            
            // Property filter
            if (!empty($params['property_id'])) {
                $whereConditions[] = "o.property_id = ?";
                $queryParams[] = $params['property_id'];
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Count total records
            $countSql = "SELECT COUNT(*) as total FROM offers o WHERE {$whereClause}";
            $totalResult = fetchOne($countSql, $queryParams);
            $totalRecords = $totalResult['total'];
            
            // Get offers
            $sql = "
                SELECT o.*, 
                       p.title as property_title,
                       p.location as property_location,
                       p.price as property_price,
                       buyer.full_name as buyer_name,
                       buyer.email as buyer_email,
                       seller.full_name as seller_name,
                       seller.email as seller_email,
                       (SELECT COUNT(*) FROM counter_offers co WHERE co.original_offer_id = o.id) as counter_offers_count
                FROM offers o
                JOIN properties p ON o.property_id = p.id
                JOIN users buyer ON o.buyer_id = buyer.id
                JOIN users seller ON o.seller_id = seller.id
                WHERE {$whereClause}
                ORDER BY o.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $queryParams[] = $pageSize;
            $queryParams[] = $offset;
            
            $offers = fetchAll($sql, $queryParams);
            
            // Format offers
            foreach ($offers as &$offer) {
                $offer['offer_amount_formatted'] = formatCurrency($offer['offer_amount']);
                $offer['property_price_formatted'] = formatCurrency($offer['property_price']);
                $offer['created_ago'] = timeAgo($offer['created_at']);
                $offer['expires_in'] = $this->getTimeRemaining($offer['expires_at']);
                $offer['is_expired'] = strtotime($offer['expires_at']) < time();
                $offer['contingencies'] = $offer['contingencies'] ? json_decode($offer['contingencies'], true) : [];
                
                // Determine user's role in this offer
                $offer['user_role'] = $offer['buyer_id'] == $user['id'] ? 'buyer' : 'seller';
            }
            
            $pagination = paginate($totalRecords, $page, $pageSize);
            
            jsonResponse([
                'success' => true,
                'offers' => $offers,
                'pagination' => $pagination
            ]);
            
        } catch (Exception $e) {
            error_log("Get offers error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch offers'], 500);
        }
    }
    
    /**
     * Respond to offer (accept/reject)
     */
    public function respondToOffer($id, $data) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        // Validate required fields
        if (empty($data['action']) || !in_array($data['action'], ['accept', 'reject'])) {
            jsonResponse(['success' => false, 'error' => 'Valid action (accept/reject) is required'], 400);
        }
        
        // Verify CSRF token
        if (!verifyCSRFToken($data['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        
        try {
            // Get offer details
            $offer = fetchOne("
                SELECT o.*, p.title as property_title, buyer.full_name as buyer_name
                FROM offers o
                JOIN properties p ON o.property_id = p.id
                JOIN users buyer ON o.buyer_id = buyer.id
                WHERE o.id = ? AND o.seller_id = ?
            ", [$id, $user['id']]);
            
            if (!$offer) {
                jsonResponse(['success' => false, 'error' => 'Offer not found or unauthorized'], 404);
            }
            
            if ($offer['status'] !== 'pending') {
                jsonResponse(['success' => false, 'error' => 'Offer is no longer pending'], 400);
            }
            
            // Check if offer has expired
            if (strtotime($offer['expires_at']) < time()) {
                jsonResponse(['success' => false, 'error' => 'Offer has expired'], 400);
            }
            
            $newStatus = $data['action'] === 'accept' ? 'accepted' : 'rejected';
            
            // Update offer status
            executeQuery("UPDATE offers SET status = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $id]);
            
            if ($data['action'] === 'accept') {
                // Create contract when offer is accepted
                $this->createContractFromOffer($offer);
                
                // Update property status to sold
                executeQuery("UPDATE properties SET status = 'sold' WHERE id = ?", [$offer['property_id']]);
                
                // Reject all other pending offers for this property
                executeQuery("
                    UPDATE offers 
                    SET status = 'rejected', updated_at = NOW() 
                    WHERE property_id = ? AND id != ? AND status = 'pending'
                ", [$offer['property_id'], $id]);
            }
            
            // Log audit
            logAudit($user['id'], 'offer_' . $data['action'], 'offers', $id, $offer, $data);
            
            // Send notification to buyer
            $notificationType = $data['action'] === 'accept' ? 'offer_accepted' : 'offer_rejected';
            $message = $data['action'] === 'accept' 
                ? "Your offer for '{$offer['property_title']}' has been accepted!"
                : "Your offer for '{$offer['property_title']}' has been rejected.";
            
            sendNotification(
                $offer['buyer_id'],
                $notificationType,
                'Offer ' . ucfirst($data['action']) . 'ed',
                $message,
                ['offer_id' => $id, 'property_id' => $offer['property_id']]
            );
            
            jsonResponse([
                'success' => true,
                'message' => "Offer {$data['action']}ed successfully",
                'status' => $newStatus
            ]);
            
        } catch (Exception $e) {
            error_log("Respond to offer error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to respond to offer'], 500);
        }
    }
    
    /**
     * Create counter offer
     */
    public function createCounterOffer($id, $data) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        // Validate required fields
        if (empty($data['counter_amount'])) {
            jsonResponse(['success' => false, 'error' => 'Counter amount is required'], 400);
        }
        
        // Verify CSRF token
        if (!verifyCSRFToken($data['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        
        try {
            // Get original offer
            $offer = fetchOne("
                SELECT o.*, p.title as property_title, buyer.full_name as buyer_name
                FROM offers o
                JOIN properties p ON o.property_id = p.id
                JOIN users buyer ON o.buyer_id = buyer.id
                WHERE o.id = ? AND o.seller_id = ?
            ", [$id, $user['id']]);
            
            if (!$offer) {
                jsonResponse(['success' => false, 'error' => 'Offer not found or unauthorized'], 404);
            }
            
            if (!in_array($offer['status'], ['pending', 'countered'])) {
                jsonResponse(['success' => false, 'error' => 'Cannot counter this offer'], 400);
            }
            
            $counterAmount = (float)$data['counter_amount'];
            
            // Create counter offer
            $sql = "INSERT INTO counter_offers (original_offer_id, counter_amount, counter_terms, created_by) VALUES (?, ?, ?, ?)";
            $params = [
                $id,
                $counterAmount,
                sanitize($data['counter_terms'] ?? ''),
                $user['id']
            ];
            
            executeQuery($sql, $params);
            $counterId = lastInsertId();
            
            // Update original offer status
            executeQuery("UPDATE offers SET status = 'countered', updated_at = NOW() WHERE id = ?", [$id]);
            
            // Log audit
            logAudit($user['id'], 'counter_offer_create', 'counter_offers', $counterId, null, $data);
            
            // Send notification to buyer
            sendNotification(
                $offer['buyer_id'],
                'counter_offer',
                'Counter Offer Received',
                "The seller has made a counter offer of " . formatCurrency($counterAmount) . " for '{$offer['property_title']}'",
                ['counter_offer_id' => $counterId, 'offer_id' => $id, 'property_id' => $offer['property_id']]
            );
            
            jsonResponse([
                'success' => true,
                'message' => 'Counter offer created successfully',
                'counter_offer_id' => $counterId
            ], 201);
            
        } catch (Exception $e) {
            error_log("Create counter offer error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to create counter offer'], 500);
        }
    }
    
    /**
     * Get offer details
     */
    public function getOfferDetails($id) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            $sql = "
                SELECT o.*, 
                       p.title as property_title,
                       p.location as property_location,
                       p.price as property_price,
                       p.area_sqm,
                       buyer.full_name as buyer_name,
                       buyer.email as buyer_email,
                       buyer.phone as buyer_phone,
                       seller.full_name as seller_name,
                       seller.email as seller_email,
                       seller.phone as seller_phone
                FROM offers o
                JOIN properties p ON o.property_id = p.id
                JOIN users buyer ON o.buyer_id = buyer.id
                JOIN users seller ON o.seller_id = seller.id
                WHERE o.id = ? AND (o.buyer_id = ? OR o.seller_id = ? OR ? = 'admin')
            ", [$id, $user['id'], $user['id'], $user['role']]);
            
            $offer = fetchOne($sql);
            
            if (!$offer) {
                jsonResponse(['success' => false, 'error' => 'Offer not found or unauthorized'], 404);
            }
            
            // Get counter offers
            $counterOffers = fetchAll("
                SELECT co.*, u.full_name as created_by_name
                FROM counter_offers co
                JOIN users u ON co.created_by = u.id
                WHERE co.original_offer_id = ?
                ORDER BY co.created_at DESC
            ", [$id]);
            
            // Format data
            $offer['offer_amount_formatted'] = formatCurrency($offer['offer_amount']);
            $offer['property_price_formatted'] = formatCurrency($offer['property_price']);
            $offer['earnest_money_formatted'] = $offer['earnest_money'] ? formatCurrency($offer['earnest_money']) : null;
            $offer['created_ago'] = timeAgo($offer['created_at']);
            $offer['expires_in'] = $this->getTimeRemaining($offer['expires_at']);
            $offer['is_expired'] = strtotime($offer['expires_at']) < time();
            $offer['contingencies'] = $offer['contingencies'] ? json_decode($offer['contingencies'], true) : [];
            $offer['user_role'] = $offer['buyer_id'] == $user['id'] ? 'buyer' : 'seller';
            
            foreach ($counterOffers as &$counter) {
                $counter['counter_amount_formatted'] = formatCurrency($counter['counter_amount']);
                $counter['created_ago'] = timeAgo($counter['created_at']);
            }
            
            jsonResponse([
                'success' => true,
                'offer' => $offer,
                'counter_offers' => $counterOffers
            ]);
            
        } catch (Exception $e) {
            error_log("Get offer details error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch offer details'], 500);
        }
    }
    
    /**
     * Withdraw offer
     */
    public function withdrawOffer($id) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            $offer = fetchOne("SELECT * FROM offers WHERE id = ? AND buyer_id = ?", [$id, $user['id']]);
            
            if (!$offer) {
                jsonResponse(['success' => false, 'error' => 'Offer not found or unauthorized'], 404);
            }
            
            if (!in_array($offer['status'], ['pending', 'countered'])) {
                jsonResponse(['success' => false, 'error' => 'Cannot withdraw this offer'], 400);
            }
            
            // Update offer status
            executeQuery("UPDATE offers SET status = 'withdrawn', updated_at = NOW() WHERE id = ?", [$id]);
            
            // Log audit
            logAudit($user['id'], 'offer_withdraw', 'offers', $id, $offer, null);
            
            // Send notification to seller
            sendNotification(
                $offer['seller_id'],
                'system',
                'Offer Withdrawn',
                "An offer on your property has been withdrawn by the buyer",
                ['offer_id' => $id, 'property_id' => $offer['property_id']]
            );
            
            jsonResponse(['success' => true, 'message' => 'Offer withdrawn successfully']);
            
        } catch (Exception $e) {
            error_log("Withdraw offer error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to withdraw offer'], 500);
        }
    }
    
    /**
     * Create contract from accepted offer
     */
    private function createContractFromOffer($offer) {
        try {
            $sql = "
                INSERT INTO contracts (
                    property_id, buyer_id, seller_id, offer_id, contract_amount,
                    earnest_money, contract_terms, closing_date, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft')
            ";
            
            $contractTerms = json_encode([
                'inclusions' => $offer['inclusions'],
                'exclusions' => $offer['exclusions'],
                'special_terms' => $offer['special_terms'],
                'contingencies' => json_decode($offer['contingencies'], true)
            ]);
            
            $params = [
                $offer['property_id'],
                $offer['buyer_id'],
                $offer['seller_id'],
                $offer['id'],
                $offer['offer_amount'],
                $offer['earnest_money'],
                $contractTerms,
                $offer['closing_date']
            ];
            
            executeQuery($sql, $params);
            $contractId = lastInsertId();
            
            // Create escrow account
            $escrowSql = "INSERT INTO escrow_accounts (contract_id, total_amount) VALUES (?, ?)";
            executeQuery($escrowSql, [$contractId, $offer['offer_amount']]);
            
            return $contractId;
            
        } catch (Exception $e) {
            error_log("Create contract error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get time remaining
     */
    private function getTimeRemaining($endTime) {
        $now = time();
        $end = strtotime($endTime);
        $diff = $end - $now;
        
        if ($diff <= 0) {
            return 'Expired';
        }
        
        $days = floor($diff / 86400);
        $hours = floor(($diff % 86400) / 3600);
        $minutes = floor(($diff % 3600) / 60);
        
        if ($days > 0) {
            return "{$days}d {$hours}h";
        } elseif ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } else {
            return "{$minutes}m";
        }
    }
}
?>
