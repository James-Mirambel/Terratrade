<?php
/**
 * Main Configuration File
 * TerraTrade Land Trading System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Manila');

// Application constants
define('APP_NAME', 'TerraTrade');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/Terratrade');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Security settings
define('JWT_SECRET', 'your-secret-key-change-in-production');
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600 * 24); // 24 hours

// File upload settings
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx']);

// Email settings (configure for production)
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('FROM_EMAIL', 'noreply@terratrade.com');
define('FROM_NAME', 'TerraTrade System');

// Pagination
define('DEFAULT_PAGE_SIZE', 20);
define('MAX_PAGE_SIZE', 100);

// Business rules
define('MIN_OFFER_PERCENTAGE', 0.5); // 50% of listing price
define('DEFAULT_OFFER_EXPIRY_DAYS', 7);
define('AUCTION_EXTENSION_MINUTES', 5);
define('ESCROW_FEE_PERCENTAGE', 2.5);

// Include required files
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Auto-create upload directories
$uploadDirs = [
    UPLOAD_PATH . 'properties/',
    UPLOAD_PATH . 'documents/',
    UPLOAD_PATH . 'kyc/',
    UPLOAD_PATH . 'contracts/',
    UPLOAD_PATH . 'avatars/'
];

foreach ($uploadDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Set default headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
?>
