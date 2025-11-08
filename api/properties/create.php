<?php
/**
 * Create Property API Endpoint for My Listings
 * TerraTrade Land Trading System
 */

require_once __DIR__ . '/../../config/config.php';

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

// Only allow POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    $user = getCurrentUser();
    $userId = $user['id'];
    
    // Get JSON data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Log the received data for debugging
    error_log("Create Property - Received data: " . print_r($data, true));
    error_log("Create Property - User ID: " . $userId);
    
    if (!$data) {
        error_log("Create Property - Invalid JSON: " . $input);
        jsonResponse(['error' => 'Invalid JSON data', 'raw_input' => substr($input, 0, 200)], 400);
    }
    
    // Validate required fields
    $required = ['title', 'price', 'area_sqm', 'city', 'province', 'region', 'contact_phone'];
    $missing = [];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        jsonResponse([
            'success' => false, 
            'error' => 'Missing required fields: ' . implode(', ', $missing),
            'received_data' => array_keys($data)
        ], 400);
    }
    
    // Set contact_name from user if not provided
    if (empty($data['contact_name'])) {
        $data['contact_name'] = $user['full_name'];
    }
    
    // Validate numeric fields
    if (!is_numeric($data['price']) || $data['price'] <= 0) {
        jsonResponse(['success' => false, 'error' => 'Price must be a positive number'], 400);
    }
    
    if (!is_numeric($data['area_sqm']) || $data['area_sqm'] <= 0) {
        jsonResponse(['success' => false, 'error' => 'Area must be a positive number'], 400);
    }
    
    // Prepare location string
    $location = trim($data['city']) . ', ' . trim($data['province']) . ', ' . trim($data['region']);
    
    // Prepare data for insertion (area_hectares is auto-calculated, don't insert it)
    $sql = "INSERT INTO properties (
        user_id, title, description, location, price, area_sqm, zoning, listing_type, 
        region, province, city, barangay, contact_name, contact_phone, 
        status, created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())";
    
    $params = [
        $userId,
        trim($data['title']),
        trim($data['description'] ?? ''),
        $location,
        (float)$data['price'],
        (float)$data['area_sqm'],
        $data['zoning'] ?? 'Residential',
        $data['type'] ?? 'sale',
        trim($data['region']),
        trim($data['province']),
        trim($data['city']),
        trim($data['barangay'] ?? ''),
        trim($data['contact_name']),
        trim($data['contact_phone'])
    ];
    
    executeQuery($sql, $params);
    $propertyId = lastInsertId();
    
    // Log the creation (skip if audit_logs table doesn't exist)
    try {
        logAudit($userId, 'property_create', 'properties', $propertyId, null, [
            'title' => $data['title'],
            'price' => $data['price'],
            'area_sqm' => $data['area_sqm']
        ]);
    } catch (Exception $auditError) {
        error_log("Audit log error (non-critical): " . $auditError->getMessage());
    }
    
    // Get the created property data
    $createdProperty = fetchOne("
        SELECT p.*, 
               p.area_hectares as hectares,
               0 as views_count,
               0 as offers_count
        FROM properties p 
        WHERE p.id = ?
    ", [$propertyId]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Property listing created successfully!',
        'property_id' => $propertyId,
        'property' => [
            'id' => (int)$createdProperty['id'],
            'title' => $createdProperty['title'],
            'description' => $createdProperty['description'],
            'price' => (float)$createdProperty['price'],
            'area_sqm' => (float)$createdProperty['area_sqm'],
            'hectares' => (float)$createdProperty['hectares'],
            'zoning' => $createdProperty['zoning'],
            'type' => $createdProperty['listing_type'],
            'status' => $createdProperty['status'],
            'city' => $createdProperty['city'],
            'province' => $createdProperty['province'],
            'region' => $createdProperty['region'],
            'location' => $createdProperty['location'],
            'views_count' => 0,
            'offers_count' => 0,
            'created_at' => $createdProperty['created_at'],
            'updated_at' => $createdProperty['updated_at']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Create Property API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    jsonResponse([
        'success' => false,
        'error' => 'Failed to create property listing',
        'details' => $e->getMessage()
    ], 500);
}
?>
