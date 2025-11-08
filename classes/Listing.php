<?php
/**
 * Listing Class - TerraTrade Full-Stack Implementation
 * Handles all listing-related operations
 */

class Listing {
    private $conn;
    private $table = 'listings';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new listing
    public function create($data) {
        // Required fields validation
        $required = ['title', 'description', 'price', 'area_sqm', 'hectares', 'zoning', 
                    'region', 'province', 'city', 'owner_id', 'contact_name', 'contact_phone'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return false;
            }
        }
        
        $query = "INSERT INTO " . $this->table . " 
                  (title, description, price, area_sqm, hectares, zoning, region, province, city, barangay,
                   owner_id, contact_name, contact_phone, type, coordinates, document_paths, thumbnails, 
                   auction_ends, created_at) 
                  VALUES (:title, :description, :price, :area_sqm, :hectares, :zoning, :region, :province, 
                          :city, :barangay, :owner_id, :contact_name, :contact_phone, :type, :coordinates, 
                          :document_paths, :thumbnails, :auction_ends, NOW())";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':area_sqm', $data['area_sqm']);
        $stmt->bindParam(':hectares', $data['hectares']);
        $stmt->bindParam(':zoning', $data['zoning']);
        $stmt->bindParam(':region', $data['region']);
        $stmt->bindParam(':province', $data['province']);
        $stmt->bindParam(':city', $data['city']);
        $stmt->bindParam(':barangay', $data['barangay'] ?? '');
        $stmt->bindParam(':owner_id', $data['owner_id']);
        $stmt->bindParam(':contact_name', $data['contact_name']);
        $stmt->bindParam(':contact_phone', $data['contact_phone']);
        $stmt->bindParam(':type', $data['type'] ?? 'sale');
        $stmt->bindParam(':coordinates', $data['coordinates'] ?? '');
        $stmt->bindParam(':document_paths', json_encode($data['document_paths'] ?? []));
        $stmt->bindParam(':thumbnails', json_encode($data['thumbnails'] ?? []));
        $stmt->bindParam(':auction_ends', $data['auction_ends'] ?? null);
        
        if ($stmt->execute()) {
            $listingId = $this->conn->lastInsertId();
            
            // Log activity
            $this->logActivity($data['owner_id'], 'listing_created', 'listing', $listingId);
            
            // Create notification for admin (if needed)
            $this->createNotification(1, 'New Listing Submitted', 
                "New listing '{$data['title']}' submitted for review", 'system', $listingId, 'listing');
            
            return $this->getById($listingId);
        }
        
        return false;
    }
    
    // Get all listings with filters
    public function getAll($filters = []) {
        $where = ["status = 'active'"];
        $params = [];
        
        // Apply filters
        if (!empty($filters['search'])) {
            $where[] = "(title LIKE :search OR description LIKE :search OR city LIKE :search OR province LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['region'])) {
            $where[] = "region LIKE :region";
            $params[':region'] = '%' . $filters['region'] . '%';
        }
        
        if (!empty($filters['zoning'])) {
            if ($filters['zoning'] === 'Auction') {
                $where[] = "type = 'auction'";
            } else {
                $where[] = "zoning = :zoning";
                $params[':zoning'] = $filters['zoning'];
            }
        }
        
        if (!empty($filters['price_min'])) {
            $where[] = "price >= :price_min";
            $params[':price_min'] = $filters['price_min'];
        }
        
        if (!empty($filters['price_max'])) {
            $where[] = "price <= :price_max";
            $params[':price_max'] = $filters['price_max'];
        }
        
        if (!empty($filters['min_area'])) {
            $where[] = "area_sqm >= :min_area";
            $params[':min_area'] = $filters['min_area'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $query = "SELECT l.*, u.name as owner_name, u.email as owner_email,
                         (SELECT COUNT(*) FROM favorites f WHERE f.listing_id = l.id) as favorites_count,
                         (SELECT COUNT(*) FROM offers o WHERE o.listing_id = l.id) as offers_count
                  FROM " . $this->table . " l 
                  JOIN users u ON l.owner_id = u.id 
                  WHERE $whereClause 
                  ORDER BY l.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        
        $listings = $stmt->fetchAll();
        
        // Process listings for frontend compatibility
        foreach ($listings as &$listing) {
            $listing = $this->processListingForFrontend($listing);
        }
        
        return $listings;
    }
    
    // Get listing by ID
    public function getById($id) {
        $query = "SELECT l.*, u.name as owner_name, u.email as owner_email,
                         (SELECT COUNT(*) FROM favorites f WHERE f.listing_id = l.id) as favorites_count,
                         (SELECT COUNT(*) FROM offers o WHERE o.listing_id = l.id) as offers_count
                  FROM " . $this->table . " l 
                  JOIN users u ON l.owner_id = u.id 
                  WHERE l.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 1) {
            $listing = $stmt->fetch();
            return $this->processListingForFrontend($listing);
        }
        
        return false;
    }
    
    // Get listings by owner
    public function getByOwner($ownerId) {
        $query = "SELECT l.*, u.name as owner_name, u.email as owner_email,
                         (SELECT COUNT(*) FROM favorites f WHERE f.listing_id = l.id) as favorites_count,
                         (SELECT COUNT(*) FROM offers o WHERE o.listing_id = l.id) as offers_count
                  FROM " . $this->table . " l 
                  JOIN users u ON l.owner_id = u.id 
                  WHERE l.owner_id = :owner_id 
                  ORDER BY l.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':owner_id', $ownerId);
        $stmt->execute();
        
        $listings = $stmt->fetchAll();
        
        foreach ($listings as &$listing) {
            $listing = $this->processListingForFrontend($listing);
        }
        
        return $listings;
    }
    
    // Update listing
    public function update($id, $data, $ownerId) {
        // Verify ownership
        if (!$this->verifyOwnership($id, $ownerId)) {
            return false;
        }
        
        $allowedFields = ['title', 'description', 'price', 'area_sqm', 'hectares', 'zoning',
                         'region', 'province', 'city', 'barangay', 'contact_name', 'contact_phone',
                         'coordinates', 'status'];
        
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
            $this->logActivity($ownerId, 'listing_updated', 'listing', $id);
            return $this->getById($id);
        }
        
        return false;
    }
    
    // Delete listing
    public function delete($id, $ownerId) {
        if (!$this->verifyOwnership($id, $ownerId)) {
            return false;
        }
        
        $query = "UPDATE " . $this->table . " SET status = 'withdrawn', updated_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $this->logActivity($ownerId, 'listing_withdrawn', 'listing', $id);
            return true;
        }
        
        return false;
    }
    
    // Increment view count
    public function incrementViews($id) {
        $query = "UPDATE " . $this->table . " SET views_count = views_count + 1 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }
    
    // Add to favorites
    public function addToFavorites($userId, $listingId) {
        $query = "INSERT IGNORE INTO favorites (user_id, listing_id, created_at) VALUES (:user_id, :listing_id, NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':listing_id', $listingId);
        
        if ($stmt->execute()) {
            $this->logActivity($userId, 'listing_favorited', 'listing', $listingId);
            return true;
        }
        
        return false;
    }
    
    // Remove from favorites
    public function removeFromFavorites($userId, $listingId) {
        $query = "DELETE FROM favorites WHERE user_id = :user_id AND listing_id = :listing_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':listing_id', $listingId);
        
        if ($stmt->execute()) {
            $this->logActivity($userId, 'listing_unfavorited', 'listing', $listingId);
            return true;
        }
        
        return false;
    }
    
    // Get user favorites
    public function getFavorites($userId) {
        $query = "SELECT l.*, u.name as owner_name, u.email as owner_email, f.created_at as favorited_at
                  FROM favorites f
                  JOIN " . $this->table . " l ON f.listing_id = l.id
                  JOIN users u ON l.owner_id = u.id
                  WHERE f.user_id = :user_id AND l.status = 'active'
                  ORDER BY f.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $favorites = $stmt->fetchAll();
        
        foreach ($favorites as &$favorite) {
            $favorite = $this->processListingForFrontend($favorite);
        }
        
        return $favorites;
    }
    
    // Check if listing is favorited by user
    public function isFavorited($userId, $listingId) {
        $query = "SELECT 1 FROM favorites WHERE user_id = :user_id AND listing_id = :listing_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':listing_id', $listingId);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    // Get listing statistics
    public function getStats($listingId) {
        $stats = [];
        
        // Get view count
        $query = "SELECT views_count FROM " . $this->table . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $listingId);
        $stmt->execute();
        $result = $stmt->fetch();
        $stats['views'] = $result ? $result['views_count'] : 0;
        
        // Get offers count
        $query = "SELECT COUNT(*) as count FROM offers WHERE listing_id = :listing_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':listing_id', $listingId);
        $stmt->execute();
        $stats['offers'] = $stmt->fetch()['count'];
        
        // Get favorites count
        $query = "SELECT COUNT(*) as count FROM favorites WHERE listing_id = :listing_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':listing_id', $listingId);
        $stmt->execute();
        $stats['favorites'] = $stmt->fetch()['count'];
        
        return $stats;
    }
    
    // Process listing for frontend compatibility
    private function processListingForFrontend($listing) {
        // Parse JSON fields
        $listing['document_paths'] = json_decode($listing['document_paths'] ?? '[]', true);
        $listing['thumbnails'] = json_decode($listing['thumbnails'] ?? '[]', true);
        
        // Frontend compatibility fields
        $listing['owner'] = $listing['owner_email'];
        $listing['area'] = $listing['area_sqm'];
        $listing['desc'] = $listing['description'];
        $listing['coords'] = $listing['coordinates'];
        $listing['type'] = $listing['type'] ?? 'sale';
        $listing['reports'] = []; // Will be populated from reports table if needed
        
        // Convert timestamps to frontend format
        if ($listing['created_at']) {
            $listing['submittedAt'] = $listing['created_at'];
        }
        
        return $listing;
    }
    
    // Verify listing ownership
    private function verifyOwnership($listingId, $userId) {
        $query = "SELECT 1 FROM " . $this->table . " WHERE id = :id AND owner_id = :owner_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $listingId);
        $stmt->bindParam(':owner_id', $userId);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
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
