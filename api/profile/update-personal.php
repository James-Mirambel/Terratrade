<?php
/**
 * Update Personal Information API
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
if (empty($input['full_name']) || empty($input['email'])) {
    jsonResponse(['success' => false, 'error' => 'Full name and email are required'], 400);
}

// Validate email format
if (!isValidEmail($input['email'])) {
    jsonResponse(['success' => false, 'error' => 'Invalid email format'], 400);
}

try {
    // Check if email is already taken by another user
    if ($input['email'] !== $currentUser['email']) {
        $existingUser = fetchOne("SELECT id FROM users WHERE email = ? AND id != ?", 
                                 [$input['email'], $userId]);
        if ($existingUser) {
            jsonResponse(['success' => false, 'error' => 'Email already in use'], 400);
        }
    }
    
    // Update user information
    $sql = "UPDATE users SET 
            full_name = ?,
            email = ?,
            phone = ?,
            updated_at = NOW()
            WHERE id = ?";
    
    executeQuery($sql, [
        sanitize($input['full_name']),
        sanitize($input['email']),
        sanitize($input['phone'] ?? ''),
        $userId
    ]);
    
    // Update session data
    $_SESSION['user_name'] = $input['full_name'];
    $_SESSION['user_email'] = $input['email'];
    
    // Log audit
    logAudit($userId, 'profile_update', 'users', $userId);
    
    jsonResponse([
        'success' => true,
        'message' => 'Personal information updated successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Update personal info error: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Failed to update information'], 500);
}
