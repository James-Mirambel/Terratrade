<header class="site-header">
    <div class="container header-inner">
        <div class="header-left"></div>
        
        <a href="index.php" class="brand" style="text-decoration: none; color: inherit; cursor: pointer; position: absolute; left: 50%; transform: translateX(-50%);">
            <img src="Logo.png" alt="TerraTrade logo" class="logo">
            <div class="brand-text">
                <div class="brand-name">TerraTrade</div>
            </div>
        </a>
        
        <div class="header-actions">
            <div class="user-menu">
                <button id="userMenuBtn" class="user-btn">
                    <span style="font-size: 20px;">☰</span>
                    <span class="user-name">MENU</span>
                </button>
                <div id="userDropdown" class="dropdown-menu hidden">
                    <a href="profile.php" class="dropdown-item active">
                        <span>👤</span> Profile
                    </a>
                    <a href="index.php" class="dropdown-item">
                        <span>🏠</span> Home
                    </a>
                    <a href="#" class="dropdown-item">
                        <span>♡</span> Favorites
                    </a>
                    <?php if ($user['kyc_status'] !== 'verified'): ?>
                        <a href="#" class="dropdown-item" onclick="switchTab('kyc')">
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
                    <a href="view-offers.php" class="dropdown-item">
                        <span>👁️</span> View Offers
                    </a>
                    <?php if ($user['role'] === 'admin'): ?>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item">
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
