/**
 * Real-time Notifications System
 * TerraTrade Land Trading System
 */

class NotificationManager {
    constructor() {
        this.unreadCount = 0;
        this.updateInterval = null;
        this.UPDATE_FREQUENCY = 10000; // Update every 10 seconds
    }
    
    init() {
        this.updateUnreadCount();
        this.startAutoUpdate();
        
        // Add hover effect to messages icon
        const messagesBtn = document.getElementById('messagesIconBtn');
        if (messagesBtn) {
            messagesBtn.addEventListener('mouseenter', () => {
                messagesBtn.style.background = 'rgba(102, 126, 234, 0.1)';
            });
            messagesBtn.addEventListener('mouseleave', () => {
                messagesBtn.style.background = 'transparent';
            });
        }
    }
    
    async updateUnreadCount() {
        try {
            const response = await fetch('api/messages/unread-count-simple.php', {
                method: 'GET',
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.unreadCount = data.unread_count;
                this.updateBadge();
            }
        } catch (error) {
            console.error('Failed to update unread count:', error);
        }
    }
    
    updateBadge() {
        const badge = document.getElementById('messagesBadge');
        if (!badge) {
            // Badge not on this page, skip silently
            return;
        }
        
        if (this.unreadCount > 0) {
            badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
            badge.style.display = 'flex';
            badge.classList.remove('hidden');
            
            // Add pulse animation
            badge.style.animation = 'pulse 2s infinite';
        } else {
            badge.style.display = 'none';
            badge.classList.add('hidden');
        }
    }
    
    startAutoUpdate() {
        // Update immediately
        this.updateUnreadCount();
        
        // Then update every 10 seconds
        this.updateInterval = setInterval(() => {
            this.updateUnreadCount();
        }, this.UPDATE_FREQUENCY);
    }
    
    stopAutoUpdate() {
        if (this.updateInterval) {
            clearInterval(this.updateInterval);
            this.updateInterval = null;
        }
    }
    
    // Show toast notification
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: ${type === 'success' ? '#00b894' : type === 'error' ? '#d63031' : '#667eea'};
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            z-index: 10000;
            font-weight: 500;
            animation: slideInRight 0.3s ease, slideOutRight 0.3s ease 2.7s;
            max-width: 300px;
        `;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    @keyframes slideInRight {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Initialize on page load
let notificationManager;
document.addEventListener('DOMContentLoaded', () => {
    notificationManager = new NotificationManager();
    notificationManager.init();
});

// Make it globally accessible
window.notificationManager = notificationManager;
