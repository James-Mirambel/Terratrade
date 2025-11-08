<?php
/**
 * User Controller
 * TerraTrade Land Trading System
 */

class UserController {
    
    /**
     * Get user profile
     */
    public function getProfile() {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            $profile = fetchOne("
                SELECT u.*, 
                       COUNT(DISTINCT p.id) as total_listings,
                       COUNT(DISTINCT f.id) as total_favorites,
                       COUNT(DISTINCT o.id) as total_offers_sent,
                       COUNT(DISTINCT or.id) as total_offers_received
                FROM users u
                LEFT JOIN properties p ON u.id = p.user_id AND p.status != 'deleted'
                LEFT JOIN user_favorites f ON u.id = f.user_id
                LEFT JOIN offers o ON u.id = o.buyer_id
                LEFT JOIN offers or ON u.id = or.seller_id
                WHERE u.id = ?
                GROUP BY u.id
            ", [$user['id']]);
            
            if ($profile) {
                // Remove sensitive data
                unset($profile['password_hash']);
                
                $profile['stats'] = [
                    'total_listings' => (int)$profile['total_listings'],
                    'total_favorites' => (int)$profile['total_favorites'],
                    'total_offers_sent' => (int)$profile['total_offers_sent'],
                    'total_offers_received' => (int)$profile['total_offers_received']
                ];
                
                $profile['member_since'] = timeAgo($profile['created_at']);
                $profile['last_login_formatted'] = $profile['last_login'] ? timeAgo($profile['last_login']) : 'Never';
            }
            
            jsonResponse(['success' => true, 'profile' => $profile]);
            
        } catch (Exception $e) {
            error_log("Get profile error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch profile'], 500);
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($data) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        // Verify CSRF token
        if (!verifyCSRFToken($data['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        
        try {
            $updateFields = [];
            $params = [];
            
            // Allowed fields to update
            $allowedFields = ['full_name', 'phone'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "{$field} = ?";
                    $params[] = sanitize($data[$field]);
                }
            }
            
            // Validate phone if provided
            if (isset($data['phone']) && !empty($data['phone']) && !isValidPhilippinePhone($data['phone'])) {
                jsonResponse(['success' => false, 'error' => 'Invalid Philippine phone number format'], 400);
            }
            
            if (!empty($updateFields)) {
                $updateFields[] = "updated_at = NOW()";
                $params[] = $user['id'];
                
                $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
                executeQuery($sql, $params);
                
                // Log audit
                logAudit($user['id'], 'profile_update', 'users', $user['id'], null, $data);
                
                // Update session data
                if (isset($data['full_name'])) {
                    $_SESSION['user_name'] = $data['full_name'];
                }
                
                jsonResponse(['success' => true, 'message' => 'Profile updated successfully']);
            } else {
                jsonResponse(['success' => false, 'error' => 'No valid fields to update'], 400);
            }
            
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Profile update failed'], 500);
        }
    }
    
    /**
     * Get user listings
     */
    public function getUserListings($params = []) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            $page = max(1, (int)($params['page'] ?? 1));
            $pageSize = min(MAX_PAGE_SIZE, max(1, (int)($params['page_size'] ?? DEFAULT_PAGE_SIZE)));
            $offset = ($page - 1) * $pageSize;
            
            // Build WHERE clause
            $whereConditions = ["p.user_id = ?"];
            $queryParams = [$user['id']];
            
            // Status filter
            if (!empty($params['status'])) {
                $whereConditions[] = "p.status = ?";
                $queryParams[] = $params['status'];
            } else {
                $whereConditions[] = "p.status != 'deleted'";
            }
            
            // Search filter
            if (!empty($params['search'])) {
                $whereConditions[] = "(p.title LIKE ? OR p.location LIKE ?)";
                $searchTerm = '%' . $params['search'] . '%';
                $queryParams[] = $searchTerm;
                $queryParams[] = $searchTerm;
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Count total records
            $countSql = "SELECT COUNT(*) as total FROM properties p WHERE {$whereClause}";
            $totalResult = fetchOne($countSql, $queryParams);
            $totalRecords = $totalResult['total'];
            
            // Get listings
            $sql = "
                SELECT p.*, 
                       (SELECT image_path FROM property_images pi WHERE pi.property_id = p.id AND pi.image_type = 'main' LIMIT 1) as main_image,
                       (SELECT COUNT(*) FROM offers o WHERE o.property_id = p.id AND o.status = 'pending') as pending_offers,
                       (SELECT COUNT(*) FROM user_favorites uf WHERE uf.property_id = p.id) as favorites_count
                FROM properties p
                WHERE {$whereClause}
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $queryParams[] = $pageSize;
            $queryParams[] = $offset;
            
            $listings = fetchAll($sql, $queryParams);
            
            // Format listings
            foreach ($listings as &$listing) {
                $listing['price_formatted'] = formatCurrency($listing['price']);
                $listing['area_formatted'] = formatArea($listing['area_sqm']);
                $listing['created_ago'] = timeAgo($listing['created_at']);
                $listing['is_auction'] = $listing['listing_type'] === 'auction';
                
                if ($listing['is_auction'] && $listing['auction_end']) {
                    $listing['auction_ends_in'] = $this->getTimeRemaining($listing['auction_end']);
                    $listing['auction_active'] = strtotime($listing['auction_end']) > time();
                }
            }
            
            $pagination = paginate($totalRecords, $page, $pageSize);
            
            jsonResponse([
                'success' => true,
                'listings' => $listings,
                'pagination' => $pagination
            ]);
            
        } catch (Exception $e) {
            error_log("Get user listings error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch listings'], 500);
        }
    }
    
    /**
     * Get user offers
     */
    public function getUserOffers($params = []) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            $page = max(1, (int)($params['page'] ?? 1));
            $pageSize = min(MAX_PAGE_SIZE, max(1, (int)($params['page_size'] ?? DEFAULT_PAGE_SIZE)));
            $offset = ($page - 1) * $pageSize;
            
            // Build WHERE clause
            $whereConditions = [];
            $queryParams = [];
            
            if ($params['type'] === 'received') {
                $whereConditions[] = "o.seller_id = ?";
                $queryParams[] = $user['id'];
            } else {
                $whereConditions[] = "o.buyer_id = ?";
                $queryParams[] = $user['id'];
            }
            
            // Status filter
            if (!empty($params['status'])) {
                $whereConditions[] = "o.status = ?";
                $queryParams[] = $params['status'];
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
                       seller.full_name as seller_name,
                       (SELECT image_path FROM property_images pi WHERE pi.property_id = p.id AND pi.image_type = 'main' LIMIT 1) as property_image
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
                $offer['user_role'] = $offer['buyer_id'] == $user['id'] ? 'buyer' : 'seller';
            }
            
            $pagination = paginate($totalRecords, $page, $pageSize);
            
            jsonResponse([
                'success' => true,
                'offers' => $offers,
                'pagination' => $pagination
            ]);
            
        } catch (Exception $e) {
            error_log("Get user offers error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch offers'], 500);
        }
    }
    
    /**
     * Get user favorites
     */
    public function getUserFavorites($params = []) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            $page = max(1, (int)($params['page'] ?? 1));
            $pageSize = min(MAX_PAGE_SIZE, max(1, (int)($params['page_size'] ?? DEFAULT_PAGE_SIZE)));
            $offset = ($page - 1) * $pageSize;
            
            // Count total records
            $countSql = "
                SELECT COUNT(*) as total 
                FROM user_favorites f 
                JOIN properties p ON f.property_id = p.id 
                WHERE f.user_id = ? AND p.status = 'active'
            ";
            $totalResult = fetchOne($countSql, [$user['id']]);
            $totalRecords = $totalResult['total'];
            
            // Get favorites
            $sql = "
                SELECT p.*, 
                       f.created_at as favorited_at,
                       u.full_name as seller_name,
                       (SELECT image_path FROM property_images pi WHERE pi.property_id = p.id AND pi.image_type = 'main' LIMIT 1) as main_image
                FROM user_favorites f
                JOIN properties p ON f.property_id = p.id
                JOIN users u ON p.user_id = u.id
                WHERE f.user_id = ? AND p.status = 'active'
                ORDER BY f.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $favorites = fetchAll($sql, [$user['id'], $pageSize, $offset]);
            
            // Format favorites
            foreach ($favorites as &$favorite) {
                $favorite['price_formatted'] = formatCurrency($favorite['price']);
                $favorite['area_formatted'] = formatArea($favorite['area_sqm']);
                $favorite['favorited_ago'] = timeAgo($favorite['favorited_at']);
                $favorite['is_auction'] = $favorite['listing_type'] === 'auction';
                
                if ($favorite['is_auction'] && $favorite['auction_end']) {
                    $favorite['auction_ends_in'] = $this->getTimeRemaining($favorite['auction_end']);
                    $favorite['auction_active'] = strtotime($favorite['auction_end']) > time();
                }
            }
            
            $pagination = paginate($totalRecords, $page, $pageSize);
            
            jsonResponse([
                'success' => true,
                'favorites' => $favorites,
                'pagination' => $pagination
            ]);
            
        } catch (Exception $e) {
            error_log("Get user favorites error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch favorites'], 500);
        }
    }
    
    /**
     * Get user notifications
     */
    public function getNotifications($params = []) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            $page = max(1, (int)($params['page'] ?? 1));
            $pageSize = min(MAX_PAGE_SIZE, max(1, (int)($params['page_size'] ?? DEFAULT_PAGE_SIZE)));
            $offset = ($page - 1) * $pageSize;
            
            // Count total and unread
            $countSql = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread
                FROM notifications 
                WHERE user_id = ?
            ";
            $countResult = fetchOne($countSql, [$user['id']]);
            
            // Get notifications
            $sql = "
                SELECT * FROM notifications 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ";
            
            $notifications = fetchAll($sql, [$user['id'], $pageSize, $offset]);
            
            // Format notifications
            foreach ($notifications as &$notification) {
                $notification['created_ago'] = timeAgo($notification['created_at']);
                $notification['is_read'] = !is_null($notification['read_at']);
                $notification['data'] = $notification['data'] ? json_decode($notification['data'], true) : null;
            }
            
            $pagination = paginate($countResult['total'], $page, $pageSize);
            
            jsonResponse([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => (int)$countResult['unread'],
                'pagination' => $pagination
            ]);
            
        } catch (Exception $e) {
            error_log("Get notifications error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch notifications'], 500);
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationRead($id) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            executeQuery("
                UPDATE notifications 
                SET read_at = NOW() 
                WHERE id = ? AND user_id = ? AND read_at IS NULL
            ", [$id, $user['id']]);
            
            jsonResponse(['success' => true, 'message' => 'Notification marked as read']);
            
        } catch (Exception $e) {
            error_log("Mark notification read error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to mark notification as read'], 500);
        }
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllNotificationsRead() {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            executeQuery("
                UPDATE notifications 
                SET read_at = NOW() 
                WHERE user_id = ? AND read_at IS NULL
            ", [$user['id']]);
            
            jsonResponse(['success' => true, 'message' => 'All notifications marked as read']);
            
        } catch (Exception $e) {
            error_log("Mark all notifications read error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to mark notifications as read'], 500);
        }
    }
    
    /**
     * Upload profile avatar
     */
    public function uploadAvatar($files) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        if (empty($files['avatar'])) {
            jsonResponse(['success' => false, 'error' => 'No avatar file provided'], 400);
        }
        
        try {
            $uploadResult = uploadFile(
                $files['avatar'],
                UPLOAD_PATH . 'avatars/',
                ALLOWED_IMAGE_TYPES
            );
            
            if (!$uploadResult['success']) {
                jsonResponse($uploadResult, 400);
            }
            
            // Update user profile image
            $imagePath = 'avatars/' . $uploadResult['filename'];
            executeQuery("UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?", 
                        [$imagePath, $user['id']]);
            
            // Log audit
            logAudit($user['id'], 'avatar_upload', 'users', $user['id'], null, ['image_path' => $imagePath]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'image_path' => $imagePath
            ]);
            
        } catch (Exception $e) {
            error_log("Upload avatar error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to upload avatar'], 500);
        }
    }
    
    /**
     * Get time remaining helper
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
