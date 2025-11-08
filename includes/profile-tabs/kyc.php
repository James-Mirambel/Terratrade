<!-- KYC Verification Tab -->
<div class="tab-pane" id="kyc">
    <div class="profile-section">
        <h2>KYC Verification</h2>
        
        <div class="kyc-status-card">
            <div class="kyc-status-header">
                <h3>Verification Status</h3>
                <span class="status-badge status-<?php echo $user['kyc_status']; ?>">
                    <?php echo ucfirst($user['kyc_status']); ?>
                </span>
            </div>
            
            <?php if ($user['kyc_status'] === 'verified'): ?>
                <p class="kyc-message success">✓ Your identity has been verified. You can now make offers and participate in transactions.</p>
            <?php elseif ($user['kyc_status'] === 'pending'): ?>
                <p class="kyc-message pending">⏳ Your documents are being reviewed. This typically takes 24-48 hours.</p>
            <?php elseif ($user['kyc_status'] === 'rejected'): ?>
                <p class="kyc-message error">✗ Your verification was rejected. Please review the reasons below and resubmit.</p>
            <?php else: ?>
                <p class="kyc-message warning">⚠ Please submit your KYC documents to unlock full platform features.</p>
            <?php endif; ?>
        </div>

        <?php if ($user['kyc_status'] !== 'verified'): ?>
            <div class="kyc-upload-section">
                <h3>Upload Verification Documents</h3>
                <p>Please upload clear, high-quality images of the following documents:</p>
                
                <form id="kycUploadForm" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    
                    <div class="form-group">
                        <label>Document Type *</label>
                        <select name="document_type" required>
                            <option value="">Select document type</option>
                            <option value="national_id">National ID</option>
                            <option value="drivers_license">Driver's License</option>
                            <option value="passport">Passport</option>
                            <option value="tin_id">TIN ID</option>
                            <option value="business_permit">Business Permit (for brokers)</option>
                            <option value="other">Other Government ID</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Document Number (Optional)</label>
                        <input type="text" name="document_number" placeholder="e.g., ID Number">
                    </div>
                    
                    <div class="form-group">
                        <label>Front Image *</label>
                        <input type="file" name="front_image" accept=".jpg,.jpeg,.png,.pdf" required>
                        <small class="form-hint">Accepted formats: JPG, PNG, PDF (Max 5MB)</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Back Image (Optional)</label>
                        <input type="file" name="back_image" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="form-hint">Upload back side if applicable</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn primary">Upload Document</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>

        <?php if (!empty($kycDocuments)): ?>
            <div class="kyc-documents-list">
                <h3>Submitted Documents</h3>
                <div class="documents-grid">
                    <?php foreach ($kycDocuments as $doc): ?>
                        <div class="document-card">
                            <div class="document-header">
                                <span class="document-type">
                                    <?php echo ucfirst(str_replace('_', ' ', $doc['document_type'])); ?>
                                </span>
                                <span class="status-badge status-<?php echo $doc['status']; ?>">
                                    <?php echo ucfirst($doc['status']); ?>
                                </span>
                            </div>
                            <div class="document-info">
                                <small>Uploaded: <?php echo date('M j, Y', strtotime($doc['created_at'])); ?></small>
                                <?php if ($doc['reviewed_at']): ?>
                                    <small>Reviewed: <?php echo date('M j, Y', strtotime($doc['reviewed_at'])); ?></small>
                                <?php endif; ?>
                            </div>
                            <?php if ($doc['status'] === 'rejected' && $doc['rejection_reason']): ?>
                                <div class="rejection-reason">
                                    <strong>Reason:</strong> <?php echo htmlspecialchars($doc['rejection_reason']); ?>
                                </div>
                            <?php endif; ?>
                            <div class="document-actions">
                                <a href="uploads/kyc/<?php echo htmlspecialchars($doc['front_image']); ?>" 
                                   target="_blank" class="btn-link">View Front</a>
                                <?php if (!empty($doc['back_image'])): ?>
                                    <a href="uploads/kyc/<?php echo htmlspecialchars($doc['back_image']); ?>" 
                                       target="_blank" class="btn-link">View Back</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
