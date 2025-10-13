// Notification module for real-time notifications
import api from '../utils/api.js';
import { UIHelpers } from '../utils/helpers.js';

export class NotificationModule {
    constructor() {
        this.notifications = [];
        this.unreadCount = 0;
        this.pollingInterval = null;
        this.pollingDelay = 30000; // 30 seconds
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadNotifications();
        this.startPolling();
    }

    bindEvents() {
        // Toggle notification dropdown
        document.addEventListener('click', (e) => {
            if (e.target.closest('#notification-bell')) {
                this.toggleNotificationDropdown();
            } else if (e.target.classList.contains('mark-read-btn')) {
                const notificationId = e.target.dataset.notificationId;
                this.markAsRead(notificationId);
            } else if (e.target.id === 'mark-all-read-btn') {
                this.markAllAsRead();
            } else if (e.target.classList.contains('delete-notification-btn')) {
                const notificationId = e.target.dataset.notificationId;
                this.deleteNotification(notificationId);
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            const dropdown = document.getElementById('notification-dropdown');
            const bell = document.getElementById('notification-bell');
            
            if (dropdown && !dropdown.contains(e.target) && !bell?.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    }

    async loadNotifications(limit = 10) {
        try {
            const response = await api.get('/notifications', { limit });
            this.notifications = response.notifications;
            this.unreadCount = response.unread_count;
            this.updateNotificationBadge();
            this.renderNotificationDropdown();
        } catch (error) {
            console.error('Failed to load notifications:', error);
        }
    }

    async markAsRead(notificationId) {
        try {
            await api.post(`/notifications/${notificationId}/read`);
            
            // Update local state
            const notification = this.notifications.find(n => n.id == notificationId);
            if (notification && !notification.read_at) {
                notification.read_at = new Date().toISOString();
                this.unreadCount = Math.max(0, this.unreadCount - 1);
                this.updateNotificationBadge();
                this.renderNotificationDropdown();
            }
        } catch (error) {
            UIHelpers.showToast('Failed to mark notification as read', 'error');
        }
    }

    async markAllAsRead() {
        try {
            const response = await api.post('/notifications/mark-all-read');
            
            // Update local state
            this.notifications.forEach(n => n.read_at = new Date().toISOString());
            this.unreadCount = 0;
            this.updateNotificationBadge();
            this.renderNotificationDropdown();
            
            UIHelpers.showToast(response.message, 'success');
        } catch (error) {
            UIHelpers.showToast('Failed to mark all as read', 'error');
        }
    }

    async deleteNotification(notificationId) {
        if (!confirm('Are you sure you want to delete this notification?')) {
            return;
        }

        try {
            await api.delete(`/notifications/${notificationId}`);
            
            // Remove from local state
            const index = this.notifications.findIndex(n => n.id == notificationId);
            if (index !== -1) {
                const notification = this.notifications[index];
                if (!notification.read_at) {
                    this.unreadCount = Math.max(0, this.unreadCount - 1);
                }
                this.notifications.splice(index, 1);
            }
            
            this.updateNotificationBadge();
            this.renderNotificationDropdown();
            
            UIHelpers.showToast('Notification deleted', 'success');
        } catch (error) {
            UIHelpers.showToast('Failed to delete notification', 'error');
        }
    }

    toggleNotificationDropdown() {
        const dropdown = document.getElementById('notification-dropdown');
        if (dropdown) {
            dropdown.classList.toggle('hidden');
            
            if (!dropdown.classList.contains('hidden')) {
                this.loadNotifications();
            }
        }
    }

    updateNotificationBadge() {
        const badge = document.getElementById('notification-badge');
        if (badge) {
            if (this.unreadCount > 0) {
                badge.textContent = this.unreadCount > 99 ? '99+' : this.unreadCount;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    }

    renderNotificationDropdown() {
        const dropdown = document.getElementById('notification-dropdown');
        if (!dropdown) return;

        const content = dropdown.querySelector('.notification-content');
        if (!content) return;

        if (!this.notifications.length) {
            content.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <div class="text-4xl mb-2">🔔</div>
                    <p>No notifications</p>
                </div>
            `;
            return;
        }

        content.innerHTML = `
            <!-- Header -->
            <div class="flex justify-between items-center px-4 py-3 border-b">
                <h3 class="font-semibold text-lg">Notifications</h3>
                ${this.unreadCount > 0 ? `
                    <button id="mark-all-read-btn" class="text-sm text-blue-600 hover:text-blue-800">
                        Mark all as read
                    </button>
                ` : ''}
            </div>

            <!-- Notifications List -->
            <div class="max-h-96 overflow-y-auto">
                ${this.notifications.map(notification => this.renderNotificationItem(notification)).join('')}
            </div>

            <!-- Footer -->
            <div class="px-4 py-3 border-t text-center">
                <a href="/notifications" class="text-sm text-blue-600 hover:text-blue-800">
                    View all notifications
                </a>
            </div>
        `;
    }

    renderNotificationItem(notification) {
        const isUnread = !notification.read_at;
        const icon = this.getNotificationIcon(notification.type);
        const time = UIHelpers.formatDate(notification.created_at, 'relative');

        return `
            <div class="notification-item px-4 py-3 border-b hover:bg-gray-50 ${isUnread ? 'bg-blue-50' : ''}"
                 data-notification-id="${notification.id}">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0 text-2xl">
                        ${icon}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start">
                            <p class="font-medium text-sm ${isUnread ? 'text-gray-900' : 'text-gray-600'}">
                                ${notification.title}
                            </p>
                            ${isUnread ? '<span class="w-2 h-2 bg-blue-600 rounded-full"></span>' : ''}
                        </div>
                        <p class="text-sm text-gray-600 mt-1">${notification.message}</p>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-xs text-gray-500">${time}</span>
                            <div class="flex space-x-2">
                                ${isUnread ? `
                                    <button class="mark-read-btn text-xs text-blue-600 hover:text-blue-800"
                                            data-notification-id="${notification.id}">
                                        Mark as read
                                    </button>
                                ` : ''}
                                <button class="delete-notification-btn text-xs text-red-600 hover:text-red-800"
                                        data-notification-id="${notification.id}">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                ${notification.action_url ? `
                    <a href="${notification.action_url}" class="absolute inset-0"></a>
                ` : ''}
            </div>
        `;
    }

    getNotificationIcon(type) {
        const icons = {
            'order_created': '📦',
            'order_updated': '📦',
            'order_shipped': '🚚',
            'order_delivered': '✅',
            'payment_received': '💰',
            'low_stock': '⚠️',
            'product_back_in_stock': '🔔',
            'review_posted': '⭐',
            'system': 'ℹ️'
        };
        return icons[type] || '📢';
    }

    startPolling() {
        // Poll for new notifications every 30 seconds
        this.pollingInterval = setInterval(() => {
            this.checkForNewNotifications();
        }, this.pollingDelay);
    }

    stopPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    }

    async checkForNewNotifications() {
        try {
            const response = await api.get('/notifications/unread-count');
            
            if (response.unread_count !== this.unreadCount) {
                // New notifications detected
                this.loadNotifications();
                
                // Show toast for new notification (optional)
                if (response.unread_count > this.unreadCount) {
                    this.showNewNotificationToast();
                }
            }
        } catch (error) {
            console.error('Failed to check for new notifications:', error);
        }
    }

    showNewNotificationToast() {
        // Play notification sound (optional)
        // this.playNotificationSound();
        
        UIHelpers.showToast('You have new notifications', 'info');
    }

    playNotificationSound() {
        const audio = new Audio('/sounds/notification.mp3');
        audio.volume = 0.5;
        audio.play().catch(e => console.log('Could not play notification sound'));
    }

    destroy() {
        this.stopPolling();
    }
}

// Auto-initialize if notification bell exists
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('notification-bell')) {
        window.notificationModule = new NotificationModule();
    }
});
