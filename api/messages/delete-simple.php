<?php
/**
 * Delete Message API (Simple Version)
 * Deletes a message (soft delete - only for sender)
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
    
    // Get request data
    $data = json_decode(file_get_contents('php://input'), true);
    $messageId = isset($data['message_id']) ? (int)$data['message_id'] : 0;
    
    if (!$messageId) {
        jsonResponse(['success' => false, 'error' => 'message_id is required'], 400);
        exit;
    }
    
    // Check if message exists
    $message = fetchOne(
        "SELECT * FROM messages WHERE id = ?",
        [$messageId]
    );
    
    if (!$message) {
        jsonResponse(['success' => false, 'error' => 'Message not found'], 404);
        exit;
    }
    
    // Check if user is involved in this conversation
    if ((int)$message['sender_id'] !== $userId && (int)$message['recipient_id'] !== $userId) {
        jsonResponse(['success' => false, 'error' => 'You are not part of this conversation'], 403);
        exit;
    }
    
    // Soft delete - mark as deleted by the appropriate party
    if ((int)$message['sender_id'] === $userId) {
        // User is the sender
        executeQuery(
            "UPDATE messages SET is_deleted_by_sender = 1 WHERE id = ?",
            [$messageId]
        );
    } else {
        // User is the recipient
        executeQuery(
            "UPDATE messages SET is_deleted_by_recipient = 1 WHERE id = ?",
            [$messageId]
        );
    }
    
    jsonResponse([
        'success' => true,
        'message' => 'Message deleted successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Delete Message Error: " . $e->getMessage());
    jsonResponse([
        'success' => false,
        'error' => 'Failed to delete message',
        'details' => $e->getMessage()
    ], 500);
}
