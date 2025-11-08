<?php
/**
 * Get Unread Message Count API (Simple Version)
 * Returns the number of unread messages for the current user
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
    
    // Count unread messages where user is the recipient
    $result = fetchOne(
        "SELECT COUNT(*) as unread_count 
         FROM messages 
         WHERE recipient_id = ? AND is_read = 0",
        [$userId]
    );
    
    $unreadCount = (int)$result['unread_count'];
    
    jsonResponse([
        'success' => true,
        'unread_count' => $unreadCount
    ]);
    
} catch (Exception $e) {
    error_log("Unread Count Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Failed to get unread count',
        'details' => $e->getMessage()
    ], 500);
}
