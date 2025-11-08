<?php
/**
 * Upload KYC Document API
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

// Validate document type
$documentType = $_POST['document_type'] ?? '';
$allowedTypes = ['national_id', 'drivers_license', 'passport', 'tin_id', 'business_permit', 'other'];

if (!in_array($documentType, $allowedTypes)) {
    jsonResponse(['success' => false, 'error' => 'Invalid document type'], 400);
}

// Check if front image was uploaded
if (!isset($_FILES['front_image']) || $_FILES['front_image']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['success' => false, 'error' => 'Front image is required'], 400);
}

$frontFile = $_FILES['front_image'];
$backFile = isset($_FILES['back_image']) && $_FILES['back_image']['error'] === UPLOAD_ERR_OK ? $_FILES['back_image'] : null;

// Validate front file type
$allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$frontMimeType = finfo_file($finfo, $frontFile['tmp_name']);

if (!in_array($frontMimeType, $allowedMimeTypes)) {
    finfo_close($finfo);
    jsonResponse(['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, and PDF are allowed'], 400);
}

// Validate front file size (5MB)
if ($frontFile['size'] > 5 * 1024 * 1024) {
    finfo_close($finfo);
    jsonResponse(['success' => false, 'error' => 'Front image must be less than 5MB'], 400);
}

// Validate back file if provided
if ($backFile) {
    $backMimeType = finfo_file($finfo, $backFile['tmp_name']);
    if (!in_array($backMimeType, $allowedMimeTypes)) {
        finfo_close($finfo);
        jsonResponse(['success' => false, 'error' => 'Invalid back image type. Only JPG, PNG, and PDF are allowed'], 400);
    }
    if ($backFile['size'] > 5 * 1024 * 1024) {
        finfo_close($finfo);
        jsonResponse(['success' => false, 'error' => 'Back image must be less than 5MB'], 400);
    }
}

finfo_close($finfo);

try {
    // Create directory if it doesn't exist
    $uploadPath = UPLOAD_PATH . 'kyc/';
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    // Generate unique filename for front image
    $frontExtension = pathinfo($frontFile['name'], PATHINFO_EXTENSION);
    $frontFilename = 'kyc_' . $userId . '_' . $documentType . '_front_' . time() . '.' . $frontExtension;
    $frontFilePath = $uploadPath . $frontFilename;
    
    // Move front file
    if (!move_uploaded_file($frontFile['tmp_name'], $frontFilePath)) {
        jsonResponse(['success' => false, 'error' => 'Failed to save front image'], 500);
    }
    
    // Handle back image if provided
    $backFilename = null;
    if ($backFile) {
        $backExtension = pathinfo($backFile['name'], PATHINFO_EXTENSION);
        $backFilename = 'kyc_' . $userId . '_' . $documentType . '_back_' . time() . '.' . $backExtension;
        $backFilePath = $uploadPath . $backFilename;
        
        if (!move_uploaded_file($backFile['tmp_name'], $backFilePath)) {
            // Clean up front image
            unlink($frontFilePath);
            jsonResponse(['success' => false, 'error' => 'Failed to save back image'], 500);
        }
    }
    
    // Get document number if provided
    $documentNumber = !empty($_POST['document_number']) ? sanitize($_POST['document_number']) : null;
    
    // Insert into database
    $sql = "INSERT INTO kyc_documents (user_id, document_type, document_number, front_image, back_image, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')";
    
    executeQuery($sql, [$userId, $documentType, $documentNumber, $frontFilename, $backFilename]);
    
    // Update user KYC status to pending if it was 'none'
    executeQuery("UPDATE users SET kyc_status = 'pending' WHERE id = ? AND kyc_status = 'none'", 
                 [$userId]);
    
    // Update session
    $_SESSION['kyc_status'] = 'pending';
    
    // Log audit
    logAudit($userId, 'kyc_document_upload', 'kyc_documents', lastInsertId());
    
    // Send notification to admins
    $admins = fetchAll("SELECT id FROM users WHERE role = 'admin'");
    foreach ($admins as $admin) {
        sendNotification(
            $admin['id'],
            'kyc_submitted',
            'New KYC Document Submitted',
            "User {$currentUser['full_name']} has submitted a KYC document for review."
        );
    }
    
    jsonResponse([
        'success' => true,
        'message' => 'Document uploaded successfully. It will be reviewed within 24-48 hours.'
    ]);
    
} catch (Exception $e) {
    error_log("Upload KYC error: " . $e->getMessage());
    jsonResponse(['success' => false, 'error' => 'Failed to upload document'], 500);
}
