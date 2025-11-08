<?php
/**
 * View Offers Management Page
 * TerraTrade Land Trading System
 */

require_once __DIR__ . '/config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$currentUser = getCurrentUser();

// Get property ID from URL parameter
$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$property = null;

if ($propertyId) {
    // Verify property ownership
    $property = fetchOne("SELECT * FROM properties WHERE id = ? AND user_id = ?", [$propertyId, $currentUser['id']]);
    if (!$property) {
        header('Location: my-listings.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $property ? 'Offers for ' . htmlspecialchars($property['title']) : 'All Property Offers'; ?> - TerraTrade</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="css/view-offers.css">
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
                        <a href="my-listings.php" class="dropdown-item">
                            <span>📋</span> My Listings
                        </a>
                        <a href="#" class="dropdown-item">
                            <span>📄</span> Contracts
                        </a>
                        <a href="view-offers.php" class="dropdown-item active">
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
                    <h1><?php echo $property ? 'Offers for "' . htmlspecialchars($property['title']) . '"' : 'All Property Offers'; ?></h1>
                    <p><?php echo $property ? 'Manage offers for this specific property' : 'View and manage all offers on your properties'; ?></p>
                </div>
                <div class="header-actions">
                    <?php if ($property): ?>
                        <button class="btn-secondary" onclick="window.location.href='view-offers.php'">
                            <span>📊</span> All Offers
                        </button>
                    <?php endif; ?>
                    <button class="btn-close" onclick="window.location.href='my-listings.php'">
                        <span>✕</span>
                    </button>
                </div>
            </div>
        </div>

        <?php if ($property): ?>
        <!-- Property Info -->
        <div class="property-info">
            <div class="property-card">
                <div class="property-image">🏞️</div>
                <div class="property-details">
                    <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                    <div class="property-meta">
                        <span class="price">₱<?php echo number_format($property['price']); ?></span>
                        <span class="area"><?php echo number_format($property['area_sqm']); ?> sqm</span>
                        <span class="location"><?php echo htmlspecialchars($property['city'] . ', ' . $property['province']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Offers Stats -->
        <div class="offers-stats">
            <div class="stat-card stat-total">
                <div class="stat-number" id="totalOffers">0</div>
                <div class="stat-label">TOTAL OFFERS</div>
            </div>
            <div class="stat-card stat-pending">
                <div class="stat-number" id="pendingOffers">0</div>
                <div class="stat-label">PENDING</div>
            </div>
            <div class="stat-card stat-accepted">
                <div class="stat-number" id="acceptedOffers">0</div>
                <div class="stat-label">ACCEPTED</div>
            </div>
            <div class="stat-card stat-highest">
                <div class="stat-number" id="highestOffer">₱0</div>
                <div class="stat-label">HIGHEST OFFER</div>
            </div>
        </div>

        <!-- Filters and Controls -->
        <div class="controls-section">
            <div class="filter-controls">
                <select id="statusFilter" class="filter-select">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="accepted">Accepted</option>
                    <option value="rejected">Rejected</option>
                    <option value="countered">Countered</option>
                </select>
                <select id="propertyFilter" class="filter-select" <?php echo $property ? 'style="display:none"' : ''; ?>>
                    <option value="">All Properties</option>
                </select>
                <input type="date" id="dateFilter" class="filter-select" placeholder="Filter by date">
            </div>
            <div class="action-controls">
                <button class="btn-secondary" onclick="exportOffers()">
                    <span>📊</span> Export
                </button>
                <button class="btn-secondary" onclick="refreshOffers()">
                    <span>🔄</span> Refresh
                </button>
            </div>
        </div>

        <!-- Offers List -->
        <div class="offers-container" id="offersContainer">
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Loading offers...</p>
            </div>
        </div>

        <!-- Empty State -->
        <div class="empty-state hidden" id="emptyState">
            <div class="empty-icon">💰</div>
            <h3>No offers found</h3>
            <p><?php echo $property ? 'This property hasn\'t received any offers yet.' : 'You haven\'t received any offers on your properties yet.'; ?></p>
            <button class="btn-primary" onclick="window.location.href='my-listings.php'">
                <span>📋</span> View My Listings
            </button>
        </div>
    </div>

    <!-- Offer Details Modal -->
    <div id="offerModal" class="modal hidden">
        <div class="modal-content large">
            <div class="modal-header">
                <h3 id="modalTitle">Offer Details</h3>
                <button class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <div id="offerDetails" class="offer-details-content">
                <!-- Offer details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Counter Offer Modal -->
    <div id="counterOfferModal" class="modal hidden">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Make Counter Offer</h3>
                <button class="modal-close" onclick="closeCounterModal()">&times;</button>
            </div>
            <form id="counterOfferForm" class="counter-offer-form">
                <div class="form-group">
                    <label for="counterAmount">Counter Offer Amount (₱)</label>
                    <input type="number" id="counterAmount" name="counter_amount" required min="1">
                </div>
                <div class="form-group">
                    <label for="counterMessage">Message (Optional)</label>
                    <textarea id="counterMessage" name="message" rows="3" placeholder="Explain your counter offer..."></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-secondary" onclick="closeCounterModal()">Cancel</button>
                    <button type="submit" class="btn-primary">
                        <span>💰</span> Send Counter Offer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Pass PHP data to JavaScript
        window.propertyId = <?php echo $propertyId ? $propertyId : 'null'; ?>;
        window.currentUserId = <?php echo $currentUser['id']; ?>;
        window.propertyData = <?php echo $property ? json_encode($property) : 'null'; ?>;
    </script>
    <script src="js/view-offers.js"></script>
</body>
</html>
