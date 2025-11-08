<?php
/**
 * Change Password API
 * TerraTrade Land Trading System
 */

require_once '../../config/config.php';

header('Content-Type: application/json');

// Require login
Auth::requireLogin();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Invalid request method'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
}

$currentUser = Auth::getCurrentUser();
$userId = $currentUser['id'];

// Validate required fields
if (empty($input['current_password']) || empty($input['new_password'])) {
    jsonResponse(['success' => false, 'error' => 'All fields are required'], 400);
}

// Validate new password length
if (strlen($input['new_password']) < 8) {
    jsonResponse(['success' => false, 'error' => 'New password must be at least 8 characters'], 400);
}

try {
    // Use Auth class method
    $result = Auth::changePassword(
        $userId,
        $input['current_password'],
        $input['new_password']
    );
    
    if ($result['success']) {
        jsonResponse([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    } else {
        jsonResponse($result, 400);
    }
    
} catch (Exception $e) {
    error_log("Change password error: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Failed to change password'], 500);
}
