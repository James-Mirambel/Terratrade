/**
 * TerraTrade API Client
 * Full-stack PHP/MySQL backend integration
 */

class TerraTrade_API {
    constructor() {
        this.baseURL = '/Terratrade/api';
        this.user = null;
        this.init();
    }
    
    async init() {
        // Check if user is logged in
        try {
            const response = await this.request('auth/me');
            if (response.user) {
                this.user = response.user;
                this.updateAuthState();
            }
        } catch (error) {
            // User not logged in
            this.user = null;
        }
    }
    
    // Generic API request method
    async request(endpoint, options = {}) {
        const url = `${this.baseURL}/${endpoint}`;
        const config = {
            method: options.method || 'GET',
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };
        
        if (config.method !== 'GET' && options.data) {
            config.body = JSON.stringify(options.data);
        }
        
        try {
            const response = await fetch(url, config);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Request failed');
            }
            
            return data;
        } catch (error) {
            console.error('API Request failed:', error);
            throw error;
        }
    }
    
    // Authentication methods
    async login(email, password = 'demo') {
        try {
            const response = await this.request('auth/login', {
                method: 'POST',
                data: { email, password }
            });
            
            this.user = response.user;
            this.updateAuthState();
            return response;
        } catch (error) {
            throw error;
        }
    }
    
    async register(name, email, password = 'demo', kyc = false) {
        try {
            const response = await this.request('auth/register', {
                method: 'POST',
                data: { name, email, password, kyc }
            });
            
            this.user = response.user;
            this.updateAuthState();
            return response;
        } catch (error) {
            throw error;
        }
    }
    
    async logout() {
        try {
            await this.request('auth/logout', { method: 'POST' });
            this.user = null;
            this.updateAuthState();
        } catch (error) {
            // Even if logout fails, clear local state
            this.user = null;
            this.updateAuthState();
        }
    }
    
    // Listing methods
    async getListings(filters = {}) {
        const queryParams = new URLSearchParams(filters).toString();
        const endpoint = queryParams ? `listings/all?${queryParams}` : 'listings/all';
        return await this.request(endpoint);
    }
    
    async createListing(listingData) {
        return await this.request('listings/create', {
            method: 'POST',
            data: listingData
        });
    }
    
    async getMyListings() {
        return await this.request('listings/my');
    }
    
    async getListing(id) {
        return await this.request(`listings/view/${id}`);
    }
    
    async addToFavorites(listingId) {
        return await this.request(`listings/favorite/${listingId}`, {
            method: 'POST'
        });
    }
    
    async removeFromFavorites(listingId) {
        return await this.request(`listings/favorite/${listingId}`, {
            method: 'DELETE'
        });
    }
    
    async getFavorites() {
        return await this.request('listings/favorites');
    }
    
    // Offer methods
    async createOffer(offerData) {
        return await this.request('offers/create', {
            method: 'POST',
            data: offerData
        });
    }
    
    async getMyOffers() {
        return await this.request('offers/my');
    }
    
    async getReceivedOffers() {
        return await this.request('offers/received');
    }
    
    async respondToOffer(offerId, response, counterPrice = null, sellerResponse = '') {
        return await this.request(`offers/respond/${offerId}`, {
            method: 'POST',
            data: { response, counter_price: counterPrice, seller_response: sellerResponse }
        });
    }
    
    async withdrawOffer(offerId) {
        return await this.request(`offers/withdraw/${offerId}`, {
            method: 'POST'
        });
    }
    
    // Contract methods
    async getMyContracts() {
        return await this.request('contracts/my');
    }
    
    async getContract(contractId) {
        return await this.request(`contracts/view/${contractId}`);
    }
    
    async signContract(contractId) {
        return await this.request(`contracts/sign/${contractId}`, {
            method: 'POST'
        });
    }
    
    // Message methods
    async sendMessage(messageData) {
        return await this.request('messages/send', {
            method: 'POST',
            data: messageData
        });
    }
    
    async getMessages() {
        return await this.request('messages/inbox');
    }
    
    async markMessageAsRead(messageId) {
        return await this.request(`messages/read/${messageId}`, {
            method: 'POST'
        });
    }
    
    // Notification methods
    async getNotifications() {
        return await this.request('notifications');
    }
    
    async markNotificationAsRead(notificationId) {
        return await this.request(`notifications/read/${notificationId}`, {
            method: 'POST'
        });
    }
    
    async markAllNotificationsAsRead() {
        return await this.request('notifications/read-all', {
            method: 'POST'
        });
    }
    
    // User methods
    async updateProfile(profileData) {
        return await this.request('user/profile', {
            method: 'PUT',
            data: profileData
        });
    }
    
    // Helper methods
    isLoggedIn() {
        return !!this.user;
    }
    
    getCurrentUser() {
        return this.user;
    }
    
    updateAuthState() {
        // Update global state object for compatibility with existing frontend
        if (window.state) {
            window.state.user = this.user;
            
            // Update UI
            if (window.updateAuthArea) {
                window.updateAuthArea();
            }
            
            // Save to localStorage for persistence
            if (this.user) {
                localStorage.setItem('terratrade_user', JSON.stringify(this.user));
            } else {
                localStorage.removeItem('terratrade_user');
            }
        }
    }
    
    // Load user from localStorage on page refresh
    loadUserFromStorage() {
        const storedUser = localStorage.getItem('terratrade_user');
        if (storedUser) {
            try {
                this.user = JSON.parse(storedUser);
                this.updateAuthState();
            } catch (error) {
                localStorage.removeItem('terratrade_user');
            }
        }
    }
}

// Initialize API client
const API = new TerraTrade_API();

// Load user from storage on page load
document.addEventListener('DOMContentLoaded', () => {
    API.loadUserFromStorage();
});

// Export for global access
window.TerraTrade_API = API;
