<?php
/**
 * Messaging Page
 * TerraTrade Land Trading System
 */

// Check if system is installed
if (!file_exists('config/installed.lock')) {
    header('Location: install/install.php');
    exit;
}

require_once 'config/config.php';

// Check if database is accessible
try {
    $testConnection = getDB();
    $testConnection->query("SELECT 1");
} catch (Exception $e) {
    header('Location: install/install.php');
    exit;
}

// Require authentication for messaging
Auth::requireLogin();

// Get current user
$currentUser = Auth::getCurrentUser();
$isLoggedIn = Auth::isLoggedIn();

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Get system settings
$siteName = getSetting('site_name', 'TerraTrade');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - <?php echo htmlspecialchars($siteName); ?></title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="css/messaging.css">
    
    <!-- Custom header styling for messaging page -->
    <style>
        .site-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            backdrop-filter: none !important;
            border-bottom: none !important;
        }
        
        .brand-name {
            color: white !important;
        }
        
        .btn.ghost {
            background: rgba(255, 255, 255, 0.15) !important;
            color: white !important;
            border-color: rgba(255, 255, 255, 0.3) !important;
            backdrop-filter: blur(10px) !important;
        }
        
        .btn.ghost:hover {
            background: rgba(255, 255, 255, 0.25) !important;
            color: white !important;
        }
        
        .btn.primary {
            background: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            border-color: rgba(255, 255, 255, 0.4) !important;
            backdrop-filter: blur(10px) !important;
        }
        
        .btn.primary:hover {
            background: rgba(255, 255, 255, 0.3) !important;
            color: white !important;
        }
    </style>
    
    <!-- Add global JavaScript variables -->
    <script>
        window.TerraTrade = {
            baseUrl: '<?php echo BASE_URL; ?>',
            apiUrl: '<?php echo BASE_URL; ?>/api',
            csrfToken: '<?php echo $csrfToken; ?>',
            currentUser: <?php echo json_encode($currentUser); ?>,
            isLoggedIn: true
        };
    </script>
    <script defer src="js/app.js"></script>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="container header-inner">
            <div class="header-left">
                <div class="brand">
                    <img src="Logo.png" alt="<?php echo htmlspecialchars($siteName); ?> logo" class="logo">
                    <div class="brand-text">
                        <div class="brand-name"><?php echo htmlspecialchars($siteName); ?></div>
                    </div>
                </div>
            </div>

            <div class="header-actions">
                <!-- Navigation Buttons -->
                <button class="btn ghost" onclick="window.location.href='index.php'">
                    <span>🏠</span> Home
                </button>
                
                <!-- User Menu -->
                <div class="user-menu">
                    <button id="menuBtn" class="btn ghost" style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 18px;">☰</span>
                        <span>MENU</span>
                    </button>
                    <div id="userDropdown" class="dropdown-menu hidden">
                        <a href="profile.php" class="dropdown-item">
                            <span>👤</span> Profile
                        </a>
                        <a href="index.php" class="dropdown-item">
                            <span>🏠</span> Home
                        </a>
                        <a href="messaging.php" class="dropdown-item">
                            <span>💬</span> Messages
                        </a>
                        <a href="#" id="myFavoritesLink" class="dropdown-item">
                            <span>♡</span> Favorites
                        </a>
                        <?php if ($currentUser['kyc_status'] !== 'verified'): ?>
                            <a href="#" id="kycLink" class="dropdown-item">
                                <span>🪪</span> KYC Verification
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="#" id="myListingsBtn" class="dropdown-item">
                            <span>📋</span> My Listings
                        </a>
                        <a href="#" id="myOffersBtn" class="dropdown-item">
                            <span>💰</span> My Offers
                        </a>
                        <a href="#" id="myContractsBtn" class="dropdown-item">
                            <span>📄</span> Contracts
                        </a>
                        <a href="#" id="viewOffersBtn" class="dropdown-item">
                            <span>👁️</span> View Offers
                        </a>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <div class="dropdown-divider"></div>
                            <a href="#" id="adminDashboardLink" class="dropdown-item">
                                <span>⚙️</span> Admin Dashboard
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="#" id="logoutBtn" class="dropdown-item">
                            <span>🚪</span> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content modern-main">
        <div class="container">
            <div class="messaging-container">
                <!-- Conversations Sidebar -->
                <div class="conversations-sidebar">
                    <div class="conversations-header">
                        <h3>Conversations</h3>
                        <button class="new-message-btn" id="newMessageBtn">
                            <span class="btn-icon">✨</span>
                            <span>New</span>
                        </button>
                    </div>
                    <div class="conversations-list" id="conversationsList">
                        <!-- Conversations will be loaded here -->
                        <div class="loading">Loading conversations...</div>
                    </div>
                </div>

                <!-- Conversation Panel -->
                <div class="conversation-panel hidden" id="conversationPanel">
                    <div class="conversation-header-panel" id="conversationHeader">
                        <h3>Select a conversation</h3>
                        <button class="conversation-menu-btn" id="conversationMenuBtn" onclick="messaging.toggleConversationMenu()">⋮</button>
                        
                        <!-- Conversation Menu Dropdown -->
                        <div class="conversation-menu-dropdown hidden" id="conversationMenuDropdown">
                            <div class="conversation-menu-profile">
                                <div class="conversation-menu-avatar" id="menuAvatar">
                                    <div class="avatar-placeholder">X</div>
                                </div>
                                <div class="conversation-menu-name" id="menuName">User Name</div>
                            </div>
                            
                            <div class="conversation-menu-actions">
                                <button class="menu-action-btn" onclick="messaging.initiateVoiceCall()" disabled title="Coming soon">
                                    <span class="action-icon">📞</span>
                                    <span>Voice Call</span>
                                </button>
                                <button class="menu-action-btn" onclick="messaging.initiateVideoCall()" disabled title="Coming soon">
                                    <span class="action-icon">📹</span>
                                    <span>Video Call</span>
                                </button>
                            </div>
                            
                            <div class="conversation-menu-divider"></div>
                            
                            <div class="conversation-menu-options">
                                <button class="menu-option-btn delete" onclick="messaging.deleteConversation()">
                                    <span>🗑️</span> Delete Conversation
                                </button>
                                <button class="menu-option-btn block" onclick="messaging.blockUser()">
                                    <span>🚫</span> Block User
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="messages-container" id="messagesContainer">
                        <div class="no-messages">
                            <p>Select a conversation to start messaging</p>
                        </div>
                    </div>
                    
                    <form class="message-form" id="messageForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <div class="message-input-container">
                            <textarea 
                                id="messageInput" 
                                class="message-input" 
                                placeholder="Type your message..."
                                rows="1"
                                required></textarea>
                            <button type="submit" class="send-btn" title="Send message">
                                <span class="send-icon">🚀</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <div class="modal delete-modal hidden" id="deleteModal">
        <div class="delete-modal-content">
            <div class="delete-modal-icon">🗑️</div>
            <h3>Delete Message?</h3>
            <p>Are you sure you want to delete this message? This action cannot be undone.</p>
            <div class="delete-modal-actions">
                <button class="btn-cancel" onclick="messaging.cancelDelete()">Cancel</button>
                <button class="btn-delete" onclick="messaging.confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <!-- New Message Modal -->
    <div class="modal message-modal hidden" id="newMessageModal">
        <div class="modal-backdrop" onclick="messaging.hideMessageModal()"></div>
        <div class="message-modal-content">
            <div class="message-modal-header">
                <h3>New Message</h3>
                <button class="close-modal-btn" id="closeMessageModal">&times;</button>
            </div>
            
            <form id="newMessageForm">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <div class="form-group">
                    <label for="recipientSelect">Send to:</label>
                    <select id="recipientSelect" required>
                        <option value="">Select a user...</option>
                        <!-- Users will be loaded here -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="propertySelect">Related to property (optional):</label>
                    <select id="propertySelect">
                        <option value="">No specific property</option>
                        <!-- Properties will be loaded here -->
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="messageSubject">Subject:</label>
                    <input type="text" id="messageSubject" placeholder="Enter subject...">
                </div>
                
                <div class="form-group">
                    <label for="messageContent">Message:</label>
                    <textarea id="messageContent" placeholder="Type your message..." required></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="messaging.hideMessageModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Send Message</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/messaging.js"></script>
    <script src="js/notifications.js"></script>
    <script>
        // Check authentication on page load
        document.addEventListener('DOMContentLoaded', async () => {
            try {
                // Update profile name
                const profileName = document.getElementById('profileName');
                if (profileName) {
                    profileName.textContent = '<?php echo htmlspecialchars($currentUser['full_name']); ?>';
                }
                
                // Load users and properties for new message modal
                await loadUsersAndProperties();
                
            } catch (error) {
                console.error('Initialization failed:', error);
            }
        });
        
        
        // Load users and properties for new message form
        async function loadUsersAndProperties() {
            try {
                // Load users
                const usersResponse = await fetch('/Terratrade/api/users.php', {
                    credentials: 'include'
                });
                
                if (usersResponse.ok) {
                    const usersData = await usersResponse.json();
                    const recipientSelect = document.getElementById('recipientSelect');
                    
                    if (usersData.success && usersData.users) {
                        usersData.users.forEach(user => {
                            const option = document.createElement('option');
                            option.value = user.id;
                            option.textContent = user.full_name;
                            recipientSelect.appendChild(option);
                        });
                    }
                }
                
                // Load properties
                const propertiesResponse = await fetch('/Terratrade/api/simple_properties.php', {
                    credentials: 'include'
                });
                
                if (propertiesResponse.ok) {
                    const propertiesData = await propertiesResponse.json();
                    const propertySelect = document.getElementById('propertySelect');
                    
                    if (propertiesData.success && propertiesData.properties) {
                        propertiesData.properties.forEach(property => {
                            const option = document.createElement('option');
                            option.value = property.id;
                            option.textContent = property.title;
                            propertySelect.appendChild(option);
                        });
                    }
                }
                
            } catch (error) {
                console.error('Failed to load users and properties:', error);
            }
        }
        
        // Handle new message form submission
        const newMessageForm = document.getElementById('newMessageForm');
        if (newMessageForm) {
            newMessageForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const recipientId = document.getElementById('recipientSelect').value;
            const propertyId = document.getElementById('propertySelect').value;
            const subject = document.getElementById('messageSubject').value;
            const content = document.getElementById('messageContent').value;
            
            if (!recipientId || !content) {
                alert('Please select a recipient and enter a message');
                return;
            }
            
            try {
                const response = await fetch('/Terratrade/api/messages/send.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        receiver_id: recipientId,
                        message: content,
                        subject: subject,
                        property_id: propertyId || null
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    messaging.hideMessageModal();
                    messaging.loadConversations();
                    
                    // Clear form
                    document.getElementById('newMessageForm').reset();
                    
                    messaging.showNotification('Message sent successfully', 'success');
                } else {
                    messaging.showNotification('Failed to send message: ' + data.error, 'error');
                }
                
            } catch (error) {
                console.error('Error sending message:', error);
                messaging.showNotification('Failed to send message', 'error');
            }
            });
        }

        // Simple menu functionality for messaging page
        function initializeMenu() {
            console.log('Initializing menu...');
            
            const menuBtn = document.getElementById('menuBtn');
            const userDropdown = document.getElementById('userDropdown');
            
            console.log('Menu button:', menuBtn);
            console.log('Dropdown:', userDropdown);

            if (menuBtn && userDropdown) {
                // Remove any existing event listeners
                menuBtn.replaceWith(menuBtn.cloneNode(true));
                const newMenuBtn = document.getElementById('menuBtn');
                
                newMenuBtn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Menu clicked!');
                    
                    if (userDropdown.classList.contains('hidden')) {
                        userDropdown.classList.remove('hidden');
                        console.log('Menu opened');
                    } else {
                        userDropdown.classList.add('hidden');
                        console.log('Menu closed');
                    }
                };

                // Close menu when clicking outside
                document.onclick = function(e) {
                    if (!userDropdown.contains(e.target) && !newMenuBtn.contains(e.target)) {
                        userDropdown.classList.add('hidden');
                    }
                };

                // Setup logout
                const logoutBtn = document.getElementById('logoutBtn');
                if (logoutBtn) {
                    logoutBtn.onclick = async function(e) {
                        e.preventDefault();
                        try {
                            await fetch('api/logout.php', {
                                method: 'POST',
                                credentials: 'include'
                            });
                            window.location.href = 'index.php';
                        } catch (error) {
                            console.error('Logout failed:', error);
                            window.location.href = 'index.php';
                        }
                    };
                }
            }
        }

        // Initialize menu when page loads
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializeMenu);
        } else {
            initializeMenu();
        }

        // Also try after a short delay to ensure all elements are loaded
        setTimeout(initializeMenu, 500);
    </script>
</body>
</html>
