<?php
/**
 * Simple Submit Offer API Endpoint (No File Upload)
 * TerraTrade Land Trading System
 */

// Enable error reporting for debugging but suppress warnings in output
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../config/config.php';

// Set JSON content type and CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    exit;
}

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
    exit;
}

try {
    $user = getCurrentUser();
    if (!$user) {
        jsonResponse(['success' => false, 'error' => 'Failed to get current user'], 500);
        exit;
    }
    $buyerId = $user['id'];
    
    // Validate required fields
    $propertyId = isset($_POST['property_id']) ? (int)$_POST['property_id'] : null;
    $offerPrice = isset($_POST['offer_price']) ? (float)$_POST['offer_price'] : null;
    $paymentMethod = $_POST['payment_method'] ?? '';
    $message = $_POST['message'] ?? '';
    
    if (!$propertyId || !$offerPrice || !$paymentMethod) {
        jsonResponse(['success' => false, 'error' => 'Property ID, offer price, and payment method are required'], 400);
        exit;
    }
    
    if ($offerPrice <= 0) {
        jsonResponse(['success' => false, 'error' => 'Offer price must be greater than 0'], 400);
        exit;
    }
    
    // Get property details and verify it exists and is active
    $property = fetchOne("SELECT * FROM properties WHERE id = ? AND status = 'active'", [$propertyId]);
    
    if (!$property) {
        jsonResponse(['success' => false, 'error' => 'Property not found or not available'], 404);
        exit;
    }
    
    // Check if user is trying to make offer on their own property
    if ($property['user_id'] == $buyerId) {
        jsonResponse(['success' => false, 'error' => 'You cannot make an offer on your own property'], 400);
        exit;
    }
    
    // Prepare additional data
    $additionalData = [
        'payment_method' => $paymentMethod,
        'buyer_info' => [
            'name' => $user['full_name'] ?? '',
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? ''
        ]
    ];
    
    // Check if user already has a pending offer on this property
    $existingOffer = fetchOne("SELECT id FROM offers WHERE property_id = ? AND buyer_id = ? AND status = 'pending'", [$propertyId, $buyerId]);
    
    if ($existingOffer) {
        // Update existing offer instead of creating new one
        $updateSql = "UPDATE offers SET 
            offer_amount = ?, 
            buyer_comments = ?, 
            contingencies = ?,
            updated_at = NOW()
            WHERE id = ?";
            
        executeQuery($updateSql, [
            $offerPrice,
            $message,
            json_encode($additionalData),
            $existingOffer['id']
        ]);
        
        $offerId = $existingOffer['id'];
        $isUpdate = true;
    } else {
        $isUpdate = false;
        
        // Insert offer into database
        $sql = "INSERT INTO offers (
            property_id, 
            buyer_id, 
            seller_id, 
            offer_amount, 
            buyer_comments, 
            status, 
            contingencies,
            created_at
        ) VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())";
        
        executeQuery($sql, [
            $propertyId,
            $buyerId,
            $property['user_id'], // seller_id
            $offerPrice,
            $message,
            json_encode($additionalData)
        ]);
        
        // Get the insert ID
        $offerId = getDB()->lastInsertId();
    }
    
    // Send notification to seller
    $notificationSql = "INSERT INTO notifications (
        user_id, 
        type, 
        title, 
        message, 
        data, 
        created_at
    ) VALUES (?, 'new_offer', ?, ?, ?, NOW())";
    
    executeQuery($notificationSql, [
        $property['user_id'],
        'New Offer Received',
        "You received a new offer of ₱" . number_format($offerPrice) . " for your property '{$property['title']}'",
        json_encode([
            'offer_id' => $offerId,
            'property_id' => $propertyId,
            'buyer_id' => $buyerId,
            'offer_amount' => $offerPrice
        ])
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => $isUpdate ? 'Offer updated successfully!' : 'Offer submitted successfully!',
        'offer_id' => $offerId,
        'is_update' => $isUpdate,
        'data' => [
            'property_title' => $property['title'],
            'offer_amount' => $offerPrice,
            'status' => 'pending'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Submit Offer Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    jsonResponse(['success' => false, 'error' => 'Internal server error: ' . $e->getMessage()], 500);
}
?>
