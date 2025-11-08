<?php
/**
 * Enhanced My Listings API Endpoint
 * TerraTrade Land Trading System
 */

require_once __DIR__ . '/../../config/config.php';

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

try {
    $userId = getCurrentUserId();
    
    // Get user's property listings with enhanced data
    $sql = "SELECT p.*, 
                   COUNT(DISTINCT pv.id) as views_count,
                   COUNT(DISTINCT o.id) as offers_count,
                   COALESCE(p.hectares, p.area_sqm / 10000) as hectares,
                   CASE 
                       WHEN p.status = 'active' THEN 'active'
                       WHEN p.status = 'pending' THEN 'pending'
                       WHEN p.status = 'sold' THEN 'sold'
                       ELSE 'withdrawn'
                   END as status
            FROM properties p
            LEFT JOIN property_views pv ON p.id = pv.property_id
            LEFT JOIN offers o ON p.id = o.listing_id AND o.status IN ('submitted', 'pending')
            WHERE p.user_id = ? AND p.status != 'deleted'
            GROUP BY p.id
            ORDER BY p.created_at DESC";
    
    $properties = fetchAll($sql, [$userId]);
    
    // Format the properties with enhanced data
    $formattedProperties = array_map(function($property) {
        // Calculate hectares if not set
        $hectares = $property['hectares'] ?: round($property['area_sqm'] / 10000, 4);
        
        return [
            'id' => (int)$property['id'],
            'title' => $property['title'],
            'description' => $property['description'] ?: '',
            'price' => (float)$property['price'],
            'area_sqm' => (float)$property['area_sqm'],
            'hectares' => $hectares,
            'zoning' => $property['zoning'] ?: 'Not specified',
            'type' => $property['type'] ?: 'sale',
            'status' => $property['status'],
            'city' => $property['city'],
            'province' => $property['province'],
            'region' => $property['region'],
            'barangay' => $property['barangay'] ?: '',
            'contact_name' => $property['contact_name'] ?: '',
            'contact_phone' => $property['contact_phone'] ?: '',
            'views_count' => (int)($property['views_count'] ?? 0),
            'offers_count' => (int)($property['offers_count'] ?? 0),
            'created_at' => $property['created_at'],
            'updated_at' => $property['updated_at'],
            'auction_ends' => $property['auction_ends']
        ];
    }, $properties);
    
    // Calculate summary statistics
    $stats = [
        'total_listings' => count($formattedProperties),
        'active_listings' => count(array_filter($formattedProperties, fn($p) => $p['status'] === 'active')),
        'pending_listings' => count(array_filter($formattedProperties, fn($p) => $p['status'] === 'pending')),
        'sold_listings' => count(array_filter($formattedProperties, fn($p) => $p['status'] === 'sold')),
        'total_value' => array_sum(array_column($formattedProperties, 'price')),
        'total_views' => array_sum(array_column($formattedProperties, 'views_count')),
        'total_offers' => array_sum(array_column($formattedProperties, 'offers_count'))
    ];
    
    jsonResponse([
        'success' => true,
        'properties' => $formattedProperties,
        'stats' => $stats,
        'total' => count($formattedProperties)
    ]);
    
} catch (Exception $e) {
    error_log("My Listings API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Failed to load listings', 'details' => $e->getMessage()], 500);
}
?>
