<!-- Security Tab -->
<div class="tab-pane" id="security">
    <div class="profile-section">
        <h2>Security Settings</h2>
        
        <div class="security-section">
            <h3>Change Password</h3>
            <form id="changePasswordForm" class="profile-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="currentPassword">Current Password *</label>
                    <input type="password" id="currentPassword" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="newPassword">New Password *</label>
                    <input type="password" id="newPassword" name="new_password" required minlength="8">
                    <small class="form-hint">Minimum 8 characters</small>
                </div>
                
                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password *</label>
                    <input type="password" id="confirmPassword" name="confirm_password" required>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn primary">Update Password</button>
                </div>
            </form>
        </div>

        <div class="security-section">
            <h3>Active Sessions</h3>
            <p>These devices are currently logged into your account:</p>
            <div class="sessions-list">
                <?php if (empty($activeSessions)): ?>
                    <p class="empty-state">No active sessions</p>
                <?php else: ?>
                    <?php foreach ($activeSessions as $session): ?>
                        <div class="session-item">
                            <div class="session-icon">💻</div>
                            <div class="session-details">
                                <div class="session-device">
                                    <?php 
                                    $userAgent = $session['user_agent'];
                                    if (strpos($userAgent, 'Mobile') !== false) {
                                        echo '📱 Mobile Device';
                                    } else {
                                        echo '💻 Desktop';
                                    }
                                    ?>
                                </div>
                                <div class="session-info">
                                    IP: <?php echo htmlspecialchars($session['ip_address']); ?> • 
                                    Last active: <?php echo timeAgo($session['last_activity']); ?>
                                </div>
                            </div>
                            <?php if ($session['id'] === session_id()): ?>
                                <span class="current-session-badge">Current</span>
                            <?php else: ?>
                                <button class="btn-link danger" onclick="terminateSession('<?php echo $session['id']; ?>')">
                                    Terminate
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
