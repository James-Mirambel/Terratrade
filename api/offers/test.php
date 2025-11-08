<?php
/**
 * Test API Endpoint
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__ . '/../../config/config.php';

// Set JSON content type
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Test basic functionality
    $response = [
        'success' => true,
        'message' => 'API is working',
        'method' => $_SERVER['REQUEST_METHOD'],
        'timestamp' => date('Y-m-d H:i:s'),
        'post_data' => $_POST,
        'files' => array_keys($_FILES)
    ];
    
    // Test if user is logged in
    if (function_exists('isLoggedIn')) {
        $response['logged_in'] = isLoggedIn();
        if (isLoggedIn() && function_exists('getCurrentUser')) {
            $user = getCurrentUser();
            $response['user_id'] = $user['id'] ?? 'unknown';
        }
    }
    
    jsonResponse($response);
    
} catch (Exception $e) {
    jsonResponse([
        'success' => false,
        'error' => 'Test API error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], 500);
}
?>
