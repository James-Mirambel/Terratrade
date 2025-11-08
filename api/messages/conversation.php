<?php
/**
 * Get Messages in a Conversation API
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
    
    $otherUserId = isset($_GET['other_user_id']) ? (int)$_GET['other_user_id'] : null;
    $listingId = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : null;
    
    if (!$otherUserId) {
        jsonResponse(['success' => false, 'error' => 'other_user_id is required'], 400);
        exit;
    }
    
    // Find or create conversation
    $sql = "SELECT c.id FROM conversations c
            INNER JOIN conversation_participants cp1 ON c.id = cp1.conversation_id AND cp1.user_id = ?
            INNER JOIN conversation_participants cp2 ON c.id = cp2.conversation_id AND cp2.user_id = ?
            WHERE 1=1";
    $params = [$userId, $otherUserId];
    
    if ($listingId) {
        $sql .= " AND c.property_id = ?";
        $params[] = $listingId;
    }
    
    $sql .= " LIMIT 1";
    $conversation = fetchOne($sql, $params);
    
    if (!$conversation) {
        // Return empty messages if conversation doesn't exist yet
        jsonResponse([
            'success' => true,
            'messages' => [],
            'total' => 0
        ]);
        exit;
    }
    
    $conversationId = $conversation['id'];
    
    // Get messages
    $sql = "SELECT 
                m.*,
                m.content as message,
                sender.full_name as sender_name,
                sender.email as sender_email,
                p.title as property_title
            FROM messages m
            LEFT JOIN users sender ON m.sender_id = sender.id
            LEFT JOIN properties p ON (SELECT property_id FROM conversations WHERE id = m.conversation_id) = p.id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at ASC";
    
    $messages = fetchAll($sql, [$conversationId]);
    
    // Mark messages as read by adding user to read_by JSON array
    executeQuery("
        UPDATE messages 
        SET read_by = JSON_ARRAY_APPEND(COALESCE(read_by, '[]'), '$', ?)
        WHERE conversation_id = ? 
        AND sender_id != ?
        AND NOT JSON_CONTAINS(read_by, ?, '$')
    ", [$userId, $conversationId, $userId, json_encode($userId)]);
    
    // Format messages
    $formattedMessages = array_map(function($msg) use ($userId) {
        $readBy = json_decode($msg['read_by'] ?? '[]', true);
        return [
            'id' => (int)$msg['id'],
            'sender_id' => (int)$msg['sender_id'],
            'sender_name' => $msg['sender_name'],
            'message' => $msg['message'],
            'property_id' => null,
            'property_title' => $msg['property_title'] ?? null,
            'is_read' => in_array($userId, $readBy),
            'created_at' => $msg['created_at'],
            'is_mine' => $msg['sender_id'] == $userId
        ];
    }, $messages);
    
    jsonResponse([
        'success' => true,
        'messages' => $formattedMessages,
        'total' => count($formattedMessages)
    ]);
    
} catch (Exception $e) {
    error_log("Conversation API Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Failed to load messages',
        'details' => $e->getMessage()
    ], 500);
}
