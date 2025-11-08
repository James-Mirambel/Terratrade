<!-- Profile Header -->
<div class="profile-header">
    <div class="profile-avatar-section">
        <div class="profile-avatar-wrapper">
            <?php if (!empty($user['profile_image'])): ?>
                <img src="uploads/avatars/<?php echo htmlspecialchars($user['profile_image']); ?>" alt="Profile" class="profile-avatar-large" id="currentAvatar">
            <?php else: ?>
                <div class="profile-avatar-placeholder-large" id="currentAvatar">
                    <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                </div>
            <?php endif; ?>
            <button class="avatar-upload-btn" onclick="document.getElementById('avatarUpload').click()">
                📷 Change Photo
            </button>
            <input type="file" id="avatarUpload" accept="image/*" style="display: none;">
        </div>
    </div>
    
    <div class="profile-header-info">
        <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
        <div class="profile-badges">
            <span class="badge badge-<?php echo $user['role']; ?>">
                <?php echo ucfirst($user['role']); ?>
            </span>
            <span class="badge badge-<?php echo $user['status']; ?>">
                <?php echo ucfirst($user['status']); ?>
            </span>
            <?php if ($user['kyc_status'] === 'verified'): ?>
                <span class="badge badge-verified">✓ KYC Verified</span>
            <?php elseif ($user['kyc_status'] === 'pending'): ?>
                <span class="badge badge-pending">⏳ KYC Pending</span>
            <?php elseif ($user['kyc_status'] === 'rejected'): ?>
                <span class="badge badge-rejected">✗ KYC Rejected</span>
            <?php else: ?>
                <span class="badge badge-warning">⚠ KYC Not Submitted</span>
            <?php endif; ?>
        </div>
        <div class="profile-meta">
            <div class="meta-item">
                <span class="meta-label">Member Since:</span>
                <span class="meta-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Last Login:</span>
                <span class="meta-value"><?php echo $user['last_login'] ? timeAgo($user['last_login']) : 'Never'; ?></span>
            </div>
        </div>
    </div>
</div>
