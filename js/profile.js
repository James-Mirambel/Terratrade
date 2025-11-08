/**
 * Profile Page JavaScript
 * TerraTrade Land Trading System
 */

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeProfile();
});

function initializeProfile() {
    setupTabSwitching();
    setupFormHandlers();
    setupAvatarUpload();
    setupUserMenu();
    setupLogout();
}

// Tab Switching
function setupTabSwitching() {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and panes
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Add active class to clicked button and corresponding pane
            this.classList.add('active');
            document.getElementById(targetTab).classList.add('active');
        });
    });
}

// Global function for switching tabs (called from PHP)
function switchTab(tabName) {
    const tabButton = document.querySelector(`[data-tab="${tabName}"]`);
    if (tabButton) {
        tabButton.click();
    }
}

// Form Handlers
function setupFormHandlers() {
    // Personal Info Form
    const personalInfoForm = document.getElementById('personalInfoForm');
    if (personalInfoForm) {
        personalInfoForm.addEventListener('submit', handlePersonalInfoUpdate);
    }
    
    // Change Password Form
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', handlePasswordChange);
    }
    
    // KYC Upload Form
    const kycUploadForm = document.getElementById('kycUploadForm');
    if (kycUploadForm) {
        kycUploadForm.addEventListener('submit', handleKYCUpload);
    }
    
    // Preferences Form
    const preferencesForm = document.getElementById('preferencesForm');
    if (preferencesForm) {
        preferencesForm.addEventListener('submit', handlePreferencesUpdate);
    }
}

// Handle Personal Info Update
async function handlePersonalInfoUpdate(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    try {
        const response = await fetch('api/profile/update-personal.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', 'Personal information updated successfully!');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showAlert('error', result.error || 'Failed to update personal information');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred. Please try again.');
    }
}

// Handle Password Change
async function handlePasswordChange(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Validate passwords match
    if (data.new_password !== data.confirm_password) {
        showAlert('error', 'New passwords do not match');
        return;
    }
    
    try {
        const response = await fetch('api/profile/change-password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', 'Password changed successfully!');
            e.target.reset();
        } else {
            showAlert('error', result.error || 'Failed to change password');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred. Please try again.');
    }
}

// Handle KYC Upload
async function handleKYCUpload(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('api/profile/upload-kyc.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', 'Document uploaded successfully! It will be reviewed within 24-48 hours.');
            setTimeout(() => window.location.reload(), 2000);
        } else {
            showAlert('error', result.error || 'Failed to upload document');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred. Please try again.');
    }
}

// Handle Preferences Update
async function handlePreferencesUpdate(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Convert checkboxes to boolean
    const checkboxes = ['notify_new_offers', 'notify_offer_updates', 'notify_messages', 
                        'notify_auction_updates', 'notify_marketing', 'show_email', 
                        'show_phone', 'allow_messages_unverified'];
    
    checkboxes.forEach(name => {
        data[name] = formData.has(name);
    });
    
    try {
        const response = await fetch('api/profile/update-preferences.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', 'Preferences saved successfully!');
        } else {
            showAlert('error', result.error || 'Failed to save preferences');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred. Please try again.');
    }
}

// Avatar Upload
function setupAvatarUpload() {
    const avatarUpload = document.getElementById('avatarUpload');
    if (avatarUpload) {
        avatarUpload.addEventListener('change', handleAvatarUpload);
    }
}

async function handleAvatarUpload(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    // Validate file type
    if (!file.type.startsWith('image/')) {
        showAlert('error', 'Please select an image file');
        return;
    }
    
    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        showAlert('error', 'Image must be less than 5MB');
        return;
    }
    
    const formData = new FormData();
    formData.append('avatar', file);
    formData.append('csrf_token', window.ProfileData.csrfToken);
    
    try {
        const response = await fetch('api/profile/upload-avatar.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', 'Profile photo updated successfully!');
            // Update avatar preview
            const currentAvatar = document.getElementById('currentAvatar');
            if (currentAvatar) {
                if (currentAvatar.tagName === 'IMG') {
                    currentAvatar.src = result.avatar_url + '?t=' + Date.now();
                } else {
                    // Replace placeholder with image
                    const img = document.createElement('img');
                    img.src = result.avatar_url;
                    img.alt = 'Profile';
                    img.className = 'profile-avatar-large';
                    img.id = 'currentAvatar';
                    currentAvatar.parentNode.replaceChild(img, currentAvatar);
                }
            }
        } else {
            showAlert('error', result.error || 'Failed to upload avatar');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred. Please try again.');
    }
}

// Terminate Session
async function terminateSession(sessionId) {
    if (!confirm('Are you sure you want to terminate this session?')) {
        return;
    }
    
    try {
        const response = await fetch('api/profile/terminate-session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                session_id: sessionId,
                csrf_token: window.ProfileData.csrfToken
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showAlert('success', 'Session terminated successfully');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showAlert('error', result.error || 'Failed to terminate session');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('error', 'An error occurred. Please try again.');
    }
}

// Reset Form
function resetForm(formId) {
    const form = document.getElementById(formId);
    if (form) {
        form.reset();
    }
}

// User Menu Toggle
function setupUserMenu() {
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('hidden');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.user-menu')) {
                userDropdown.classList.add('hidden');
            }
        });
    }
}

// Logout
function setupLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to logout?')) {
                return;
            }
            
            try {
                const response = await fetch('api/logout.php', {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = 'index.php';
                }
            } catch (error) {
                console.error('Error:', error);
                window.location.href = 'index.php';
            }
        });
    }
}

// Show Alert
function showAlert(type, message) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `${type === 'success' ? '✓' : '✗'} ${message}`;
    
    // Insert at top of main content
    const mainContent = document.querySelector('.profile-container');
    if (mainContent) {
        mainContent.insertBefore(alert, mainContent.firstChild);
        
        // Scroll to alert
        alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            alert.remove();
        }, 5000);
    }
}

// Export functions for global use
window.switchTab = switchTab;
window.terminateSession = terminateSession;
window.resetForm = resetForm;
