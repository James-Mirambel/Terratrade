<?php
/**
 * Upload Avatar API
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

// Verify CSRF token
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
}

$currentUser = Auth::getCurrentUser();
$userId = $currentUser['id'];

// Check if file was uploaded
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['success' => false, 'error' => 'No file uploaded'], 400);
}

$file = $_FILES['avatar'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    jsonResponse(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed'], 400);
}

// Validate file size (5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    jsonResponse(['success' => false, 'error' => 'File size must be less than 5MB'], 400);
}

try {
    // Get current avatar to delete later
    $currentAvatar = fetchOne("SELECT profile_image FROM users WHERE id = ?", [$userId]);
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
    $uploadPath = UPLOAD_PATH . 'avatars/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    $filePath = $uploadPath . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        jsonResponse(['success' => false, 'error' => 'Failed to save file'], 500);
    }
    
    // Update database
    executeQuery("UPDATE users SET profile_image = ?, updated_at = NOW() WHERE id = ?", 
                 [$filename, $userId]);
    
    // Delete old avatar if exists
    if (!empty($currentAvatar['profile_image'])) {
        $oldFile = $uploadPath . $currentAvatar['profile_image'];
        if (file_exists($oldFile)) {
            unlink($oldFile);
        }
    }
    
    // Log audit
    logAudit($userId, 'avatar_update', 'users', $userId);
    
    jsonResponse([
        'success' => true,
        'message' => 'Avatar updated successfully',
        'avatar_url' => BASE_URL . '/uploads/avatars/' . $filename
    ]);
    
} catch (Exception $e) {
    error_log("Upload avatar error: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Failed to upload avatar'], 500);
}
