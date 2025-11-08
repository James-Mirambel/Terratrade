<?php
/**
 * Common Functions
 * TerraTrade Land Trading System
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

/**
 * Format area
 */
function formatArea($sqm) {
    if ($sqm >= 10000) {
        return number_format($sqm / 10000, 2) . ' hectares';
    }
    return number_format($sqm, 0) . ' sqm';
}

/**
 * Time ago function
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

/**
 * Generate unique filename
 */
function generateUniqueFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return uniqid() . '_' . time() . '.' . $extension;
}

/**
 * Upload file
 */
function uploadFile($file, $uploadDir, $allowedTypes = null) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large'];
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if ($allowedTypes && !in_array($extension, $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    
    $filename = generateUniqueFilename($file['name']);
    $filepath = $uploadDir . $filename;
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $filepath];
    }
    
    return ['success' => false, 'error' => 'Upload failed'];
}

/**
 * Send JSON response
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Log audit trail
 */
function logAudit($userId, $action, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
    try {
        $sql = "INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $userId,
            $action,
            $tableName,
            $recordId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        executeQuery($sql, $params);
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

/**
 * Send notification
 */
function sendNotification($userId, $type, $title, $message, $data = null) {
    try {
        $sql = "INSERT INTO notifications (user_id, type, title, message, data) VALUES (?, ?, ?, ?, ?)";
        $params = [$userId, $type, $title, $message, $data ? json_encode($data) : null];
        executeQuery($sql, $params);
        return true;
    } catch (Exception $e) {
        error_log("Notification failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get system setting
 */
function getSetting($key, $default = null) {
    static $settings = [];
    
    if (!isset($settings[$key])) {
        try {
            $result = fetchOne("SELECT setting_value, setting_type FROM system_settings WHERE setting_key = ?", [$key]);
            
            if ($result) {
                $value = $result['setting_value'];
                switch ($result['setting_type']) {
                    case 'boolean':
                        $settings[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                        break;
                    case 'number':
                        $settings[$key] = is_numeric($value) ? (float)$value : $default;
                        break;
                    case 'json':
                        $settings[$key] = json_decode($value, true) ?: $default;
                        break;
                    default:
                        $settings[$key] = $value;
                }
            } else {
                $settings[$key] = $default;
            }
        } catch (Exception $e) {
            // Database not available, return default
            $settings[$key] = $default;
        }
    }
    
    return $settings[$key];
}

/**
 * Calculate distance between two coordinates
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c;
}

/**
 * Validate Philippine phone number
 */
function isValidPhilippinePhone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check various Philippine phone formats
    $patterns = [
        '/^(09|639)\d{9}$/',     // Mobile: 09xxxxxxxxx or 639xxxxxxxxx
        '/^(02|032|033|034|035|036|038|042|043|044|045|046|047|048|049|052|053|054|055|056|062|063|064|065|068|072|074|075|077|078|082|083|084|085|086|087|088)\d{7}$/' // Landline
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $phone)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
}

/**
 * Validate file upload
 */
function validateFileUpload($file, $allowedTypes, $maxSize = MAX_FILE_SIZE) {
    $errors = [];
    
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $errors[] = 'No file uploaded';
        return $errors;
    }
    
    if ($file['size'] > $maxSize) {
        $errors[] = 'File size exceeds limit (' . formatBytes($maxSize) . ')';
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedTypes)) {
        $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', $allowedTypes);
    }
    
    return $errors;
}

/**
 * Format bytes
 */
function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

/**
 * Paginate results
 */
function paginate($totalRecords, $currentPage = 1, $pageSize = DEFAULT_PAGE_SIZE) {
    $totalPages = ceil($totalRecords / $pageSize);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $pageSize;
    
    return [
        'total_records' => $totalRecords,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'page_size' => $pageSize,
        'offset' => $offset,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

/**
 * Note: Database helper functions (getDB, executeQuery, fetchOne, fetchAll, lastInsertId) 
 * are defined in config/database.php to avoid conflicts
 */

?>
