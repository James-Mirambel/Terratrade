<?php
/**
 * Offers Management API
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
            handleLoadOffers($userId, $_GET);
            break;
            
        case 'accept':
            if ($method !== 'POST' || !$id) {
                jsonResponse(['success' => false, 'error' => 'Method not allowed or ID missing'], 405);
                exit;
            }
            handleAcceptOffer($userId, $id);
            break;
            
        case 'reject':
            if ($method !== 'POST' || !$id) {
                jsonResponse(['success' => false, 'error' => 'Method not allowed or ID missing'], 405);
                exit;
            }
            handleRejectOffer($userId, $id);
            break;
            
        case 'counter':
            if ($method !== 'POST' || !$id) {
                jsonResponse(['success' => false, 'error' => 'Method not allowed or ID missing'], 405);
                exit;
            }
            handleCounterOffer($userId, $id, $input);
            break;
            
        default:
            jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
    }
    
} catch (Exception $e) {
    error_log("Offers API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    jsonResponse(['success' => false, 'error' => 'Internal server error', 'details' => $e->getMessage()], 500);
}

/**
 * Load offers for user's properties
 */
function handleLoadOffers($userId, $params) {
    try {
        $propertyId = isset($params['property_id']) ? (int)$params['property_id'] : null;
        
        // Base query to get offers on user's properties
        $sql = "SELECT o.*, 
                       p.title as property_title,
                       p.price as property_price,
                       u.full_name as buyer_name,
                       u.email as buyer_email,
                       u.phone as buyer_phone
                FROM offers o
                INNER JOIN properties p ON o.property_id = p.id
                INNER JOIN users u ON o.buyer_id = u.id
                WHERE p.user_id = ?";
        
        $params_array = [$userId];
        
        // Filter by specific property if provided
        if ($propertyId) {
            // Verify property ownership first
            $property = fetchOne("SELECT id FROM properties WHERE id = ? AND user_id = ?", [$propertyId, $userId]);
            if (!$property) {
                jsonResponse(['success' => false, 'error' => 'Property not found or access denied'], 404);
                return;
            }
            
            $sql .= " AND p.id = ?";
            $params_array[] = $propertyId;
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        $offers = fetchAll($sql, $params_array);
        
        // Format offers
        $formattedOffers = array_map(function($offer) {
            return [
                'id' => (int)$offer['id'],
                'property_id' => (int)$offer['property_id'],
                'property_title' => $offer['property_title'],
                'property_price' => (float)$offer['property_price'],
                'buyer_id' => (int)$offer['buyer_id'],
                'buyer_name' => $offer['buyer_name'],
                'buyer_email' => $offer['buyer_email'],
                'buyer_phone' => $offer['buyer_phone'] ?: '',
                'offer_amount' => (float)$offer['offer_amount'],
                'message' => $offer['buyer_comments'] ?: '',
                'status' => $offer['status'],
                'created_at' => $offer['created_at'],
                'updated_at' => $offer['updated_at']
            ];
        }, $offers);
        
        // Calculate stats
        $stats = [
            'total_offers' => count($formattedOffers),
            'pending_offers' => count(array_filter($formattedOffers, function($o) { return $o['status'] === 'pending'; })),
            'accepted_offers' => count(array_filter($formattedOffers, function($o) { return $o['status'] === 'accepted'; })),
            'rejected_offers' => count(array_filter($formattedOffers, function($o) { return $o['status'] === 'rejected'; })),
            'highest_offer' => count($formattedOffers) > 0 ? max(array_column($formattedOffers, 'offer_amount')) : 0
        ];
        
        jsonResponse([
            'success' => true,
            'offers' => $formattedOffers,
            'stats' => $stats,
            'total' => count($formattedOffers)
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Accept an offer
 */
function handleAcceptOffer($userId, $offerId) {
    try {
        // Verify offer ownership (offer on user's property)
        $offer = fetchOne("
            SELECT o.*, p.user_id as property_owner_id, p.title as property_title
            FROM offers o
            INNER JOIN properties p ON o.property_id = p.id
            WHERE o.id = ? AND p.user_id = ?
        ", [$offerId, $userId]);
        
        if (!$offer) {
            jsonResponse(['success' => false, 'error' => 'Offer not found or access denied'], 404);
            return;
        }
        
        if ($offer['status'] !== 'pending') {
            jsonResponse(['success' => false, 'error' => 'Only pending offers can be accepted'], 400);
            return;
        }
        
        // Update offer status
        executeQuery("UPDATE offers SET status = 'accepted', updated_at = NOW() WHERE id = ?", [$offerId]);
        
        // Reject all other pending offers for the same property
        executeQuery("
            UPDATE offers 
            SET status = 'rejected', updated_at = NOW() 
            WHERE property_id = ? AND id != ? AND status = 'pending'
        ", [$offer['property_id'], $offerId]);
        
        // Update property status to sold
        executeQuery("UPDATE properties SET status = 'sold', updated_at = NOW() WHERE id = ?", [$offer['property_id']]);
        
        // Send notification to buyer
        sendNotification(
            $offer['buyer_id'],
            'offer_accepted',
            'Offer Accepted!',
            "Your offer of ₱" . number_format($offer['offer_amount']) . " for '{$offer['property_title']}' has been accepted!",
            ['offer_id' => $offerId, 'property_id' => $offer['property_id']]
        );
        
        // Log audit
        if (function_exists('logAudit')) {
            logAudit($userId, 'offer_accept', 'offers', $offerId, 
                    ['status' => 'pending'], ['status' => 'accepted']);
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'Offer accepted successfully!'
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Reject an offer
 */
function handleRejectOffer($userId, $offerId) {
    try {
        // Verify offer ownership
        $offer = fetchOne("
            SELECT o.*, p.user_id as property_owner_id, p.title as property_title
            FROM offers o
            INNER JOIN properties p ON o.property_id = p.id
            WHERE o.id = ? AND p.user_id = ?
        ", [$offerId, $userId]);
        
        if (!$offer) {
            jsonResponse(['success' => false, 'error' => 'Offer not found or access denied'], 404);
            return;
        }
        
        if ($offer['status'] !== 'pending') {
            jsonResponse(['success' => false, 'error' => 'Only pending offers can be rejected'], 400);
            return;
        }
        
        // Update offer status
        executeQuery("UPDATE offers SET status = 'rejected', updated_at = NOW() WHERE id = ?", [$offerId]);
        
        // Send notification to buyer
        sendNotification(
            $offer['buyer_id'],
            'offer_rejected',
            'Offer Rejected',
            "Your offer for '{$offer['property_title']}' has been rejected.",
            ['offer_id' => $offerId, 'property_id' => $offer['property_id']]
        );
        
        // Log audit
        if (function_exists('logAudit')) {
            logAudit($userId, 'offer_reject', 'offers', $offerId, 
                    ['status' => 'pending'], ['status' => 'rejected']);
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'Offer rejected successfully!'
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

/**
 * Make counter offer
 */
function handleCounterOffer($userId, $offerId, $data) {
    if (!$data || empty($data['counter_amount'])) {
        jsonResponse(['success' => false, 'error' => 'Counter amount is required'], 400);
        return;
    }
    
    if (!is_numeric($data['counter_amount']) || $data['counter_amount'] <= 0) {
        jsonResponse(['success' => false, 'error' => 'Counter amount must be a positive number'], 400);
        return;
    }
    
    try {
        // Verify offer ownership
        $offer = fetchOne("
            SELECT o.*, p.user_id as property_owner_id, p.title as property_title
            FROM offers o
            INNER JOIN properties p ON o.property_id = p.id
            WHERE o.id = ? AND p.user_id = ?
        ", [$offerId, $userId]);
        
        if (!$offer) {
            jsonResponse(['success' => false, 'error' => 'Offer not found or access denied'], 404);
            return;
        }
        
        if ($offer['status'] !== 'pending') {
            jsonResponse(['success' => false, 'error' => 'Only pending offers can be countered'], 400);
            return;
        }
        
        // Update original offer status and add counter info
        executeQuery("
            UPDATE offers 
            SET status = 'countered', 
                counter_amount = ?, 
                counter_message = ?, 
                updated_at = NOW() 
            WHERE id = ?
        ", [
            (float)$data['counter_amount'],
            $data['message'] ?? '',
            $offerId
        ]);
        
        // Send notification to buyer
        sendNotification(
            $offer['buyer_id'],
            'offer_countered',
            'Counter Offer Received',
            "The seller has made a counter offer of ₱" . number_format($data['counter_amount']) . " for '{$offer['property_title']}'.",
            ['offer_id' => $offerId, 'property_id' => $offer['property_id'], 'counter_amount' => $data['counter_amount']]
        );
        
        // Log audit
        if (function_exists('logAudit')) {
            logAudit($userId, 'offer_counter', 'offers', $offerId, 
                    ['status' => 'pending'], ['status' => 'countered', 'counter_amount' => $data['counter_amount']]);
        }
        
        jsonResponse([
            'success' => true,
            'message' => 'Counter offer sent successfully!'
        ]);
        
    } catch (Exception $e) {
        throw $e;
    }
}

?>
