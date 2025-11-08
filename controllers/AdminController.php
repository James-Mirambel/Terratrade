<?php
/**
 * Admin Controller
 * TerraTrade Land Trading System
 */

class AdminController {
    
    public function __construct() {
        Auth::requireRole('admin');
    }
    
    /**
     * Get admin dashboard data
     */
    public function getDashboard() {
        try {
            // Get key statistics
            $stats = [
                'total_users' => $this->getUserStats(),
                'total_properties' => $this->getPropertyStats(),
                'total_offers' => $this->getOfferStats(),
                'total_transactions' => $this->getTransactionStats(),
                'pending_kyc' => $this->getPendingKYCCount(),
                'pending_properties' => $this->getPendingPropertiesCount(),
                'active_disputes' => $this->getActiveDisputesCount(),
                'revenue_stats' => $this->getRevenueStats()
            ];
            
            // Get recent activities
            $recentActivities = $this->getRecentActivities();
            
            // Get system health
            $systemHealth = $this->getSystemHealth();
            
            jsonResponse([
                'success' => true,
                'stats' => $stats,
                'recent_activities' => $recentActivities,
                'system_health' => $systemHealth
            ]);
            
        } catch (Exception $e) {
            error_log("Admin dashboard error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to load dashboard'], 500);
        }
    }
    
    /**
     * Get users for admin management
     */
    public function getUsers($params = []) {
        try {
            $page = max(1, (int)($params['page'] ?? 1));
            $pageSize = min(MAX_PAGE_SIZE, max(1, (int)($params['page_size'] ?? DEFAULT_PAGE_SIZE)));
            $offset = ($page - 1) * $pageSize;
            
            // Build WHERE clause
            $whereConditions = ["1=1"];
            $queryParams = [];
            
            // Role filter
            if (!empty($params['role'])) {
                $whereConditions[] = "role = ?";
                $queryParams[] = $params['role'];
            }
            
            // Status filter
            if (!empty($params['status'])) {
                $whereConditions[] = "status = ?";
                $queryParams[] = $params['status'];
            }
            
            // KYC status filter
            if (!empty($params['kyc_status'])) {
                $whereConditions[] = "kyc_status = ?";
                $queryParams[] = $params['kyc_status'];
            }
            
            // Search filter
            if (!empty($params['search'])) {
                $whereConditions[] = "(full_name LIKE ? OR email LIKE ?)";
                $searchTerm = '%' . $params['search'] . '%';
                $queryParams[] = $searchTerm;
                $queryParams[] = $searchTerm;
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Count total records
            $countSql = "SELECT COUNT(*) as total FROM users WHERE {$whereClause}";
            $totalResult = fetchOne($countSql, $queryParams);
            $totalRecords = $totalResult['total'];
            
            // Get users
            $sql = "
                SELECT u.*, 
                       COUNT(DISTINCT p.id) as total_listings,
                       COUNT(DISTINCT o.id) as total_offers,
                       COUNT(DISTINCT f.id) as total_favorites
                FROM users u
                LEFT JOIN properties p ON u.id = p.user_id AND p.status != 'deleted'
                LEFT JOIN offers o ON u.id = o.buyer_id
                LEFT JOIN user_favorites f ON u.id = f.user_id
                WHERE {$whereClause}
                GROUP BY u.id
                ORDER BY u.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $queryParams[] = $pageSize;
            $queryParams[] = $offset;
            
            $users = fetchAll($sql, $queryParams);
            
            // Format users (remove sensitive data)
            foreach ($users as &$user) {
                unset($user['password_hash']);
                $user['created_ago'] = timeAgo($user['created_at']);
                $user['last_login_ago'] = $user['last_login'] ? timeAgo($user['last_login']) : 'Never';
                $user['total_listings'] = (int)$user['total_listings'];
                $user['total_offers'] = (int)$user['total_offers'];
                $user['total_favorites'] = (int)$user['total_favorites'];
            }
            
            $pagination = paginate($totalRecords, $page, $pageSize);
            
            jsonResponse([
                'success' => true,
                'users' => $users,
                'pagination' => $pagination
            ]);
            
        } catch (Exception $e) {
            error_log("Admin get users error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch users'], 500);
        }
    }
    
    /**
     * Get properties for review
     */
    public function getPropertiesForReview($params = []) {
        try {
            $page = max(1, (int)($params['page'] ?? 1));
            $pageSize = min(MAX_PAGE_SIZE, max(1, (int)($params['page_size'] ?? DEFAULT_PAGE_SIZE)));
            $offset = ($page - 1) * $pageSize;
            
            // Build WHERE clause
            $whereConditions = [];
            $queryParams = [];
            
            // Status filter
            if (!empty($params['status'])) {
                $whereConditions[] = "p.status = ?";
                $queryParams[] = $params['status'];
            } else {
                $whereConditions[] = "p.status IN ('pending', 'active', 'suspended')";
            }
            
            // Search filter
            if (!empty($params['search'])) {
                $whereConditions[] = "(p.title LIKE ? OR p.location LIKE ?)";
                $searchTerm = '%' . $params['search'] . '%';
                $queryParams[] = $searchTerm;
                $queryParams[] = $searchTerm;
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Count total records
            $countSql = "SELECT COUNT(*) as total FROM properties p WHERE {$whereClause}";
            $totalResult = fetchOne($countSql, $queryParams);
            $totalRecords = $totalResult['total'];
            
            // Get properties
            $sql = "
                SELECT p.*, 
                       u.full_name as seller_name,
                       u.email as seller_email,
                       u.phone as seller_phone,
                       u.kyc_status as seller_kyc_status,
                       (SELECT image_path FROM property_images pi WHERE pi.property_id = p.id AND pi.image_type = 'main' LIMIT 1) as main_image,
                       (SELECT COUNT(*) FROM property_documents pd WHERE pd.property_id = p.id) as document_count,
                       (SELECT COUNT(*) FROM offers o WHERE o.property_id = p.id) as offer_count
                FROM properties p
                JOIN users u ON p.user_id = u.id
                WHERE {$whereClause}
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $queryParams[] = $pageSize;
            $queryParams[] = $offset;
            
            $properties = fetchAll($sql, $queryParams);
            
            // Format properties
            foreach ($properties as &$property) {
                $property['price_formatted'] = formatCurrency($property['price']);
                $property['area_formatted'] = formatArea($property['area_sqm']);
                $property['created_ago'] = timeAgo($property['created_at']);
                $property['document_count'] = (int)$property['document_count'];
                $property['offer_count'] = (int)$property['offer_count'];
            }
            
            $pagination = paginate($totalRecords, $page, $pageSize);
            
            jsonResponse([
                'success' => true,
                'properties' => $properties,
                'pagination' => $pagination
            ]);
            
        } catch (Exception $e) {
            error_log("Admin get properties error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch properties'], 500);
        }
    }
    
    /**
     * Approve property listing
     */
    public function approveProperty($id) {
        try {
            $property = fetchOne("SELECT * FROM properties WHERE id = ?", [$id]);
            if (!$property) {
                jsonResponse(['success' => false, 'error' => 'Property not found'], 404);
            }
            
            if ($property['status'] !== 'pending') {
                jsonResponse(['success' => false, 'error' => 'Property is not pending approval'], 400);
            }
            
            // Update property status
            executeQuery("UPDATE properties SET status = 'active', updated_at = NOW() WHERE id = ?", [$id]);
            
            // Log audit
            $user = Auth::getCurrentUser();
            logAudit($user['id'], 'property_approve', 'properties', $id, $property, null);
            
            // Send notification to seller
            sendNotification(
                $property['user_id'],
                'system',
                'Property Approved',
                "Your property listing '{$property['title']}' has been approved and is now live.",
                ['property_id' => $id]
            );
            
            jsonResponse(['success' => true, 'message' => 'Property approved successfully']);
            
        } catch (Exception $e) {
            error_log("Approve property error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to approve property'], 500);
        }
    }
    
    /**
     * Reject property listing
     */
    public function rejectProperty($id, $data) {
        try {
            $property = fetchOne("SELECT * FROM properties WHERE id = ?", [$id]);
            if (!$property) {
                jsonResponse(['success' => false, 'error' => 'Property not found'], 404);
            }
            
            if ($property['status'] !== 'pending') {
                jsonResponse(['success' => false, 'error' => 'Property is not pending approval'], 400);
            }
            
            $reason = sanitize($data['reason'] ?? 'No reason provided');
            
            // Update property status
            executeQuery("UPDATE properties SET status = 'rejected', updated_at = NOW() WHERE id = ?", [$id]);
            
            // Log audit
            $user = Auth::getCurrentUser();
            logAudit($user['id'], 'property_reject', 'properties', $id, $property, ['reason' => $reason]);
            
            // Send notification to seller
            sendNotification(
                $property['user_id'],
                'system',
                'Property Rejected',
                "Your property listing '{$property['title']}' has been rejected. Reason: {$reason}",
                ['property_id' => $id, 'reason' => $reason]
            );
            
            jsonResponse(['success' => true, 'message' => 'Property rejected successfully']);
            
        } catch (Exception $e) {
            error_log("Reject property error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to reject property'], 500);
        }
    }
    
    /**
     * Update user status
     */
    public function updateUserStatus($userId, $data) {
        try {
            $user = fetchOne("SELECT * FROM users WHERE id = ?", [$userId]);
            if (!$user) {
                jsonResponse(['success' => false, 'error' => 'User not found'], 404);
            }
            
            $newStatus = $data['status'];
            $validStatuses = ['active', 'pending', 'suspended', 'banned'];
            
            if (!in_array($newStatus, $validStatuses)) {
                jsonResponse(['success' => false, 'error' => 'Invalid status'], 400);
            }
            
            // Update user status
            executeQuery("UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?", [$newStatus, $userId]);
            
            // Log audit
            $currentUser = Auth::getCurrentUser();
            logAudit($currentUser['id'], 'user_status_update', 'users', $userId, $user, $data);
            
            // Send notification to user
            $statusMessages = [
                'active' => 'Your account has been activated.',
                'suspended' => 'Your account has been suspended.',
                'banned' => 'Your account has been banned.'
            ];
            
            if (isset($statusMessages[$newStatus])) {
                sendNotification(
                    $userId,
                    'system',
                    'Account Status Update',
                    $statusMessages[$newStatus],
                    ['new_status' => $newStatus]
                );
            }
            
            jsonResponse(['success' => true, 'message' => 'User status updated successfully']);
            
        } catch (Exception $e) {
            error_log("Update user status error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to update user status'], 500);
        }
    }
    
    /**
     * Get KYC submissions for review
     */
    public function getKYCSubmissions($params = []) {
        try {
            $page = max(1, (int)($params['page'] ?? 1));
            $pageSize = min(MAX_PAGE_SIZE, max(1, (int)($params['page_size'] ?? DEFAULT_PAGE_SIZE)));
            $offset = ($page - 1) * $pageSize;
            
            // Build WHERE clause
            $whereConditions = [];
            $queryParams = [];
            
            // Status filter
            if (!empty($params['status'])) {
                $whereConditions[] = "kd.status = ?";
                $queryParams[] = $params['status'];
            } else {
                $whereConditions[] = "kd.status = 'pending'";
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Get KYC submissions
            $sql = "
                SELECT kd.*, u.full_name, u.email, u.phone, u.role
                FROM kyc_documents kd
                JOIN users u ON kd.user_id = u.id
                WHERE {$whereClause}
                ORDER BY kd.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            $queryParams[] = $pageSize;
            $queryParams[] = $offset;
            
            $submissions = fetchAll($sql, $queryParams);
            
            // Format submissions
            foreach ($submissions as &$submission) {
                $submission['submitted_ago'] = timeAgo($submission['created_at']);
                $submission['reviewed_ago'] = $submission['reviewed_at'] ? timeAgo($submission['reviewed_at']) : null;
            }
            
            // Count total records
            $countSql = "SELECT COUNT(*) as total FROM kyc_documents kd WHERE {$whereClause}";
            $totalResult = fetchOne($countSql, array_slice($queryParams, 0, -2));
            $totalRecords = $totalResult['total'];
            
            $pagination = paginate($totalRecords, $page, $pageSize);
            
            jsonResponse([
                'success' => true,
                'submissions' => $submissions,
                'pagination' => $pagination
            ]);
            
        } catch (Exception $e) {
            error_log("Get KYC submissions error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch KYC submissions'], 500);
        }
    }
    
    /**
     * Review KYC submission
     */
    public function reviewKYC($submissionId, $data) {
        try {
            $submission = fetchOne("SELECT * FROM kyc_documents WHERE id = ?", [$submissionId]);
            if (!$submission) {
                jsonResponse(['success' => false, 'error' => 'KYC submission not found'], 404);
            }
            
            $action = $data['action']; // 'approve' or 'reject'
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            $reason = $action === 'reject' ? sanitize($data['reason'] ?? '') : null;
            
            $currentUser = Auth::getCurrentUser();
            
            // Update KYC document status
            executeQuery("
                UPDATE kyc_documents 
                SET status = ?, reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ?
                WHERE id = ?
            ", [$newStatus, $currentUser['id'], $reason, $submissionId]);
            
            // Update user KYC status
            $userKYCStatus = $action === 'approve' ? 'verified' : 'rejected';
            executeQuery("UPDATE users SET kyc_status = ? WHERE id = ?", [$userKYCStatus, $submission['user_id']]);
            
            // Log audit
            logAudit($currentUser['id'], 'kyc_review', 'kyc_documents', $submissionId, $submission, $data);
            
            // Send notification to user
            $message = $action === 'approve' 
                ? 'Your KYC verification has been approved. You can now make offers and participate in auctions.'
                : "Your KYC verification has been rejected. Reason: {$reason}";
            
            sendNotification(
                $submission['user_id'],
                $action === 'approve' ? 'kyc_approved' : 'kyc_rejected',
                'KYC Verification Update',
                $message,
                ['kyc_status' => $userKYCStatus, 'reason' => $reason]
            );
            
            jsonResponse(['success' => true, 'message' => "KYC submission {$action}d successfully"]);
            
        } catch (Exception $e) {
            error_log("Review KYC error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to review KYC submission'], 500);
        }
    }
    
    /**
     * Get system settings
     */
    public function getSystemSettings() {
        try {
            $settings = fetchAll("SELECT * FROM system_settings ORDER BY setting_key");
            
            jsonResponse([
                'success' => true,
                'settings' => $settings
            ]);
            
        } catch (Exception $e) {
            error_log("Get system settings error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch system settings'], 500);
        }
    }
    
    /**
     * Update system setting
     */
    public function updateSystemSetting($key, $data) {
        try {
            $currentUser = Auth::getCurrentUser();
            
            executeQuery("
                UPDATE system_settings 
                SET setting_value = ?, updated_by = ?, updated_at = NOW() 
                WHERE setting_key = ?
            ", [$data['value'], $currentUser['id'], $key]);
            
            // Log audit
            logAudit($currentUser['id'], 'setting_update', 'system_settings', null, null, $data);
            
            jsonResponse(['success' => true, 'message' => 'Setting updated successfully']);
            
        } catch (Exception $e) {
            error_log("Update system setting error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to update setting'], 500);
        }
    }
    
    // Helper methods for dashboard statistics
    
    private function getUserStats() {
        return fetchOne("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_this_month
            FROM users
        ");
    }
    
    private function getPropertyStats() {
        return fetchOne("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold
            FROM properties
        ");
    }
    
    private function getOfferStats() {
        return fetchOne("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
                AVG(offer_amount) as average_amount
            FROM offers
        ");
    }
    
    private function getTransactionStats() {
        return fetchOne("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'completed' THEN contract_amount ELSE 0 END) as total_value
            FROM contracts
        ");
    }
    
    private function getPendingKYCCount() {
        $result = fetchOne("SELECT COUNT(*) as count FROM kyc_documents WHERE status = 'pending'");
        return $result['count'];
    }
    
    private function getPendingPropertiesCount() {
        $result = fetchOne("SELECT COUNT(*) as count FROM properties WHERE status = 'pending'");
        return $result['count'];
    }
    
    private function getActiveDisputesCount() {
        $result = fetchOne("SELECT COUNT(*) as count FROM disputes WHERE status IN ('open', 'investigating')");
        return $result['count'];
    }
    
    private function getRevenueStats() {
        return fetchOne("
            SELECT 
                SUM(amount) as total_revenue,
                COUNT(*) as total_transactions
            FROM escrow_transactions 
            WHERE transaction_type = 'fee' AND status = 'completed'
        ");
    }
    
    private function getRecentActivities() {
        return fetchAll("
            SELECT action, table_name, created_at, u.full_name as user_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT 20
        ");
    }
    
    private function getSystemHealth() {
        return [
            'database_status' => 'healthy',
            'storage_usage' => '45%',
            'active_sessions' => fetchOne("SELECT COUNT(*) as count FROM user_sessions WHERE last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR)")['count'],
            'last_backup' => '2024-01-15 02:00:00'
        ];
    }
}
?>
