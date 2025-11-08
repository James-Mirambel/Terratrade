<?php
/**
 * Dedicated My Listings API Router
 * TerraTrade Land Trading System
 */

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

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

// Get JSON input for POST/PUT requests
$input = null;
if (in_array($method, ['POST', 'PUT'])) {
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if ($rawInput && !$input) {
        jsonResponse(['success' => false, 'error' => 'Invalid JSON input'], 400);
        exit;
    }
}

try {
    $user = getCurrentUser();
    $userId = $user['id'];
    
    switch ($action) {
        case 'load':
        case 'list':
        case '':
            // Load user's listings
            handleLoadListings($userId);
            break;
            
        case 'create':
            if ($method !== 'POST') {
                jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
                exit;
            }
            handleCreateListing($userId, $input);
            break;
            
        case 'update':
            if ($method !== 'PUT' || !$id) {
                jsonResponse(['success' => false, 'error' => 'Method not allowed or ID missing'], 405);
                exit;
            }
            handleUpdateListing($userId, $id, $input);
            break;
            
        case 'delete':
            if ($method !== 'DELETE' || !$id) {
                jsonResponse(['success' => false, 'error' => 'Method not allowed or ID missing'], 405);
                exit;
            }
            handleDeleteListing($userId, $id);
            break;
            
        case 'toggle-status':
            if ($method !== 'POST' || !$id) {
                jsonResponse(['success' => false, 'error' => 'Method not allowed or ID missing'], 405);
                exit;
            }
            handleToggleStatus($userId, $id, $input);
            break;
            
        default:
            jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
    }
    
} catch (Exception $e) {
    error_log("My Listings API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    jsonResponse(['success' => false, 'error' => 'Internal server error', 'details' => $e->getMessage()], 500);
}

/**
 * Load user's property listings
 */
function handleLoadListings($userId) {
    try {
        $sql = "SELECT p.*, 
                       COUNT(DISTINCT pv.id) as views_count,
                       COUNT(DISTINCT o.id) as offers_count,
                       COALESCE(p.hectares, p.area_sqm / 10000) as hectares
                FROM properties p
                LEFT JOIN property_views pv ON p.id = pv.property_id
                LEFT JOIN offers o ON p.id = o.listing_id AND o.status IN ('submitted', 'pending')
                WHERE p.user_id = ? AND p.status != 'deleted'
                GROUP BY p.id
                ORDER BY p.created_at DESC";
        
        $properties = fetchAll($sql, [$userId]);
        
        $formattedProperties = array_map(function($property) {
            return [
                'id' => (int)$property['id'],
                'title' => $property['title'],
                'description' => $property['description'] ?: '',
                'price' => (float)$property['price'],
                'area_sqm' => (float)$property['area_sqm'],
                'hectares' => round((float)$property['hectares'], 4),
                'zoning' => $property['zoning'] ?: 'Not specified',
                'type' => $property['type'] ?: 'sale',
                'status' => $property['status'],
                'city' => $property['city'],
                'province' => $property['province'],
                'region' => $property['region'],
                'barangay' => $property['barangay'] ?: '',
                'contact_name' => $property['contact_name'] ?: '',
                'contact_phone' => $property['contact_phone'] ?: '',
                'views_count' => (int)(isset($property['views_count']) ? $property['views_count'] : 0),
                'offers_count' => (int)(isset($property['offers_count']) ? $property['offers_count'] : 0),
                'created_at' => $property['created_at'],
                'updated_at' => $property['updated_at']
            ];
        }, $properties);
        
        // Calculate stats
        $activeCount = 0;
        $pendingCount = 0;
        foreach ($formattedProperties as $property) {
            if ($property['status'] === 'active') $activeCount++;
            if ($property['status'] === 'pending') $pendingCount++;
        }
        
        $stats = [
            'total_listings' => count($formattedProperties),
            'active_listings' => $activeCount,
            'pending_listings' => $pendingCount,
            'total_value' => array_sum(array_column($formattedProperties, 'price'))
        ];
        
        jsonResponse([
            'success' => true,
            'properties' => $formattedProperties,
            'stats' => $stats,
            'total' => count($formattedProperties)
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Create new property listing
 */
function handleCreateListing($userId, $data) {
    if (!$data) {
        jsonResponse(['success' => false, 'error' => 'No data provided'], 400);
        return;
    }
    
    // Validate required fields
    $required = ['title', 'price', 'area_sqm', 'city', 'province', 'region', 'contact_name', 'contact_phone'];
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
        return;
    }
    
    // Validate numeric fields
    if (!is_numeric($data['price']) || $data['price'] <= 0) {
        jsonResponse(['success' => false, 'error' => 'Price must be a positive number'], 400);
        return;
    }
    
    if (!is_numeric($data['area_sqm']) || $data['area_sqm'] <= 0) {
        jsonResponse(['success' => false, 'error' => 'Area must be a positive number'], 400);
        return;
    }
    
    try {
        // Log the incoming data for debugging
        error_log("Create Listing Data: " . json_encode($data));
        
        $hectares = round($data['area_sqm'] / 10000, 4);
        
        $sql = "INSERT INTO properties (
            user_id, title, description, price, area_sqm, hectares, zoning, type, 
            region, province, city, barangay, contact_name, contact_phone, 
            status, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())";
        
        $params = [
            $userId,
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
            trim($data['contact_name']),
            trim($data['contact_phone'])
        ];
        
        // Log the SQL and params for debugging
        error_log("Create Listing SQL: " . $sql);
        error_log("Create Listing Params: " . json_encode($params));
        
        executeQuery($sql, $params);
        $propertyId = lastInsertId();
        
        // Log the creation (only if logAudit function exists)
        if (function_exists('logAudit')) {
            logAudit($userId, 'property_create', 'properties', $propertyId, null, [
                'title' => $data['title'],
                'price' => $data['price']
            ]);
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'Property listing created successfully!',
            'property_id' => $propertyId
        ]);
        
    } catch (Exception $e) {
        error_log("Create Listing Error: " . $e->getMessage());
        error_log("Create Listing Stack Trace: " . $e->getTraceAsString());
        throw $e;
    }
}

/**
 * Update property listing
 */
function handleUpdateListing($userId, $propertyId, $data) {
    if (!$data) {
        jsonResponse(['success' => false, 'error' => 'No data provided'], 400);
        return;
    }
    
    // Verify ownership
    $property = fetchOne("SELECT * FROM properties WHERE id = ? AND user_id = ?", [$propertyId, $userId]);
    if (!$property) {
        jsonResponse(['success' => false, 'error' => 'Property not found or access denied'], 404);
        return;
    }
    
    try {
        if (isset($data['status']) && count($data) === 1) {
            // Status-only update
            $allowedStatuses = ['active', 'withdrawn', 'sold'];
            if (!in_array($data['status'], $allowedStatuses)) {
                jsonResponse(['success' => false, 'error' => 'Invalid status'], 400);
                return;
            }
            
            executeQuery("UPDATE properties SET status = ?, updated_at = NOW() WHERE id = ?", 
                        [$data['status'], $propertyId]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Property status updated successfully!'
            ]);
            return;
        }
        
        // Full update - validate required fields
        $required = ['title', 'price', 'area_sqm', 'city', 'province', 'region'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                jsonResponse(['success' => false, 'error' => "Field {$field} is required"], 400);
                return;
            }
        }
        
        $hectares = round($data['area_sqm'] / 10000, 4);
        
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
        
        logAudit($userId, 'property_update', 'properties', $propertyId, 
                ['title' => $property['title']], ['title' => $data['title']]);
        
        jsonResponse([
            'success' => true,
            'message' => 'Property updated successfully!'
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Delete property listing
 */
function handleDeleteListing($userId, $propertyId) {
    // Verify ownership
    $property = fetchOne("SELECT * FROM properties WHERE id = ? AND user_id = ?", [$propertyId, $userId]);
    if (!$property) {
        jsonResponse(['success' => false, 'error' => 'Property not found or access denied'], 404);
        return;
    }
    
    try {
        // Start transaction for data consistency
        $db = getDB();
        $db->beginTransaction();
        
        // Check for active offers that would prevent deletion
        $activeOffers = fetchOne("SELECT COUNT(*) as count FROM offers WHERE property_id = ? AND status IN ('submitted', 'pending')", [$propertyId]);
        if ($activeOffers['count'] > 0) {
            $db->rollback();
            jsonResponse([
                'success' => false,
                'error' => 'Cannot delete property with active offers. Please reject all offers first.'
            ], 400);
            return;
        }
        
        // Check for active contracts that would prevent deletion
        $activeContracts = fetchOne("SELECT COUNT(*) as count FROM contracts WHERE property_id = ? AND status IN ('pending_signatures', 'signed')", [$propertyId]);
        if ($activeContracts['count'] > 0) {
            $db->rollback();
            jsonResponse([
                'success' => false,
                'error' => 'Cannot delete property with active contracts. Please complete or cancel contracts first.'
            ], 400);
            return;
        }
        
        // Clean up related records (soft delete or mark as inactive)
        
        // 1. Mark rejected/withdrawn offers as withdrawn (offers table doesn't have 'deleted' status)
        executeQuery("UPDATE offers SET status = 'withdrawn', updated_at = NOW() WHERE property_id = ? AND status IN ('rejected')", [$propertyId]);
        
        // 2. Remove from user favorites
        executeQuery("DELETE FROM user_favorites WHERE property_id = ?", [$propertyId]);
        
        // 3. Delete property-related notifications (notifications table doesn't have status column)
        executeQuery("DELETE FROM notifications WHERE type IN ('offer_received', 'offer_accepted', 'offer_rejected') AND JSON_EXTRACT(data, '$.property_id') = ?", [$propertyId]);
        
        // 4. Mark conversations about this property as archived
        executeQuery("UPDATE conversations SET status = 'archived', updated_at = NOW() WHERE property_id = ?", [$propertyId]);
        
        // 5. Keep property documents and images for audit trail, but mark property as deleted
        // (Files will remain on disk for potential recovery/audit purposes)
        
        // 6. Soft delete the property itself
        executeQuery("UPDATE properties SET status = 'deleted', updated_at = NOW() WHERE id = ? AND user_id = ?", 
                    [$propertyId, $userId]);
        
        // Commit transaction
        $db->commit();
        
        // Log the deletion with comprehensive audit trail
        if (function_exists('logAudit')) {
            logAudit($userId, 'property_delete', 'properties', $propertyId, [
                'title' => $property['title'],
                'price' => $property['price'],
                'area_sqm' => $property['area_sqm'],
                'city' => $property['city'],
                'province' => $property['province']
            ], null);
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'Property and all related data deleted successfully!'
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        if (isset($db) && $db->inTransaction()) {
            $db->rollback();
        }
        error_log("Delete Property Error: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Toggle property status
 */
function handleToggleStatus($userId, $propertyId, $data) {
    $property = fetchOne("SELECT * FROM properties WHERE id = ? AND user_id = ?", [$propertyId, $userId]);
    if (!$property) {
        jsonResponse(['success' => false, 'error' => 'Property not found or access denied'], 404);
        return;
    }
    
    try {
        $newStatus = $property['status'] === 'active' ? 'withdrawn' : 'active';
        
        executeQuery("UPDATE properties SET status = ?, updated_at = NOW() WHERE id = ?", 
                    [$newStatus, $propertyId]);
        
        jsonResponse([
            'success' => true,
            'message' => "Property {$newStatus} successfully!",
            'new_status' => $newStatus
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}
?>
