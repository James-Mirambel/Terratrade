/**
 * My Listings Management JavaScript
 * TerraTrade Land Trading System
 */

class MyListingsManager {
    constructor() {
        this.listings = [];
        this.filteredListings = [];
        this.selectedListings = [];
        this.currentEditingId = null;
        this.apiUrl = window.location.origin + '/Terratrade/api/my-listings-api.php';
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadListings();
    }
    
    setupEventListeners() {
        // Search and filters
        document.getElementById('searchInput').addEventListener('input', (e) => {
            this.filterListings();
        });
        
        document.getElementById('statusFilter').addEventListener('change', (e) => {
            this.filterListings();
        });
        
        document.getElementById('typeFilter').addEventListener('change', (e) => {
            this.filterListings();
        });
        
        // Form submission removed - redirects to main page
        
        // Modal close handlers removed
        
        // Keyboard shortcuts removed
    }
    
    async loadListings() {
        try {
            this.showLoadingState();
            
            // Use the test load listings endpoint
            const response = await fetch(window.location.origin + '/Terratrade/api/test-load-listings.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON Response:', text);
                throw new Error('Server returned non-JSON response');
            }
            
            const data = await response.json();
            console.log('Load listings response:', data);
            
            if (data.success) {
                this.listings = data.properties || [];
                this.filteredListings = [...this.listings];
                this.updateStats(data.stats);
                this.renderListings();
            } else {
                this.showError('Failed to load listings: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Load listings error:', error);
            this.showError('Failed to load listings. Please try again.');
        }
    }
    
    updateStats(serverStats = null) {
        let stats;
        
        if (serverStats) {
            stats = serverStats;
        } else {
            stats = {
                total_listings: this.listings.length,
                active_listings: this.listings.filter(l => l.status === 'active').length,
                pending_listings: this.listings.filter(l => l.status === 'pending').length,
                total_value: this.listings.reduce((sum, l) => sum + parseFloat(l.price || 0), 0)
            };
        }
        
        document.getElementById('totalListings').textContent = stats.total_listings || 0;
        document.getElementById('activeListings').textContent = stats.active_listings || 0;
        document.getElementById('pendingListings').textContent = stats.pending_listings || 0;
        document.getElementById('totalValue').textContent = '₱' + (stats.total_value || 0).toLocaleString();
    }
    
    filterListings() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value;
        const typeFilter = document.getElementById('typeFilter').value;
        
        this.filteredListings = this.listings.filter(listing => {
            const matchesSearch = !searchTerm || 
                listing.title.toLowerCase().includes(searchTerm) ||
                listing.description.toLowerCase().includes(searchTerm) ||
                listing.city.toLowerCase().includes(searchTerm) ||
                listing.province.toLowerCase().includes(searchTerm);
            
            const matchesStatus = !statusFilter || listing.status === statusFilter;
            const matchesType = !typeFilter || listing.type === typeFilter;
            
            return matchesSearch && matchesStatus && matchesType;
        });
        
        this.renderListings();
    }
    
    renderListings() {
        const grid = document.getElementById('listingsGrid');
        const emptyState = document.getElementById('emptyState');
        
        if (this.filteredListings.length === 0) {
            grid.innerHTML = '';
            emptyState.classList.remove('hidden');
            return;
        }
        
        emptyState.classList.add('hidden');
        
        grid.innerHTML = this.filteredListings.map(listing => `
            <div class="listing-card" data-id="${listing.id}" onclick="myListings.viewProperty(${listing.id})" style="cursor: pointer;">
                <div class="listing-image">
                    🏞️
                    <div class="listing-status status-${listing.status}">
                        ${this.getStatusLabel(listing.status)}
                    </div>
                </div>
                <div class="listing-content">
                    <h3 class="listing-title">${listing.title}</h3>
                    <div class="listing-price">₱${parseFloat(listing.price).toLocaleString()}</div>
                    
                    <div class="listing-details">
                        <div class="detail-item">
                            <span class="detail-label">Area:</span>
                            ${listing.hectares}ha
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Zoning:</span>
                            ${listing.zoning || 'N/A'}
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Type:</span>
                            For Sale
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Created:</span>
                            ${this.formatDate(listing.created_at)}
                        </div>
                    </div>
                    
                    <div class="listing-location">
                        📍 ${listing.city}, ${listing.province}
                    </div>
                    
                    <div class="listing-stats">
                        <div class="stat-item">
                            <div class="stat-value">${listing.views_count || 0}</div>
                            <div class="stat-label">Views</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${listing.offers_count || 0}</div>
                            <div class="stat-label">Offers</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value">${this.getDaysListed(listing.created_at)}</div>
                            <div class="stat-label">Days</div>
                        </div>
                    </div>
                    
                    <div class="listing-actions">
                        <button class="action-btn secondary" onclick="event.stopPropagation(); myListings.editListing(${listing.id})">
                            ✏️ Edit
                        </button>
                        <button class="action-btn primary" onclick="event.stopPropagation(); myListings.viewOffers(${listing.id})">
                            👁️ Offers
                        </button>
                        <button class="action-btn ${listing.status === 'active' ? 'secondary' : 'primary'}" 
                                onclick="event.stopPropagation(); myListings.toggleStatus(${listing.id})">
                            ${listing.status === 'active' ? '⏸️ Pause' : '▶️ Activate'}
                        </button>
                        <button class="action-btn danger" onclick="event.stopPropagation(); myListings.deleteListing(${listing.id})">
                            🗑️ Delete
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    showLoadingState() {
        const grid = document.getElementById('listingsGrid');
        grid.innerHTML = `
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Loading your listings...</p>
            </div>
        `;
    }
    
    getStatusLabel(status) {
        const labels = {
            'active': 'Active',
            'pending': 'Pending',
            'sold': 'Sold',
            'withdrawn': 'Withdrawn'
        };
        return labels[status] || status;
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        });
    }
    
    getDaysListed(dateString) {
        const created = new Date(dateString);
        const now = new Date();
        const diffTime = Math.abs(now - created);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return diffDays;
    }
    
    // API Methods
    async apiRequest(endpoint, options = {}) {
        const url = `${this.apiUrl}${endpoint}`;
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const finalOptions = { ...defaultOptions, ...options };
        
        try {
            console.log('API Request:', url, finalOptions);
            const response = await fetch(url, finalOptions);
            
            if (!response.ok) {
                console.error('HTTP Error:', response.status, response.statusText);
            }
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON Response:', text);
                throw new Error('Server returned non-JSON response');
            }
            
            const data = await response.json();
            console.log('API Response:', data);
            return data;
        } catch (error) {
            console.error('API Request Error:', error);
            throw error;
        }
    }
    
    // Modal Methods
    showModal(modalId) {
        document.getElementById(modalId).classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    closeModal() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.classList.add('hidden');
        });
        document.body.style.overflow = '';
    }
    
    // resetForm removed - no longer needed
    
    // CRUD Operations - form submission removed, redirects to main page
    
    async editListing(id) {
        // Redirect to main page for editing (functionality to be implemented)
        window.location.href = `index.php?edit=${id}`;
    }
    
    async deleteListing(id) {
        if (!confirm('Are you sure you want to delete this listing? This action cannot be undone.')) {
            return;
        }
        
        try {
            const response = await this.apiRequest(`?action=delete&id=${id}`, {
                method: 'DELETE'
            });
            
            if (response.success) {
                this.showSuccess('Listing deleted successfully!');
                this.loadListings();
            } else {
                this.showError(response.error || 'Failed to delete listing');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showError('Failed to delete listing. Please try again.');
        }
    }
    
    async toggleStatus(id) {
        const listing = this.listings.find(l => l.id === id);
        if (!listing) return;
        
        try {
            const response = await this.apiRequest(`?action=toggle-status&id=${id}`, {
                method: 'POST',
                body: JSON.stringify({})
            });
            
            if (response.success) {
                this.showSuccess(response.message || 'Status updated successfully!');
                this.loadListings();
            } else {
                this.showError(response.error || 'Failed to update listing status');
            }
        } catch (error) {
            console.error('Toggle status error:', error);
            this.showError('Failed to update listing status. Please try again.');
        }
    }
    
    viewOffers(id) {
        // Redirect to view offers page for this property
        window.location.href = `view-offers.php?id=${id}`;
    }
    
    viewProperty(id) {
        // Redirect to property details page
        window.location.href = `property-details.php?id=${id}`;
    }
    
    // Utility Methods - form loading removed
    
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">
                    ${type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️'}
                </span>
                <span class="notification-message">${message}</span>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">×</button>
        `;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }
}

// Global functions for HTML onclick handlers
function showAddListingModal() {
    window.location.href = 'index.php#add-listing';
}

function showBulkActions() {
    myListings.showModal('bulkActionsModal');
}

function closeBulkModal() {
    document.getElementById('bulkActionsModal').classList.add('hidden');
}

function closeModal() {
    myListings.closeModal();
}

function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('hidden');
}

function showProfile() {
    window.location.href = 'profile.php';
}

// My Offers functionality removed - will be reimplemented properly

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'api/logout.php';
    }
}

// Bulk action functions
function bulkActivate() {
    alert('Bulk activate functionality coming soon!');
}

function bulkDeactivate() {
    alert('Bulk deactivate functionality coming soon!');
}

function bulkDelete() {
    alert('Bulk delete functionality coming soon!');
}

function exportSelected() {
    alert('Export functionality coming soon!');
}

// Initialize the application
let myListings;
document.addEventListener('DOMContentLoaded', () => {
    myListings = new MyListingsManager();
});

// Close dropdown when clicking outside
document.addEventListener('click', (e) => {
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.getElementById('userDropdown');
    
    if (!userMenu.contains(e.target)) {
        dropdown.classList.add('hidden');
    }
});

// Add notification styles dynamically
const notificationStyles = `
<style>
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 12px;
    padding: 16px 20px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-width: 300px;
    z-index: 3000;
    animation: slideIn 0.3s ease-out;
}

.notification-success {
    border-left: 4px solid #27ae60;
}

.notification-error {
    border-left: 4px solid #e74c3c;
}

.notification-info {
    border-left: 4px solid #3498db;
}

.notification-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.notification-icon {
    font-size: 18px;
}

.notification-message {
    font-size: 14px;
    color: #2c3e50;
    font-weight: 500;
}

.notification-close {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
    color: #6c757d;
    padding: 0;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.3s ease;
}

.notification-close:hover {
    background: #f8f9fa;
    color: #495057;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', notificationStyles);

// Price Calculation Functions
const zoningSuggestedPrices = {
    'Residential': 50000,
    'Commercial': 150000,
    'Agricultural': 2000,
    'Industrial': 30000,
    'Mixed': 80000,
    'Institutional': 40000,
    'Tourism': 60000,
    'Special Economic Zone': 100000
};

const zoningPriceRanges = {
    'Residential': '₱5,000 - ₱400,000/sqm (Metro Manila: ₱96,000 - ₱400,000)',
    'Commercial': '₱30,000 - ₱940,000/sqm (Metro Manila CBD: ₱337,000 - ₱940,000)',
    'Agricultural': '₱100 - ₱10,000/sqm (Near urban: ₱5,000 - ₱10,000)',
    'Industrial': '₱10,000 - ₱80,000/sqm (Near economic zones: ₱20,000 - ₱80,000)',
    'Mixed': '₱30,000 - ₱300,000/sqm (Metro Manila: ₱100,000 - ₱300,000)',
    'Institutional': '₱20,000 - ₱100,000/sqm',
    'Tourism': '₱30,000 - ₱150,000/sqm',
    'Special Economic Zone': '₱50,000 - ₱200,000/sqm'
};

function updateSuggestedPrice() {
    const zoning = document.getElementById('zoning').value;
    const suggestedPriceSpan = document.getElementById('suggestedPrice');
    const zoningHint = document.getElementById('zoningHint');
    const pricePerSqmInput = document.getElementById('price_per_sqm');
    
    if (zoning && zoningSuggestedPrices[zoning]) {
        const suggestedPrice = zoningSuggestedPrices[zoning];
        suggestedPriceSpan.textContent = '₱' + suggestedPrice.toLocaleString() + '/sqm';
        suggestedPriceSpan.style.color = '#667eea';
        suggestedPriceSpan.style.fontWeight = '600';
        
        // Price range display removed
        
        // Always auto-fill the price per sqm with suggested value when zoning changes
        pricePerSqmInput.value = suggestedPrice;
        calculateTotalPrice();
    } else {
        suggestedPriceSpan.textContent = '-';
        zoningHint.textContent = '';
    }
}

function calculateTotalPrice() {
    const area = parseFloat(document.getElementById('area_sqm').value) || 0;
    const pricePerSqm = parseFloat(document.getElementById('price_per_sqm').value) || 0;
    const priceInput = document.getElementById('price');
    
    if (area > 0 && pricePerSqm > 0) {
        const totalPrice = area * pricePerSqm;
        priceInput.value = Math.round(totalPrice);
    } else {
        priceInput.value = '';
    }
}
