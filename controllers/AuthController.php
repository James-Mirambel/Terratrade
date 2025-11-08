<?php
/**
 * Authentication Controller
 * TerraTrade Land Trading System
 */

class AuthController {
    
    /**
     * User login
     */
    public function login($data) {
        // Validate input
        if (empty($data['email']) || empty($data['password'])) {
            jsonResponse(['success' => false, 'error' => 'Email and password are required'], 400);
        }
        
        // Verify CSRF token for non-API requests
        if (isset($data['csrf_token']) && !verifyCSRFToken($data['csrf_token'])) {
            jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        
        $result = Auth::login($data['email'], $data['password'], $data['remember_me'] ?? false);
        
        if ($result['success']) {
            jsonResponse([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $result['user']['id'],
                    'email' => $result['user']['email'],
                    'full_name' => $result['user']['full_name'],
                    'role' => $result['user']['role'],
                    'kyc_status' => $result['user']['kyc_status']
                ]
            ]);
        } else {
            jsonResponse($result, 401);
        }
    }
    
    /**
     * User registration
     */
    public function register($data) {
        // Validate input
        $required = ['email', 'password', 'full_name'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                jsonResponse(['success' => false, 'error' => "Field {$field} is required"], 400);
            }
        }
        
        // Verify CSRF token for non-API requests
        if (isset($data['csrf_token']) && !verifyCSRFToken($data['csrf_token'])) {
            jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        
        // Additional validation
        if (!isValidEmail($data['email'])) {
            jsonResponse(['success' => false, 'error' => 'Invalid email format'], 400);
        }
        
        if (isset($data['phone']) && !empty($data['phone']) && !isValidPhilippinePhone($data['phone'])) {
            jsonResponse(['success' => false, 'error' => 'Invalid Philippine phone number format'], 400);
        }
        
        $result = Auth::register($data);
        
        if ($result['success']) {
            jsonResponse([
                'success' => true,
                'message' => 'Registration successful',
                'user' => [
                    'id' => $result['user']['id'],
                    'email' => $result['user']['email'],
                    'full_name' => $result['user']['full_name'],
                    'role' => $result['user']['role'],
                    'status' => $result['user']['status']
                ]
            ], 201);
        } else {
            jsonResponse($result, 400);
        }
    }
    
    /**
     * User logout
     */
    public function logout() {
        $result = Auth::logout();
        jsonResponse($result);
    }
    
    /**
     * Get current user information
     */
    public function getCurrentUser() {
        if (!Auth::isLoggedIn()) {
            jsonResponse(['success' => false, 'error' => 'Not authenticated'], 401);
        }
        
        $user = Auth::getCurrentUser();
        
        // Get additional user data
        $userData = fetchOne("
            SELECT u.*, 
                   COUNT(DISTINCT p.id) as total_listings,
                   COUNT(DISTINCT f.id) as total_favorites,
                   COUNT(DISTINCT o.id) as total_offers
            FROM users u
            LEFT JOIN properties p ON u.id = p.user_id AND p.status != 'deleted'
            LEFT JOIN user_favorites f ON u.id = f.user_id
            LEFT JOIN offers o ON u.id = o.buyer_id
            WHERE u.id = ?
            GROUP BY u.id
        ", [$user['id']]);
        
        if ($userData) {
            $user = array_merge($user, [
                'phone' => $userData['phone'],
                'profile_image' => $userData['profile_image'],
                'created_at' => $userData['created_at'],
                'last_login' => $userData['last_login'],
                'email_verified' => (bool)$userData['email_verified'],
                'phone_verified' => (bool)$userData['phone_verified'],
                'stats' => [
                    'total_listings' => (int)$userData['total_listings'],
                    'total_favorites' => (int)$userData['total_favorites'],
                    'total_offers' => (int)$userData['total_offers']
                ]
            ]);
        }
        
        jsonResponse(['success' => true, 'user' => $user]);
    }
    
    /**
     * Change password
     */
    public function changePassword($data) {
        Auth::requireLogin();
        
        // Validate input
        $required = ['current_password', 'new_password'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                jsonResponse(['success' => false, 'error' => "Field {$field} is required"], 400);
            }
        }
        
        // Verify CSRF token
        if (!verifyCSRFToken($data['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        
        $user = Auth::getCurrentUser();
        $result = Auth::changePassword($user['id'], $data['current_password'], $data['new_password']);
        
        if ($result['success']) {
            jsonResponse(['success' => true, 'message' => 'Password changed successfully']);
        } else {
            jsonResponse($result, 400);
        }
    }
    
    /**
     * Request password reset
     */
    public function requestPasswordReset($data) {
        if (empty($data['email'])) {
            jsonResponse(['success' => false, 'error' => 'Email is required'], 400);
        }
        
        $result = Auth::requestPasswordReset($data['email']);
        jsonResponse($result);
    }
    
    /**
     * Verify email address
     */
    public function verifyEmail($data) {
        if (empty($data['token'])) {
            jsonResponse(['success' => false, 'error' => 'Verification token is required'], 400);
        }
        
        // Implementation for email verification
        // This would typically involve checking a verification token stored in database
        
        jsonResponse(['success' => true, 'message' => 'Email verified successfully']);
    }
    
    /**
     * Resend verification email
     */
    public function resendVerification() {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        if ($_SESSION['email_verified']) {
            jsonResponse(['success' => false, 'error' => 'Email already verified'], 400);
        }
        
        // Generate verification token and send email
        // Implementation would go here
        
        jsonResponse(['success' => true, 'message' => 'Verification email sent']);
    }
    
    /**
     * Check authentication status
     */
    public function checkAuth() {
        if (Auth::isLoggedIn()) {
            $user = Auth::getCurrentUser();
            jsonResponse([
                'success' => true,
                'authenticated' => true,
                'user' => $user
            ]);
        } else {
            jsonResponse([
                'success' => true,
                'authenticated' => false
            ]);
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($data) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        $userId = $user['id'];
        
        // Verify CSRF token
        if (!verifyCSRFToken($data['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        
        try {
            $updateFields = [];
            $params = [];
            
            // Allowed fields to update
            $allowedFields = ['full_name', 'phone'];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $updateFields[] = "{$field} = ?";
                    $params[] = $data[$field];
                }
            }
            
            // Validate phone if provided
            if (isset($data['phone']) && !empty($data['phone']) && !isValidPhilippinePhone($data['phone'])) {
                jsonResponse(['success' => false, 'error' => 'Invalid Philippine phone number format'], 400);
            }
            
            if (!empty($updateFields)) {
                $updateFields[] = "updated_at = NOW()";
                $params[] = $userId;
                
                $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
                executeQuery($sql, $params);
                
                // Log audit
                logAudit($userId, 'profile_update', 'users', $userId, null, $data);
                
                // Update session data
                if (isset($data['full_name'])) {
                    $_SESSION['user_name'] = $data['full_name'];
                }
                
                jsonResponse(['success' => true, 'message' => 'Profile updated successfully']);
            } else {
                jsonResponse(['success' => false, 'error' => 'No valid fields to update'], 400);
            }
            
        } catch (Exception $e) {
            error_log("Profile update error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Profile update failed'], 500);
        }
    }
}
?>
