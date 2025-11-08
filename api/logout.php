<?php
/**
 * Simple Logout Endpoint
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

try {
    // Log audit if user is logged in
    if (isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/../config/config.php';
        
        try {
            logAudit($_SESSION['user_id'], 'logout');
        } catch (Exception $e) {
            // Ignore audit log errors
        }
    }
    
    // Clear session
    session_destroy();
    
    echo json_encode([
        'success' => true,
        'message' => 'Logout successful'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Logout failed: ' . $e->getMessage()
    ]);
}
?>
