<!-- Privacy Tab -->
<div class="tab-pane" id="preferences">
    <div class="profile-section">
        <h2>Privacy</h2>
        
        <form id="preferencesForm" class="profile-form">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <h3>Privacy Settings</h3>
            
            <div class="preference-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="show_email" checked>
                    <span>Show my email to verified users</span>
                </label>
            </div>
            
            <div class="preference-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="show_phone" checked>
                    <span>Show my phone number to verified users</span>
                </label>
            </div>
            
            <div class="preference-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="allow_messages_unverified">
                    <span>Allow messages from non-verified users</span>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn primary">Save Preferences</button>
            </div>
        </form>
    </div>
</div>
