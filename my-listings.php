<?php
/**
 * My Listings Management Page
 * TerraTrade Land Trading System
 */

require_once __DIR__ . '/config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$currentUser = getCurrentUser();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings - TerraTrade</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="css/my-listings.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="modern-nav">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="index.php" class="brand-link">
                    <img src="Logo.png" alt="TerraTrade logo" class="logo">
                    <div class="brand-text">
                        <h1>TerraTrade</h1>
                    </div>
                </a>
            </div>
            
            <div class="nav-actions">
                <button class="nav-btn" onclick="window.location.href='index.php'">
                    <span>🏠</span> Home
                </button>
                <button class="nav-btn" onclick="window.location.href='messaging.php'">
                    <span>💬</span> Messages
                </button>
                <div class="user-menu">
                    <button class="user-menu-btn" onclick="toggleUserMenu()">
                        <span style="font-size: 20px;">☰</span>
                        <span class="user-name">MENU</span>
                    </button>
                    <div id="userDropdown" class="dropdown-menu hidden">
                        <a href="profile.php" class="dropdown-item">
                            <span>👤</span> Profile
                        </a>
                        <a href="index.php" class="dropdown-item">
                            <span>🏠</span> Home
                        </a>
                        <a href="#" class="dropdown-item">
                            <span>♡</span> Favorites
                        </a>
                        <?php if ($currentUser['kyc_status'] !== 'verified'): ?>
                            <a href="#" class="dropdown-item">
                                <span>🪪</span> KYC Verification
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="my-listings.php" class="dropdown-item active">
                            <span>📋</span> My Listings
                        </a>
                        <a href="#" class="dropdown-item">
                            <span>📄</span> Contracts
                        </a>
                        <a href="view-offers.php" class="dropdown-item">
                            <span>👁️</span> View Offers
                        </a>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item">
                                <span>⚙️</span> Admin Dashboard
                            </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item" onclick="logout()">
                            <span>🚪</span> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <h1>🏠 My Listings Management</h1>
                    <p>Manage your property listings and track their performance</p>
                </div>
                <button class="btn-close" onclick="window.location.href='index.php'">
                    <span>✕</span>
                </button>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="dashboard-stats">
            <div class="stat-card stat-total">
                <div class="stat-number" id="totalListings">0</div>
                <div class="stat-label">TOTAL LISTINGS</div>
            </div>
            <div class="stat-card stat-active">
                <div class="stat-number" id="activeListings">0</div>
                <div class="stat-label">ACTIVE</div>
            </div>
            <div class="stat-card stat-pending">
                <div class="stat-number" id="pendingListings">0</div>
                <div class="stat-label">PENDING REVIEW</div>
            </div>
            <div class="stat-card stat-value">
                <div class="stat-number" id="totalValue">₱0</div>
                <div class="stat-label">TOTAL VALUE</div>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls-section">
            <div class="search-controls">
                <input type="text" id="searchInput" placeholder="Search my listings..." class="search-input">
                <select id="statusFilter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="pending">Pending Review</option>
                    <option value="sold">Sold</option>
                    <option value="withdrawn">Withdrawn</option>
                </select>
                <select id="typeFilter" class="filter-select">
                    <option value="">All Types</option>
                    <option value="sale">For Sale</option>
                </select>
            </div>
            <div class="action-controls">
                <button class="btn-primary" onclick="window.location.href='index.php#add-listing'">
                    <span>➕</span> Add New Listing
                </button>
                <button class="btn-secondary" onclick="showBulkActions()">
                    <span>⚙️</span> Bulk Actions
                </button>
            </div>
        </div>

        <!-- Listings Grid -->
        <div class="listings-grid" id="listingsGrid">
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Loading your listings...</p>
            </div>
        </div>

        <!-- Empty State -->
        <div class="empty-state hidden" id="emptyState">
            <div class="empty-icon">📋</div>
            <h3>No listings found</h3>
            <p>You haven't created any property listings yet.</p>
            <button class="btn-primary" onclick="window.location.href='index.php#add-listing'">
                <span>➕</span> Create Your First Listing
            </button>
        </div>
    </div>


    <!-- Bulk Actions Modal -->
    <div id="bulkActionsModal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Bulk Actions</h3>
                <button class="modal-close" onclick="closeBulkModal()">&times;</button>
            </div>
            <div class="bulk-actions">
                <button class="bulk-btn" onclick="bulkActivate()">
                    <span>✅</span> Activate Selected
                </button>
                <button class="bulk-btn" onclick="bulkDeactivate()">
                    <span>⏸️</span> Deactivate Selected
                </button>
                <button class="bulk-btn danger" onclick="bulkDelete()">
                    <span>🗑️</span> Delete Selected
                </button>
                <button class="bulk-btn" onclick="exportSelected()">
                    <span>📊</span> Export Selected
                </button>
            </div>
        </div>
    </div>

    <script src="js/my-listings.js"></script>
</body>
</html>
