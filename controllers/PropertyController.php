<?php
/**
 * Property Controller
 * TerraTrade Land Trading System
 */

class PropertyController {
    
    /**
     * Get properties with filters and pagination
     */
    public function getProperties($params = []) {
        try {
            $page = max(1, (int)($params['page'] ?? 1));
            $pageSize = min(MAX_PAGE_SIZE, max(1, (int)($params['page_size'] ?? DEFAULT_PAGE_SIZE)));
            $offset = ($page - 1) * $pageSize;
            
            // Build WHERE clause
            $whereConditions = ["p.status = 'active'"];
            $queryParams = [];
            
            // Search filter
            if (!empty($params['search'])) {
                $whereConditions[] = "(p.title LIKE ? OR p.description LIKE ? OR p.location LIKE ?)";
                $searchTerm = '%' . $params['search'] . '%';
                $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm, $searchTerm]);
            }
            
            // Region filter
            if (!empty($params['region'])) {
                $whereConditions[] = "p.region LIKE ?";
                $queryParams[] = '%' . $params['region'] . '%';
            }
            
            // Zoning filter
            if (!empty($params['zoning'])) {
                $whereConditions[] = "p.zoning = ?";
                $queryParams[] = $params['zoning'];
            }
            
            // Price range filter
            if (!empty($params['price_min'])) {
                $whereConditions[] = "p.price >= ?";
                $queryParams[] = (float)$params['price_min'];
            }
            
            if (!empty($params['price_max'])) {
                $whereConditions[] = "p.price <= ?";
                $queryParams[] = (float)$params['price_max'];
            }
            
            // Area filter
            if (!empty($params['min_area'])) {
                $whereConditions[] = "p.area_sqm >= ?";
                $queryParams[] = (float)$params['min_area'];
            }
            
            // Listing type filter
            if (!empty($params['listing_type'])) {
                $whereConditions[] = "p.listing_type = ?";
                $queryParams[] = $params['listing_type'];
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Count total records
            $countSql = "
                SELECT COUNT(*) as total 
                FROM properties p 
                WHERE {$whereClause}
            ";
            $totalResult = fetchOne($countSql, $queryParams);
            $totalRecords = $totalResult['total'];
            
            // Get properties
            $sql = "
                SELECT p.*, 
                       u.full_name as seller_name,
                       u.phone as seller_phone,
                       (SELECT image_path FROM property_images pi WHERE pi.property_id = p.id AND pi.image_type = 'main' LIMIT 1) as main_image,
                       (SELECT COUNT(*) FROM user_favorites uf WHERE uf.property_id = p.id) as favorites_count,
                       CASE WHEN p.listing_type = 'auction' AND p.auction_end > NOW() THEN 1 ELSE 0 END as is_active_auction
                FROM properties p
                JOIN users u ON p.user_id = u.id
                WHERE {$whereClause}
                ORDER BY p.featured DESC, p.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $queryParams[] = $pageSize;
            $queryParams[] = $offset;
            
            $properties = fetchAll($sql, $queryParams);
            
            // Format properties
            foreach ($properties as &$property) {
                $property['price_formatted'] = formatCurrency($property['price']);
                $property['area_formatted'] = formatArea($property['area_sqm']);
                $property['created_ago'] = timeAgo($property['created_at']);
                $property['is_auction'] = $property['listing_type'] === 'auction';
                $property['auction_active'] = (bool)$property['is_active_auction'];
                
                if ($property['is_auction'] && $property['auction_end']) {
                    $property['auction_ends_in'] = $this->getTimeRemaining($property['auction_end']);
                }
                
                // Check if current user has favorited this property
                if (Auth::isLoggedIn()) {
                    $user = Auth::getCurrentUser();
                    $favorite = fetchOne("SELECT id FROM user_favorites WHERE user_id = ? AND property_id = ?", 
                                       [$user['id'], $property['id']]);
                    $property['is_favorited'] = (bool)$favorite;
                }
            }
            
            $pagination = paginate($totalRecords, $page, $pageSize);
            
            jsonResponse([
                'success' => true,
                'properties' => $properties,
                'pagination' => $pagination
            ]);
            
        } catch (Exception $e) {
            error_log("Get properties error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch properties'], 500);
        }
    }
    
    /**
     * Get property details
     */
    public function getPropertyDetails($id) {
        try {
            $sql = "
                SELECT p.*, 
                       u.full_name as seller_name,
                       u.phone as seller_phone,
                       u.email as seller_email,
                       u.profile_image as seller_avatar,
                       (SELECT COUNT(*) FROM user_favorites uf WHERE uf.property_id = p.id) as favorites_count
                FROM properties p
                JOIN users u ON p.user_id = u.id
                WHERE p.id = ? AND p.status IN ('active', 'pending')
            ";
            
            $property = fetchOne($sql, [$id]);
            
            if (!$property) {
                jsonResponse(['success' => false, 'error' => 'Property not found'], 404);
            }
            
            // Get property images
            $images = fetchAll("
                SELECT * FROM property_images 
                WHERE property_id = ? 
                ORDER BY image_type = 'main' DESC, display_order ASC
            ", [$id]);
            
            // Get property documents
            $documents = fetchAll("
                SELECT * FROM property_documents 
                WHERE property_id = ? 
                ORDER BY document_type, created_at DESC
            ", [$id]);
            
            // Get recent offers (for seller view)
            $offers = [];
            if (Auth::isLoggedIn()) {
                $user = Auth::getCurrentUser();
                if ($user['id'] == $property['user_id'] || $user['role'] === 'admin') {
                    $offers = fetchAll("
                        SELECT o.*, u.full_name as buyer_name
                        FROM offers o
                        JOIN users u ON o.buyer_id = u.id
                        WHERE o.property_id = ? AND o.status IN ('pending', 'countered')
                        ORDER BY o.created_at DESC
                        LIMIT 10
                    ", [$id]);
                }
            }
            
            // Get auction bids if it's an auction
            $bids = [];
            if ($property['listing_type'] === 'auction') {
                $bids = fetchAll("
                    SELECT ab.*, u.full_name as bidder_name
                    FROM auction_bids ab
                    JOIN users u ON ab.bidder_id = u.id
                    WHERE ab.property_id = ? AND ab.status = 'active'
                    ORDER BY ab.bid_amount DESC, ab.created_at ASC
                    LIMIT 10
                ", [$id]);
            }
            
            // Update view count
            executeQuery("UPDATE properties SET views_count = views_count + 1 WHERE id = ?", [$id]);
            
            // Format property data
            $property['price_formatted'] = formatCurrency($property['price']);
            $property['area_formatted'] = formatArea($property['area_sqm']);
            $property['created_ago'] = timeAgo($property['created_at']);
            $property['is_auction'] = $property['listing_type'] === 'auction';
            
            if ($property['is_auction'] && $property['auction_end']) {
                $property['auction_ends_in'] = $this->getTimeRemaining($property['auction_end']);
                $property['auction_active'] = strtotime($property['auction_end']) > time();
            }
            
            // Check if current user has favorited this property
            if (Auth::isLoggedIn()) {
                $user = Auth::getCurrentUser();
                $favorite = fetchOne("SELECT id FROM user_favorites WHERE user_id = ? AND property_id = ?", 
                                   [$user['id'], $property['id']]);
                $property['is_favorited'] = (bool)$favorite;
                $property['is_owner'] = $user['id'] == $property['user_id'];
            }
            
            jsonResponse([
                'success' => true,
                'property' => $property,
                'images' => $images,
                'documents' => $documents,
                'offers' => $offers,
                'bids' => $bids
            ]);
            
        } catch (Exception $e) {
            error_log("Get property details error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch property details'], 500);
        }
    }
    
    /**
     * Create new property listing
     */
    public function createProperty($data) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        // Validate required fields
        $required = ['title', 'location', 'zoning', 'area_sqm', 'price'];
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
            // Parse location components
            $locationParts = array_map('trim', explode(',', $data['location']));
            $barangay = $locationParts[0] ?? '';
            $city = $locationParts[1] ?? '';
            $province = $locationParts[2] ?? '';
            $region = $this->getRegionFromProvince($province);
            
            $sql = "
                INSERT INTO properties (
                    user_id, title, description, location, region, province, city, barangay,
                    zoning, area_sqm, price, listing_type, status, auction_start, auction_end,
                    minimum_bid, bid_increment, latitude, longitude
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            
            $params = [
                $user['id'],
                sanitize($data['title']),
                sanitize($data['description'] ?? ''),
                sanitize($data['location']),
                $region,
                $province,
                $city,
                $barangay,
                $data['zoning'],
                (float)$data['area_sqm'],
                (float)$data['price'],
                $data['listing_type'] ?? 'sale',
                'pending', // All listings need admin approval
                $data['auction_start'] ?? null,
                $data['auction_end'] ?? null,
                isset($data['minimum_bid']) ? (float)$data['minimum_bid'] : null,
                isset($data['bid_increment']) ? (float)$data['bid_increment'] : getSetting('auction_bid_increment', 10000),
                isset($data['latitude']) ? (float)$data['latitude'] : null,
                isset($data['longitude']) ? (float)$data['longitude'] : null
            ];
            
            executeQuery($sql, $params);
            $propertyId = lastInsertId();
            
            // Log audit
            logAudit($user['id'], 'property_create', 'properties', $propertyId, null, $data);
            
            // Send notification to admins
            $admins = fetchAll("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
            foreach ($admins as $admin) {
                sendNotification(
                    $admin['id'],
                    'system',
                    'New Property Listing',
                    "A new property listing '{$data['title']}' requires review.",
                    ['property_id' => $propertyId]
                );
            }
            
            jsonResponse([
                'success' => true,
                'message' => 'Property listing created successfully and is pending review',
                'property_id' => $propertyId
            ], 201);
            
        } catch (Exception $e) {
            error_log("Create property error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to create property listing'], 500);
        }
    }
    
    /**
     * Update property listing
     */
    public function updateProperty($id, $data) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        // Check if user owns the property or is admin
        $property = fetchOne("SELECT * FROM properties WHERE id = ?", [$id]);
        if (!$property) {
            jsonResponse(['success' => false, 'error' => 'Property not found'], 404);
        }
        
        if ($property['user_id'] != $user['id'] && $user['role'] !== 'admin') {
            jsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
        }
        
        // Verify CSRF token
        if (!verifyCSRFToken($data['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        
        try {
            $updateFields = [];
            $params = [];
            
            // Allowed fields to update
            $allowedFields = [
                'title', 'description', 'location', 'zoning', 'area_sqm', 'price',
                'listing_type', 'auction_start', 'auction_end', 'minimum_bid',
                'bid_increment', 'latitude', 'longitude'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "{$field} = ?";
                    $params[] = $field === 'description' ? sanitize($data[$field]) : $data[$field];
                }
            }
            
            if (!empty($updateFields)) {
                // Update location components if location changed
                if (isset($data['location'])) {
                    $locationParts = array_map('trim', explode(',', $data['location']));
                    $updateFields[] = "barangay = ?";
                    $updateFields[] = "city = ?";
                    $updateFields[] = "province = ?";
                    $updateFields[] = "region = ?";
                    $params[] = $locationParts[0] ?? '';
                    $params[] = $locationParts[1] ?? '';
                    $params[] = $locationParts[2] ?? '';
                    $params[] = $this->getRegionFromProvince($locationParts[2] ?? '');
                }
                
                $updateFields[] = "updated_at = NOW()";
                $params[] = $id;
                
                $sql = "UPDATE properties SET " . implode(', ', $updateFields) . " WHERE id = ?";
                executeQuery($sql, $params);
                
                // Log audit
                logAudit($user['id'], 'property_update', 'properties', $id, $property, $data);
                
                jsonResponse(['success' => true, 'message' => 'Property updated successfully']);
            } else {
                jsonResponse(['success' => false, 'error' => 'No valid fields to update'], 400);
            }
            
        } catch (Exception $e) {
            error_log("Update property error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to update property'], 500);
        }
    }
    
    /**
     * Delete property listing
     */
    public function deleteProperty($id) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        // Check if user owns the property or is admin
        $property = fetchOne("SELECT * FROM properties WHERE id = ?", [$id]);
        if (!$property) {
            jsonResponse(['success' => false, 'error' => 'Property not found'], 404);
        }
        
        if ($property['user_id'] != $user['id'] && $user['role'] !== 'admin') {
            jsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
        }
        
        try {
            // Soft delete - update status instead of actual deletion
            executeQuery("UPDATE properties SET status = 'deleted', updated_at = NOW() WHERE id = ?", [$id]);
            
            // Log audit
            logAudit($user['id'], 'property_delete', 'properties', $id, $property, null);
            
            jsonResponse(['success' => true, 'message' => 'Property deleted successfully']);
            
        } catch (Exception $e) {
            error_log("Delete property error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to delete property'], 500);
        }
    }
    
    /**
     * Toggle property favorite
     */
    public function toggleFavorite($id) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            // Check if property exists
            $property = fetchOne("SELECT id FROM properties WHERE id = ? AND status = 'active'", [$id]);
            if (!$property) {
                jsonResponse(['success' => false, 'error' => 'Property not found'], 404);
            }
            
            // Check if already favorited
            $favorite = fetchOne("SELECT id FROM user_favorites WHERE user_id = ? AND property_id = ?", 
                               [$user['id'], $id]);
            
            if ($favorite) {
                // Remove favorite
                executeQuery("DELETE FROM user_favorites WHERE user_id = ? AND property_id = ?", 
                           [$user['id'], $id]);
                $action = 'removed';
            } else {
                // Add favorite
                executeQuery("INSERT INTO user_favorites (user_id, property_id) VALUES (?, ?)", 
                           [$user['id'], $id]);
                $action = 'added';
            }
            
            // Update favorites count in properties table
            executeQuery("UPDATE properties SET favorites_count = (SELECT COUNT(*) FROM user_favorites WHERE property_id = ?) WHERE id = ?", 
                       [$id, $id]);
            
            jsonResponse([
                'success' => true,
                'message' => "Property {$action} to favorites",
                'favorited' => $action === 'added'
            ]);
            
        } catch (Exception $e) {
            error_log("Toggle favorite error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to update favorite'], 500);
        }
    }
    
    /**
     * Upload property image
     */
    public function uploadImage($id, $files) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        // Check if user owns the property
        $property = fetchOne("SELECT user_id FROM properties WHERE id = ?", [$id]);
        if (!$property || $property['user_id'] != $user['id']) {
            jsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
        }
        
        if (empty($files['image'])) {
            jsonResponse(['success' => false, 'error' => 'No image file provided'], 400);
        }
        
        try {
            $uploadResult = uploadFile(
                $files['image'],
                UPLOAD_PATH . 'properties/',
                ALLOWED_IMAGE_TYPES
            );
            
            if (!$uploadResult['success']) {
                jsonResponse($uploadResult, 400);
            }
            
            // Save image record
            $sql = "INSERT INTO property_images (property_id, image_path, image_type, display_order) VALUES (?, ?, ?, ?)";
            $params = [
                $id,
                'properties/' . $uploadResult['filename'],
                $_POST['image_type'] ?? 'gallery',
                (int)($_POST['display_order'] ?? 0)
            ];
            
            executeQuery($sql, $params);
            $imageId = lastInsertId();
            
            jsonResponse([
                'success' => true,
                'message' => 'Image uploaded successfully',
                'image_id' => $imageId,
                'image_path' => $uploadResult['filename']
            ]);
            
        } catch (Exception $e) {
            error_log("Upload image error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to upload image'], 500);
        }
    }
    
    /**
     * Get time remaining for auction
     */
    private function getTimeRemaining($endTime) {
        $now = time();
        $end = strtotime($endTime);
        $diff = $end - $now;
        
        if ($diff <= 0) {
            return 'Auction ended';
        }
        
        $days = floor($diff / 86400);
        $hours = floor(($diff % 86400) / 3600);
        $minutes = floor(($diff % 3600) / 60);
        
        if ($days > 0) {
            return "{$days}d {$hours}h {$minutes}m";
        } elseif ($hours > 0) {
            return "{$hours}h {$minutes}m";
        } else {
            return "{$minutes}m";
        }
    }
    
    /**
     * Get region from province (simplified mapping)
     */
    private function getRegionFromProvince($province) {
        $regionMap = [
            'Metro Manila' => 'NCR',
            'Cebu' => 'Region VII',
            'Davao' => 'Region XI',
            'Laguna' => 'Region IV-A',
            'Cavite' => 'Region IV-A',
            'Bulacan' => 'Region III',
            'Pampanga' => 'Region III',
            // Add more mappings as needed
        ];
        
        return $regionMap[$province] ?? 'Unknown';
    }
}
?>
