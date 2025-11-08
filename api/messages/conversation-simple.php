<?php
/**
 * Get Conversation Messages API (Simple Version)
 * Gets all messages between current user and another user
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
    exit;
}

// Get parameters
$otherUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$propertyId = isset($_GET['property_id']) ? (int)$_GET['property_id'] : null;

if (!$otherUserId) {
    jsonResponse(['success' => false, 'error' => 'user_id is required'], 400);
    exit;
}

try {
    $user = getCurrentUser();
    $userId = $user['id'];
    
    // Get all messages between these two users
    $sql = "SELECT 
                m.id,
                m.sender_id,
                m.recipient_id,
                m.message,
                m.is_read,
                m.created_at,
                m.listing_id,
                sender.full_name as sender_name,
                sender.profile_image as sender_image,
                p.title as property_title
            FROM messages m
            LEFT JOIN users sender ON m.sender_id = sender.id
            LEFT JOIN properties p ON m.listing_id = p.id
            WHERE (
                (m.sender_id = ? AND m.recipient_id = ? AND m.is_deleted_by_sender = 0)
                OR (m.sender_id = ? AND m.recipient_id = ? AND m.is_deleted_by_recipient = 0)
            )";
    
    $params = [$userId, $otherUserId, $otherUserId, $userId];
    
    if ($propertyId) {
        $sql .= " AND m.listing_id = ?";
        $params[] = $propertyId;
    }
    
    $sql .= " ORDER BY m.created_at ASC";
    
    $messages = fetchAll($sql, $params);
    
    // Mark messages as read
    executeQuery(
        "UPDATE messages SET is_read = 1, read_at = NOW() 
         WHERE recipient_id = ? AND sender_id = ? AND is_read = 0",
        [$userId, $otherUserId]
    );
    
    // Format messages
    $formattedMessages = array_map(function($msg) use ($userId) {
        return [
            'id' => (int)$msg['id'],
            'sender_id' => (int)$msg['sender_id'],
            'sender_name' => $msg['sender_name'],
            'sender_image' => $msg['sender_image'],
            'message' => $msg['message'],
            'is_read' => (bool)$msg['is_read'],
            'created_at' => $msg['created_at'],
            'is_mine' => (int)$msg['sender_id'] === $userId,
            'property_id' => $msg['listing_id'],
            'property_title' => $msg['property_title']
        ];
    }, $messages);
    
    // Get other user info
    $otherUser = fetchOne("SELECT id, full_name, profile_image FROM users WHERE id = ?", [$otherUserId]);
    
    jsonResponse([
        'success' => true,
        'messages' => $formattedMessages,
        'other_user' => [
            'id' => (int)$otherUser['id'],
            'name' => $otherUser['full_name'],
            'image' => $otherUser['profile_image']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get Conversation Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Failed to load messages',
        'details' => $e->getMessage()
    ], 500);
}
