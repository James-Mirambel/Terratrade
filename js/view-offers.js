/**
 * View Offers Management JavaScript
 * TerraTrade Land Trading System
 */

class ViewOffersManager {
    constructor() {
        this.offers = [];
        this.filteredOffers = [];
        this.properties = [];
        this.currentOfferId = null;
        this.apiUrl = 'api/offers-api.php';
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadOffers();
        this.loadProperties();
    }
    
    setupEventListeners() {
        // Filters
        document.getElementById('statusFilter').addEventListener('change', () => {
            this.filterOffers();
        });
        
        document.getElementById('propertyFilter').addEventListener('change', () => {
            this.filterOffers();
        });
        
        document.getElementById('dateFilter').addEventListener('change', () => {
            this.filterOffers();
        });
        
        // Counter offer form
        document.getElementById('counterOfferForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleCounterOffer();
        });
        
        // Modal close handlers
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal();
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }
    
    async loadOffers() {
        try {
            this.showLoadingState();
            
            // Build URL with proper parameters
            const params = new URLSearchParams();
            params.append('action', 'load');
            if (window.propertyId) {
                params.append('property_id', window.propertyId);
            }
            
            const response = await fetch(`${this.apiUrl}?${params}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            console.log('Response status:', response.status);
            console.log('Response headers:', [...response.headers.entries()]);
            
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON Response:', text);
                throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
            }
            
            const data = await response.json();
            console.log('Load offers response:', data);
            
            if (data.success) {
                this.offers = data.offers || [];
                this.filteredOffers = [...this.offers];
                
                // Filter by property if viewing specific property
                if (window.propertyId) {
                    this.filteredOffers = this.offers.filter(offer => offer.property_id == window.propertyId);
                }
                
                this.updateStats(data.stats);
                this.renderOffers();
            } else {
                this.showError('Failed to load offers: ' + (data.error || 'Unknown error'));
                console.error('API Error Details:', data);
            }
        } catch (error) {
            console.error('Load offers error:', error);
            this.showError('Failed to load offers. Please try again. Error: ' + error.message);
        }
    }
    
    async loadProperties() {
        try {
            const response = await fetch(window.location.origin + '/Terratrade/api/test-load-listings.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            if (data.success) {
                this.properties = data.properties || [];
                this.populatePropertyFilter();
            }
        } catch (error) {
            console.error('Load properties error:', error);
        }
    }
    
    populatePropertyFilter() {
        const propertyFilter = document.getElementById('propertyFilter');
        if (!propertyFilter || window.propertyId) return;
        
        // Clear existing options except the first one
        while (propertyFilter.children.length > 1) {
            propertyFilter.removeChild(propertyFilter.lastChild);
        }
        
        this.properties.forEach(property => {
            const option = document.createElement('option');
            option.value = property.id;
            option.textContent = property.title;
            propertyFilter.appendChild(option);
        });
    }
    
    updateStats(serverStats = null) {
        let stats;
        
        if (serverStats) {
            stats = serverStats;
        } else {
            stats = {
                total_offers: this.offers.length,
                pending_offers: this.offers.filter(o => o.status === 'pending').length,
                accepted_offers: this.offers.filter(o => o.status === 'accepted').length,
                highest_offer: Math.max(...this.offers.map(o => parseFloat(o.offer_amount || 0)), 0)
            };
        }
        
        document.getElementById('totalOffers').textContent = stats.total_offers || 0;
        document.getElementById('pendingOffers').textContent = stats.pending_offers || 0;
        document.getElementById('acceptedOffers').textContent = stats.accepted_offers || 0;
        document.getElementById('highestOffer').textContent = '₱' + (stats.highest_offer || 0).toLocaleString();
    }
    
    filterOffers() {
        const statusFilter = document.getElementById('statusFilter').value;
        const propertyFilter = document.getElementById('propertyFilter').value;
        const dateFilter = document.getElementById('dateFilter').value;
        
        this.filteredOffers = this.offers.filter(offer => {
            const matchesStatus = !statusFilter || offer.status === statusFilter;
            const matchesProperty = !propertyFilter || offer.property_id == propertyFilter;
            const matchesDate = !dateFilter || offer.created_at.startsWith(dateFilter);
            
            return matchesStatus && matchesProperty && matchesDate;
        });
        
        this.renderOffers();
        this.updateStats();
    }
    
    renderOffers() {
        const container = document.getElementById('offersContainer');
        const emptyState = document.getElementById('emptyState');
        
        if (this.filteredOffers.length === 0) {
            container.innerHTML = '';
            emptyState.classList.remove('hidden');
            return;
        }
        
        emptyState.classList.add('hidden');
        
        container.innerHTML = this.filteredOffers.map(offer => `
            <div class="offer-card" data-id="${offer.id}">
                <div class="offer-header">
                    <div class="offer-info">
                        <div class="offer-avatar">
                            ${this.getInitials(offer.buyer_name)}
                        </div>
                        <div class="offer-details">
                            <h4>${offer.buyer_name}</h4>
                            <div class="offer-meta">
                                ${this.formatDate(offer.created_at)} • ${offer.buyer_email}
                            </div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <div class="offer-amount">₱${parseFloat(offer.offer_amount).toLocaleString()}</div>
                        <div class="offer-status status-${offer.status}">
                            ${this.getStatusLabel(offer.status)}
                        </div>
                    </div>
                </div>
                
                <div class="offer-content">
                    ${!window.propertyId ? `
                        <div class="offer-property">
                            <strong>Property:</strong> ${offer.property_title}
                        </div>
                    ` : ''}
                    
                    ${offer.message ? `
                        <div class="offer-message">
                            "${offer.message}"
                        </div>
                    ` : ''}
                    
                    <div class="offer-actions">
                        ${offer.status === 'pending' ? `
                            <button class="action-btn accept" onclick="viewOffers.acceptOffer(${offer.id})">
                                ✅ Accept
                            </button>
                            <button class="action-btn reject" onclick="viewOffers.rejectOffer(${offer.id})">
                                ❌ Reject
                            </button>
                            <button class="action-btn counter" onclick="viewOffers.showCounterOffer(${offer.id})">
                                💰 Counter
                            </button>
                        ` : ''}
                        
                        <button class="action-btn message" onclick="viewOffers.messageOffer(${offer.id})">
                            💬 Message
                        </button>
                        <button class="action-btn view" onclick="viewOffers.viewOfferDetails(${offer.id})">
                            👁️ Details
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    showLoadingState() {
        const container = document.getElementById('offersContainer');
        container.innerHTML = `
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Loading offers...</p>
            </div>
        `;
    }
    
    getInitials(name) {
        return name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
    }
    
    getStatusLabel(status) {
        const labels = {
            'pending': 'Pending',
            'accepted': 'Accepted',
            'rejected': 'Rejected',
            'countered': 'Countered'
        };
        return labels[status] || status;
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }
    
    // Offer Actions
    async acceptOffer(offerId) {
        if (!confirm('Are you sure you want to accept this offer? This action cannot be undone.')) {
            return;
        }
        
        try {
            const response = await fetch(`${this.apiUrl}?action=accept&id=${offerId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('Offer accepted successfully!');
                this.loadOffers();
            } else {
                this.showError(data.error || 'Failed to accept offer');
            }
        } catch (error) {
            console.error('Accept offer error:', error);
            this.showError('Failed to accept offer. Please try again.');
        }
    }
    
    async rejectOffer(offerId) {
        if (!confirm('Are you sure you want to reject this offer?')) {
            return;
        }
        
        try {
            const response = await fetch(`${this.apiUrl}?action=reject&id=${offerId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showSuccess('Offer rejected successfully!');
                this.loadOffers();
            } else {
                this.showError(data.error || 'Failed to reject offer');
            }
        } catch (error) {
            console.error('Reject offer error:', error);
            this.showError('Failed to reject offer. Please try again.');
        }
    }
    
    showCounterOffer(offerId) {
        this.currentOfferId = offerId;
        const offer = this.offers.find(o => o.id === offerId);
        
        if (offer) {
            // Pre-fill with a suggested counter amount (10% higher than original offer)
            const suggestedAmount = Math.round(parseFloat(offer.offer_amount) * 1.1);
            document.getElementById('counterAmount').value = suggestedAmount;
        }
        
        this.showModal('counterOfferModal');
    }
    
    async handleCounterOffer() {
        const formData = new FormData(document.getElementById('counterOfferForm'));
        const data = {
            counter_amount: formData.get('counter_amount'),
            message: formData.get('message')
        };
        
        try {
            const response = await fetch(`${this.apiUrl}?action=counter&id=${this.currentOfferId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('Counter offer sent successfully!');
                this.closeModal();
                this.loadOffers();
            } else {
                this.showError(result.error || 'Failed to send counter offer');
            }
        } catch (error) {
            console.error('Counter offer error:', error);
            this.showError('Failed to send counter offer. Please try again.');
        }
    }
    
    messageOffer(offerId) {
        const offer = this.offers.find(o => o.id === offerId);
        if (offer) {
            // Redirect to messaging with the buyer
            window.location.href = `messaging.php?user=${offer.buyer_id}`;
        }
    }
    
    viewOfferDetails(offerId) {
        const offer = this.offers.find(o => o.id === offerId);
        if (!offer) return;
        
        const property = this.properties.find(p => p.id == offer.property_id);
        
        document.getElementById('offerDetails').innerHTML = `
            <div class="detail-section">
                <h4>Offer Information</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Offer Amount</div>
                        <div class="detail-value">₱${parseFloat(offer.offer_amount).toLocaleString()}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">
                            <span class="offer-status status-${offer.status}">
                                ${this.getStatusLabel(offer.status)}
                            </span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Date Submitted</div>
                        <div class="detail-value">${this.formatDate(offer.created_at)}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Last Updated</div>
                        <div class="detail-value">${this.formatDate(offer.updated_at)}</div>
                    </div>
                </div>
            </div>
            
            <div class="detail-section">
                <h4>Buyer Information</h4>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Name</div>
                        <div class="detail-value">${offer.buyer_name}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email</div>
                        <div class="detail-value">${offer.buyer_email}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Phone</div>
                        <div class="detail-value">${offer.buyer_phone || 'Not provided'}</div>
                    </div>
                </div>
            </div>
            
            ${window.propertyData ? `
                <div class="detail-section">
                    <h4>Property Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">Property</div>
                            <div class="detail-value">${window.propertyData.title}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Listed Price</div>
                            <div class="detail-value">₱${parseFloat(window.propertyData.price || 0).toLocaleString()}</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Area</div>
                            <div class="detail-value">${parseFloat(window.propertyData.area_sqm || 0).toLocaleString()} sqm</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Location</div>
                            <div class="detail-value">${window.propertyData.city || ''}, ${window.propertyData.province || ''}</div>
                        </div>
                    </div>
                </div>
            ` : ''}
            
            ${offer.message ? `
                <div class="detail-section">
                    <h4>Buyer's Message</h4>
                    <div class="offer-message">${offer.message}</div>
                </div>
            ` : ''}
            
            <div class="modal-actions">
                ${offer.status === 'pending' ? `
                    <button class="btn-secondary" onclick="viewOffers.acceptOffer(${offer.id}); viewOffers.closeModal();">
                        ✅ Accept Offer
                    </button>
                    <button class="btn-secondary" onclick="viewOffers.rejectOffer(${offer.id}); viewOffers.closeModal();">
                        ❌ Reject Offer
                    </button>
                    <button class="btn-primary" onclick="viewOffers.closeModal(); viewOffers.showCounterOffer(${offer.id});">
                        💰 Make Counter Offer
                    </button>
                ` : `
                    <button class="btn-primary" onclick="viewOffers.messageOffer(${offer.id});">
                        💬 Message Buyer
                    </button>
                `}
            </div>
        `;
        
        this.showModal('offerModal');
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
        this.currentOfferId = null;
    }
    
    // Utility Methods
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
function refreshOffers() {
    viewOffers.loadOffers();
}

function exportOffers() {
    // Implement export functionality
    alert('Export functionality coming soon!');
}

function closeModal() {
    viewOffers.closeModal();
}

function closeCounterModal() {
    viewOffers.closeModal();
}

function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('hidden');
}

function showProfile() {
    alert('Profile functionality coming soon!');
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'api/logout.php';
    }
}

// Initialize the application
let viewOffers;
document.addEventListener('DOMContentLoaded', () => {
    viewOffers = new ViewOffersManager();
});

// Close dropdown when clicking outside
document.addEventListener('click', (e) => {
    const userMenu = document.querySelector('.user-menu');
    const dropdown = document.getElementById('userDropdown');
    
    if (userMenu && dropdown && !userMenu.contains(e.target)) {
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
