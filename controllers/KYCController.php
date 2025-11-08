<?php
/**
 * KYC Controller
 * TerraTrade Land Trading System
 */

class KYCController {
    
    /**
     * Submit KYC documents
     */
    public function submitKYC($data, $files) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        // Check if user already has verified KYC
        if ($user['kyc_status'] === 'verified') {
            jsonResponse(['success' => false, 'error' => 'KYC already verified'], 400);
        }
        
        // Validate required fields
        $required = ['document_type', 'document_number'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                jsonResponse(['success' => false, 'error' => "Field {$field} is required"], 400);
            }
        }
        
        // Validate required files
        if (empty($files['front_image']) || empty($files['back_image'])) {
            jsonResponse(['success' => false, 'error' => 'Both front and back images are required'], 400);
        }
        
        // Verify CSRF token
        if (!verifyCSRFToken($data['csrf_token'] ?? '')) {
            jsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], 403);
        }
        
        try {
            // Upload front image
            $frontUpload = uploadFile(
                $files['front_image'],
                UPLOAD_PATH . 'kyc/',
                ALLOWED_IMAGE_TYPES
            );
            
            if (!$frontUpload['success']) {
                jsonResponse(['success' => false, 'error' => 'Failed to upload front image: ' . $frontUpload['error']], 400);
            }
            
            // Upload back image
            $backUpload = uploadFile(
                $files['back_image'],
                UPLOAD_PATH . 'kyc/',
                ALLOWED_IMAGE_TYPES
            );
            
            if (!$backUpload['success']) {
                // Clean up front image if back upload fails
                unlink($frontUpload['filepath']);
                jsonResponse(['success' => false, 'error' => 'Failed to upload back image: ' . $backUpload['error']], 400);
            }
            
            // Check for existing pending submission
            $existingSubmission = fetchOne("
                SELECT id FROM kyc_documents 
                WHERE user_id = ? AND status = 'pending'
            ", [$user['id']]);
            
            if ($existingSubmission) {
                // Update existing submission
                $sql = "
                    UPDATE kyc_documents 
                    SET document_type = ?, document_number = ?, front_image = ?, back_image = ?, created_at = NOW()
                    WHERE id = ?
                ";
                $params = [
                    $data['document_type'],
                    sanitize($data['document_number']),
                    'kyc/' . $frontUpload['filename'],
                    'kyc/' . $backUpload['filename'],
                    $existingSubmission['id']
                ];
                executeQuery($sql, $params);
                $submissionId = $existingSubmission['id'];
            } else {
                // Create new submission
                $sql = "
                    INSERT INTO kyc_documents (user_id, document_type, document_number, front_image, back_image)
                    VALUES (?, ?, ?, ?, ?)
                ";
                $params = [
                    $user['id'],
                    $data['document_type'],
                    sanitize($data['document_number']),
                    'kyc/' . $frontUpload['filename'],
                    'kyc/' . $backUpload['filename']
                ];
                executeQuery($sql, $params);
                $submissionId = lastInsertId();
            }
            
            // Update user KYC status to pending
            executeQuery("UPDATE users SET kyc_status = 'pending' WHERE id = ?", [$user['id']]);
            $_SESSION['kyc_status'] = 'pending';
            
            // Log audit
            logAudit($user['id'], 'kyc_submit', 'kyc_documents', $submissionId, null, $data);
            
            // Send notification to admins
            $admins = fetchAll("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
            foreach ($admins as $admin) {
                sendNotification(
                    $admin['id'],
                    'system',
                    'New KYC Submission',
                    "A new KYC submission from {$user['full_name']} requires review.",
                    ['kyc_submission_id' => $submissionId, 'user_id' => $user['id']]
                );
            }
            
            jsonResponse([
                'success' => true,
                'message' => 'KYC documents submitted successfully and are under review',
                'submission_id' => $submissionId
            ], 201);
            
        } catch (Exception $e) {
            error_log("Submit KYC error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to submit KYC documents'], 500);
        }
    }
    
    /**
     * Get KYC status for current user
     */
    public function getKYCStatus() {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        try {
            // Get KYC submission details
            $submission = fetchOne("
                SELECT kd.*, u.full_name as reviewed_by_name
                FROM kyc_documents kd
                LEFT JOIN users u ON kd.reviewed_by = u.id
                WHERE kd.user_id = ?
                ORDER BY kd.created_at DESC
                LIMIT 1
            ", [$user['id']]);
            
            $response = [
                'success' => true,
                'kyc_status' => $user['kyc_status'],
                'submission' => null
            ];
            
            if ($submission) {
                $response['submission'] = [
                    'id' => $submission['id'],
                    'document_type' => $submission['document_type'],
                    'status' => $submission['status'],
                    'submitted_at' => $submission['created_at'],
                    'submitted_ago' => timeAgo($submission['created_at']),
                    'reviewed_at' => $submission['reviewed_at'],
                    'reviewed_ago' => $submission['reviewed_at'] ? timeAgo($submission['reviewed_at']) : null,
                    'reviewed_by_name' => $submission['reviewed_by_name'],
                    'rejection_reason' => $submission['rejection_reason']
                ];
            }
            
            jsonResponse($response);
            
        } catch (Exception $e) {
            error_log("Get KYC status error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch KYC status'], 500);
        }
    }
    
    /**
     * Get KYC requirements and guidelines
     */
    public function getKYCRequirements() {
        $requirements = [
            'accepted_documents' => [
                'philippine_id' => 'Philippine National ID',
                'drivers_license' => "Driver's License",
                'passport' => 'Passport',
                'sss' => 'SSS ID',
                'tin' => 'TIN ID',
                'voters' => "Voter's ID",
                'postal' => 'Postal ID'
            ],
            'guidelines' => [
                'Ensure all text on the document is clearly visible and readable',
                'Document should not be expired',
                'Photos should be well-lit and in focus',
                'All four corners of the document should be visible',
                'File size should not exceed 5MB',
                'Accepted formats: JPG, PNG, PDF'
            ],
            'processing_time' => '1-3 business days',
            'required_for' => [
                'Making offers on properties',
                'Participating in auctions',
                'Creating high-value listings',
                'Accessing escrow services'
            ]
        ];
        
        jsonResponse([
            'success' => true,
            'requirements' => $requirements
        ]);
    }
    
    /**
     * Resubmit KYC after rejection
     */
    public function resubmitKYC($data, $files) {
        Auth::requireLogin();
        
        $user = Auth::getCurrentUser();
        
        // Check if user's KYC was rejected
        if ($user['kyc_status'] !== 'rejected') {
            jsonResponse(['success' => false, 'error' => 'KYC resubmission not allowed'], 400);
        }
        
        // Use the same logic as submitKYC
        $this->submitKYC($data, $files);
    }
    
    /**
     * Get KYC document (for admin review)
     */
    public function getKYCDocument($submissionId) {
        Auth::requireRole('admin');
        
        try {
            $submission = fetchOne("
                SELECT kd.*, u.full_name, u.email, u.phone, u.role
                FROM kyc_documents kd
                JOIN users u ON kd.user_id = u.id
                WHERE kd.id = ?
            ", [$submissionId]);
            
            if (!$submission) {
                jsonResponse(['success' => false, 'error' => 'KYC submission not found'], 404);
            }
            
            // Format submission data
            $submission['submitted_ago'] = timeAgo($submission['created_at']);
            $submission['reviewed_ago'] = $submission['reviewed_at'] ? timeAgo($submission['reviewed_at']) : null;
            
            jsonResponse([
                'success' => true,
                'submission' => $submission
            ]);
            
        } catch (Exception $e) {
            error_log("Get KYC document error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch KYC document'], 500);
        }
    }
    
    /**
     * Bulk approve/reject KYC submissions (admin only)
     */
    public function bulkReviewKYC($data) {
        Auth::requireRole('admin');
        
        if (empty($data['submission_ids']) || empty($data['action'])) {
            jsonResponse(['success' => false, 'error' => 'Submission IDs and action are required'], 400);
        }
        
        $action = $data['action']; // 'approve' or 'reject'
        $submissionIds = $data['submission_ids'];
        $reason = $action === 'reject' ? sanitize($data['reason'] ?? '') : null;
        
        if (!in_array($action, ['approve', 'reject'])) {
            jsonResponse(['success' => false, 'error' => 'Invalid action'], 400);
        }
        
        try {
            $currentUser = Auth::getCurrentUser();
            $newStatus = $action === 'approve' ? 'approved' : 'rejected';
            $userKYCStatus = $action === 'approve' ? 'verified' : 'rejected';
            
            $successCount = 0;
            $errors = [];
            
            foreach ($submissionIds as $submissionId) {
                try {
                    // Get submission
                    $submission = fetchOne("SELECT * FROM kyc_documents WHERE id = ? AND status = 'pending'", [$submissionId]);
                    
                    if (!$submission) {
                        $errors[] = "Submission {$submissionId} not found or not pending";
                        continue;
                    }
                    
                    // Update KYC document status
                    executeQuery("
                        UPDATE kyc_documents 
                        SET status = ?, reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ?
                        WHERE id = ?
                    ", [$newStatus, $currentUser['id'], $reason, $submissionId]);
                    
                    // Update user KYC status
                    executeQuery("UPDATE users SET kyc_status = ? WHERE id = ?", [$userKYCStatus, $submission['user_id']]);
                    
                    // Log audit
                    logAudit($currentUser['id'], 'kyc_bulk_review', 'kyc_documents', $submissionId, $submission, $data);
                    
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
                    
                    $successCount++;
                    
                } catch (Exception $e) {
                    $errors[] = "Failed to process submission {$submissionId}: " . $e->getMessage();
                }
            }
            
            jsonResponse([
                'success' => true,
                'message' => "Successfully processed {$successCount} submissions",
                'processed_count' => $successCount,
                'errors' => $errors
            ]);
            
        } catch (Exception $e) {
            error_log("Bulk review KYC error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to process bulk review'], 500);
        }
    }
    
    /**
     * Get KYC statistics (admin only)
     */
    public function getKYCStatistics() {
        Auth::requireRole('admin');
        
        try {
            $stats = fetchOne("
                SELECT 
                    COUNT(*) as total_submissions,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as this_week,
                    SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as this_month
                FROM kyc_documents
            ");
            
            $userStats = fetchOne("
                SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN kyc_status = 'verified' THEN 1 ELSE 0 END) as verified_users,
                    SUM(CASE WHEN kyc_status = 'pending' THEN 1 ELSE 0 END) as pending_users,
                    SUM(CASE WHEN kyc_status = 'rejected' THEN 1 ELSE 0 END) as rejected_users,
                    SUM(CASE WHEN kyc_status = 'none' THEN 1 ELSE 0 END) as unverified_users
                FROM users
            ");
            
            $documentTypeStats = fetchAll("
                SELECT document_type, COUNT(*) as count
                FROM kyc_documents
                GROUP BY document_type
                ORDER BY count DESC
            ");
            
            jsonResponse([
                'success' => true,
                'submission_stats' => $stats,
                'user_stats' => $userStats,
                'document_type_stats' => $documentTypeStats
            ]);
            
        } catch (Exception $e) {
            error_log("Get KYC statistics error: " . $e->getMessage());
            jsonResponse(['success' => false, 'error' => 'Failed to fetch KYC statistics'], 500);
        }
    }
}
?>
