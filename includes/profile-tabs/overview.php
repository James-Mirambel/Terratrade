<!-- Overview Tab -->
<div class="tab-pane active" id="overview">
    <div class="profile-section">
        <h2>Account Overview</h2>
        
        <div class="info-grid">
            <div class="info-item">
                <label>Email Address</label>
                <div class="info-value">
                    <?php echo htmlspecialchars($user['email']); ?>
                    <?php if ($user['email_verified']): ?>
                        <span class="verified-badge">✓ Verified</span>
                    <?php else: ?>
                        <span class="unverified-badge">Not Verified</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="info-item">
                <label>Phone Number</label>
                <div class="info-value">
                    <?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Not provided'; ?>
                    <?php if ($user['phone'] && $user['phone_verified']): ?>
                        <span class="verified-badge">✓ Verified</span>
                    <?php elseif ($user['phone']): ?>
                        <span class="unverified-badge">Not Verified</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="info-item">
                <label>Account Status</label>
                <div class="info-value">
                    <span class="status-badge status-<?php echo $user['status']; ?>">
                        <?php echo ucfirst($user['status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="info-item">
                <label>KYC Status</label>
                <div class="info-value">
                    <span class="status-badge status-<?php echo $user['kyc_status']; ?>">
                        <?php echo ucfirst($user['kyc_status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if ($user['kyc_status'] !== 'verified'): ?>
            <div class="alert alert-warning" style="margin-top: 20px;">
                <strong>⚠ KYC Verification Required</strong>
                <p>To make offers and participate in transactions, please complete your KYC verification.</p>
                <button class="btn primary" onclick="switchTab('kyc')">Complete KYC Now</button>
            </div>
        <?php endif; ?>
    </div>
</div>
