<?php
/**
 * Submit Offer API Endpoint
 * TerraTrade Land Trading System
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
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
    // Debug: Log the request
    error_log("Offer submission attempt - Method: " . $_SERVER['REQUEST_METHOD']);
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r(array_keys($_FILES), true));
    
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
    
    // Check if user already has a pending offer on this property
    $existingOffer = fetchOne("SELECT id FROM offers WHERE property_id = ? AND buyer_id = ? AND status = 'pending'", [$propertyId, $buyerId]);
    
    if ($existingOffer) {
        jsonResponse(['success' => false, 'error' => 'You already have a pending offer on this property'], 400);
        exit;
    }
    
    // Handle file uploads
    $uploadedFiles = [];
    $uploadDir = __DIR__ . '/../../uploads/offers/';
    
    // Create upload directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Process valid ID upload
    if (isset($_FILES['valid_id']) && $_FILES['valid_id']['error'] === UPLOAD_ERR_OK) {
        $validIdFile = $_FILES['valid_id'];
        
        // Validate file
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array($validIdFile['type'], $allowedTypes)) {
            jsonResponse(['success' => false, 'error' => 'Valid ID must be a JPEG, PNG, or PDF file'], 400);
            exit;
        }
        
        if ($validIdFile['size'] > 5 * 1024 * 1024) { // 5MB limit
            jsonResponse(['success' => false, 'error' => 'Valid ID file size must be less than 5MB'], 400);
            exit;
        }
        
        $extension = pathinfo($validIdFile['name'], PATHINFO_EXTENSION);
        $validIdFilename = 'valid_id_' . $buyerId . '_' . $propertyId . '_' . time() . '.' . $extension;
        $validIdPath = $uploadDir . $validIdFilename;
        
        if (move_uploaded_file($validIdFile['tmp_name'], $validIdPath)) {
            $uploadedFiles['valid_id'] = 'uploads/offers/' . $validIdFilename;
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to upload valid ID'], 500);
            exit;
        }
    } else {
        jsonResponse(['success' => false, 'error' => 'Valid ID is required'], 400);
        exit;
    }
    
    // Process proof of funds upload (optional)
    if (isset($_FILES['proof_of_funds']) && $_FILES['proof_of_funds']['error'] === UPLOAD_ERR_OK) {
        $proofFile = $_FILES['proof_of_funds'];
        
        // Validate file
        if (!in_array($proofFile['type'], $allowedTypes)) {
            jsonResponse(['success' => false, 'error' => 'Proof of funds must be a JPEG, PNG, or PDF file'], 400);
            exit;
        }
        
        if ($proofFile['size'] > 5 * 1024 * 1024) { // 5MB limit
            jsonResponse(['success' => false, 'error' => 'Proof of funds file size must be less than 5MB'], 400);
            exit;
        }
        
        $extension = pathinfo($proofFile['name'], PATHINFO_EXTENSION);
        $proofFilename = 'proof_funds_' . $buyerId . '_' . $propertyId . '_' . time() . '.' . $extension;
        $proofPath = $uploadDir . $proofFilename;
        
        if (move_uploaded_file($proofFile['tmp_name'], $proofPath)) {
            $uploadedFiles['proof_of_funds'] = 'uploads/offers/' . $proofFilename;
        }
    }
    
    // Prepare additional data
    $additionalData = [
        'payment_method' => $paymentMethod,
        'uploaded_files' => $uploadedFiles,
        'buyer_info' => [
            'name' => $user['full_name'] ?? '',
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? ''
        ]
    ];
    
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
    
    // Log audit trail
    if (function_exists('logAudit')) {
        logAudit($buyerId, 'offer_submit', 'offers', $offerId, [], [
            'property_id' => $propertyId,
            'offer_amount' => $offerPrice,
            'status' => 'pending'
        ]);
    }
    
    jsonResponse([
        'success' => true,
        'message' => 'Offer submitted successfully!',
        'offer_id' => $offerId,
        'data' => [
            'property_title' => $property['title'],
            'offer_amount' => $offerPrice,
            'status' => 'pending'
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Submit Offer Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    jsonResponse(['success' => false, 'error' => 'Internal server error', 'details' => $e->getMessage()], 500);
}
?>
