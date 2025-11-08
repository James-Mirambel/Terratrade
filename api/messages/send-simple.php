<?php
/**
 * Send Message API (Simple Version)
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
    
    // Insert message using simple schema
    $sql = "INSERT INTO messages (sender_id, recipient_id, message, listing_id, is_read, created_at) 
            VALUES (?, ?, ?, ?, 0, NOW())";
    
    executeQuery($sql, [$userId, $receiverId, $message, $propertyId]);
    $messageId = lastInsertId();
    
    // Get the created message
    $createdMessage = fetchOne("
        SELECT m.*, 
               sender.full_name as sender_name,
               recipient.full_name as recipient_name,
               p.title as property_title
        FROM messages m
        LEFT JOIN users sender ON m.sender_id = sender.id
        LEFT JOIN users recipient ON m.recipient_id = recipient.id
        LEFT JOIN properties p ON m.listing_id = p.id
        WHERE m.id = ?
    ", [$messageId]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Message sent successfully',
        'data' => [
            'id' => (int)$createdMessage['id'],
            'sender_id' => (int)$createdMessage['sender_id'],
            'sender_name' => $createdMessage['sender_name'],
            'recipient_id' => (int)$createdMessage['recipient_id'],
            'recipient_name' => $createdMessage['recipient_name'],
            'message' => $createdMessage['message'],
            'property_id' => $propertyId,
            'property_title' => $createdMessage['property_title'] ?? null,
            'is_read' => false,
            'created_at' => $createdMessage['created_at']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Send Message API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    jsonResponse([
        'success' => false,
        'error' => 'Failed to send message',
        'details' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], 500);
}
