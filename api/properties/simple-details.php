<?php
/**
 * Simple Property Details API
 * TerraTrade Land Trading System
 */

// Prevent any HTML output
ob_start();
ini_set('display_errors', 0);
error_reporting(0);

try {
    require_once __DIR__ . '/../../config/config.php';
    
    // Clear any previous output
    ob_clean();
    
    // Set JSON headers
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    // Only allow GET method
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    
    // Get property ID
    $propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$propertyId) {
        echo json_encode(['success' => false, 'error' => 'Property ID is required']);
        exit;
    }
    
    // Auto-login test user if not logged in
    if (!isLoggedIn()) {
        $testUser = fetchOne("SELECT * FROM users WHERE email = 'test@example.com' LIMIT 1");
        if ($testUser) {
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $testUser['id'];
            $_SESSION['user_email'] = $testUser['email'];
            $_SESSION['user_name'] = $testUser['full_name'];
            $_SESSION['user_role'] = $testUser['role'];
        }
    }
    
    // Get property details
    $sql = "SELECT p.*, u.full_name as owner_name, u.email as owner_email
            FROM properties p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.id = ? AND p.status != 'deleted'";
    
    $property = fetchOne($sql, [$propertyId]);
    
    if (!$property) {
        echo json_encode(['success' => false, 'error' => 'Property not found']);
        exit;
    }
    
    // Format property data safely
    $formattedProperty = [
        'id' => (int)$property['id'],
        'title' => $property['title'] ?? 'Untitled Property',
        'description' => $property['description'] ?? 'No description available',
        'price' => (float)($property['price'] ?? 0),
        'price_formatted' => '₱' . number_format($property['price'] ?? 0),
        'area_sqm' => (float)($property['area_sqm'] ?? 0),
        'area_formatted' => number_format($property['area_sqm'] ?? 0) . ' sqm',
        'hectares' => round(($property['area_sqm'] ?? 0) / 10000, 4),
        'zoning' => $property['zoning'] ?? 'Not specified',
        'type' => $property['type'] ?? 'sale',
        'status' => $property['status'] ?? 'active',
        'region' => $property['region'] ?? '',
        'province' => $property['province'] ?? '',
        'city' => $property['city'] ?? '',
        'location_full' => trim(($property['city'] ?? '') . ', ' . ($property['province'] ?? '') . ', ' . ($property['region'] ?? ''), ', '),
        'contact_name' => $property['contact_name'] ?? $property['owner_name'] ?? '',
        'contact_phone' => $property['contact_phone'] ?? '',
        'owner_name' => $property['owner_name'] ?? 'Unknown',
        'views_count' => 0,
        'offers_count' => 0,
        'created_at' => $property['created_at'] ?? date('Y-m-d H:i:s'),
        'updated_at' => $property['updated_at'] ?? $property['created_at'] ?? date('Y-m-d H:i:s')
    ];
    
    // Sample images
    $images = [
        [
            'id' => 1,
            'url' => 'https://via.placeholder.com/800x600/4CAF50/white?text=Property+Image',
            'caption' => 'Main view of the property',
            'is_primary' => true
        ],
        [
            'id' => 2,
            'url' => 'https://via.placeholder.com/800x600/2196F3/white?text=Aerial+View',
            'caption' => 'Aerial view',
            'is_primary' => false
        ]
    ];
    
    // Sample documents
    $documents = [
        [
            'id' => 1,
            'name' => 'Land Title',
            'type' => 'PDF',
            'size' => '2.5 MB'
        ]
    ];
    
    // Check permissions
    $currentUserId = isLoggedIn() ? getCurrentUserId() : 0;
    $isOwner = ($currentUserId == $property['user_id']);
    
    $permissions = [
        'can_make_offer' => !$isOwner && $property['status'] === 'active',
        'is_owner' => $isOwner,
        'can_edit' => $isOwner,
        'can_view_offers' => $isOwner
    ];
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'property' => $formattedProperty,
        'images' => $images,
        'documents' => $documents,
        'permissions' => $permissions
    ]);
    
} catch (Exception $e) {
    // Clear any output and return error
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load property details',
        'debug' => $e->getMessage()
    ]);
}

// End output buffering
ob_end_flush();
?>
