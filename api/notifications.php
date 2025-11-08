<?php
/**
 * Notifications API
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
    
    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        // Get all notifications for the user
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
        
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        $params = [$userId];
        
        if ($unreadOnly) {
            $sql .= " AND read_at IS NULL";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $notifications = fetchAll($sql, $params);
        
        // Format notifications
        $formattedNotifications = array_map(function($notif) {
            return [
                'id' => (int)$notif['id'],
                'type' => $notif['type'],
                'title' => $notif['title'],
                'message' => $notif['message'],
                'data' => json_decode($notif['data'] ?? '{}', true),
                'read_at' => $notif['read_at'],
                'is_read' => !is_null($notif['read_at']),
                'created_at' => $notif['created_at']
            ];
        }, $notifications);
        
        // Get unread count
        $unreadCount = fetchOne("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND read_at IS NULL", [$userId]);
        
        jsonResponse([
            'success' => true,
            'notifications' => $formattedNotifications,
            'unread_count' => (int)$unreadCount['count'],
            'total' => count($formattedNotifications)
        ]);
        
    } elseif ($method === 'POST') {
        // Mark notification(s) as read
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['notification_id'])) {
            // Mark single notification as read
            $notificationId = (int)$input['notification_id'];
            executeQuery("UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?", [$notificationId, $userId]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        } elseif (isset($input['mark_all_read']) && $input['mark_all_read']) {
            // Mark all notifications as read
            executeQuery("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL", [$userId]);
            
            jsonResponse([
                'success' => true,
                'message' => 'All notifications marked as read'
            ]);
        } else {
            jsonResponse(['success' => false, 'error' => 'Invalid request'], 400);
        }
        
    } elseif ($method === 'DELETE') {
        // Delete notification
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (isset($input['notification_id'])) {
            $notificationId = (int)$input['notification_id'];
            executeQuery("DELETE FROM notifications WHERE id = ? AND user_id = ?", [$notificationId, $userId]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Notification deleted'
            ]);
        } else {
            jsonResponse(['success' => false, 'error' => 'notification_id required'], 400);
        }
        
    } else {
        jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    }
    
} catch (Exception $e) {
    error_log("Notifications API Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Failed to load notifications',
        'details' => $e->getMessage()
    ], 500);
}
