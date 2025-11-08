<?php
/**
 * Update Property API Endpoint for My Listings
 * TerraTrade Land Trading System
 */

require_once __DIR__ . '/../../config/config.php';

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

// Only allow PUT method
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    $user = getCurrentUser();
    $userId = $user['id'];
    
    // Get property ID from URL - multiple methods
    $propertyId = null;
    
    // Method 1: From REQUEST_URI
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (preg_match('/\/properties\/(\d+)/', $requestUri, $matches)) {
        $propertyId = (int)$matches[1];
    }
    
    // Method 2: From PATH_INFO if Method 1 failed
    if (!$propertyId) {
        $pathInfo = $_SERVER['PATH_INFO'] ?? '';
        $pathParts = explode('/', trim($pathInfo, '/'));
        
        foreach ($pathParts as $i => $part) {
            if ($part === 'properties' && isset($pathParts[$i + 1]) && is_numeric($pathParts[$i + 1])) {
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
        jsonResponse(['error' => 'Property ID is required'], 400);
    }
    
    // Verify property ownership
    $property = fetchOne("SELECT * FROM properties WHERE id = ? AND user_id = ?", [$propertyId, $userId]);
    if (!$property) {
        jsonResponse(['error' => 'Property not found or access denied'], 404);
    }
    
    // Get JSON data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        jsonResponse(['error' => 'Invalid JSON data'], 400);
    }
    
    // If only updating status, handle it separately
    if (isset($data['status']) && count($data) === 1) {
        $allowedStatuses = ['active', 'withdrawn', 'sold'];
        if (!in_array($data['status'], $allowedStatuses)) {
            jsonResponse(['error' => 'Invalid status'], 400);
        }
        
        executeQuery("UPDATE properties SET status = ?, updated_at = NOW() WHERE id = ?", 
                    [$data['status'], $propertyId]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Property status updated successfully!'
        ]);
    }
    
    // Full property update
    $required = ['title', 'price', 'area_sqm', 'city', 'province', 'region'];
    $missing = [];
    
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $missing[] = $field;
        }
    }
    
    if (!empty($missing)) {
        jsonResponse([
            'success' => false, 
            'error' => 'Missing required fields: ' . implode(', ', $missing)
        ], 400);
    }
    
    // Validate numeric fields
    if (!is_numeric($data['price']) || $data['price'] <= 0) {
        jsonResponse(['success' => false, 'error' => 'Price must be a positive number'], 400);
    }
    
    if (!is_numeric($data['area_sqm']) || $data['area_sqm'] <= 0) {
        jsonResponse(['success' => false, 'error' => 'Area must be a positive number'], 400);
    }
    
    // Calculate hectares
    $hectares = round($data['area_sqm'] / 10000, 4);
    
    // Store old values for audit
    $oldValues = [
        'title' => $property['title'],
        'price' => $property['price'],
        'area_sqm' => $property['area_sqm']
    ];
    
    // Update property
    $sql = "UPDATE properties SET 
        title = ?, description = ?, price = ?, area_sqm = ?, hectares = ?, 
        zoning = ?, type = ?, region = ?, province = ?, city = ?, barangay = ?, 
        contact_name = ?, contact_phone = ?, updated_at = NOW()
        WHERE id = ? AND user_id = ?";
    
    $params = [
        trim($data['title']),
        trim($data['description'] ?? ''),
        (float)$data['price'],
        (float)$data['area_sqm'],
        $hectares,
        $data['zoning'] ?? 'Residential',
        $data['type'] ?? 'sale',
        trim($data['region']),
        trim($data['province']),
        trim($data['city']),
        trim($data['barangay'] ?? ''),
        trim($data['contact_name'] ?? ''),
        trim($data['contact_phone'] ?? ''),
        $propertyId,
        $userId
    ];
    
    executeQuery($sql, $params);
    
    // Log the update
    logAudit($userId, 'property_update', 'properties', $propertyId, $oldValues, [
        'title' => $data['title'],
        'price' => $data['price'],
        'area_sqm' => $data['area_sqm']
    ]);
    
    // Get updated property data
    $updatedProperty = fetchOne("
        SELECT p.*, 
               COALESCE(p.hectares, p.area_sqm / 10000) as hectares,
               COUNT(DISTINCT pv.id) as views_count,
               COUNT(DISTINCT o.id) as offers_count
        FROM properties p
        LEFT JOIN property_views pv ON p.id = pv.property_id
        LEFT JOIN offers o ON p.id = o.listing_id AND o.status IN ('submitted', 'pending')
        WHERE p.id = ?
        GROUP BY p.id
    ", [$propertyId]);
    
    jsonResponse([
        'success' => true,
        'message' => 'Property updated successfully!',
        'property' => [
            'id' => (int)$updatedProperty['id'],
            'title' => $updatedProperty['title'],
            'description' => $updatedProperty['description'],
            'price' => (float)$updatedProperty['price'],
            'area_sqm' => (float)$updatedProperty['area_sqm'],
            'hectares' => (float)$updatedProperty['hectares'],
            'zoning' => $updatedProperty['zoning'],
            'type' => $updatedProperty['type'],
            'status' => $updatedProperty['status'],
            'city' => $updatedProperty['city'],
            'province' => $updatedProperty['province'],
            'region' => $updatedProperty['region'],
            'views_count' => (int)$updatedProperty['views_count'],
            'offers_count' => (int)$updatedProperty['offers_count'],
            'created_at' => $updatedProperty['created_at'],
            'updated_at' => $updatedProperty['updated_at']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Update Property API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    jsonResponse([
        'success' => false,
        'error' => 'Failed to update property',
        'details' => $e->getMessage()
    ], 500);
}
?>
