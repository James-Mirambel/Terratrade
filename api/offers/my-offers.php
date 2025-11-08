<?php
/**
 * My Offers API Endpoint
 * TerraTrade Land Trading System
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
    exit;
}

try {
    $user = getCurrentUser();
    $userId = $user['id'];
    
    // Get user's offers (where user is the buyer)
    $sql = "SELECT o.*, 
                   p.title as property_title,
                   p.price as property_price,
                   CONCAT(p.city, ', ', p.province) as property_location,
                   p.id as property_id,
                   u.full_name as seller_name
            FROM offers o
            JOIN properties p ON o.property_id = p.id
            JOIN users u ON o.seller_id = u.id
            WHERE o.buyer_id = ? AND o.offer_type = 'direct'
            ORDER BY o.created_at DESC";
    
    $offers = fetchAll($sql, [$userId]);
    
    // Format the offers
    $formattedOffers = array_map(function($offer) {
        return [
            'id' => (int)$offer['id'],
            'property_id' => (int)$offer['property_id'],
            'property_title' => $offer['property_title'],
            'property_price' => (float)$offer['property_price'],
            'property_location' => $offer['property_location'],
            'price' => (float)$offer['offer_amount'],
            'seller_name' => $offer['seller_name'],
            'status' => $offer['status'],
            'submitted_at' => $offer['created_at'],
            'expires_at' => $offer['expires_at'],
            'buyer_comments' => $offer['buyer_comments'],
            'special_terms' => $offer['special_terms']
        ];
    }, $offers);
    
    jsonResponse([
        'success' => true,
        'offers' => $formattedOffers,
        'total' => count($formattedOffers)
    ]);
    
} catch (Exception $e) {
    error_log("My Offers API Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Failed to load offers',
        'details' => $e->getMessage()
    ], 500);
}
?>
