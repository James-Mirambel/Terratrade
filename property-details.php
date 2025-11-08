<?php
/**
 * Property Details Page - Modern Design
 * TerraTrade Land Trading System
 */

// Check if system is installed
if (!file_exists('config/installed.lock')) {
    header('Location: install/install.php');
    exit;
}

require_once 'config/config.php';

// Get property ID from URL
$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$propertyId) {
    header('Location: index.php');
    exit;
}

// Get system settings
$siteName = getSetting('site_name', 'TerraTrade');
$currentUser = getCurrentUser();
$user = $currentUser; // For profile-hero.php compatibility

// Add missing fields for profile-hero.php
if ($user) {
    $user['created_at'] = $user['created_at'] ?? date('Y-m-d H:i:s');
    $user['last_login'] = $user['last_login'] ?? null;
}

$isLoggedIn = isLoggedIn();

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Fetch property details from database
try {
    $sql = "SELECT p.*, 
                   u.full_name as owner_name,
                   u.email as owner_email,
                   u.phone as owner_phone
            FROM properties p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.id = ? AND p.status != 'deleted'";
    
    $property = fetchOne($sql, [$propertyId]);
    
    if (!$property) {
        header('Location: index.php');
        exit;
    }
    
    // Calculate hectares
    $hectares = round($property['area_sqm'] / 10000, 4);
    $pricePerSqm = round($property['price'] / $property['area_sqm'], 2);
    
    // Set default values for missing fields
    $property['property_type'] = $property['property_type'] ?? 'Land';
    $property['title_type'] = $property['title_type'] ?? 'N/A';
    $property['zoning'] = $property['zoning'] ?? 'N/A';
    $property['features'] = $property['features'] ?? null;
    
} catch (Exception $e) {
    error_log("Error fetching property: " . $e->getMessage());
    header('Location: index.php');
    exit;
}

$pageTitle = htmlspecialchars($property['title']) . ' - ' . $siteName;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="css/property-details.css">
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="header-container">
            <div class="header-left">
                <a href="index.php" class="logo">
                    <span class="logo-icon">🏡</span>
                    <div class="logo-text">
                        <span class="logo-title">TerraTrade</span>
                    </div>
                </a>
            </div>
            
            <div class="header-right">
                <a href="index.php" class="header-btn">
                    <span class="btn-icon">🏠</span>
                    <span>Home</span>
                </a>
                
                <?php if ($isLoggedIn): ?>
                <a href="messages.php" class="header-btn">
                    <span class="btn-icon">💬</span>
                    <span>Messages</span>
                </a>
                
                <button class="header-btn menu-btn" onclick="toggleMenu()">
                    <span class="btn-icon">☰</span>
                    <span>MENU</span>
                </button>
                
                <!-- Dropdown Menu -->
                <div class="header-dropdown" id="headerMenu">
                    <a href="profile.php" class="dropdown-item">
                        <span>👤</span> Profile
                    </a>
                    <a href="my-listings.php" class="dropdown-item">
                        <span>📋</span> My Listings
                    </a>
                    <a href="view-offers.php" class="dropdown-item">
                        <span>💼</span> My Offers
                    </a>
                    <a href="settings.php" class="dropdown-item">
                        <span>⚙️</span> Settings
                    </a>
                    <hr class="dropdown-divider">
                    <a href="logout.php" class="dropdown-item logout">
                        <span>🚪</span> Logout
                    </a>
                </div>
                <?php else: ?>
                <a href="login.php" class="header-btn">
                    <span class="btn-icon">🔐</span>
                    <span>Login</span>
                </a>
                <a href="register.php" class="header-btn primary">
                    <span class="btn-icon">📝</span>
                    <span>Register</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Property Details Container -->
    <div class="property-details-container">
        <!-- Map Section (Top) -->
        <section class="property-map-hero">
            <div class="map-container">
                <div class="map-placeholder-hero">
                    <div class="map-content">
                        <h3>📍 <?php echo htmlspecialchars($property['location']); ?></h3>
                        <p class="map-note">Interactive map coming soon</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Main Content -->
        <div class="content-wrapper">
            <div class="content-grid">
                <!-- Left Column: Property Information -->
                <div class="property-main">
                    <!-- Property Header -->
                    <div class="property-header-card">
                        <h1 class="property-title"><?php echo htmlspecialchars($property['title']); ?></h1>
                    </div>

                    <!-- Quick Stats -->
                    <div class="quick-stats">
                        <div class="stat-card">
                            <div class="stat-icon">📏</div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo number_format($property['area_sqm']); ?> m²</div>
                                <div class="stat-label">Total Area</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">🌾</div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $hectares; ?> ha</div>
                                <div class="stat-label">Hectares</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">💰</div>
                            <div class="stat-content">
                                <div class="stat-value">₱<?php echo number_format($pricePerSqm, 2); ?></div>
                                <div class="stat-label">Per m²</div>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">📄</div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo htmlspecialchars($property['title_type']); ?></div>
                                <div class="stat-label">Title Type</div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="info-card">
                        <h2 class="card-title">📝 Description</h2>
                        <p class="property-description"><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>
                    </div>

                    <!-- Property Details -->
                    <div class="info-card">
                        <h2 class="card-title">ℹ️ Property Details</h2>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="detail-label">Listing Type</span>
                                <span class="detail-value"><?php echo ucfirst($property['listing_type']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Zoning</span>
                                <span class="detail-value"><?php echo htmlspecialchars($property['zoning']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status</span>
                                <span class="detail-value status-<?php echo $property['status']; ?>">
                                    <?php echo ucfirst($property['status']); ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Featured</span>
                                <span class="detail-value"><?php echo $property['featured'] ? 'Yes' : 'No'; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Location Details -->
                    <div class="info-card">
                        <h2 class="card-title">📍 Location Details</h2>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="detail-label">Region</span>
                                <span class="detail-value"><?php echo htmlspecialchars($property['region'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Province</span>
                                <span class="detail-value"><?php echo htmlspecialchars($property['province'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">City</span>
                                <span class="detail-value"><?php echo htmlspecialchars($property['city'] ?: 'N/A'); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Barangay</span>
                                <span class="detail-value"><?php echo htmlspecialchars($property['barangay'] ?: 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Features & Amenities -->
                    <?php if (!empty($property['features'])): ?>
                    <div class="info-card">
                        <h2 class="card-title">✨ Features & Amenities</h2>
                        <div class="features-grid">
                            <?php 
                            $features = json_decode($property['features'], true);
                            if ($features && is_array($features)):
                                foreach ($features as $feature): ?>
                                <div class="feature-tag">
                                    <span class="feature-icon">✓</span>
                                    <?php echo htmlspecialchars($feature); ?>
                                </div>
                            <?php endforeach;
                            endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Photo Gallery -->
                    <div class="info-card gallery-card">
                        <!-- Media Header -->
                        <div class="media-header">
                            <h3>MEDIA</h3>
                        </div>

                        <!-- Gallery Tabs -->
                        <div class="gallery-tabs">
                            <a href="#" class="gallery-tab active" data-tab="photos">
                                PHOTOS (24)
                            </a>
                            <a href="#" class="gallery-tab" data-tab="maps">
                                MAPS (2)
                            </a>
                        </div>

                        <!-- Gallery Content -->
                        <div class="gallery-content">
                            <!-- Photos Tab -->
                            <div class="gallery-tab-content active" id="photos-tab">
                                <div class="main-photo">
                                    <button class="nav-arrow nav-prev" onclick="previousImage()">‹</button>
                                    <img src="<?php echo htmlspecialchars($property['image_url'] ?: 'https://via.placeholder.com/1200x600/4CAF50/ffffff?text=Property+Image'); ?>" 
                                         alt="<?php echo htmlspecialchars($property['title']); ?>" 
                                         class="gallery-main-image"
                                         id="mainGalleryImage">
                                    <button class="nav-arrow nav-next" onclick="nextImage()">›</button>
                                    <button class="fullscreen-btn" onclick="toggleFullscreen()">⛶</button>
                                </div>
                                
                                <!-- Thumbnails -->
                                <div class="gallery-thumbnails">
                                    <div class="thumbnail active" onclick="changeMainImage(this, 0)">
                                        <img src="<?php echo htmlspecialchars($property['image_url'] ?: 'https://via.placeholder.com/200x150/4CAF50/ffffff?text=Photo+1'); ?>" 
                                             alt="Photo 1">
                                    </div>
                                    <div class="thumbnail" onclick="changeMainImage(this, 1)">
                                        <img src="https://via.placeholder.com/200x150/2196F3/ffffff?text=Photo+2" 
                                             alt="Photo 2">
                                    </div>
                                    <div class="thumbnail" onclick="changeMainImage(this, 2)">
                                        <img src="https://via.placeholder.com/200x150/FF9800/ffffff?text=Photo+3" 
                                             alt="Photo 3">
                                    </div>
                                    <div class="thumbnail" onclick="changeMainImage(this, 3)">
                                        <img src="https://via.placeholder.com/200x150/9C27B0/ffffff?text=Photo+4" 
                                             alt="Photo 4">
                                    </div>
                                </div>
                            </div>

                            <!-- Maps Tab -->
                            <div class="gallery-tab-content" id="maps-tab">
                                <div class="main-photo">
                                    <div class="map-placeholder-gallery">
                                        <h3>📍 <?php echo htmlspecialchars($property['location']); ?></h3>
                                        <p>Interactive map coming soon</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Contact & Actions -->
                <div class="property-sidebar">
                    <?php if ($isLoggedIn && $currentUser['id'] != $property['user_id']): ?>
                    <!-- Make Offer Card -->
                    <div class="action-card offer-card">
                        <div class="card-header">
                            <h3>💰 Interested in this property?</h3>
                        </div>
                        <button class="btn-make-offer" id="makeOfferBtn">
                            <span class="btn-icon">💼</span>
                            <span>Make an Offer</span>
                        </button>
                        <p class="offer-hint">Submit your offer and negotiate directly with the seller</p>
                    </div>
                    <?php endif; ?>

                    <?php if (!$isLoggedIn || $currentUser['id'] != $property['user_id']): ?>
                    <!-- Contact Card -->
                    <div class="action-card contact-card">
                        <div class="card-header">
                            <h3>📞 Contact Seller</h3>
                        </div>
                        
                        <!-- Seller Info -->
                        <div class="seller-info">
                            <div class="seller-avatar">
                                <span class="avatar-icon">👤</span>
                            </div>
                            <div class="seller-details">
                                <h4><?php echo htmlspecialchars($property['contact_name'] ?: $property['owner_name']); ?></h4>
                                <p class="seller-role">Property Owner</p>
                            </div>
                        </div>

                        <!-- Contact Form -->
                        <form class="contact-form" id="contactForm">
                            <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                            <input type="hidden" name="receiver_id" value="<?php echo $property['user_id']; ?>">
                            
                            <div class="form-group">
                                <input type="text" name="name" placeholder="Your Name" required>
                            </div>
                            
                            <div class="form-group">
                                <input type="email" name="email" placeholder="Your Email" required>
                            </div>
                            
                            <div class="form-group">
                                <input type="tel" name="phone" placeholder="Your Phone Number" required>
                            </div>
                            
                            <div class="form-group">
                                <textarea name="message" rows="4" placeholder="I'm interested in this property..." required></textarea>
                            </div>
                            
                            <button type="submit" class="btn-contact">
                                <span class="btn-icon">📧</span>
                                <span>Send Message</span>
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                    <!-- Property Stats Card -->
                    <div class="action-card stats-card">
                        <div class="card-header">
                            <h3>📊 Property Stats</h3>
                        </div>
                        <div class="stats-list">
                            <div class="stat-row">
                                <span class="stat-label">Listed</span>
                                <span class="stat-value"><?php echo date('M d, Y', strtotime($property['created_at'])); ?></span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Property ID</span>
                                <span class="stat-value">#<?php echo str_pad($property['id'], 6, '0', STR_PAD_LEFT); ?></span>
                            </div>
                            <div class="stat-row">
                                <span class="stat-label">Views</span>
                                <span class="stat-value">-</span>
                            </div>
                        </div>
                    </div>

                    <!-- Share Card -->
                    <div class="action-card share-card">
                        <div class="card-header">
                            <h3>🔗 Share Property</h3>
                        </div>
                        <div class="share-buttons">
                            <button class="share-btn" onclick="shareProperty('facebook')">
                                <span>📘</span>
                            </button>
                            <button class="share-btn" onclick="shareProperty('twitter')">
                                <span>🐦</span>
                            </button>
                            <button class="share-btn" onclick="shareProperty('whatsapp')">
                                <span>💬</span>
                            </button>
                            <button class="share-btn" onclick="copyLink()">
                                <span>🔗</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($siteName); ?>. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Pass property data to JavaScript
        window.propertyData = <?php echo json_encode($property); ?>;
    </script>
    <script src="js/property-details.js"></script>
</body>
</html>
