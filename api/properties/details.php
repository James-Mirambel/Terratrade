<?php
/**
 * Property Details API Endpoint
 * TerraTrade Land Trading System
 */

// Disable HTML error output to prevent JSON corruption
ini_set('display_errors', 0);
error_reporting(0);

require_once __DIR__ . '/../../config/config.php';

// Set JSON content type and CORS headers
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
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
    exit;
}

try {
    // Get property ID from URL
    $propertyId = null;
    
    // Method 1: From REQUEST_URI
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('/\/properties\/details\/(\d+)/', $requestUri, $matches)) {
        $propertyId = (int)$matches[1];
    }
    
    // Method 2: From PATH_INFO if Method 1 failed
    if (!$propertyId) {
        $pathInfo = $_SERVER['PATH_INFO'] ?? '';
        $pathParts = explode('/', trim($pathInfo, '/'));
        
        foreach ($pathParts as $i => $part) {
            if ($part === 'details' && isset($pathParts[$i + 1]) && is_numeric($pathParts[$i + 1])) {
                $propertyId = (int)$pathParts[$i + 1];
                break;
            }
        }
    }
    
    // Method 3: From query parameter as fallback
    if (!$propertyId && isset($_GET['id'])) {
        $propertyId = (int)$_GET['id'];
    }
    
    if (!$propertyId) {
        jsonResponse(['success' => false, 'error' => 'Property ID is required'], 400);
        exit;
    }
    
    // Get property details with owner information
    $sql = "SELECT p.*, 
                   u.full_name as owner_name,
                   u.email as owner_email,
                   u.phone as owner_phone,
                   COUNT(DISTINCT pv.id) as views_count,
                   COUNT(DISTINCT o.id) as offers_count
            FROM properties p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN property_views pv ON p.id = pv.property_id
            LEFT JOIN offers o ON p.id = o.listing_id AND o.status IN ('pending', 'accepted')
            WHERE p.id = ? AND p.status != 'deleted'
            GROUP BY p.id";
    
    $property = fetchOne($sql, [$propertyId]);
    
    if (!$property) {
        jsonResponse(['success' => false, 'error' => 'Property not found'], 404);
        exit;
    }
    
    // Record property view if user is logged in and not the owner
    if (isLoggedIn()) {
        $currentUser = getCurrentUser();
        if ($currentUser['id'] != $property['user_id']) {
            // Check if view already recorded today
            $today = date('Y-m-d');
            $existingView = fetchOne("
                SELECT id FROM property_views 
                WHERE property_id = ? AND user_id = ? AND DATE(created_at) = ?
            ", [$propertyId, $currentUser['id'], $today]);
            
            if (!$existingView) {
                executeQuery("
                    INSERT INTO property_views (property_id, user_id, created_at) 
                    VALUES (?, ?, NOW())
                ", [$propertyId, $currentUser['id']]);
            }
        }
    }
    
    // Format property data
    $formattedProperty = [
        'id' => (int)$property['id'],
        'title' => $property['title'],
        'description' => $property['description'] ?: 'No description available',
        'price' => (float)$property['price'],
        'price_formatted' => '₱' . number_format($property['price']),
        'area_sqm' => (float)$property['area_sqm'],
        'area_formatted' => number_format($property['area_sqm']) . ' sqm',
        'hectares' => round($property['area_sqm'] / 10000, 4),
        'hectares_formatted' => number_format(round($property['area_sqm'] / 10000, 4), 4) . ' ha',
        'zoning' => $property['zoning'] ?: 'Not specified',
        'type' => $property['type'] ?: 'sale',
        'status' => $property['status'],
        'region' => $property['region'],
        'province' => $property['province'],
        'city' => $property['city'],
        'barangay' => $property['barangay'] ?: '',
        'location_full' => trim(implode(', ', array_filter([
            $property['barangay'],
            $property['city'],
            $property['province'],
            $property['region']
        ]))),
        'contact_name' => $property['contact_name'] ?: $property['owner_name'],
        'contact_phone' => $property['contact_phone'] ?: $property['owner_phone'],
        'owner_name' => $property['owner_name'],
        'owner_email' => $property['owner_email'],
        'views_count' => (int)($property['views_count'] ?? 0),
        'offers_count' => (int)($property['offers_count'] ?? 0),
        'created_at' => $property['created_at'],
        'updated_at' => $property['updated_at'],
        'auction_ends' => $property['auction_ends']
    ];
    
    // Get property images (placeholder for now)
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
            'caption' => 'Aerial view of the land',
            'is_primary' => false
        ]
    ];
    
    // Get property documents (placeholder for now)
    $documents = [
        [
            'id' => 1,
            'name' => 'Land Title',
            'type' => 'PDF',
            'size' => '2.5 MB',
            'url' => '#',
            'uploaded_at' => $property['created_at']
        ],
        [
            'id' => 2,
            'name' => 'Tax Declaration',
            'type' => 'PDF',
            'size' => '1.2 MB',
            'url' => '#',
            'uploaded_at' => $property['created_at']
        ]
    ];
    
    // Check if current user can make offers (not owner and logged in)
    $canMakeOffer = false;
    $isOwner = false;
    
    if (isLoggedIn()) {
        $currentUser = getCurrentUser();
        $isOwner = ($currentUser['id'] == $property['user_id']);
        $canMakeOffer = !$isOwner && $property['status'] === 'active';
    }
    
    jsonResponse([
        'success' => true,
        'property' => $formattedProperty,
        'images' => $images,
        'documents' => $documents,
        'permissions' => [
            'can_make_offer' => $canMakeOffer,
            'is_owner' => $isOwner,
            'can_edit' => $isOwner,
            'can_view_offers' => $isOwner
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Property Details API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    jsonResponse([
        'success' => false,
        'error' => 'Failed to load property details',
        'details' => $e->getMessage()
    ], 500);
}
?>
