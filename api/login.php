<?php
/**
 * Simple Login Endpoint
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    require_once __DIR__ . '/../config/config.php';
    
    // Get input data
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    
    // Validate required fields
    if (empty($input['email']) || empty($input['password'])) {
        echo json_encode(['success' => false, 'error' => 'Email and password are required']);
        exit;
    }
    
    // Find user
    $user = fetchOne("SELECT * FROM users WHERE email = ?", [$input['email']]);
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
        exit;
    }
    
    // Verify password
    if (!password_verify($input['password'], $user['password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid email or password']);
        exit;
    }
    
    // Check if user is active
    if ($user['status'] !== 'active') {
        echo json_encode(['success' => false, 'error' => 'Account is not active']);
        exit;
    }
    
    // Update last login
    executeQuery("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
    
    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    
    // Log audit
    try {
        logAudit($user['id'], 'login');
    } catch (Exception $e) {
        // Ignore audit log errors
    }
    
    // Return user data (without password)
    unset($user['password_hash']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => $user
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Login failed: ' . $e->getMessage()
    ]);
}
?>
