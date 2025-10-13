<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Warehouse E-Commerce') }}</title>
    
    <!-- Tailwind CSS CDN (for development) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js for reactive components -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Axios for API calls -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-50">
    <div id="app" x-data="appData()" x-cloak>
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <a href="/" class="text-2xl font-bold text-blue-600">
                            🏪 Warehouse E-Commerce
                        </a>
                    </div>
                    
                    <!-- Main Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="/" class="text-gray-700 hover:text-blue-600 transition">Home</a>
                        <a href="/products" class="text-gray-700 hover:text-blue-600 transition">Products</a>
                        <a href="/categories" class="text-gray-700 hover:text-blue-600 transition">Categories</a>
                        
                        <template x-if="isAuthenticated">
                            <a href="/orders" class="text-gray-700 hover:text-blue-600 transition">My Orders</a>
                        </template>
                    </div>
                    
                    <!-- Right Side Navigation -->
                    <div class="flex items-center space-x-4">
                        <template x-if="isAuthenticated">
                            <div class="flex items-center space-x-4">
                                <!-- Wishlist -->
                                <a href="/wishlist" class="relative">
                                    <svg class="w-6 h-6 text-gray-700 hover:text-red-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                                    </svg>
                                    <span x-show="wishlistCount > 0" id="wishlist-count" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center" x-text="wishlistCount"></span>
                                </a>
                                
                                <!-- Notifications -->
                                <button @click="toggleNotifications" class="relative">
                                    <svg class="w-6 h-6 text-gray-700 hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                                    </svg>
                                    <span x-show="notificationCount > 0" id="notification-badge" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center" x-text="notificationCount"></span>
                                </button>
                                
                                <!-- Cart -->
                                <a href="/cart" class="relative">
                                    <svg class="w-6 h-6 text-gray-700 hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <span x-show="cartCount > 0" class="absolute -top-2 -right-2 bg-blue-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center" x-text="cartCount"></span>
                                </a>
                                
                                <!-- User Dropdown -->
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 hover:text-blue-600 transition">
                                        <span x-text="user.full_name"></span>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    
                                    <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                        <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                        <a href="/addresses" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Addresses</a>
                                        <template x-if="user.is_admin">
                                            <a href="/admin/dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Admin Dashboard</a>
                                        </template>
                                        <button @click="logout" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        
                        <template x-if="!isAuthenticated">
                            <div class="flex items-center space-x-4">
                                <a href="/login" class="text-gray-700 hover:text-blue-600 transition">Login</a>
                                <a href="/register" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">Sign Up</a>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Notification Dropdown -->
        <div x-show="showNotifications" @click.away="showNotifications = false" class="fixed right-4 top-20 w-96 bg-white rounded-lg shadow-xl z-50" id="notification-dropdown">
            <div class="notification-content p-4">
                <div class="flex justify-between items-center mb-4 border-b pb-2">
                    <h3 class="font-semibold text-lg">Notifications</h3>
                    <button @click="markAllAsRead" class="text-sm text-blue-600 hover:text-blue-800">Mark all as read</button>
                </div>
                
                <div class="max-h-96 overflow-y-auto">
                    <template x-if="notifications.length === 0">
                        <div class="text-center py-8 text-gray-500">
                            <div class="text-4xl mb-2">🔔</div>
                            <p>No notifications</p>
                        </div>
                    </template>
                    
                    <template x-for="notification in notifications" :key="notification.id">
                        <div class="border-b last:border-b-0 py-3 hover:bg-gray-50" :class="{ 'bg-blue-50': !notification.read_at }">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <p class="font-medium text-sm" x-text="notification.title"></p>
                                    <p class="text-sm text-gray-600 mt-1" x-text="notification.message"></p>
                                    <p class="text-xs text-gray-500 mt-1" x-text="formatDate(notification.created_at)"></p>
                                </div>
                                <button @click="deleteNotification(notification.id)" class="text-red-500 hover:text-red-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @yield('content')
        </main>
        
        <!-- Footer -->
        <footer class="bg-gray-800 text-white mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">About Us</h3>
                        <p class="text-gray-400">Your trusted warehouse e-commerce platform for all your shopping needs.</p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Customer Service</h3>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="/help" class="hover:text-white">Help Center</a></li>
                            <li><a href="/shipping" class="hover:text-white">Shipping Info</a></li>
                            <li><a href="/returns" class="hover:text-white">Returns</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                        <ul class="space-y-2 text-gray-400">
                            <li><a href="/products" class="hover:text-white">Products</a></li>
                            <li><a href="/categories" class="hover:text-white">Categories</a></li>
                            <li><a href="/contact" class="hover:text-white">Contact</a></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Follow Us</h3>
                        <div class="flex space-x-4">
                            <a href="#" class="text-gray-400 hover:text-white">📱</a>
                            <a href="#" class="text-gray-400 hover:text-white">📧</a>
                            <a href="#" class="text-gray-400 hover:text-white">🐦</a>
                        </div>
                    </div>
                </div>
                <div class="mt-8 pt-8 border-t border-gray-700 text-center text-gray-400">
                    <p>&copy; 2025 Warehouse E-Commerce. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- Toast Container -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 w-96 max-w-full"></div>
    
    <script>
        function appData() {
            return {
                isAuthenticated: false,
                user: null,
                cartCount: 0,
                wishlistCount: 0,
                notificationCount: 0,
                showNotifications: false,
                notifications: [],
                
                init() {
                    this.checkAuth();
                    if (this.isAuthenticated) {
                        this.loadCounts();
                        this.startNotificationPolling();
                    }
                },
                
                async checkAuth() {
                    const token = localStorage.getItem('auth_token');
                    if (token) {
                        try {
                            const response = await axios.get('/api/me', {
                                headers: { 'Authorization': `Bearer ${token}` }
                            });
                            this.user = response.data.user;
                            this.isAuthenticated = true;
                        } catch (error) {
                            localStorage.removeItem('auth_token');
                            this.isAuthenticated = false;
                        }
                    }
                },
                
                async loadCounts() {
                    try {
                        const [cart, wishlist, notifications] = await Promise.all([
                            axios.get('/api/cart'),
                            axios.get('/api/wishlist/count'),
                            axios.get('/api/notifications/unread-count')
                        ]);
                        this.cartCount = cart.data.cart?.cart_details?.length || 0;
                        this.wishlistCount = wishlist.data.count;
                        this.notificationCount = notifications.data.unread_count;
                    } catch (error) {
                        console.error('Failed to load counts:', error);
                    }
                },
                
                async toggleNotifications() {
                    this.showNotifications = !this.showNotifications;
                    if (this.showNotifications) {
                        await this.loadNotifications();
                    }
                },
                
                async loadNotifications() {
                    try {
                        const response = await axios.get('/api/notifications?limit=10');
                        this.notifications = response.data.notifications;
                    } catch (error) {
                        console.error('Failed to load notifications:', error);
                    }
                },
                
                async markAllAsRead() {
                    try {
                        await axios.post('/api/notifications/mark-all-read');
                        this.notifications.forEach(n => n.read_at = new Date().toISOString());
                        this.notificationCount = 0;
                    } catch (error) {
                        console.error('Failed to mark as read:', error);
                    }
                },
                
                async deleteNotification(id) {
                    try {
                        await axios.delete(`/api/notifications/${id}`);
                        this.notifications = this.notifications.filter(n => n.id !== id);
                        await this.loadCounts();
                    } catch (error) {
                        console.error('Failed to delete notification:', error);
                    }
                },
                
                startNotificationPolling() {
                    setInterval(async () => {
                        await this.loadCounts();
                    }, 30000); // Every 30 seconds
                },
                
                async logout() {
                    try {
                        await axios.post('/api/logout');
                        localStorage.removeItem('auth_token');
                        window.location.href = '/login';
                    } catch (error) {
                        console.error('Logout failed:', error);
                    }
                },
                
                formatDate(date) {
                    const d = new Date(date);
                    const now = new Date();
                    const diffMs = now - d;
                    const diffMins = Math.floor(diffMs / 60000);
                    
                    if (diffMins < 1) return 'just now';
                    if (diffMins < 60) return `${diffMins}m ago`;
                    if (diffMins < 1440) return `${Math.floor(diffMins / 60)}h ago`;
                    return `${Math.floor(diffMins / 1440)}d ago`;
                }
            }
        }
        
        // Configure axios defaults
        axios.defaults.baseURL = '/api';
        axios.defaults.headers.common['Accept'] = 'application/json';
        axios.defaults.headers.common['Content-Type'] = 'application/json';
        
        // Add auth token to all requests
        axios.interceptors.request.use(config => {
            const token = localStorage.getItem('auth_token');
            if (token) {
                config.headers.Authorization = `Bearer ${token}`;
            }
            return config;
        });
    </script>
</body>
</html>
