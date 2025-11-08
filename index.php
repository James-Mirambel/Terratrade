<?php
/**
 * TerraTrade Main Application
 * Full PHP Land Trading System
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
    // Database not accessible, redirect to installation
    header('Location: install/install.php');
    exit;
}

// Get current user if logged in
$currentUser = Auth::getCurrentUser();
$isLoggedIn = Auth::isLoggedIn();

// Generate CSRF token
$csrfToken = generateCSRFToken();

// Get system settings
$siteName = getSetting('site_name', 'TerraTrade');
$kycRequired = getSetting('kyc_required', true);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?php echo htmlspecialchars($siteName); ?> — Land Trading System (LTS)</title>

  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles.css" />
  <link rel="stylesheet" href="css/modals.css" />
  
  <!-- Add global JavaScript variables -->
  <script>
    window.TerraTrade = {
      baseUrl: '<?php echo BASE_URL; ?>',
      apiUrl: '<?php echo BASE_URL; ?>/api',
      csrfToken: '<?php echo $csrfToken; ?>',
      currentUser: <?php echo $isLoggedIn ? json_encode($currentUser) : 'null'; ?>,
      isLoggedIn: <?php echo $isLoggedIn ? 'true' : 'false'; ?>,
      kycRequired: <?php echo $kycRequired ? 'true' : 'false'; ?>
    };
  </script>
  <script defer src="js/app.js"></script>
  <script defer src="js/notifications.js"></script>
</head>
<body>
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
        <!-- Main Action Buttons -->
        <button id="sellBtn" class="btn primary">
          <span>🏠</span> Sell Land
        </button>
        
        <!-- User Actions -->
        <?php if ($isLoggedIn): ?>
          <a href="messaging.php" id="messagesBtn" class="icon-btn" title="Messages" style="text-decoration: none; color: inherit; position: relative;">
            💬
            <span id="messagesBadge" class="notif-badge hidden" style="position: absolute; top: -5px; right: -5px; background: linear-gradient(135deg, #e17055 0%, #d63031 100%); color: white; border-radius: 50%; min-width: 20px; height: 20px; font-size: 11px; font-weight: 700; display: none; align-items: center; justify-content: center; box-shadow: 0 2px 6px rgba(214, 48, 49, 0.4);">0</span>
          </a>
          
          <button id="notificationsBtn" class="icon-btn" title="Notifications">
            🔔
            <span id="notifBadge" class="notif-badge hidden">0</span>
          </button>

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
        <?php else: ?>
          <button id="loginBtn" class="btn primary">
            <span>🔐</span> Login
          </button>
        <?php endif; ?>

      </div>
    </div>
  </header>

  <!-- Filters Panel Modal -->
  <div id="filtersPanel" class="modal" style="display: none;">
    <div class="modal-content" style="max-width: 500px;">
      <div class="modal-header">
        <h3>🔧 Filters</h3>
        <button class="modal-close" onclick="closeFiltersPanel()">&times;</button>
      </div>
      <div class="modal-body">
        <div class="form-group">
          <label>Region</label>
          <input id="filterRegion" class="form-control" placeholder="Region VII" />
        </div>
        
        <div class="form-group">
          <label>Zoning</label>
          <select id="filterZoning" class="form-control">
            <option value="">Any</option>
            <option>Residential</option>
            <option>Agricultural</option>
            <option>Commercial</option>
            <option>Industrial</option>
          </select>
        </div>

        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
          <div class="form-group">
            <label>Price min</label>
            <input id="filterPriceMin" type="number" class="form-control" placeholder="0" />
          </div>
          <div class="form-group">
            <label>Price max</label>
            <input id="filterPriceMax" type="number" class="form-control" placeholder="No limit" />
          </div>
        </div>

        <div class="form-group">
          <label>Min area (sqm)</label>
          <input id="filterMinArea" type="number" class="form-control" placeholder="0" />
        </div>

        <div class="filter-actions" style="display: flex; gap: 10px; margin-top: 20px;">
          <button id="clearFilters" class="btn ghost" style="flex: 1;">Clear</button>
          <button id="applyFilters" class="btn primary" style="flex: 1;">Apply</button>
        </div>
      </div>
    </div>
  </div>

  <main class="container" style="max-width: 1400px; margin: 0 auto; padding: 20px;">
    <section class="main-col" style="width: 100%;">
      <section class="hero-banner">
        <div class="hero-overlay">
          <div class="hero-content">
            <h1 class="hero-title">TerraTrade Philippines</h1>
            <p class="hero-subtitle">Discover, Buy, Sell. All in One Land Trading Hub</p>
            
            <!-- Centered Search Box -->
            <div class="hero-search">
              <div class="hero-search-container">
                <input type="text" id="heroSearch" placeholder="Enter an address, neighborhood, city, or ZIP code" 
                       class="hero-search-input">
                <button class="hero-search-btn">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                  </svg>
                </button>
              </div>
              <button id="heroFiltersBtn" class="hero-filters-btn">
                <span>🔧</span> Filters
              </button>
            </div>
          </div>
        </div>
      </section>

      <section class="listings-section">
        <div class="listings-header">
          <h2>Featured Listings</h2>
          <div class="controls-right">
            <div class="result-count" id="resultCount">Loading...</div>
            <button id="toggleGridList" class="btn ghost">Toggle view</button>
          </div>
        </div>
        <div id="listingsGrid" class="listings-grid">
          <div class="loading-spinner">Loading properties...</div>
        </div>
        
        <!-- Pagination -->
        <div id="paginationContainer" class="pagination-container hidden">
          <button id="prevPageBtn" class="btn ghost">← Previous</button>
          <div id="pageNumbers" class="page-numbers"></div>
          <button id="nextPageBtn" class="btn ghost">Next →</button>
        </div>
      </section>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <div>
        <strong><?php echo strtolower(htmlspecialchars($siteName)); ?></strong>
        <div class="muted small">Full System — Data Privacy (RA 10173) • E-sign (RA 8792)</div>
      </div>
      <div class="muted small">© <span id="year"><?php echo date('Y'); ?></span> <?php echo strtolower(htmlspecialchars($siteName)); ?></div>
    </div>
  </footer>

  <!-- Notifications panel -->
  <div id="notificationsPanel" class="panel-modal hidden">
    <div class="panel-head">
      <h4>Notifications</h4>
      <button id="closeNotifs" class="modal-close">✕</button>
    </div>
    <ul id="notificationsList" class="notifications-list">
      <li class="loading">Loading notifications...</li>
    </ul>
  </div>

  <!-- Auth Modal (login/register with role) -->
  <div id="authModal" class="modal hidden" aria-hidden="true">
    <div class="modal-content auth-card">
      <button class="modal-close" id="authClose">✕</button>
      <div class="auth-tabs" role="tablist">
        <button id="loginTab" class="tab active" data-tab="login" role="tab" aria-selected="true">Login</button>
        <button id="registerTab" class="tab" data-tab="register" role="tab" aria-selected="false">Register</button>
      </div>

      <div id="loginPanel" class="auth-panel">
        <h3>Welcome back</h3>
        <form id="loginForm">
          <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
          <div class="input-group">
            <input id="authEmail" name="email" type="email" placeholder="Email" required />
          </div>
          <div class="input-group">
            <input id="authPassword" name="password" type="password" placeholder="Password" required />
          </div>
          <div class="modal-row" style="margin-top:8px;">
            <button type="submit" id="authSubmit" class="btn primary">Login</button>
          </div>
        </form>
      </div>

      <div id="registerPanel" class="auth-panel hidden" aria-hidden="true">
        <h3>Create an account</h3>
        <form id="registerForm">
          <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
          <div class="input-group">
            <input id="authName" name="full_name" type="text" placeholder="Full name" required />
          </div>
          <div class="input-group">
            <input id="authEmailR" name="email" type="email" placeholder="Email" required />
          </div>
          <div class="input-group">
            <input id="authPhone" name="phone" type="tel" placeholder="Phone (optional)" />
          </div>
          <div class="input-group">
            <input id="authPasswordR" name="password" type="password" placeholder="Password" required />
          </div>
          <input type="hidden" name="role" value="user">

          <div class="modal-row" style="margin-top:8px;">
            <button type="submit" id="authRegister" class="btn primary">Register</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- All other modals from the original file -->
  <!-- Listing Details Modal -->
  <div id="listingModal" class="modal hidden">
    <div class="modal-content large two-col">
      <button class="modal-close" id="listingClose">✕</button>
      <div class="left">
        <div id="mapBox" class="map-placeholder">[Map placeholder]</div>
        <div class="thumbs" id="thumbsRow"></div>
      </div>

      <div class="right">
        <h3 id="ldTitle">Title</h3>
        <div class="badges" id="ldBadges"></div>
        <p id="ldDesc" class="muted"></p>
        <div class="ld-grid">
          <div><strong>Zoning</strong><div id="ldZoning"></div></div>
          <div><strong>Area</strong><div id="ldArea"></div></div>
          <div><strong>Price</strong><div id="ldPrice"></div></div>
          <div><strong>Region</strong><div id="ldRegion"></div></div>
        </div>

        <div class="modal-actions">
          <button id="favBtn" class="btn">♡ Favorite</button>
          <button id="messageSellerBtn" class="btn primary">💬 Message</button>
          <button id="makeOfferBtn" class="btn primary hidden">💰 Make Offer</button>
          <button id="acceptOfferBtn" class="btn ghost hidden">Accept Offer</button>
          <button id="counterOfferBtn" class="btn ghost hidden">Counter Offer</button>
          <button id="signDocBtn" class="btn ghost hidden">Sign Document (PSA)</button>
        </div>

        <div id="quickOfferSection" class="quick-action-section hidden" style="margin-top: 15px;">
          <!-- Content populated by JavaScript -->
        </div>


        <div id="listingReports" style="margin-top:10px;"></div>
      </div>
    </div>
  </div>

  <!-- Loading Modal -->
  <div id="loadingModal" class="modal hidden">
    <div class="modal-content small">
      <div class="loading-content">
        <div class="loading-spinner"></div>
        <p id="loadingMessage">Processing...</p>
      </div>
    </div>
  </div>

  <!-- Alert Modal -->
  <div id="alertModal" class="modal hidden">
    <div class="modal-content small">
      <div class="alert-content">
        <div id="alertIcon" class="alert-icon">ℹ️</div>
        <h3 id="alertTitle">Notice</h3>
        <p id="alertMessage">Message</p>
        <div class="modal-actions">
          <button id="alertOkBtn" class="btn primary">OK</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Sell Property Modal -->
  <div id="sellPropertyModal" class="modal hidden">
    <div class="modal-content extra-large">
      <div class="modal-header">
        <h2 class="modal-title">🏞️ List Your Property</h2>
        <button class="modal-close">✕</button>
      </div>
      
      <div class="sell-property-wizard">
        <!-- Step Indicator -->
        <div class="wizard-steps">
          <div class="step active" data-step="1">
            <div class="step-number">1</div>
            <div class="step-label">Property Details</div>
          </div>
          <div class="step" data-step="2">
            <div class="step-number">2</div>
            <div class="step-label">Ownership Verification</div>
          </div>
          <div class="step" data-step="3">
            <div class="step-number">3</div>
            <div class="step-label">Review & Submit</div>
          </div>
        </div>

        <form id="sellPropertyForm">
          <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
          
          <!-- Step 1: Property Details -->
          <div class="wizard-content" data-step="1">
            <div class="step-header">
              <h3>📋 Property Information</h3>
              <p>Provide detailed information about your property</p>
            </div>
            
            <!-- Property Photos Section -->
            <div class="photo-upload-section">
              <h4>📸 Property Photos</h4>
              <p class="section-description">Upload high-quality photos of your property to attract more buyers</p>
              
              <div class="photo-upload-area">
                <div class="photo-upload-zone" id="photoUploadZone">
                  <div class="upload-icon">📷</div>
                  <h4>Upload Property Photos</h4>
                  <p>Drag and drop photos here or click to browse</p>
                  <p class="upload-specs">Recommended: High resolution (min 1024x768), JPG/PNG format</p>
                  <input type="file" id="propertyPhotos" name="property_photos[]" multiple 
                         accept=".jpg,.jpeg,.png" hidden>
                </div>
                <div class="uploaded-photos" id="uploadedPhotos"></div>
              </div>
            </div>

            <div class="form-grid">
              <div class="form-group full-width">
                <label for="propertyTitle">Property Title *</label>
                <input type="text" id="propertyTitle" name="title" required 
                       placeholder="e.g., 2.5ha Prime Agricultural Land in Cebu with Mountain View">
                <div class="field-hint">Create an attractive title that highlights key features</div>
              </div>
              
              <div class="form-group full-width">
                <label for="propertyDescription">Property Description *</label>
                <textarea id="propertyDescription" name="description" required rows="5" 
                          placeholder="Provide detailed description including: terrain type, soil quality, water source, accessibility, nearby landmarks, development potential, current use, etc."></textarea>
                <div class="field-hint">Detailed descriptions help buyers make informed decisions</div>
              </div>
              
              <div class="form-group">
                <label for="propertyArea">Total Area (sqm) *</label>
                <input type="number" id="propertyArea" name="area_sqm" required min="1" 
                       oninput="calculateTotalPriceIndex()">
                <div class="field-hint">Exact area as per land title</div>
              </div>
              
              <div class="form-group">
                <label for="propertyAreaHectares">Area in Hectares</label>
                <input type="number" id="propertyAreaHectares" name="area_hectares" step="0.01" 
                       placeholder="" readonly>
                <div class="field-hint">Automatically calculated</div>
              </div>
              
              <div class="form-group">
                <label for="propertyZoning">Zoning Classification *</label>
                <select id="propertyZoning" name="zoning" required onchange="updateSuggestedPriceIndex()">
                  <option value="">Select zoning type</option>
                  <option value="Residential">Residential</option>
                  <option value="Agricultural">Agricultural</option>
                  <option value="Commercial">Commercial</option>
                  <option value="Industrial">Industrial</option>
                  <option value="Mixed Use">Mixed Use</option>
                  <option value="Institutional">Institutional</option>
                  <option value="Tourism">Tourism</option>
                  <option value="Special Economic Zone">Special Economic Zone</option>
                </select>
                <div class="field-hint" id="zoningHintIndex"></div>
              </div>
              
              <div class="form-group">
                <label for="pricePerSqm">Price per sqm (₱) *</label>
                <input type="number" id="pricePerSqm" name="price_per_sqm" required min="1" 
                       placeholder="" oninput="calculateTotalPriceIndex()">
                <div class="field-hint">Suggested: <span id="suggestedPriceIndex">-</span></div>
              </div>
              
              <div class="form-group">
                <label for="totalPrice">Total Price (₱)</label>
                <input type="number" id="totalPrice" name="price" min="1" 
                       placeholder="" readonly>
                <div class="field-hint">Automatically calculated</div>
              </div>
              
            </div>
            
            <!-- Development Potential -->
            <div class="development-section">
              <h4>🏗️ Development Information</h4>
              <div class="form-grid">
                <div class="form-group">
                  <label for="developmentPotential">Development Potential</label>
                  <select id="developmentPotential" name="development_potential">
                    <option value="">Select potential</option>
                    <option value="Residential Subdivision">Residential Subdivision</option>
                    <option value="Commercial Complex">Commercial Complex</option>
                    <option value="Industrial Park">Industrial Park</option>
                    <option value="Resort/Tourism">Resort/Tourism</option>
                    <option value="Agricultural">Agricultural Only</option>
                    <option value="Mixed Development">Mixed Development</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label for="nearbyLandmarks">Nearby Landmarks</label>
                  <input type="text" id="nearbyLandmarks" name="nearby_landmarks" 
                         placeholder="e.g., 5km from city center, near SM Mall">
                  <div class="field-hint">Mention schools, malls, hospitals, etc.</div>
                </div>
                
                <div class="form-group">
                  <label for="distanceToCity">Distance to City Center (km)</label>
                  <input type="number" id="distanceToCity" name="distance_to_city" step="0.1" 
                         placeholder="15.5">
                </div>
                
                <div class="form-group">
                  <label for="floodProne">Flood Risk</label>
                  <select id="floodProne" name="flood_risk">
                    <option value="">Select flood risk</option>
                    <option value="No Risk">No Risk</option>
                    <option value="Low Risk">Low Risk</option>
                    <option value="Medium Risk">Medium Risk</option>
                    <option value="High Risk">High Risk</option>
                  </select>
                </div>
              </div>
            </div>
            
            <div class="location-section">
              <h4>📍 Property Location</h4>
              <div class="form-grid">
                <div class="form-group">
                  <label for="propertyRegion">Region *</label>
                  <input type="text" id="propertyRegion" name="region" required 
                         placeholder="e.g., Region VII - Central Visayas">
                </div>
                
                <div class="form-group">
                  <label for="propertyProvince">Province *</label>
                  <input type="text" id="propertyProvince" name="province" required 
                         placeholder="e.g., Cebu">
                </div>
                
                <div class="form-group">
                  <label for="propertyCity">City/Municipality *</label>
                  <input type="text" id="propertyCity" name="city" required 
                         placeholder="e.g., Cebu City">
                </div>
                
                <div class="form-group">
                  <label for="propertyBarangay">Barangay</label>
                  <input type="text" id="propertyBarangay" name="barangay" 
                         placeholder="e.g., Lahug">
                </div>
              </div>
            </div>
            
            <div class="form-group">
              <label for="propertyContact">Contact Information *</label>
              <input type="text" id="propertyContact" name="contact_phone" required 
                     placeholder="e.g., +63 917 123 4567">
              <div class="field-hint">Buyers will use this to contact you directly</div>
            </div>
            
            
            <div class="wizard-navigation">
              <button type="button" class="btn ghost" onclick="App.closeModal('sellPropertyModal')">Cancel</button>
              <button type="button" class="btn primary" onclick="App.nextWizardStep()">Next Step</button>
            </div>
          </div>
          
          <!-- Step 2: Ownership Verification -->
          <div class="wizard-content hidden" data-step="2">
            <div class="step-header">
              <h3>🔐 Ownership Verification</h3>
              <p>Verify that you are the legal owner or authorized to sell this property</p>
            </div>
            
            <div class="ownership-verification">
              <div class="verification-options">
                <div class="verification-option">
                  <input type="radio" id="ownershipOwner" name="ownership_type" value="owner" required>
                  <label for="ownershipOwner" class="option-card">
                    <div class="option-icon">👤</div>
                    <div class="option-content">
                      <h4>I am the Legal Owner</h4>
                      <p>I own this property and have the legal title</p>
                    </div>
                  </label>
                </div>
                
                <div class="verification-option">
                  <input type="radio" id="ownershipAgent" name="ownership_type" value="agent" required>
                  <label for="ownershipAgent" class="option-card">
                    <div class="option-icon">🏢</div>
                    <div class="option-content">
                      <h4>I am an Authorized Agent</h4>
                      <p>I have legal authorization to sell this property</p>
                    </div>
                  </label>
                </div>
                
                <div class="verification-option">
                  <input type="radio" id="ownershipHeir" name="ownership_type" value="heir" required>
                  <label for="ownershipHeir" class="option-card">
                    <div class="option-icon">👨‍👩‍👧‍👦</div>
                    <div class="option-content">
                      <h4>I am an Heir/Successor</h4>
                      <p>I inherited this property legally</p>
                    </div>
                  </label>
                </div>
              </div>
              
              <!-- Document Upload Section -->
              <div class="document-upload-section">
                <h4>📄 Required Documents</h4>
                <div class="upload-requirements" id="uploadRequirements">
                  <!-- Dynamic content based on ownership type -->
                </div>
                
                <div class="file-upload-area">
                  <div class="upload-zone" id="documentUploadZone">
                    <div class="upload-icon">📁</div>
                    <h4>Upload Supporting Documents</h4>
                    <p>Drag and drop files here or click to browse</p>
                    <input type="file" id="ownershipDocuments" name="ownership_documents[]" multiple 
                           accept=".pdf,.jpg,.jpeg,.png" hidden>
                  </div>
                  <div class="uploaded-files" id="uploadedFiles"></div>
                </div>
                
                <div class="verification-checklist">
                  <h4>✅ Verification Checklist</h4>
                  <div class="checklist-items">
                    <label class="checklist-item">
                      <input type="checkbox" required>
                      <span>I confirm that all uploaded documents are authentic and valid</span>
                    </label>
                    <label class="checklist-item">
                      <input type="checkbox" required>
                      <span>I have the legal right to sell or authorize the sale of this property</span>
                    </label>
                    <label class="checklist-item">
                      <input type="checkbox" required>
                      <span>The property information provided is accurate and complete</span>
                    </label>
                    <label class="checklist-item">
                      <input type="checkbox" required>
                      <span>I understand that false information may result in legal consequences</span>
                    </label>
                  </div>
                </div>
              </div>
            </div>
            
            <div class="wizard-navigation">
              <button type="button" class="btn ghost" onclick="App.prevWizardStep()">← Back</button>
              <button type="button" class="btn primary" onclick="App.nextWizardStep()">Next: Review →</button>
            </div>
          </div>
          
          <!-- Step 3: Review & Submit -->
          <div class="wizard-content hidden" data-step="3">
            <div class="step-header">
              <h3>📋 Review Your Listing</h3>
              <p>Please review all information before submitting your property listing</p>
            </div>
            
            <div class="review-section">
              <div class="review-card">
                <h4>Property Summary</h4>
                <div id="propertyReview" class="review-content">
                  <!-- Dynamic content populated by JavaScript -->
                </div>
              </div>
              
              <div class="review-card">
                <h4>Ownership Verification</h4>
                <div id="ownershipReview" class="review-content">
                  <!-- Dynamic content populated by JavaScript -->
                </div>
              </div>
              
              <div class="submission-notice">
                <div class="notice-icon">⚠️</div>
                <div class="notice-content">
                  <h4>Important Notice</h4>
                  <p>Your listing will be reviewed by our team before going live. This process typically takes 24-48 hours. You will be notified via email once your listing is approved.</p>
                </div>
              </div>
            </div>
            
            <div class="wizard-navigation">
              <button type="button" class="btn ghost" onclick="App.prevWizardStep()">← Back</button>
              <button type="submit" class="btn primary" id="submitListingBtn">
                <span class="btn-text">Submit Listing</span>
                <span class="btn-loading hidden">Submitting...</span>
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>


  <!-- My Offers Modal -->
  <div id="myOffersModal" class="modal hidden">
    <div class="modal-content large">
      <button class="modal-close">✕</button>
      <h3>My Offers</h3>
      <div id="myOffersContainer" class="offers-container">
        <div class="loading">Loading your offers...</div>
      </div>
    </div>
  </div>

  <!-- Notifications Modal -->
  <div id="notificationsModal" class="modal hidden">
    <div class="modal-content medium">
      <button class="modal-close">✕</button>
      <h3>Notifications</h3>
      <div id="notificationsContainer" class="notifications-container">
        <div class="loading">Loading notifications...</div>
      </div>
    </div>
  </div>

  <!-- Additional modals can be added here as needed -->

  <script>
  // Price Calculation Functions for Index Page
  const zoningSuggestedPrices = {
      'Residential': 50000,
      'Commercial': 150000,
      'Agricultural': 2000,
      'Industrial': 30000,
      'Mixed Use': 80000,
      'Institutional': 40000,
      'Tourism': 60000,
      'Special Economic Zone': 100000
  };

  const zoningPriceRanges = {
      'Residential': '₱5,000 - ₱400,000/sqm (Metro Manila: ₱96,000 - ₱400,000)',
      'Commercial': '₱30,000 - ₱940,000/sqm (Metro Manila CBD: ₱337,000 - ₱940,000)',
      'Agricultural': '₱100 - ₱10,000/sqm (Near urban: ₱5,000 - ₱10,000)',
      'Industrial': '₱10,000 - ₱80,000/sqm (Near economic zones: ₱20,000 - ₱80,000)',
      'Mixed Use': '₱30,000 - ₱300,000/sqm (Metro Manila: ₱100,000 - ₱300,000)',
      'Institutional': '₱20,000 - ₱100,000/sqm',
      'Tourism': '₱30,000 - ₱150,000/sqm',
      'Special Economic Zone': '₱50,000 - ₱200,000/sqm'
  };

  function updateSuggestedPriceIndex() {
      const zoning = document.getElementById('propertyZoning').value;
      const suggestedPriceSpan = document.getElementById('suggestedPriceIndex');
      const zoningHint = document.getElementById('zoningHintIndex');
      const pricePerSqmInput = document.getElementById('pricePerSqm');
      
      if (zoning && zoningSuggestedPrices[zoning]) {
          const suggestedPrice = zoningSuggestedPrices[zoning];
          suggestedPriceSpan.textContent = '₱' + suggestedPrice.toLocaleString() + '/sqm';
          suggestedPriceSpan.style.color = '#667eea';
          suggestedPriceSpan.style.fontWeight = '600';
          
          // Price range display removed
          
          // Always auto-fill the price per sqm with suggested value when zoning changes
          pricePerSqmInput.value = suggestedPrice;
          calculateTotalPriceIndex();
      } else {
          suggestedPriceSpan.textContent = '-';
          zoningHint.textContent = '';
      }
  }

  function calculateTotalPriceIndex() {
      calculateHectares();
      calculateTotalPrice();
  }

  function calculateHectares() {
      const area = parseFloat(document.getElementById('propertyArea').value) || 0;
      const hectaresInput = document.getElementById('propertyAreaHectares');
      
      // Calculate hectares
      if (area > 0) {
          const hectares = area / 10000;
          hectaresInput.value = hectares.toFixed(4);
      } else {
          hectaresInput.value = '';
      }
  }

  function calculateTotalPrice() {
      const area = parseFloat(document.getElementById('propertyArea').value) || 0;
      const pricePerSqm = parseFloat(document.getElementById('pricePerSqm').value) || 0;
      const totalPriceInput = document.getElementById('totalPrice');
      
      // Calculate total price
      if (area > 0 && pricePerSqm > 0) {
          const totalPrice = area * pricePerSqm;
          totalPriceInput.value = Math.round(totalPrice);
      } else {
          totalPriceInput.value = '';
      }
  }
  </script>

</body>
</html>
<?php
// Clean up any output buffers
if (ob_get_level()) {
    ob_end_flush();
}
?>
