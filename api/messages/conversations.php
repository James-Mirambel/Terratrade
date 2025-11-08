<?php
/**
 * Get Conversations API
 * TerraTrade Land Trading System
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
    
    // Get all conversations for the current user
    $sql = "SELECT 
                c.id,
                c.property_id as listing_id,
                c.subject,
                c.updated_at as last_message_time,
                p.title as property_title,
                (SELECT content FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message,
                (SELECT sender_id FROM messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_sender_id,
                (SELECT user_id FROM conversation_participants 
                 WHERE conversation_id = c.id AND user_id != ? LIMIT 1) as other_user_id,
                (SELECT u.full_name FROM conversation_participants cp 
                 JOIN users u ON cp.user_id = u.id 
                 WHERE cp.conversation_id = c.id AND cp.user_id != ? LIMIT 1) as other_user_name,
                (SELECT u.email FROM conversation_participants cp 
                 JOIN users u ON cp.user_id = u.id 
                 WHERE cp.conversation_id = c.id AND cp.user_id != ? LIMIT 1) as other_user_email,
                (SELECT COUNT(*) FROM messages m2 
                 WHERE m2.conversation_id = c.id 
                 AND m2.sender_id != ?
                 AND NOT JSON_CONTAINS(m2.read_by, ?, '$')) as unread_count
            FROM conversations c
            LEFT JOIN properties p ON c.property_id = p.id
            INNER JOIN conversation_participants cp ON c.id = cp.conversation_id
            WHERE cp.user_id = ?
            ORDER BY c.updated_at DESC";
    
    $conversations = fetchAll($sql, [$userId, $userId, $userId, $userId, json_encode($userId), $userId]);
    
    // Format conversations
    $formattedConversations = [];
    
    foreach ($conversations as $conv) {
        $formattedConversations[] = [
            'id' => (int)$conv['id'],
            'other_user_id' => (int)$conv['other_user_id'],
            'other_user_name' => $conv['other_user_name'] ?? 'Unknown User',
            'other_user_email' => $conv['other_user_email'] ?? '',
            'other_user_image' => null,
            'listing_id' => $conv['listing_id'] ? (int)$conv['listing_id'] : null,
            'property_title' => $conv['property_title'] ?? null,
            'last_message' => $conv['last_message'] ?? '',
            'last_message_time' => $conv['last_message_time'],
            'unread_count' => (int)$conv['unread_count'],
            'is_sender' => $conv['last_sender_id'] == $userId
        ];
    }
    
    jsonResponse([
        'success' => true,
        'conversations' => $formattedConversations,
        'total' => count($formattedConversations)
    ]);
    
} catch (Exception $e) {
    error_log("Conversations API Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Failed to load conversations',
        'details' => $e->getMessage()
    ], 500);
}
