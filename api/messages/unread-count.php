<?php
/**
 * Unread Messages Count API
 * TerraTrade Land Trading System
 */

// Prevent HTML output
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

// Set JSON header
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit;
}

try {
    // Start session if needed
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    $userId = $_SESSION['user_id'] ?? 0;
    
    if (!$userId) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'unread_count' => 0
        ]);
        exit;
    }
    
    // Connect to database
    $pdo = new PDO('mysql:host=localhost;dbname=terratrade_db;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Count unread messages (messages not in read_by JSON array)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM messages m
        INNER JOIN conversation_participants cp ON m.conversation_id = cp.conversation_id
        WHERE cp.user_id = ? 
        AND m.sender_id != ?
        AND NOT JSON_CONTAINS(COALESCE(m.read_by, '[]'), ?, '$')
    ");
    $stmt->execute([$userId, $userId, json_encode($userId)]);
    $result = $stmt->fetch();
    
    $unreadCount = (int)($result['count'] ?? 0);
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'unread_count' => $unreadCount
    ]);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => true,
        'unread_count' => 0
    ]);
}

ob_end_flush();
?>
