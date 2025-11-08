<?php
/**
 * Minimal Property Details API - Pure JSON
 * TerraTrade Land Trading System
 */

// Start output buffering and disable all error display
ob_start();
ini_set('display_errors', 0);
ini_set('log_errors', 0);
error_reporting(0);

// Set JSON header immediately
header('Content-Type: application/json; charset=utf-8');

try {
    // Basic database connection without config includes
    $host = 'localhost';
    $dbname = 'terratrade_db';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // Get property ID
    $propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if (!$propertyId) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Property ID is required']);
        exit;
    }
    
    // Simple query
    $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? LIMIT 1");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch();
    
    if (!$property) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Property not found']);
        exit;
    }
    
    // Format response
    $response = [
        'success' => true,
        'property' => [
            'id' => (int)$property['id'],
            'title' => $property['title'] ?? 'Untitled Property',
            'description' => $property['description'] ?? 'No description available',
            'price' => (float)($property['price'] ?? 0),
            'price_formatted' => '₱' . number_format($property['price'] ?? 0),
            'area_sqm' => (float)($property['area_sqm'] ?? 0),
            'area_formatted' => number_format($property['area_sqm'] ?? 0) . ' sqm',
            'zoning' => $property['zoning'] ?? 'Not specified',
            'type' => $property['type'] ?? 'sale',
            'status' => $property['status'] ?? 'active',
            'region' => $property['region'] ?? '',
            'province' => $property['province'] ?? '',
            'city' => $property['city'] ?? '',
            'location_full' => ($property['city'] ?? '') . ', ' . ($property['province'] ?? ''),
            'views_count' => 0,
            'offers_count' => 0
        ],
        'images' => [
            [
                'id' => 1,
                'url' => 'https://via.placeholder.com/800x600/4CAF50/white?text=Property+Image',
                'caption' => 'Property view',
                'is_primary' => true
            ]
        ],
        'documents' => [],
        'permissions' => [
            'can_make_offer' => true,
            'is_owner' => false,
            'can_edit' => false,
            'can_view_offers' => false
        ]
    ];
    
    // Clear buffer and output JSON
    ob_clean();
    echo json_encode($response);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'message' => $e->getMessage()
    ]);
}

ob_end_flush();
?>
