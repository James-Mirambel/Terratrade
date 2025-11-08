<?php
/**
 * Terminate Session API
 * TerraTrade Land Trading System
 */

require_once '../../config/config.php';

header('Content-Type: application/json');

// Require login
Auth::requireLogin();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Invalid request method'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
}

$currentUser = Auth::getCurrentUser();
$userId = $currentUser['id'];
$sessionId = $input['session_id'] ?? '';

if (empty($sessionId)) {
    jsonResponse(['success' => false, 'error' => 'Session ID is required'], 400);
}

// Prevent terminating current session
if ($sessionId === session_id()) {
    jsonResponse(['success' => false, 'error' => 'Cannot terminate current session'], 400);
}

try {
    // Verify the session belongs to the current user
    $session = fetchOne("SELECT user_id FROM user_sessions WHERE id = ?", [$sessionId]);
    
    if (!$session) {
        jsonResponse(['success' => false, 'error' => 'Session not found'], 404);
    }
    
    if ($session['user_id'] != $userId) {
        jsonResponse(['success' => false, 'error' => 'Unauthorized'], 403);
    }
    
    // Delete the session
    executeQuery("DELETE FROM user_sessions WHERE id = ?", [$sessionId]);
    
    // Log audit
    logAudit($userId, 'session_terminate', 'user_sessions', null, [
        'terminated_session' => $sessionId
    ]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Session terminated successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Terminate session error: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Failed to terminate session'], 500);
}
