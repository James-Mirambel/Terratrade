<?php
/**
 * Get Users List API
 * TerraTrade Land Trading System
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
    exit;
}

try {
    $user = getCurrentUser();
    $userId = $user['id'];
    
    // Get all users except current user
    $sql = "SELECT id, full_name, email, role, kyc_status 
            FROM users 
            WHERE id != ? AND status = 'active'
            ORDER BY full_name ASC";
    
    $users = fetchAll($sql, [$userId]);
    
    // Format users
    $formattedUsers = array_map(function($u) {
        return [
            'id' => (int)$u['id'],
            'name' => $u['full_name'],
            'email' => $u['email'],
            'role' => $u['role'],
            'kyc_status' => $u['kyc_status']
        ];
    }, $users);
    
    jsonResponse([
        'success' => true,
        'users' => $formattedUsers,
        'total' => count($formattedUsers)
    ]);
    
} catch (Exception $e) {
    error_log("Users API Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Failed to load users',
        'details' => $e->getMessage()
    ], 500);
}
