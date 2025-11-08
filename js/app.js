/**
 * TerraTrade Main Application JavaScript
 * Full PHP Land Trading System Frontend
 */

// Utility functions
function number_format(number) {
    return new Intl.NumberFormat('en-US').format(number);
}

// Global application state
const App = {
    currentUser: window.TerraTrade?.currentUser || null,
    isLoggedIn: window.TerraTrade?.isLoggedIn || false,
    csrfToken: window.TerraTrade?.csrfToken || '',
    baseUrl: window.TerraTrade?.baseUrl || '',
    apiUrl: window.TerraTrade?.apiUrl || '/api',
    
    // Current state
    currentPage: 1,
    currentFilters: {},
    properties: [],
    currentWizardStep: 1,
    
    // Initialize application
    init() {
        this.setupEventListeners();
        this.loadInitialData();
        this.setupUserInterface();
        
        if (this.isLoggedIn) {
            this.loadUserNotifications();
            this.loadUnreadMessageCount();
        }
        
        console.log('TerraTrade application initialized');
    },
    
    // Setup all event listeners
    setupEventListeners() {
        // Search functionality
        document.getElementById('searchBtn')?.addEventListener('click', () => this.performSearch());
        document.getElementById('searchInput')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.performSearch();
        });
        
        // Filter functionality
        document.getElementById('applyFilters')?.addEventListener('click', () => this.applyFilters());
        document.getElementById('clearFilters')?.addEventListener('click', () => this.clearFilters());
        
        // Authentication
        document.getElementById('loginBtn')?.addEventListener('click', () => this.showLoginModal());
        document.getElementById('logoutBtn')?.addEventListener('click', () => this.logout());
        
        // Hero tabs functionality
        this.setupHeroTabs();
        
        // Sell property wizard
        this.setupSellPropertyWizard();
        
        // Auto-calculations for property form
        this.setupPropertyCalculations();
        
        // User menu
        document.getElementById('userMenuBtn')?.addEventListener('click', () => this.toggleUserMenu());
        document.getElementById('menuBtn')?.addEventListener('click', () => this.toggleUserMenu());
        
        // Filters panel
        document.getElementById('filtersBtn')?.addEventListener('click', () => this.openFiltersPanel());
        
        // Hero search
        document.getElementById('heroSearch')?.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') this.performHeroSearch();
        });
        
        // Hero search button
        document.querySelector('.hero-search-btn')?.addEventListener('click', () => this.performHeroSearch());
        
        // Hero filters button
        document.getElementById('heroFiltersBtn')?.addEventListener('click', () => this.openFiltersPanel());
        
        // Hero actions
        document.getElementById('sellBtn')?.addEventListener('click', () => this.showSellModal());
        document.getElementById('browseBtn')?.addEventListener('click', () => this.scrollToListings());
        document.getElementById('myListingsBtn')?.addEventListener('click', () => window.location.href = 'my-listings.php');
        document.getElementById('myOffersBtn')?.addEventListener('click', () => this.showMyOffers());
        
        // Notifications
        document.getElementById('notificationsBtn')?.addEventListener('click', () => this.showNotifications());
        document.getElementById('messagesBtn')?.addEventListener('click', () => this.showMessages());
        
        // Modal close handlers
        document.querySelectorAll('.modal-close').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const modal = e.target.closest('.modal');
                if (modal) this.closeModal(modal.id);
            });
        });
        
        // Auth form handlers
        document.getElementById('loginForm')?.addEventListener('submit', (e) => this.handleLogin(e));
        document.getElementById('registerForm')?.addEventListener('submit', (e) => this.handleRegister(e));
        
        // Property form handlers
        document.getElementById('sellPropertyForm')?.addEventListener('submit', (e) => this.handleSellProperty(e));
        document.getElementById('propertyType')?.addEventListener('change', (e) => this.toggleAuctionFields(e));
        
        // Dropdown menu handlers
        document.getElementById('profileLink')?.addEventListener('click', (e) => { e.preventDefault(); this.showProfile(); });
        document.getElementById('myListingsLink')?.addEventListener('click', (e) => { e.preventDefault(); window.location.href = 'my-listings.php'; });
        document.getElementById('myOffersLink')?.addEventListener('click', (e) => { e.preventDefault(); this.showMyOffers(); });
        document.getElementById('myFavoritesLink')?.addEventListener('click', (e) => { e.preventDefault(); this.showMyFavorites(); });
        document.getElementById('kycLink')?.addEventListener('click', (e) => { e.preventDefault(); this.showKYCVerification(); });
        document.getElementById('adminDashboardLink')?.addEventListener('click', (e) => { e.preventDefault(); this.showAdminDashboard(); });
        
        // Hero button handlers (additional ones)
        document.getElementById('myContractsBtn')?.addEventListener('click', () => this.showMyContracts());
        document.getElementById('viewOffersBtn')?.addEventListener('click', () => this.showViewOffers());
        document.getElementById('adminDashboardBtn')?.addEventListener('click', () => this.showAdminDashboard());
        
        // Auth tab switching
        document.getElementById('loginTab')?.addEventListener('click', () => this.switchAuthTab('login'));
        document.getElementById('registerTab')?.addEventListener('click', () => this.switchAuthTab('register'));
        
        // Pagination
        document.getElementById('prevPageBtn')?.addEventListener('click', () => this.goToPage(this.currentPage - 1));
        document.getElementById('nextPageBtn')?.addEventListener('click', () => this.goToPage(this.currentPage + 1));
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.user-menu')) {
                document.getElementById('userDropdown')?.classList.add('hidden');
            }
        });
    },
    
    // Setup user interface based on login status
    setupUserInterface() {
        if (this.isLoggedIn && this.currentUser) {
            // Show user-specific elements
            document.querySelectorAll('.auth-required').forEach(el => el.classList.remove('hidden'));
            
            // Update user menu
            const userMenuBtn = document.getElementById('userMenuBtn');
            if (userMenuBtn) {
                userMenuBtn.innerHTML = `
                    ${this.currentUser.profile_image ? 
                        `<img src="uploads/${this.currentUser.profile_image}" alt="Profile" class="user-avatar">` :
                        '<span class="user-avatar-placeholder">👤</span>'
                    }
                    <span>${this.currentUser.full_name}</span>
                    <span class="dropdown-arrow">▼</span>
                `;
            }
            
            // Show KYC warning if not verified
            if (this.currentUser.kyc_status !== 'verified') {
                this.showKYCWarning();
            }
            
            // Update messages badge
            this.updateMessagesBadge();
        }
    },
    
    // Load initial data
    async loadInitialData() {
        await this.loadProperties();
    },
    
    // API request helper
    async apiRequest(endpoint, options = {}) {
        const url = `${this.apiUrl}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        // Add CSRF token for non-GET requests
        if (options.method && options.method !== 'GET') {
            if (options.body && typeof options.body === 'object') {
                options.body.csrf_token = this.csrfToken;
            }
            if (options.body && typeof options.body === 'string') {
                const data = JSON.parse(options.body);
                data.csrf_token = this.csrfToken;
                options.body = JSON.stringify(data);
            }
        }
        
        const response = await fetch(url, { ...defaultOptions, ...options });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    },
    
    // Load properties with filters
    async loadProperties(page = 1) {
        try {
            this.showLoading('Loading properties...');
            
            const params = new URLSearchParams({
                page: page,
                page_size: 20,
                ...this.currentFilters
            });
            
            const response = await this.apiRequest(`/simple_properties.php?${params}`);
            
            if (response.success) {
                this.properties = response.properties;
                this.currentPage = page;
                this.renderProperties(response.properties);
                this.renderPagination(response.pagination);
                this.updateResultCount(response.pagination.total_records);
            } else {
                this.showAlert('Error', response.error || 'Failed to load properties');
            }
        } catch (error) {
            console.error('Error loading properties:', error);
            this.showAlert('Error', 'Failed to load properties. Please try again.');
        } finally {
            this.hideLoading();
        }
    },
    
    // Render properties grid
    renderProperties(properties) {
        const grid = document.getElementById('listingsGrid');
        if (!grid) return;
        
        if (properties.length === 0) {
            grid.innerHTML = `
                <div class="no-results">
                    <div class="no-results-icon">🏠</div>
                    <h3>No properties found</h3>
                    <p>Try adjusting your search filters or browse all listings.</p>
                </div>
            `;
            return;
        }
        
        grid.innerHTML = properties.map(property => `
            <div class="listing-card" data-property-id="${property.id}">
                <div class="card-image">
                    ${property.main_image ? 
                        `<img src="uploads/${property.main_image}" alt="${property.title}" loading="lazy" style="width: 100%; height: 100%; object-fit: cover;">` :
                        '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: var(--secondary-400); font-size: 48px;">🏠</div>'
                    }
                    <div class="card-badges">
                        ${property.featured ? '<div class="badge featured">⭐ Featured</div>' : ''}
                        ${property.listing_type === 'auction' ? '<div class="badge">🔨 Auction</div>' : ''}
                        <div class="badge">${property.zoning}</div>
                    </div>
                </div>
                <div class="card-content">
                    <h3 class="card-title">${property.title}</h3>
                    <div class="card-location">
                        <span>📍</span> ${property.location}
                    </div>
                    <div class="card-details">
                        <div class="detail-item">
                            <div class="detail-label">Area</div>
                            <div class="detail-value">${property.formatted_area || (property.area_sqm ? number_format(property.area_sqm) + ' sqm' : 'N/A')}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Type</div>
                            <div class="detail-value">${property.zoning}</div>
                        </div>
                    </div>
                    <div class="card-price">
                        ${property.formatted_price || (property.price ? '₱' + number_format(property.price) : 'Price on request')}
                    </div>
                </div>
            </div>
        `).join('');
        
        // Add event listeners to property cards
        grid.querySelectorAll('.listing-card').forEach(card => {
            card.addEventListener('click', (e) => {
                const propertyId = card.dataset.propertyId;
                this.showPropertyDetails(propertyId);
            });
            
            // Add hover effect
            card.style.cursor = 'pointer';
        });
        
        grid.querySelectorAll('.favorite-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const propertyId = e.target.dataset.propertyId;
                this.toggleFavorite(propertyId, e.target);
            });
        });
    },
    
    // Render pagination
    renderPagination(pagination) {
        const container = document.getElementById('paginationContainer');
        const prevBtn = document.getElementById('prevPageBtn');
        const nextBtn = document.getElementById('nextPageBtn');
        const pageNumbers = document.getElementById('pageNumbers');
        
        if (!container || !pagination) return;
        
        if (pagination.total_pages <= 1) {
            container.classList.add('hidden');
            return;
        }
        
        container.classList.remove('hidden');
        
        // Update prev/next buttons
        prevBtn.disabled = !pagination.has_previous;
        nextBtn.disabled = !pagination.has_next;
        
        // Generate page numbers
        let pages = [];
        const current = pagination.current_page;
        const total = pagination.total_pages;
        
        // Always show first page
        if (current > 3) pages.push(1, '...');
        
        // Show pages around current
        for (let i = Math.max(1, current - 2); i <= Math.min(total, current + 2); i++) {
            pages.push(i);
        }
        
        // Always show last page
        if (current < total - 2) pages.push('...', total);
        
        pageNumbers.innerHTML = pages.map(page => {
            if (page === '...') {
                return '<span class="page-ellipsis">...</span>';
            }
            return `
                <button class="page-btn ${page === current ? 'active' : ''}" 
                        data-page="${page}">${page}</button>
            `;
        }).join('');
        
        // Add event listeners to page buttons
        pageNumbers.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const page = parseInt(e.target.dataset.page);
                this.goToPage(page);
            });
        });
    },
    
    // Update result count
    updateResultCount(total) {
        const countEl = document.getElementById('resultCount');
        if (countEl) {
            countEl.textContent = `${total} properties found`;
        }
    },
    
    // Go to specific page
    goToPage(page) {
        if (page < 1) return;
        this.loadProperties(page);
    },
    
    // Perform search
    performSearch() {
        const searchInput = document.getElementById('searchInput');
        const query = searchInput?.value.trim();
        
        if (query) {
            this.currentFilters.search = query;
        } else {
            delete this.currentFilters.search;
        }
        
        this.currentPage = 1;
        this.loadProperties(1);
    },
    
    // Apply filters
    applyFilters() {
        const filters = {};
        
        // Get filter values
        const heroSearch = document.getElementById('heroSearch')?.value.trim();
        const filterRegion = document.getElementById('filterRegion')?.value.trim();
        const filterZoning = document.getElementById('filterZoning')?.value;
        const filterPriceMin = document.getElementById('filterPriceMin')?.value;
        const filterPriceMax = document.getElementById('filterPriceMax')?.value;
        const filterMinArea = document.getElementById('filterMinArea')?.value;
        
        if (heroSearch) filters.search = heroSearch;
        if (filterRegion) filters.region = filterRegion;
        if (filterZoning) filters.zoning = filterZoning;
        if (filterPriceMin) filters.price_min = filterPriceMin;
        if (filterPriceMax) filters.price_max = filterPriceMax;
        if (filterMinArea) filters.min_area = filterMinArea;
        
        this.currentFilters = filters;
        this.currentPage = 1;
        this.loadProperties(1);
        
        // Close filters panel
        closeFiltersPanel();
    },
    
    // Clear filters
    clearFilters() {
        // Clear filter inputs
        if (document.getElementById('heroSearch')) document.getElementById('heroSearch').value = '';
        if (document.getElementById('filterRegion')) document.getElementById('filterRegion').value = '';
        if (document.getElementById('filterZoning')) document.getElementById('filterZoning').value = '';
        if (document.getElementById('filterPriceMin')) document.getElementById('filterPriceMin').value = '';
        if (document.getElementById('filterPriceMax')) document.getElementById('filterPriceMax').value = '';
        if (document.getElementById('filterMinArea')) document.getElementById('filterMinArea').value = '';
        
        this.currentFilters = {};
        this.currentPage = 1;
        this.loadProperties(1);
        
        // Close filters panel
        closeFiltersPanel();
    },
    
    // Show property details - redirect to dedicated page
    async showPropertyDetails(propertyId) {
        // Redirect to property details page
        window.location.href = `property-details.php?id=${propertyId}`;
    },
    
    // Render property details in modal
    renderPropertyDetails(property, images, documents, permissions) {
        // Update modal content
        document.getElementById('ldTitle').textContent = property.title;
        document.getElementById('ldDesc').textContent = property.description || 'No description available';
        document.getElementById('ldZoning').textContent = property.zoning;
        document.getElementById('ldArea').textContent = property.area_formatted;
        document.getElementById('ldPrice').textContent = property.price_formatted;
        document.getElementById('ldRegion').textContent = property.location_full || property.region || 'N/A';
        
        // Store current property for actions
        this.currentProperty = property;
        
        // Update action buttons based on permissions
        if (permissions) {
            const makeOfferBtn = document.getElementById('makeOfferBtn');
            const makeBidBtn = document.getElementById('makeBidBtn');
            const messageSellerBtn = document.getElementById('messageSellerBtn');
            
            if (permissions.can_make_offer) {
                makeOfferBtn?.classList.remove('hidden');
                if (property.type === 'auction') {
                    makeBidBtn?.classList.remove('hidden');
                }
            } else {
                makeOfferBtn?.classList.add('hidden');
                makeBidBtn?.classList.add('hidden');
            }
            
            if (permissions.is_owner) {
                messageSellerBtn?.classList.add('hidden');
            } else {
                messageSellerBtn?.classList.remove('hidden');
            }
        }
        
        // Update badges
        const badgesContainer = document.getElementById('ldBadges');
        if (badgesContainer) {
            let badges = [];
            badges.push(`<span class="badge ${property.status}">${property.status.toUpperCase()}</span>`);
            badges.push(`<span class="badge type">${property.type === 'auction' ? 'AUCTION' : 'FOR SALE'}</span>`);
            if (property.views_count > 0) {
                badges.push(`<span class="badge views">${property.views_count} VIEWS</span>`);
            }
            if (property.offers_count > 0) {
                badges.push(`<span class="badge offers">${property.offers_count} OFFERS</span>`);
            }
            badgesContainer.innerHTML = badges.join('');
        }
        
        // Update thumbnails/images
        const thumbsContainer = document.getElementById('thumbsRow');
        if (thumbsContainer && images) {
            thumbsContainer.innerHTML = images.map(img => `
                <div class="thumb ${img.is_primary ? 'active' : ''}" data-image-id="${img.id}">
                    <img src="${img.url}" alt="${img.caption}" />
                </div>
            `).join('');
        }
        
        // Set main image in map box
        const mapBox = document.getElementById('mapBox');
        if (mapBox && images && images.length > 0) {
            mapBox.innerHTML = `<img src="${images[0].url}" alt="Main property image" class="main-image" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">`;
        } else if (mapBox) {
            mapBox.innerHTML = '<div class="map-placeholder" style="display: flex; align-items: center; justify-content: center; height: 200px; background: #f0f0f0; border-radius: 8px;">📍 Property Location</div>';
        }
    },
    
    // Setup hero tabs functionality
    setupHeroTabs() {
        const heroTabs = document.querySelectorAll('.hero-tab');
        heroTabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                // Remove active class from all tabs
                heroTabs.forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                e.target.classList.add('active');
                
                // Get the tab type
                const tabType = e.target.dataset.tab;
                
                // Filter properties based on tab
                this.filterByType(tabType);
            });
        });
    },
    
    // Filter properties by type
    filterByType(type) {
        if (type === 'all') {
            delete this.currentFilters.type;
        } else {
            this.currentFilters.type = type;
        }
        
        this.currentPage = 1;
        this.loadProperties(1);
    },
    
    // Update property action buttons
    updatePropertyActionButtons(property) {
        const favoriteBtn = document.getElementById('favBtn');
        const messageBtn = document.getElementById('messageSellerBtn');
        const makeOfferBtn = document.getElementById('makeOfferBtn');
        const makeBidBtn = document.getElementById('makeBidBtn');
        
        if (!this.isLoggedIn) {
            // Hide all action buttons for non-logged-in users
            [favoriteBtn, messageBtn, makeOfferBtn, makeBidBtn].forEach(btn => {
                if (btn) btn.classList.add('hidden');
            });
            return;
        }
        
        // Show/hide buttons based on property type and user status
        if (favoriteBtn) {
            favoriteBtn.classList.remove('hidden');
            favoriteBtn.innerHTML = property.is_favorited ? '♥ Favorited' : '♡ Favorite';
            favoriteBtn.onclick = () => this.toggleFavorite(property.id, favoriteBtn);
        }
        
        if (messageBtn) {
            messageBtn.classList.remove('hidden');
            messageBtn.onclick = () => this.startConversation(property.user_id, property.id);
        }
        
        if (property.is_auction && property.auction_active) {
            if (makeBidBtn) {
                makeBidBtn.classList.remove('hidden');
                makeBidBtn.onclick = () => this.showBidModal(property);
            }
            if (makeOfferBtn) makeOfferBtn.classList.add('hidden');
        } else if (!property.is_auction && property.status === 'active') {
            if (makeOfferBtn) {
                makeOfferBtn.classList.remove('hidden');
                makeOfferBtn.onclick = () => this.showOfferModal(property);
            }
            if (makeBidBtn) makeBidBtn.classList.add('hidden');
        }
        
        // Hide buttons if user owns the property
        if (property.is_owner) {
            [makeOfferBtn, makeBidBtn].forEach(btn => {
                if (btn) btn.classList.add('hidden');
            });
        }
    },
    
    // Toggle favorite
    async toggleFavorite(propertyId, buttonElement) {
        if (!this.isLoggedIn) {
            this.showLoginModal();
            return;
        }
        
        try {
            const response = await this.apiRequest(`/properties/favorite/${propertyId}`, {
                method: 'POST',
                body: JSON.stringify({})
            });
            
            if (response.success) {
                // Update button appearance
                if (buttonElement) {
                    if (response.favorited) {
                        buttonElement.innerHTML = '♥';
                        buttonElement.classList.add('favorited');
                    } else {
                        buttonElement.innerHTML = '♡';
                        buttonElement.classList.remove('favorited');
                    }
                }
                
                this.showToast(response.message);
            } else {
                this.showAlert('Error', response.error);
            }
        } catch (error) {
            console.error('Error toggling favorite:', error);
            this.showAlert('Error', 'Failed to update favorite');
        }
    },
    
    // Authentication methods
    showLoginModal() {
        this.showModal('authModal');
        this.switchAuthTab('login');
    },
    
    async handleLogin(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        try {
            this.showLoading('Logging in...');
            
            const response = await this.apiRequest('/login.php', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            if (response.success) {
                this.showToast('Login successful!');
                this.closeModal('authModal');
                // Reload page to update UI
                window.location.reload();
            } else {
                this.showAlert('Login Failed', response.error);
            }
        } catch (error) {
            console.error('Login error:', error);
            this.showAlert('Error', 'Login failed. Please try again.');
        } finally {
            this.hideLoading();
        }
    },
    
    async handleRegister(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        // Basic validation
        if (data.password.length < 8) {
            this.showAlert('Validation Error', 'Password must be at least 8 characters long');
            return;
        }
        
        try {
            this.showLoading('Creating account...');
            
            const response = await this.apiRequest('/register.php', {
                method: 'POST',
                body: JSON.stringify(data)
            });
            
            if (response.success) {
                this.showAlert('Success', 'Account created successfully! Please log in.');
                this.switchAuthTab('login');
            } else {
                this.showAlert('Registration Failed', response.error);
            }
        } catch (error) {
            console.error('Registration error:', error);
            this.showAlert('Error', 'Registration failed. Please try again.');
        } finally {
            this.hideLoading();
        }
    },
    
    async logout() {
        try {
            await this.apiRequest('/logout.php', { method: 'POST' });
            window.location.reload();
        } catch (error) {
            console.error('Logout error:', error);
            window.location.reload(); // Reload anyway
        }
    },
    
    switchAuthTab(tab) {
        const loginTab = document.getElementById('loginTab');
        const registerTab = document.getElementById('registerTab');
        const loginPanel = document.getElementById('loginPanel');
        const registerPanel = document.getElementById('registerPanel');
        
        if (tab === 'login') {
            loginTab.classList.add('active');
            registerTab.classList.remove('active');
            loginPanel.classList.remove('hidden');
            registerPanel.classList.add('hidden');
        } else {
            registerTab.classList.add('active');
            loginTab.classList.remove('active');
            registerPanel.classList.remove('hidden');
            loginPanel.classList.add('hidden');
        }
    },
    
    // User interface methods
    toggleUserMenu() {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown) {
            dropdown.classList.toggle('hidden');
        }
    },
    
    scrollToListings() {
        const listingsSection = document.querySelector('.listings-section');
        if (listingsSection) {
            listingsSection.scrollIntoView({ behavior: 'smooth' });
        }
    },
    
    // Modal management
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        }
    },
    
    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    },
    
    // Loading and alerts
    showLoading(message = 'Loading...') {
        const modal = document.getElementById('loadingModal');
        const messageEl = document.getElementById('loadingMessage');
        
        if (modal && messageEl) {
            messageEl.textContent = message;
            this.showModal('loadingModal');
        }
    },
    
    hideLoading() {
        this.closeModal('loadingModal');
    },
    
    showAlert(title, message, type = 'info') {
        const modal = document.getElementById('alertModal');
        const titleEl = document.getElementById('alertTitle');
        const messageEl = document.getElementById('alertMessage');
        const iconEl = document.getElementById('alertIcon');
        
        if (modal && titleEl && messageEl && iconEl) {
            titleEl.textContent = title;
            messageEl.textContent = message;
            
            // Set icon based on type
            const icons = {
                info: 'ℹ️',
                success: '✅',
                warning: '⚠️',
                error: '❌'
            };
            iconEl.textContent = icons[type] || icons.info;
            
            this.showModal('alertModal');
            
            // Auto-close success messages
            if (type === 'success') {
                setTimeout(() => this.closeModal('alertModal'), 3000);
            }
        }
    },
    
    showToast(message, type = 'success') {
        // Create toast element if it doesn't exist
        let toast = document.getElementById('toast');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'toast';
            toast.className = 'toast';
            document.body.appendChild(toast);
        }
        
        toast.textContent = message;
        toast.className = `toast toast-${type} show`;
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    },
    
    // Show sell property modal
    showSellModal() {
        if (!this.isLoggedIn) {
            this.showAlert('Login Required', 'Please login to list a property.');
            return;
        }
        
        this.showModal('sellPropertyModal');
    },
    
    // Toggle auction fields based on property type
    toggleAuctionFields(e) {
        const auctionFields = document.getElementById('auctionFields');
        const auctionEnds = document.getElementById('auctionEnds');
        
        if (e.target.value === 'auction') {
            auctionFields.classList.remove('hidden');
            auctionEnds.required = true;
        } else {
            auctionFields.classList.add('hidden');
            auctionEnds.required = false;
        }
    },
    
    // Handle sell property form submission
    async handleSellProperty(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);
        
        // Calculate hectares from square meters
        data.hectares = (parseFloat(data.area_sqm) / 10000).toFixed(4);
        
        // Set contact name to current user's name
        if (this.currentUser && this.currentUser.full_name) {
            data.contact_name = this.currentUser.full_name;
        } else if (window.TerraTrade && window.TerraTrade.currentUser && window.TerraTrade.currentUser.full_name) {
            data.contact_name = window.TerraTrade.currentUser.full_name;
            this.currentUser = window.TerraTrade.currentUser; // Update local reference
        } else {
            // Last resort: use email or a default
            data.contact_name = data.contact_phone || 'Property Owner';
        }
        
        // Ensure contact_phone exists
        if (!data.contact_phone) {
            this.showAlert('Error', 'Please provide a contact phone number');
            return;
        }
        
        console.log('Submitting property data:', data);
        
        try {
            this.showLoading('Creating property listing...');
            
            // Direct call to create.php since routing might not work
            const response = await fetch('/Terratrade/api/properties/create.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            }).then(res => res.json());
            
            if (response.success) {
                this.showAlert('Success', 'Property listed successfully!');
                this.closeModal('sellPropertyModal');
                document.getElementById('sellPropertyForm').reset();
                
                // Refresh property listings
                await this.loadProperties();
            } else {
                console.error('Create property failed:', response);
                this.showAlert('Error', response.error || response.details || 'Failed to create listing');
            }
        } catch (error) {
            console.error('Sell property error:', error);
            console.error('Error details:', error.message, error.stack);
            this.showAlert('Error', 'Failed to create listing. Error: ' + error.message);
        } finally {
            this.hideLoading();
        }
    },
    
    displayMyListings(listings) {
        const container = document.getElementById('myListingsContainer');
        
        if (listings.length === 0) {
            container.innerHTML = `
                <div class="no-listings">
                    <p>You haven't listed any properties yet.</p>
                    <p>Browse properties and make your first offer!</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = listings.map(listing => `
            <div class="my-listing-card" data-listing-id="${listing.id}">
                <div class="listing-header">
                    <h4>${listing.title}</h4>
                    <span class="status-badge status-${listing.status}">${listing.status}</span>
                </div>
                <div class="listing-details">
                    <p><strong>Price:</strong> ₱${parseFloat(listing.price).toLocaleString()}</p>
                    <p><strong>Area:</strong> ${listing.hectares}ha (${parseInt(listing.area_sqm).toLocaleString()} sqm)</p>
                    <p><strong>Location:</strong> ${listing.city}, ${listing.province}</p>
                    <p><strong>Views:</strong> ${listing.views_count || 0}</p>
                </div>
                <div class="listing-actions">
                    <button class="btn ghost" onclick="App.viewListing(${listing.id})">View</button>
                    <button class="btn ghost" onclick="App.editListing(${listing.id})">Edit</button>
                    ${listing.status === 'active' ? 
                        `<button class="btn primary" onclick="App.deactivateListing(${listing.id})">Deactivate</button>` :
                        `<button class="btn ghost" onclick="App.activateListing(${listing.id})">Activate</button>`
                    }
                </div>
            </div>
        `).join('');
    },
    
    async showMyOffers() {
        if (!this.isLoggedIn) {
            this.showAlert('Login Required', 'Please login to view your offers.');
            return;
        }
        
        try {
            this.showLoading('Loading your offers...');
            
            const response = await fetch('/Terratrade/api/offers/my-offers.php', {
                method: 'GET',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json'
                }
            }).then(res => res.json());
            
            if (response.success) {
                this.displayMyOffers(response.offers);
                this.showModal('myOffersModal');
            } else {
                this.showAlert('Error', response.error || 'Failed to load your offers');
            }
        } catch (error) {
            console.error('My offers error:', error);
            this.showAlert('Error', 'Failed to load offers. Please try again.');
        } finally {
            this.hideLoading();
        }
    },
    
    displayMyOffers(offers) {
        const container = document.getElementById('myOffersContainer');
        
        if (offers.length === 0) {
            container.innerHTML = `
                <div class="no-offers">
                    <p>You haven't made any offers yet.</p>
                    <p>Browse properties and make your first offer!</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = offers.map(offer => `
            <div class="my-offer-card" data-offer-id="${offer.id}">
                <div class="offer-header">
                    <h4>${offer.property_title}</h4>
                    <span class="status-badge status-${offer.status}">${offer.status}</span>
                </div>
                <div class="offer-details">
                    <p><strong>Your Offer:</strong> ₱${parseFloat(offer.price).toLocaleString()}</p>
                    <p><strong>Property Price:</strong> ₱${parseFloat(offer.property_price).toLocaleString()}</p>
                    <p><strong>Location:</strong> ${offer.property_location}</p>
                    <p><strong>Submitted:</strong> ${new Date(offer.submitted_at).toLocaleDateString()}</p>
                </div>
                <div class="offer-actions">
                    <button class="btn ghost" onclick="App.viewPropertyDetails(${offer.listing_id})">View Property</button>
                    ${offer.status === 'submitted' ? 
                        `<button class="btn ghost" onclick="App.withdrawOffer(${offer.id})">Withdraw</button>` : ''
                    }
                </div>
            </div>
        `).join('');
    },
    
    async showNotifications() {
        if (!this.isLoggedIn) {
            this.showAlert('Login Required', 'Please login to view notifications.');
            return;
        }
        
        try {
            this.showLoading('Loading notifications...');
            
            const response = await fetch('/Terratrade/api/notifications.php', {
                credentials: 'include'
            }).then(res => res.json());
            
            if (response.success) {
                this.displayNotifications(response.notifications);
                this.showModal('notificationsModal');
            } else {
                this.showAlert('Error', 'Failed to load notifications');
            }
        } catch (error) {
            console.error('Notifications error:', error);
            this.showAlert('Error', 'Failed to load notifications. Please try again.');
        } finally {
            this.hideLoading();
        }
    },
    
    displayNotifications(notifications) {
        const container = document.getElementById('notificationsContainer');
        
        if (notifications.length === 0) {
            container.innerHTML = `
                <div class="no-notifications">
                    <p>No notifications yet.</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = notifications.map(notification => `
            <div class="notification-item ${notification.is_read ? '' : 'unread'}" data-notification-id="${notification.id}">
                <div class="notification-icon">${this.getNotificationIcon(notification.type)}</div>
                <div class="notification-content">
                    <h5>${notification.title}</h5>
                    <p>${notification.message}</p>
                    <small>${new Date(notification.created_at).toLocaleString()}</small>
                </div>
                ${!notification.is_read ? 
                    `<button class="btn ghost small" onclick="App.markNotificationRead(${notification.id})">Mark Read</button>` : ''
                }
            </div>
        `).join('');
    },
    
    getNotificationIcon(type) {
        const icons = {
            'message': '💬',
            'offer': '💰',
            'contract': '📄',
            'system': '🔔',
            'kyc': '🪪'
        };
        return icons[type] || '📢';
    },
    
    // Additional placeholder functions
    showProfile() {
        this.showAlert('Coming Soon', 'Profile management will be available soon.');
    },
    
    showMyFavorites() {
        this.showAlert('Coming Soon', 'Favorites feature will be available soon.');
    },
    
    showKYCVerification() {
        this.showAlert('Coming Soon', 'KYC verification will be available soon.');
    },
    
    showAdminDashboard() {
        this.showAlert('Coming Soon', 'Admin dashboard will be available soon.');
    },
    
    showMyContracts() {
        this.showAlert('Coming Soon', 'Contracts management will be available soon.');
    },
    
    showViewOffers() {
        // Redirect to the View Offers page
        window.location.href = 'view-offers.php';
    },
    
    showMessages() {
        if (this.isLoggedIn) {
            window.location.href = 'messaging.php';
        } else {
            this.showAlert('Login Required', 'Please login to access your messages.');
        }
    },
    
    async updateMessagesBadge() {
        if (!this.isLoggedIn) return;
        
        try {
            const response = await fetch('/Terratrade/api/messages/unread-count.php', {
                credentials: 'include'
            }).then(res => res.json());
            if (response.success) {
                const badge = document.getElementById('messagesBadge');
                if (badge) {
                    if (response.unread_count > 0) {
                        badge.textContent = response.unread_count;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                }
            }
        } catch (error) {
            console.error('Failed to update messages badge:', error);
        }
    },
    
    // Setup sell property wizard
    setupSellPropertyWizard() {
        // Ownership type change handler
        document.querySelectorAll('input[name="ownership_type"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                this.updateOwnershipRequirements(e.target.value);
            });
        });
        
        // File upload handler for ownership documents
        const uploadZone = document.getElementById('documentUploadZone');
        const fileInput = document.getElementById('ownershipDocuments');
        
        if (uploadZone && fileInput) {
            uploadZone.addEventListener('click', () => fileInput.click());
            uploadZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadZone.classList.add('dragover');
            });
            uploadZone.addEventListener('dragleave', () => {
                uploadZone.classList.remove('dragover');
            });
            uploadZone.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadZone.classList.remove('dragover');
                this.handleFileUpload(e.dataTransfer.files);
            });
            
            fileInput.addEventListener('change', (e) => {
                this.handleFileUpload(e.target.files);
            });
        }
        
        // Photo upload handler
        const photoUploadZone = document.getElementById('photoUploadZone');
        const photoInput = document.getElementById('propertyPhotos');
        
        if (photoUploadZone && photoInput) {
            photoUploadZone.addEventListener('click', () => photoInput.click());
            photoUploadZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                photoUploadZone.classList.add('dragover');
            });
            photoUploadZone.addEventListener('dragleave', () => {
                photoUploadZone.classList.remove('dragover');
            });
            photoUploadZone.addEventListener('drop', (e) => {
                e.preventDefault();
                photoUploadZone.classList.remove('dragover');
                this.handlePhotoUpload(e.dataTransfer.files);
            });
            
            photoInput.addEventListener('change', (e) => {
                this.handlePhotoUpload(e.target.files);
            });
        }
    },
    
    // Navigate to next wizard step
    nextWizardStep() {
        if (this.validateCurrentStep()) {
            this.currentWizardStep++;
            this.updateWizardDisplay();
            if (this.currentWizardStep === 3) {
                this.populateReviewSection();
            }
        }
    },
    
    // Navigate to previous wizard step
    prevWizardStep() {
        if (this.currentWizardStep > 1) {
            this.currentWizardStep--;
            this.updateWizardDisplay();
        }
    },
    
    // Update wizard display
    updateWizardDisplay() {
        // Update step indicators
        document.querySelectorAll('.step').forEach((step, index) => {
            const stepNumber = index + 1;
            step.classList.remove('active', 'completed');
            
            if (stepNumber === this.currentWizardStep) {
                step.classList.add('active');
            } else if (stepNumber < this.currentWizardStep) {
                step.classList.add('completed');
            }
        });
        
        // Update content visibility
        document.querySelectorAll('.wizard-content').forEach((content, index) => {
            const stepNumber = index + 1;
            if (stepNumber === this.currentWizardStep) {
                content.classList.remove('hidden');
            } else {
                content.classList.add('hidden');
            }
        });
    },
    
    // Validate current wizard step
    validateCurrentStep() {
        const currentContent = document.querySelector(`.wizard-content[data-step="${this.currentWizardStep}"]`);
        if (!currentContent) return false;
        
        const requiredFields = currentContent.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('error');
                isValid = false;
            } else {
                field.classList.remove('error');
            }
        });
        
        // Removed validation alert - fields will show error styling instead
        
        return isValid;
    },
    
    // Update ownership requirements based on type
    updateOwnershipRequirements(ownershipType) {
        const requirementsDiv = document.getElementById('uploadRequirements');
        if (!requirementsDiv) return;
        
        let requirements = '';
        
        switch (ownershipType) {
            case 'owner':
                requirements = `
                    <div class="requirement-item">
                        <h5>📋 Required Documents for Property Owner:</h5>
                        <ul>
                            <li>Original Land Title (TCT/OCT)</li>
                            <li>Valid Government ID</li>
                            <li>Tax Declaration</li>
                            <li>Real Property Tax Receipt (Current Year)</li>
                            <li>Barangay Clearance</li>
                        </ul>
                    </div>
                `;
                break;
            case 'agent':
                requirements = `
                    <div class="requirement-item">
                        <h5>🏢 Required Documents for Authorized Agent:</h5>
                        <ul>
                            <li>Special Power of Attorney (SPA)</li>
                            <li>Original Land Title (TCT/OCT)</li>
                            <li>Valid Government ID (Agent)</li>
                            <li>Valid Government ID (Property Owner)</li>
                            <li>Authorization Letter from Owner</li>
                        </ul>
                    </div>
                `;
                break;
            case 'heir':
                requirements = `
                    <div class="requirement-item">
                        <h5>👨‍👩‍👧‍👦 Required Documents for Heir/Successor:</h5>
                        <ul>
                            <li>Death Certificate of Previous Owner</li>
                            <li>Extrajudicial Settlement of Estate</li>
                            <li>Original Land Title (TCT/OCT)</li>
                            <li>Valid Government ID</li>
                            <li>Affidavit of Heirship</li>
                        </ul>
                    </div>
                `;
                break;
        }
        
        requirementsDiv.innerHTML = requirements;
    },
    
    // Handle file upload
    handleFileUpload(files) {
        const uploadedFilesDiv = document.getElementById('uploadedFiles');
        if (!uploadedFilesDiv) return;
        
        Array.from(files).forEach(file => {
            const fileItem = document.createElement('div');
            fileItem.className = 'uploaded-file-item';
            fileItem.innerHTML = `
                <div class="file-info">
                    <span class="file-name">${file.name}</span>
                    <span class="file-size">${this.formatFileSize(file.size)}</span>
                </div>
                <button type="button" class="remove-file" onclick="this.parentElement.remove()">✕</button>
            `;
            uploadedFilesDiv.appendChild(fileItem);
        });
    },
    
    // Format file size
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },
    
    // Populate review section
    populateReviewSection() {
        const form = document.getElementById('sellPropertyForm');
        const formData = new FormData(form);
        
        // Property review
        const propertyReview = document.getElementById('propertyReview');
        if (propertyReview) {
            propertyReview.innerHTML = `
                <div class="review-item">
                    <strong>Title:</strong> ${formData.get('title') || 'Not specified'}
                </div>
                <div class="review-item">
                    <strong>Price:</strong> ₱${Number(formData.get('price') || 0).toLocaleString()}
                </div>
                <div class="review-item">
                    <strong>Area:</strong> ${Number(formData.get('area_sqm') || 0).toLocaleString()} sqm
                </div>
                <div class="review-item">
                    <strong>Location:</strong> ${formData.get('city')}, ${formData.get('province')}, ${formData.get('region')}
                </div>
                <div class="review-item">
                    <strong>Zoning:</strong> ${formData.get('zoning') || 'Not specified'}
                </div>
                <div class="review-item">
                    <strong>Type:</strong> ${formData.get('type') === 'sale' ? 'For Sale' : 'Auction'}
                </div>
            `;
        }
        
        // Ownership review
        const ownershipReview = document.getElementById('ownershipReview');
        if (ownershipReview) {
            const ownershipType = formData.get('ownership_type');
            const ownershipLabels = {
                'owner': 'Legal Owner',
                'agent': 'Authorized Agent',
                'heir': 'Heir/Successor'
            };
            
            ownershipReview.innerHTML = `
                <div class="review-item">
                    <strong>Ownership Type:</strong> ${ownershipLabels[ownershipType] || 'Not specified'}
                </div>
                <div class="review-item">
                    <strong>Documents Uploaded:</strong> ${document.querySelectorAll('.uploaded-file-item').length} files
                </div>
            `;
        }
    },
    
    // Setup property calculations
    setupPropertyCalculations() {
        const priceInput = document.getElementById('propertyPrice');
        const areaInput = document.getElementById('propertyArea');
        const pricePerSqmInput = document.getElementById('pricePerSqm');
        const areaHectaresInput = document.getElementById('propertyAreaHectares');
        
        if (priceInput && areaInput && pricePerSqmInput && areaHectaresInput) {
            const calculateValues = () => {
                const price = parseFloat(priceInput.value) || 0;
                const area = parseFloat(areaInput.value) || 0;
                
                if (price > 0 && area > 0) {
                    // Calculate price per sqm
                    const pricePerSqm = Math.round(price / area);
                    pricePerSqmInput.value = pricePerSqm;
                }
                
                if (area > 0) {
                    // Calculate hectares (1 hectare = 10,000 sqm)
                    const hectares = (area / 10000).toFixed(4);
                    areaHectaresInput.value = hectares;
                }
            };
            
            priceInput.addEventListener('input', calculateValues);
            areaInput.addEventListener('input', calculateValues);
        }
    },
    
    // Handle photo upload
    handlePhotoUpload(files) {
        const uploadedPhotosDiv = document.getElementById('uploadedPhotos');
        if (!uploadedPhotosDiv) return;
        
        Array.from(files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const photoItem = document.createElement('div');
                    photoItem.className = 'uploaded-photo-item';
                    photoItem.innerHTML = `
                        <img src="${e.target.result}" alt="Property photo">
                        <div class="photo-info">
                            <div class="file-name">${file.name}</div>
                            <div class="file-size">${this.formatFileSize(file.size)}</div>
                        </div>
                        <button type="button" class="remove-photo" onclick="this.parentElement.remove()">✕</button>
                        <button type="button" class="set-primary-photo" onclick="App.setPrimaryPhoto(this)">Set Primary</button>
                    `;
                    uploadedPhotosDiv.appendChild(photoItem);
                    
                    // Set first photo as primary automatically
                    if (uploadedPhotosDiv.children.length === 1) {
                        this.setPrimaryPhoto(photoItem.querySelector('.set-primary-photo'));
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    },
    
    // Set primary photo
    setPrimaryPhoto(button) {
        // Remove primary class from all photos
        document.querySelectorAll('.uploaded-photo-item').forEach(item => {
            item.classList.remove('primary-photo');
        });
        
        // Add primary class to selected photo
        const photoItem = button.closest('.uploaded-photo-item');
        photoItem.classList.add('primary-photo');
        
        // Update button text
        document.querySelectorAll('.set-primary-photo').forEach(btn => {
            btn.textContent = 'Set Primary';
        });
        button.textContent = 'Primary';
    },
    
    showOfferModal(property) {
        this.showAlert('Coming Soon', 'Make offer feature will be available soon.');
    },
    
    showBidModal(property) {
        this.showAlert('Coming Soon', 'Auction bidding feature will be available soon.');
    },
    
    startConversation(userId, propertyId) {
        if (this.isLoggedIn) {
            const params = new URLSearchParams();
            if (userId) params.append('user_id', userId);
            if (propertyId) params.append('property_id', propertyId);
            
            window.location.href = `messaging.php?${params.toString()}`;
        } else {
            this.showAlert('Login Required', 'Please login to start a conversation.');
        }
    },
    
    showKYCWarning() {
        // Show a subtle warning about KYC verification
        const warning = document.createElement('div');
        warning.className = 'kyc-warning';
        warning.innerHTML = `
            <div class="warning-content">
                <span class="warning-icon">⚠️</span>
                <span>Complete KYC verification to make offers and participate in auctions.</span>
                <button class="btn-link" onclick="App.showAlert('Coming Soon', 'KYC verification will be available soon.')">Verify Now</button>
            </div>
        `;
        
        const header = document.querySelector('.site-header');
        if (header) {
            header.insertAdjacentElement('afterend', warning);
        }
    },
    
    // Load user notifications (placeholder)
    async loadUserNotifications() {
        // This will be implemented when notification system is ready
    },
    
    // Load unread message count (placeholder)
    async loadUnreadMessageCount() {
        // This will be implemented when messaging system is ready
    },
    
    // Open filters panel
    openFiltersPanel() {
        const panel = document.getElementById('filtersPanel');
        if (panel) {
            panel.style.display = 'flex';
        }
    },
    
    // Perform hero search
    performHeroSearch() {
        const searchValue = document.getElementById('heroSearch')?.value.trim();
        if (searchValue) {
            this.currentFilters = { search: searchValue };
            this.currentPage = 1;
            this.loadProperties(1);
            this.scrollToListings();
        }
    }
};

// Global function to close filters panel
function closeFiltersPanel() {
    const panel = document.getElementById('filtersPanel');
    if (panel) {
        panel.style.display = 'none';
    }
}

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});

// Handle alert modal OK button
document.addEventListener('DOMContentLoaded', () => {
    const alertOkBtn = document.getElementById('alertOkBtn');
    if (alertOkBtn) {
        alertOkBtn.addEventListener('click', () => {
            App.closeModal('alertModal');
        });
    }
});

// Export for global access
window.App = App;
