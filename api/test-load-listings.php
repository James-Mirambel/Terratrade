<?php
/**
 * Test Load Listings API
 * TerraTrade Land Trading System
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

try {
    // Check authentication
    if (!isLoggedIn()) {
        // Auto-login test user for debugging
        $testUser = fetchOne("SELECT * FROM users WHERE email = 'test@example.com' LIMIT 1");
        if ($testUser) {
            $_SESSION['user_id'] = $testUser['id'];
            $_SESSION['user_email'] = $testUser['email'];
            $_SESSION['user_name'] = $testUser['full_name'];
            $_SESSION['user_role'] = $testUser['role'];
        }
        
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'error' => 'Authentication required']);
            exit;
        }
    }
    
    $user = getCurrentUser();
    $userId = $user['id'];
    
    // Simple query to get user's properties
    $sql = "SELECT p.* FROM properties p WHERE p.user_id = ? AND p.status != 'deleted' ORDER BY p.created_at DESC";
    
    $properties = fetchAll($sql, [$userId]);
    
    // Format properties with basic data
    $formattedProperties = [];
    foreach ($properties as $property) {
        $formattedProperties[] = [
            'id' => (int)$property['id'],
            'title' => $property['title'],
            'description' => isset($property['description']) ? $property['description'] : '',
            'price' => (float)$property['price'],
            'area_sqm' => (float)$property['area_sqm'],
            'hectares' => isset($property['hectares']) ? (float)$property['hectares'] : round($property['area_sqm'] / 10000, 4),
            'zoning' => isset($property['zoning']) ? $property['zoning'] : 'Not specified',
            'type' => isset($property['type']) ? $property['type'] : 'sale',
            'status' => $property['status'],
            'city' => isset($property['city']) ? $property['city'] : '',
            'province' => isset($property['province']) ? $property['province'] : '',
            'region' => isset($property['region']) ? $property['region'] : '',
            'barangay' => isset($property['barangay']) ? $property['barangay'] : '',
            'contact_name' => isset($property['contact_name']) ? $property['contact_name'] : '',
            'contact_phone' => isset($property['contact_phone']) ? $property['contact_phone'] : '',
            'views_count' => 0, // Simplified for now
            'offers_count' => 0, // Simplified for now
            'created_at' => $property['created_at'],
            'updated_at' => isset($property['updated_at']) ? $property['updated_at'] : $property['created_at']
        ];
    }
    
    // Calculate basic stats
    $activeCount = 0;
    $pendingCount = 0;
    $totalValue = 0;
    
    foreach ($formattedProperties as $property) {
        if ($property['status'] === 'active') $activeCount++;
        if ($property['status'] === 'pending') $pendingCount++;
        $totalValue += $property['price'];
    }
    
    $stats = [
        'total_listings' => count($formattedProperties),
        'active_listings' => $activeCount,
        'pending_listings' => $pendingCount,
        'total_value' => $totalValue
    ];
    
    echo json_encode([
        'success' => true,
        'properties' => $formattedProperties,
        'stats' => $stats,
        'total' => count($formattedProperties),
        'debug_info' => [
            'user_id' => $userId,
            'user_email' => $user['email'],
            'sql' => $sql,
            'raw_count' => count($properties)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Load Listings Error: " . $e->getMessage());
    error_log("Stack Trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load listings: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
