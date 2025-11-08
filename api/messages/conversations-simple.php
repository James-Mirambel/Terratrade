<?php
/**
 * Get Conversations API (Simple Version)
 * Lists all conversations for the current user
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
    
    // Get all conversations (messages grouped by other user)
    $sql = "SELECT 
                m.id as message_id,
                m.sender_id,
                m.recipient_id,
                m.message,
                m.is_read,
                m.created_at,
                m.listing_id,
                CASE 
                    WHEN m.sender_id = ? THEN m.recipient_id 
                    ELSE m.sender_id 
                END as other_user_id,
                CASE 
                    WHEN m.sender_id = ? THEN recipient.full_name 
                    ELSE sender.full_name 
                END as other_user_name,
                CASE 
                    WHEN m.sender_id = ? THEN recipient.profile_image 
                    ELSE sender.profile_image 
                END as other_user_image,
                p.title as property_title
            FROM messages m
            LEFT JOIN users sender ON m.sender_id = sender.id
            LEFT JOIN users recipient ON m.recipient_id = recipient.id
            LEFT JOIN properties p ON m.listing_id = p.id
            WHERE m.sender_id = ? OR m.recipient_id = ?
            ORDER BY m.created_at DESC";
    
    $messages = fetchAll($sql, [$userId, $userId, $userId, $userId, $userId]);
    
    // Group messages by other user
    $conversations = [];
    $seen = [];
    
    foreach ($messages as $msg) {
        $otherUserId = $msg['other_user_id'];
        
        if (!isset($seen[$otherUserId])) {
            // Count unread messages from this user
            $unreadCount = fetchOne(
                "SELECT COUNT(*) as count FROM messages 
                 WHERE sender_id = ? AND recipient_id = ? AND is_read = 0",
                [$otherUserId, $userId]
            );
            
            $conversations[] = [
                'id' => $otherUserId, // Using user ID as conversation ID
                'other_user_id' => (int)$otherUserId,
                'other_user_name' => $msg['other_user_name'],
                'other_user_image' => $msg['other_user_image'],
                'last_message' => substr($msg['message'], 0, 100) . (strlen($msg['message']) > 100 ? '...' : ''),
                'last_message_time' => $msg['created_at'],
                'unread_count' => (int)$unreadCount['count'],
                'property_id' => $msg['listing_id'],
                'property_title' => $msg['property_title']
            ];
            $seen[$otherUserId] = true;
        }
    }
    
    jsonResponse([
        'success' => true,
        'conversations' => $conversations
    ]);
    
} catch (Exception $e) {
    error_log("Get Conversations Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Failed to load conversations',
        'details' => $e->getMessage()
    ], 500);
}
