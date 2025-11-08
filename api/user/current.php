<?php
/**
 * Current User API Endpoint
 * TerraTrade Land Trading System
 */

require_once __DIR__ . '/../../config/config.php';

// Set JSON content type and CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    exit;
}

try {
    // Check if user is logged in
    if (!isLoggedIn()) {
        jsonResponse(['success' => false, 'error' => 'Not authenticated'], 401);
        exit;
    }
    
    $user = getCurrentUser();
    
    if (!$user) {
        jsonResponse(['success' => false, 'error' => 'User not found'], 404);
        exit;
    }
    
    // Return safe user data (exclude sensitive fields)
    $safeUserData = [
        'id' => (int)$user['id'],
        'full_name' => $user['full_name'] ?? '',
        'email' => $user['email'] ?? '',
        'phone' => $user['phone'] ?? '',
        'role' => $user['role'] ?? 'buyer',
        'kyc_status' => $user['kyc_status'] ?? 'pending',
        'created_at' => $user['created_at'] ?? null
    ];
    
    jsonResponse([
        'success' => true,
        'user' => $safeUserData
    ]);
    
} catch (Exception $e) {
    error_log("Current User API Error: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Internal server error'], 500);
}
?>
