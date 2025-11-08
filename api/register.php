<?php
/**
 * Simple Registration Endpoint
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
    $required = ['email', 'password', 'full_name'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            echo json_encode(['success' => false, 'error' => "Field {$field} is required"]);
            exit;
        }
    }
    
    // Validate email
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Invalid email format']);
        exit;
    }
    
    // Check if email already exists
    $existing = fetchOne("SELECT id FROM users WHERE email = ?", [$input['email']]);
    if ($existing) {
        echo json_encode(['success' => false, 'error' => 'Email already registered']);
        exit;
    }
    
    // Hash password
    $passwordHash = password_hash($input['password'], PASSWORD_DEFAULT);
    
    // Insert user
    $sql = "INSERT INTO users (email, password_hash, full_name, phone, role, status, email_verified) VALUES (?, ?, ?, ?, ?, 'active', 1)";
    $params = [
        $input['email'],
        $passwordHash,
        $input['full_name'],
        $input['phone'] ?? null,
        $input['role'] ?? 'user'
    ];
    
    executeQuery($sql, $params);
    $userId = lastInsertId();
    
    // Get created user
    $user = fetchOne("SELECT id, email, full_name, role, status FROM users WHERE id = ?", [$userId]);
    
    // Log audit
    try {
        logAudit($userId, 'register');
    } catch (Exception $e) {
        // Ignore audit log errors
    }
    
    // Send notification
    try {
        sendNotification($userId, 'system', 'Welcome to TerraTrade!', 'Your account has been created successfully.');
    } catch (Exception $e) {
        // Ignore notification errors
    }
    
    // Set session
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $user['email'];
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'user' => $user
    ]);
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Registration failed: ' . $e->getMessage()
    ]);
}
?>
