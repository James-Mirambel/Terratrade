<!-- Personal Info Tab -->
<div class="tab-pane" id="personal">
    <div class="profile-section">
        <h2>Personal Information</h2>
        <form id="personalInfoForm" class="profile-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="form-group">
                <label for="fullName">Full Name *</label>
                <input type="text" id="fullName" name="full_name" 
                       value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                <small class="form-hint">Changing your email will require verification</small>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" 
                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                       placeholder="+63 917 123 4567">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn primary">Save Changes</button>
                <button type="button" class="btn ghost" onclick="resetForm('personalInfoForm')">Cancel</button>
            </div>
        </form>
    </div>
</div>
