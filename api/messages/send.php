<?php
/**
 * Send Message API
 * TerraTrade Land Trading System
 */

require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json');

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    exit;
}

try {
    $user = getCurrentUser();
    $userId = $user['id'];
    
    // Get JSON data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        jsonResponse(['success' => false, 'error' => 'Invalid JSON data'], 400);
        exit;
    }
    
    // Validate required fields
    if (empty($data['receiver_id']) || empty($data['message'])) {
        jsonResponse(['success' => false, 'error' => 'receiver_id and message are required'], 400);
        exit;
    }
    
    $receiverId = (int)$data['receiver_id'];
    $message = trim($data['message']);
    $propertyId = isset($data['property_id']) ? (int)$data['property_id'] : null;
    
    // Verify receiver exists
    $receiver = fetchOne("SELECT id, full_name FROM users WHERE id = ?", [$receiverId]);
    if (!$receiver) {
        jsonResponse(['success' => false, 'error' => 'Receiver not found'], 404);
        exit;
    }
    
    // Find or create conversation
    $sql = "SELECT c.id FROM conversations c
            INNER JOIN conversation_participants cp1 ON c.id = cp1.conversation_id AND cp1.user_id = ?
            INNER JOIN conversation_participants cp2 ON c.id = cp2.conversation_id AND cp2.user_id = ?
            WHERE 1=1";
    $params = [$userId, $receiverId];
    
    if ($propertyId) {
        $sql .= " AND c.property_id = ?";
        $params[] = $propertyId;
    }
    
    $sql .= " LIMIT 1";
    $conversation = fetchOne($sql, $params);
    
    if (!$conversation) {
        // Create new conversation
        executeQuery("INSERT INTO conversations (property_id, status, created_at, updated_at) VALUES (?, 'active', NOW(), NOW())", [$propertyId]);
        $conversationId = lastInsertId();
        
        // Add participants
        executeQuery("INSERT INTO conversation_participants (conversation_id, user_id, role, joined_at) VALUES (?, ?, 'buyer', NOW())", [$conversationId, $userId]);
        executeQuery("INSERT INTO conversation_participants (conversation_id, user_id, role, joined_at) VALUES (?, ?, 'seller', NOW())", [$conversationId, $receiverId]);
    } else {
        $conversationId = $conversation['id'];
    }
    
    // Insert message
    executeQuery("INSERT INTO messages (conversation_id, sender_id, message_type, content, created_at) VALUES (?, ?, 'text', ?, NOW())", 
                 [$conversationId, $userId, $message]);
    $messageId = lastInsertId();
    
    // Update conversation timestamp
    executeQuery("UPDATE conversations SET updated_at = NOW() WHERE id = ?", [$conversationId]);
    
    // Get the created message
    $createdMessage = fetchOne("
        SELECT m.*, 
               m.content as message,
               sender.full_name as sender_name,
               p.title as property_title
        FROM messages m
        LEFT JOIN users sender ON m.sender_id = sender.id
        LEFT JOIN properties p ON (SELECT property_id FROM conversations WHERE id = m.conversation_id) = p.id
        WHERE m.id = ?
    ", [$messageId]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Message sent successfully',
        'data' => [
            'id' => (int)$createdMessage['id'],
            'sender_id' => (int)$createdMessage['sender_id'],
            'sender_name' => $createdMessage['sender_name'],
            'message' => $createdMessage['message'],
            'property_id' => $propertyId,
            'property_title' => $createdMessage['property_title'] ?? null,
            'is_read' => false,
            'created_at' => $createdMessage['created_at'],
            'is_mine' => true
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Send Message API Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Failed to send message',
        'details' => $e->getMessage()
    ], 500);
}
