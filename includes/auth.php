<?php
/**
 * Authentication System
 * TerraTrade Land Trading System
 */

class Auth {
    
    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Login user
     */
    public static function login($email, $password, $rememberMe = false) {
        try {
            $user = fetchOne("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
            
            if (!$user || !self::verifyPassword($password, $user['password_hash'])) {
                return ['success' => false, 'error' => 'Invalid email or password'];
            }
            
            // Update last login
            executeQuery("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
            
            // Create session
            self::createSession($user);
            
            // Log audit
            logAudit($user['id'], 'login');
            
            return ['success' => true, 'user' => $user];
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Login failed'];
        }
    }
    
    /**
     * Register user
     */
    public static function register($data) {
        try {
            // Validate required fields
            $required = ['email', 'password', 'full_name'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    return ['success' => false, 'error' => "Field {$field} is required"];
                }
            }
            
            // Validate email
            if (!isValidEmail($data['email'])) {
                return ['success' => false, 'error' => 'Invalid email format'];
            }
            
            // Check if email exists
            $existing = fetchOne("SELECT id FROM users WHERE email = ?", [$data['email']]);
            if ($existing) {
                return ['success' => false, 'error' => 'Email already registered'];
            }
            
            // Validate password strength
            if (strlen($data['password']) < 8) {
                return ['success' => false, 'error' => 'Password must be at least 8 characters'];
            }
            
            // Hash password
            $passwordHash = self::hashPassword($data['password']);
            
            // Insert user
            $sql = "INSERT INTO users (email, password_hash, full_name, phone, role) VALUES (?, ?, ?, ?, ?)";
            $params = [
                $data['email'],
                $passwordHash,
                $data['full_name'],
                $data['phone'] ?? null,
                $data['role'] ?? 'user'
            ];
            
            executeQuery($sql, $params);
            $userId = lastInsertId();
            
            // Get created user
            $user = fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
            
            // Log audit
            logAudit($userId, 'register');
            
            // Send welcome notification
            sendNotification($userId, 'system', 'Welcome to TerraTrade!', 'Your account has been created successfully.');
            
            return ['success' => true, 'user' => $user];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Registration failed'];
        }
    }
    
    /**
     * Create user session
     */
    private static function createSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_status'] = $user['status'];
        $_SESSION['kyc_status'] = $user['kyc_status'];
        $_SESSION['login_time'] = time();
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Store session in database
        $sessionId = session_id();
        $sql = "INSERT INTO user_sessions (id, user_id, ip_address, user_agent) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                last_activity = CURRENT_TIMESTAMP, 
                ip_address = VALUES(ip_address), 
                user_agent = VALUES(user_agent)";
        
        executeQuery($sql, [
            $sessionId,
            $user['id'],
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        if (self::isLoggedIn()) {
            $userId = $_SESSION['user_id'] ?? null;
            $sessionId = session_id();
            
            // Remove session from database
            if ($sessionId) {
                executeQuery("DELETE FROM user_sessions WHERE id = ?", [$sessionId]);
            }
            
            // Log audit
            if ($userId) {
                logAudit($userId, 'logout');
            }
        }
        
        // Clear session
        session_unset();
        session_destroy();
        
        return ['success' => true];
    }
    
    /**
     * Check if user is logged in
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Get current user
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['user_email'] ?? '',
            'full_name' => $_SESSION['user_name'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'user',
            'status' => $_SESSION['user_status'] ?? 'active',
            'kyc_status' => $_SESSION['kyc_status'] ?? 'pending'
        ];
    }
    
    /**
     * Check user role
     */
    public static function hasRole($role) {
        return self::isLoggedIn() && ($_SESSION['user_role'] ?? 'user') === $role;
    }
    
    /**
     * Check if user has any of the specified roles
     */
    public static function hasAnyRole($roles) {
        if (!self::isLoggedIn()) {
            return false;
        }
        
        return in_array(($_SESSION['user_role'] ?? 'user'), (array)$roles);
    }
    
    /**
     * Require login
     */
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            if (self::isAjaxRequest()) {
                jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
            } else {
                header('Location: /login.php');
                exit;
            }
        }
    }
    
    /**
     * Require role
     */
    public static function requireRole($role) {
        self::requireLogin();
        
        if (!self::hasRole($role)) {
            if (self::isAjaxRequest()) {
                jsonResponse(['success' => false, 'error' => 'Insufficient permissions'], 403);
            } else {
                header('HTTP/1.1 403 Forbidden');
                include '403.php';
                exit;
            }
        }
    }
    
    /**
     * Require KYC verification
     */
    public static function requireKYC() {
        self::requireLogin();
        
        if (($_SESSION['kyc_status'] ?? 'pending') !== 'verified') {
            if (self::isAjaxRequest()) {
                jsonResponse(['success' => false, 'error' => 'KYC verification required'], 403);
            } else {
                header('Location: /kyc.php');
                exit;
            }
        }
    }
    
    /**
     * Check if request is AJAX
     */
    private static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Change password
     */
    public static function changePassword($userId, $currentPassword, $newPassword) {
        try {
            // Get current user
            $user = fetchOne("SELECT password_hash FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                return ['success' => false, 'error' => 'User not found'];
            }
            
            // Verify current password
            if (!self::verifyPassword($currentPassword, $user['password_hash'])) {
                return ['success' => false, 'error' => 'Current password is incorrect'];
            }
            
            // Validate new password
            if (strlen($newPassword) < 8) {
                return ['success' => false, 'error' => 'New password must be at least 8 characters'];
            }
            
            // Update password
            $newHash = self::hashPassword($newPassword);
            executeQuery("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?", [$newHash, $userId]);
            
            // Log audit
            logAudit($userId, 'password_change');
            
            return ['success' => true];
            
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Password change failed'];
        }
    }
    
    /**
     * Reset password request
     */
    public static function requestPasswordReset($email) {
        try {
            $user = fetchOne("SELECT id, full_name FROM users WHERE email = ? AND status = 'active'", [$email]);
            if (!$user) {
                // Don't reveal if email exists
                return ['success' => true, 'message' => 'If the email exists, a reset link has been sent'];
            }
            
            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store reset token (you might want to create a password_resets table)
            // For now, we'll use a simple approach
            
            // Log audit
            logAudit($user['id'], 'password_reset_request');
            
            return ['success' => true, 'message' => 'If the email exists, a reset link has been sent'];
            
        } catch (Exception $e) {
            error_log("Password reset request error: " . $e->getMessage());
            return ['success' => false, 'error' => 'Reset request failed'];
        }
    }
}

// Standalone helper functions for backward compatibility
function isLoggedIn() {
    return Auth::isLoggedIn();
}

function getCurrentUser() {
    return Auth::getCurrentUser();
}

function getCurrentUserId() {
    $user = Auth::getCurrentUser();
    return $user ? $user['id'] : null;
}

function requireLogin() {
    return Auth::requireLogin();
}

function hasRole($role) {
    return Auth::hasRole($role);
}
?>
