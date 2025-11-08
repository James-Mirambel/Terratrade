/**
 * Messaging System JavaScript
 * TerraTrade Land Trading System
 */

class MessagingSystem {
    constructor() {
        this.currentConversation = null;
        this.conversations = [];
        this.messages = [];
        this.unreadCount = 0;
        this.refreshInterval = null;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadConversations();
        this.startAutoRefresh();
        this.updateUnreadCount();
        
        // Close menus when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.message-actions')) {
                document.querySelectorAll('.message-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });
    }
    
    bindEvents() {
        // Message form submission
        const messageForm = document.getElementById('messageForm');
        if (messageForm) {
            messageForm.addEventListener('submit', (e) => this.sendMessage(e));
        }
        
        // New message button
        const newMessageBtn = document.getElementById('newMessageBtn');
        if (newMessageBtn) {
            newMessageBtn.addEventListener('click', () => this.showNewMessageModal());
        }
        
        // Close message modal
        const closeMessageModal = document.getElementById('closeMessageModal');
        if (closeMessageModal) {
            closeMessageModal.addEventListener('click', () => this.hideMessageModal());
        }
        
        // Message input auto-resize
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('input', this.autoResizeTextarea);
        }
    }
    
    async loadConversations() {
        try {
            const response = await fetch('api/messages/conversations-simple.php', {
                method: 'GET',
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.conversations = data.conversations;
                this.renderConversations();
            } else {
                console.error('Failed to load conversations:', data.error);
            }
        } catch (error) {
            console.error('Error loading conversations:', error);
        }
    }
    
    renderConversations() {
        const conversationsList = document.getElementById('conversationsList');
        if (!conversationsList) return;
        
        if (this.conversations.length === 0) {
            conversationsList.innerHTML = `
                <div class="no-conversations">
                    <div class="empty-state-icon">💬</div>
                    <h3>No conversations yet</h3>
                    <p>Start connecting with other users about properties and land deals</p>
                    <button class="btn btn-primary modern-btn" onclick="messaging.showNewMessageModal()">
                        <span class="btn-icon">✨</span>
                        <span>Start a conversation</span>
                    </button>
                </div>
            `;
            return;
        }
        
        conversationsList.innerHTML = this.conversations.map(conversation => `
            <div class="conversation-item ${conversation.unread_count > 0 ? 'unread' : ''}" 
                 onclick="messaging.openConversation(${conversation.other_user_id}, '${conversation.other_user_name}', ${conversation.listing_id || 'null'}, event)">
                <div class="conversation-avatar">
                    ${conversation.other_user_image ? 
                        `<img src="${conversation.other_user_image}" alt="${conversation.other_user_name}">` :
                        `<div class="avatar-placeholder">${conversation.other_user_name.charAt(0)}</div>`
                    }
                </div>
                <div class="conversation-content">
                    <div class="conversation-header">
                        <h4>${conversation.other_user_name}</h4>
                        <span class="conversation-time">${conversation.last_message_ago || ''}</span>
                    </div>
                    <div class="conversation-preview">
                        ${conversation.property_title ? `<span class="property-tag">${conversation.property_title}</span>` : ''}
                        <p>${conversation.last_message || 'No messages yet'}</p>
                    </div>
                    ${conversation.unread_count > 0 ? `<div class="unread-badge">${conversation.unread_count}</div>` : ''}
                </div>
            </div>
        `).join('');
    }
    
    async openConversation(otherUserId, otherUserName, listingId = null, event = null) {
        this.currentConversation = {
            other_user_id: otherUserId,
            other_user_name: otherUserName,
            listing_id: listingId,
            property_id: listingId
        };
        
        // Update conversation header
        const headerTitle = document.querySelector('.conversation-header-panel h3');
        if (headerTitle) {
            headerTitle.textContent = otherUserName;
        }
        
        // Update conversation menu with user info
        const menuName = document.getElementById('menuName');
        const menuAvatar = document.getElementById('menuAvatar');
        if (menuName) menuName.textContent = otherUserName;
        if (menuAvatar) {
            menuAvatar.innerHTML = `<div class="avatar-placeholder">${otherUserName.charAt(0)}</div>`;
        }
        
        // Show conversation panel
        const conversationPanel = document.querySelector('.conversation-panel');
        if (conversationPanel) {
            conversationPanel.classList.remove('hidden');
        }
        
        // Load messages for this conversation
        await this.loadMessages(otherUserId, listingId);
        
        // Mark conversation as active
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('active');
        });
        if (event && event.currentTarget) {
            event.currentTarget.classList.add('active');
        }
    }
    
    async loadMessages(otherUserId, listingId = null) {
        try {
            const params = new URLSearchParams({
                user_id: otherUserId
            });
            
            if (listingId) {
                params.append('property_id', listingId);
            }
            
            const response = await fetch(`api/messages/conversation-simple.php?${params}`, {
                method: 'GET',
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.messages = data.messages;
                console.log('Loaded messages:', this.messages);
                this.renderMessages();
            } else {
                console.error('Failed to load messages:', data.error);
            }
        } catch (error) {
            console.error('Error loading messages:', error);
        }
    }
    
    renderMessages() {
        const messagesContainer = document.getElementById('messagesContainer');
        if (!messagesContainer) return;
        
        if (this.messages.length === 0) {
            messagesContainer.innerHTML = `
                <div class="no-messages">
                    <div class="empty-state-icon">🗨️</div>
                    <h3>No messages yet</h3>
                    <p>Start the conversation by sending your first message!</p>
                </div>
            `;
            return;
        }
        
        messagesContainer.innerHTML = this.messages.map(message => {
            const isMine = message.is_mine === true || message.is_mine === 1 || message.is_mine === '1';
            console.log(`Message ${message.id}: is_mine=${message.is_mine}, isMine=${isMine}, sender_id=${message.sender_id}`);
            return `
            <div class="message ${isMine ? 'own' : 'other'}" data-message-id="${message.id}">
                <div class="message-content">
                    ${message.subject ? `<div class="message-subject">${message.subject}</div>` : ''}
                    <div class="message-text">${this.formatMessageText(message.message)}</div>
                    <div class="message-meta">
                        <span class="message-time">${this.formatTime(message.created_at)}</span>
                        ${isMine && message.is_read ? '<span class="read-indicator" title="Read">✓✓</span>' : ''}
                        ${isMine && !message.is_read ? '<span class="sent-indicator" title="Sent">✓</span>' : ''}
                    </div>
                </div>
                <div class="message-actions">
                    <button class="message-menu-btn" onclick="messaging.toggleMessageMenu(event, ${message.id})">⋮</button>
                    <div class="message-menu hidden" id="messageMenu${message.id}">
                        <button class="menu-item" onclick="messaging.copyMessage(${message.id}, event)">
                            <span>📋</span> Copy Message
                        </button>
                        <button class="menu-item delete" onclick="messaging.deleteMessage(${message.id}, event)">
                            <span>🗑️</span> Delete
                        </button>
                    </div>
                </div>
            </div>
        `;
        }).join('');
        
        // Scroll to bottom
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    formatMessageText(text) {
        // Basic text formatting - escape HTML and convert line breaks
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\n/g, '<br>');
    }
    
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);
        
        if (seconds < 60) return 'Just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days < 7) return `${days}d ago`;
        
        // Format as date
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
    
    async sendMessage(event) {
        event.preventDefault();
        
        if (!this.currentConversation) {
            alert('Please select a conversation first');
            return;
        }
        
        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();
        
        if (!message) {
            alert('Please enter a message');
            return;
        }
        
        try {
            const response = await fetch('api/messages/send-simple.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({
                    receiver_id: this.currentConversation.other_user_id,
                    message: message,
                    property_id: this.currentConversation.property_id
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                messageInput.value = '';
                this.autoResizeTextarea.call(messageInput);
                
                // Reload messages and conversations
                await this.loadMessages(this.currentConversation.other_user_id, this.currentConversation.property_id);
                await this.loadConversations();
                
                this.showNotification('Message sent successfully', 'success');
            } else {
                this.showNotification('Failed to send message: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error sending message:', error);
            this.showNotification('Failed to send message', 'error');
        }
    }
    
    showNewMessageModal() {
        const modal = document.getElementById('newMessageModal');
        if (modal) {
            modal.classList.remove('hidden');
        }
    }
    
    hideMessageModal() {
        const modal = document.getElementById('newMessageModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
    
    async updateUnreadCount() {
        try {
            const response = await fetch('/Terratrade/api/messages/unread-count.php', {
                method: 'GET',
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.unreadCount = data.unread_count;
                this.updateUnreadBadge();
            }
        } catch (error) {
            console.error('Error updating unread count:', error);
        }
    }
    
    updateUnreadBadge() {
        const badge = document.getElementById('messagesBadge');
        if (badge) {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    }
    
    startAutoRefresh() {
        // Refresh conversations and unread count every 30 seconds
        this.refreshInterval = setInterval(() => {
            this.updateUnreadCount();
            if (this.currentConversation) {
                this.loadMessages(this.currentConversation.other_user_id, this.currentConversation.listing_id);
            }
        }, 30000);
    }
    
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }
    
    autoResizeTextarea() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    }
    
    // Add typing indicator functionality
    showTypingIndicator(conversationId) {
        const messagesContainer = document.getElementById('messagesContainer');
        if (!messagesContainer) return;
        
        // Remove existing typing indicator
        const existingIndicator = messagesContainer.querySelector('.typing-indicator');
        if (existingIndicator) {
            existingIndicator.remove();
        }
        
        // Add new typing indicator
        const typingIndicator = document.createElement('div');
        typingIndicator.className = 'typing-indicator';
        typingIndicator.innerHTML = `
            <span>Someone is typing</span>
            <div class="typing-dots">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        `;
        
        messagesContainer.appendChild(typingIndicator);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (typingIndicator.parentNode) {
                typingIndicator.remove();
            }
        }, 3000);
    }
    
    // Enhanced message sending with better UX
    async sendMessageWithFeedback(messageText) {
        const sendBtn = document.querySelector('.send-btn');
        const messageInput = document.getElementById('messageInput');
        
        // Disable send button and show loading state
        sendBtn.disabled = true;
        sendBtn.innerHTML = '<span class="loading-spinner"></span>';
        
        try {
            // Simulate API call delay for demo
            await new Promise(resolve => setTimeout(resolve, 500));
            
            // Add message to UI immediately for better UX
            this.addMessageToUI({
                message: messageText,
                is_own: true,
                created_ago: 'Just now',
                sender_name: 'You'
            });
            
            // Clear input
            messageInput.value = '';
            this.autoResizeTextarea.call(messageInput);
            
            // Show success feedback
            this.showNotification('Message sent!', 'success');
            
        } catch (error) {
            this.showNotification('Failed to send message', 'error');
        } finally {
            // Re-enable send button
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<span class="send-icon">🚀</span>';
        }
    }
    
    // Add message to UI without API call
    addMessageToUI(message) {
        const messagesContainer = document.getElementById('messagesContainer');
        if (!messagesContainer) return;
        
        const messageElement = document.createElement('div');
        messageElement.className = `message ${message.is_own ? 'own' : 'other'}`;
        messageElement.innerHTML = `
            <div class="message-content">
                <div class="message-text">${this.formatMessageText(message.message)}</div>
                <div class="message-meta">
                    <span class="message-time">${message.created_ago}</span>
                </div>
            </div>
        `;
        
        messagesContainer.appendChild(messageElement);
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
    
    toggleMessageMenu(event, messageId) {
        event.stopPropagation();
        const menu = document.getElementById(`messageMenu${messageId}`);
        const allMenus = document.querySelectorAll('.message-menu');
        
        // Close all other menus
        allMenus.forEach(m => {
            if (m !== menu) m.classList.add('hidden');
        });
        
        // Toggle current menu
        menu.classList.toggle('hidden');
    }
    
    async copyMessage(messageId, event) {
        event.stopPropagation();
        const messageElement = document.querySelector(`[data-message-id="${messageId}"] .message-text`);
        if (!messageElement) return;
        
        const text = messageElement.innerText;
        
        try {
            await navigator.clipboard.writeText(text);
            this.showNotification('Message copied!', 'success');
        } catch (error) {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            this.showNotification('Message copied!', 'success');
        }
        
        // Close menu
        document.getElementById(`messageMenu${messageId}`).classList.add('hidden');
    }
    
    deleteMessage(messageId, event) {
        event.stopPropagation();
        
        // Store the message ID for confirmation
        this.messageToDelete = messageId;
        
        // Show custom delete modal
        const modal = document.getElementById('deleteModal');
        modal.classList.remove('hidden');
        
        // Close the message menu
        document.getElementById(`messageMenu${messageId}`).classList.add('hidden');
    }
    
    cancelDelete() {
        // Hide modal
        const modal = document.getElementById('deleteModal');
        modal.classList.add('hidden');
        this.messageToDelete = null;
    }
    
    async confirmDelete() {
        const messageId = this.messageToDelete;
        if (!messageId) return;
        
        // Hide modal
        const modal = document.getElementById('deleteModal');
        modal.classList.add('hidden');
        
        try {
            const response = await fetch(`api/messages/delete-simple.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify({ message_id: messageId })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Remove message from UI
                const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
                if (messageElement) {
                    messageElement.style.animation = 'fadeOut 0.3s ease';
                    setTimeout(() => messageElement.remove(), 300);
                }
                this.showNotification('Message deleted', 'success');
            } else {
                this.showNotification('Failed to delete message', 'error');
            }
        } catch (error) {
            console.error('Delete error:', error);
            this.showNotification('Failed to delete message', 'error');
        }
        
        this.messageToDelete = null;
    }
    
    toggleConversationMenu() {
        const menu = document.getElementById('conversationMenuDropdown');
        if (menu) {
            menu.classList.toggle('hidden');
        }
    }
    
    initiateVoiceCall() {
        this.showNotification('Voice call feature coming soon!', 'info');
    }
    
    initiateVideoCall() {
        this.showNotification('Video call feature coming soon!', 'info');
    }
    
    deleteConversation() {
        if (!this.currentConversation) return;
        
        if (confirm(`Delete entire conversation with ${this.currentConversation.other_user_name}?`)) {
            this.showNotification('Conversation deleted', 'success');
            // Hide the menu
            document.getElementById('conversationMenuDropdown').classList.add('hidden');
            // TODO: Implement actual deletion API call
        }
    }
    
    blockUser() {
        if (!this.currentConversation) return;
        
        if (confirm(`Block ${this.currentConversation.other_user_name}? They won't be able to message you.`)) {
            this.showNotification('User blocked', 'success');
            // Hide the menu
            document.getElementById('conversationMenuDropdown').classList.add('hidden');
            // TODO: Implement actual blocking API call
        }
    }
    
    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        // Add to page
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => document.body.removeChild(notification), 300);
        }, 3000);
    }
}

// Initialize messaging system when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    if (typeof window.messaging === 'undefined') {
        window.messaging = new MessagingSystem();
    }
});
