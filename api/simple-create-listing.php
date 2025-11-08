<?php
/**
 * Simple Create Listing API - Debug Version
 * TerraTrade Land Trading System
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';

// Set JSON content type and CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log all incoming data
error_log("=== CREATE LISTING DEBUG ===");
error_log("Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Headers: " . json_encode(getallheaders()));

try {
    // Check if user is logged in
    if (!function_exists('isLoggedIn') || !isLoggedIn()) {
        // Try to auto-login test user for debugging
        if (isset($_SESSION)) {
            $testUser = fetchOne("SELECT * FROM users WHERE email = 'test@example.com' LIMIT 1");
            if ($testUser) {
                $_SESSION['user_id'] = $testUser['id'];
                $_SESSION['user_email'] = $testUser['email'];
                $_SESSION['user_name'] = $testUser['full_name'];
                $_SESSION['user_role'] = $testUser['role'];
                error_log("Auto-logged in test user: " . $testUser['email']);
            }
        }
        
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'error' => 'Authentication required']);
            exit;
        }
    }
    
    $user = getCurrentUser();
    $userId = $user['id'];
    error_log("User ID: " . $userId);
    
    // Only allow POST method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    
    // Get JSON input
    $rawInput = file_get_contents('php://input');
    error_log("Raw Input: " . $rawInput);
    
    $data = json_decode($rawInput, true);
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
        exit;
    }
    
    error_log("Parsed Data: " . json_encode($data));
    
    // Validate required fields
    $required = ['title', 'price', 'area_sqm', 'city', 'province', 'region', 'contact_name', 'contact_phone'];
    $missing = [];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        echo json_encode([
            'success' => false, 
            'error' => 'Missing required fields: ' . implode(', ', $missing),
            'received_fields' => array_keys($data)
        ]);
        exit;
    }
    
    // Validate numeric fields
    if (!is_numeric($data['price']) || $data['price'] <= 0) {
        echo json_encode(['success' => false, 'error' => 'Price must be a positive number']);
        exit;
    }
    
    if (!is_numeric($data['area_sqm']) || $data['area_sqm'] <= 0) {
        echo json_encode(['success' => false, 'error' => 'Area must be a positive number']);
        exit;
    }
    
    // Calculate hectares
    $hectares = round($data['area_sqm'] / 10000, 4);
    
    // Check if properties table exists and get its structure
    $db = getDB();
    $tableCheck = $db->query("SHOW TABLES LIKE 'properties'");
    if ($tableCheck->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Properties table does not exist']);
        exit;
    }
    
    // Get table columns
    $columnsResult = $db->query("DESCRIBE properties");
    $columns = $columnsResult->fetchAll(PDO::FETCH_COLUMN);
    error_log("Available columns: " . json_encode($columns));
    
    // Build INSERT query based on available columns
    $insertColumns = ['user_id', 'title', 'price', 'area_sqm'];
    $insertValues = ['?', '?', '?', '?'];
    $params = [
        $userId,
        trim($data['title']),
        (float)$data['price'],
        (float)$data['area_sqm']
    ];
    
    // Add optional columns if they exist
    $optionalFields = [
        'description' => $data['description'] ?? '',
        'hectares' => $hectares,
        'zoning' => $data['zoning'] ?? 'Residential',
        'type' => $data['type'] ?? 'sale',
        'region' => trim($data['region']),
        'province' => trim($data['province']),
        'city' => trim($data['city']),
        'barangay' => trim($data['barangay'] ?? ''),
        'contact_name' => trim($data['contact_name']),
        'contact_phone' => trim($data['contact_phone']),
        'status' => 'active'
    ];
    
    foreach ($optionalFields as $column => $value) {
        if (in_array($column, $columns)) {
            $insertColumns[] = $column;
            $insertValues[] = '?';
            $params[] = $value;
        }
    }
    
    // Add timestamp columns if they exist
    if (in_array('created_at', $columns)) {
        $insertColumns[] = 'created_at';
        $insertValues[] = 'NOW()';
    }
    if (in_array('updated_at', $columns)) {
        $insertColumns[] = 'updated_at';
        $insertValues[] = 'NOW()';
    }
    
    $sql = "INSERT INTO properties (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
    
    error_log("Final SQL: " . $sql);
    error_log("Final Params: " . json_encode($params));
    
    // Execute the query
    executeQuery($sql, $params);
    $propertyId = lastInsertId();
    
    error_log("Property created with ID: " . $propertyId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Property listing created successfully!',
        'property_id' => $propertyId,
        'debug_info' => [
            'user_id' => $userId,
            'columns_used' => $insertColumns,
            'sql' => $sql
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Create Listing Error: " . $e->getMessage());
    error_log("Stack Trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
