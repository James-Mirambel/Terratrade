<?php
/**
 * Delete Property API Endpoint for My Listings
 * TerraTrade Land Trading System
 */

require_once __DIR__ . '/../../config/config.php';

// Check authentication
if (!isLoggedIn()) {
    jsonResponse(['error' => 'Authentication required'], 401);
}

// Only allow DELETE method
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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
    
    // Start transaction for data consistency
    $db = getDB();
    $db->beginTransaction();
    
    // Check if property has active offers
    $activeOffers = fetchOne("SELECT COUNT(*) as count FROM offers WHERE property_id = ? AND status IN ('submitted', 'pending')", [$propertyId]);
    if ($activeOffers['count'] > 0) {
        $db->rollback();
        jsonResponse([
            'success' => false,
            'error' => 'Cannot delete property with active offers. Please reject all offers first.'
        ], 400);
    }
    
    // Check for active contracts that would prevent deletion
    $activeContracts = fetchOne("SELECT COUNT(*) as count FROM contracts WHERE property_id = ? AND status IN ('pending_signatures', 'signed')", [$propertyId]);
    if ($activeContracts['count'] > 0) {
        $db->rollback();
        jsonResponse([
            'success' => false,
            'error' => 'Cannot delete property with active contracts. Please complete or cancel contracts first.'
        ], 400);
    }
    
    // Clean up related records (soft delete or mark as inactive)
    
    // 1. Mark rejected offers as withdrawn (offers table doesn't have 'deleted' status)
    executeQuery("UPDATE offers SET status = 'withdrawn', updated_at = NOW() WHERE property_id = ? AND status IN ('rejected')", [$propertyId]);
    
    // 2. Remove from user favorites
    executeQuery("DELETE FROM user_favorites WHERE property_id = ?", [$propertyId]);
    
    // 3. Delete property-related notifications (notifications table doesn't have status column)
    executeQuery("DELETE FROM notifications WHERE type IN ('offer_received', 'offer_accepted', 'offer_rejected') AND JSON_EXTRACT(data, '$.property_id') = ?", [$propertyId]);
    
    // 4. Mark conversations about this property as archived
    executeQuery("UPDATE conversations SET status = 'archived', updated_at = NOW() WHERE property_id = ?", [$propertyId]);
    
    // 5. Soft delete the property itself
    executeQuery("UPDATE properties SET status = 'deleted', updated_at = NOW() WHERE id = ? AND user_id = ?", 
                [$propertyId, $userId]);
    
    // Commit transaction
    $db->commit();
    
    // Log the deletion with comprehensive audit trail
    logAudit($userId, 'property_delete', 'properties', $propertyId, [
        'title' => $property['title'],
        'price' => $property['price'],
        'area_sqm' => $property['area_sqm'],
        'city' => $property['city'],
        'province' => $property['province']
    ], null);
    
    jsonResponse([
        'success' => true,
        'message' => 'Property deleted successfully!'
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    
    error_log("Delete Property API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    jsonResponse([
        'success' => false,
        'error' => 'Failed to delete property',
        'details' => $e->getMessage()
    ], 500);
}
?>
