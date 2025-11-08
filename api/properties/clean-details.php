<?php
/**
 * Clean Property Details API - Zero HTML Output
 * TerraTrade Land Trading System
 */

// Absolutely no HTML output allowed
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 0);
error_reporting(0);

// Prevent any session output
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header first thing
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean();
    http_response_code(200);
    exit;
}

try {
    // Direct database connection - no includes
    $pdo = new PDO('mysql:host=localhost;dbname=terratrade_db;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    
    // Get and validate property ID
    $propertyId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$propertyId || $propertyId <= 0) {
        ob_clean();
        echo json_encode([
            'success' => false, 
            'error' => 'Valid property ID is required'
        ]);
        exit;
    }
    
    // Get property with owner info
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name as owner_name, u.email as owner_email 
        FROM properties p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE p.id = ? AND p.status != 'deleted'
        LIMIT 1
    ");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();
    
    if (!$property) {
        ob_clean();
        echo json_encode([
            'success' => false, 
            'error' => 'Property not found'
        ]);
        exit;
    }
    
    // Get view count
    $viewStmt = $pdo->prepare("SELECT COUNT(*) as count FROM property_views WHERE property_id = ?");
    $viewStmt->execute([$propertyId]);
    $viewCount = $viewStmt->fetch()['count'] ?? 0;
    
    // Get offer count
    $offerStmt = $pdo->prepare("SELECT COUNT(*) as count FROM offers WHERE listing_id = ? AND status IN ('pending', 'accepted')");
    $offerStmt->execute([$propertyId]);
    $offerCount = $offerStmt->fetch()['count'] ?? 0;
    
    // Format property data
    $formattedProperty = [
        'id' => (int)$property['id'],
        'title' => $property['title'] ?: 'Untitled Property',
        'description' => $property['description'] ?: 'No description available',
        'price' => (float)$property['price'],
        'price_formatted' => '₱' . number_format((float)$property['price']),
        'area_sqm' => (float)$property['area_sqm'],
        'area_formatted' => number_format((float)$property['area_sqm']) . ' sqm',
        'hectares' => round((float)$property['area_sqm'] / 10000, 4),
        'zoning' => $property['zoning'] ?: 'Not specified',
        'type' => $property['type'] ?: 'sale',
        'status' => $property['status'] ?: 'active',
        'region' => $property['region'] ?: '',
        'province' => $property['province'] ?: '',
        'city' => $property['city'] ?: '',
        'barangay' => $property['barangay'] ?: '',
        'location_full' => trim(implode(', ', array_filter([
            $property['barangay'],
            $property['city'],
            $property['province'],
            $property['region']
        ]))),
        'contact_name' => $property['contact_name'] ?: $property['owner_name'] ?: '',
        'contact_phone' => $property['contact_phone'] ?: '',
        'owner_name' => $property['owner_name'] ?: 'Unknown Owner',
        'views_count' => (int)$viewCount,
        'offers_count' => (int)$offerCount,
        'created_at' => $property['created_at'],
        'updated_at' => $property['updated_at'] ?: $property['created_at']
    ];
    
    // Sample images
    $images = [
        [
            'id' => 1,
            'url' => 'https://via.placeholder.com/800x600/4CAF50/white?text=Property+View',
            'caption' => 'Main property view',
            'is_primary' => true
        ],
        [
            'id' => 2,
            'url' => 'https://via.placeholder.com/800x600/2196F3/white?text=Aerial+View',
            'caption' => 'Aerial perspective',
            'is_primary' => false
        ]
    ];
    
    // Sample documents
    $documents = [
        [
            'id' => 1,
            'name' => 'Land Title',
            'type' => 'PDF',
            'size' => '2.5 MB',
            'uploaded_at' => $property['created_at']
        ]
    ];
    
    // Check current user permissions
    $currentUserId = $_SESSION['user_id'] ?? 0;
    $isOwner = ($currentUserId && $currentUserId == $property['user_id']);
    
    $permissions = [
        'can_make_offer' => !$isOwner && $property['status'] === 'active' && $currentUserId > 0,
        'is_owner' => $isOwner,
        'can_edit' => $isOwner,
        'can_view_offers' => $isOwner
    ];
    
    // Clean output buffer and send response
    ob_clean();
    echo json_encode([
        'success' => true,
        'property' => $formattedProperty,
        'images' => $images,
        'documents' => $documents,
        'permissions' => $permissions
    ], JSON_UNESCAPED_UNICODE);
    
} catch (PDOException $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed'
    ]);
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Server error occurred'
    ]);
}

ob_end_flush();
?>
