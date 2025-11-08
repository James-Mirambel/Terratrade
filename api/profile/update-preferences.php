<?php
/**
 * Update User Preferences API
 * TerraTrade Land Trading System
 */

require_once '../../config/config.php';

header('Content-Type: application/json');

// Require login
Auth::requireLogin();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Invalid request method'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!verifyCSRFToken($input['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
}

$currentUser = Auth::getCurrentUser();
$userId = $currentUser['id'];

try {
    // Prepare preferences data
    $preferences = [
        'notifications' => [
            'new_offers' => $input['notify_new_offers'] ?? false,
            'offer_updates' => $input['notify_offer_updates'] ?? false,
            'messages' => $input['notify_messages'] ?? false,
            'auction_updates' => $input['notify_auction_updates'] ?? false,
            'marketing' => $input['notify_marketing'] ?? false,
        ],
        'display' => [
            'area_unit' => $input['area_unit'] ?? 'sqm',
            'currency' => $input['currency'] ?? 'PHP',
        ],
        'privacy' => [
            'show_email' => $input['show_email'] ?? false,
            'show_phone' => $input['show_phone'] ?? false,
            'allow_messages_unverified' => $input['allow_messages_unverified'] ?? false,
        ]
    ];
    
    $preferencesJson = json_encode($preferences);
    
    // Check if user preferences record exists
    $existing = fetchOne("SELECT id FROM user_preferences WHERE user_id = ?", [$userId]);
    
    if ($existing) {
        // Update existing preferences
        executeQuery("UPDATE user_preferences SET preferences = ?, updated_at = NOW() WHERE user_id = ?",
                    [$preferencesJson, $userId]);
    } else {
        // Insert new preferences
        executeQuery("INSERT INTO user_preferences (user_id, preferences) VALUES (?, ?)",
                    [$userId, $preferencesJson]);
    }
    
    // Log audit
    logAudit($userId, 'preferences_update', 'user_preferences', $userId);
    
    jsonResponse([
        'success' => true,
        'message' => 'Preferences saved successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Update preferences error: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Failed to save preferences'], 500);
}
