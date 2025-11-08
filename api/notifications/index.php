<?php
/**
 * Notifications API Endpoint
 * TerraTrade Land Trading System
 */

require_once __DIR__ . '/../../config/config.php';

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

try {
    $userId = getCurrentUserId();
    
    // Get user's notifications
    $sql = "SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 50";
    
    $notifications = fetchAll($sql, [$userId]);
    
    // Format the notifications
    $formattedNotifications = array_map(function($notification) {
        return [
            'id' => $notification['id'],
            'type' => $notification['type'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'data' => $notification['data'] ? json_decode($notification['data'], true) : null,
            'is_read' => (bool)$notification['is_read'],
            'created_at' => $notification['created_at']
        ];
    }, $notifications);
    
    jsonResponse([
        'success' => true,
        'notifications' => $formattedNotifications,
        'total' => count($formattedNotifications)
    ]);
    
} catch (Exception $e) {
    error_log("Notifications API Error: " . $e->getMessage());
    jsonResponse(['error' => 'Failed to load notifications'], 500);
}
?>
