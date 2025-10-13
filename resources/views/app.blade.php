<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Warehouse E-Commerce Management</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <!-- Navigation Header -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800">Warehouse E-Commerce</h1>
                </div>

                <!-- Navigation Links -->
                <div class="hidden md:flex space-x-8">
                    <a href="#dashboard" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2">Dashboard</a>
                    <a href="#products" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2">Products</a>
                    <a href="#inventory" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2">Inventory</a>
                    <a href="#orders" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2">Orders</a>
                    <a href="#cart" class="nav-link text-gray-700 hover:text-blue-600 px-3 py-2">
                        Cart <span id="cart-count" class="bg-red-500 text-white text-xs rounded-full px-2 py-1 ml-1">0</span>
                    </a>
                </div>

                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <!-- Auth Links (when not logged in) -->
                    <div id="auth-links" class="flex space-x-4">
                        <a href="#login" class="text-gray-700 hover:text-blue-600 px-3 py-2">Login</a>
                        <a href="#register" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">Register</a>
                    </div>

                    <!-- User Menu (when logged in) -->
                    <div id="user-menu" class="relative hidden">
                        <button id="user-menu-button" class="flex items-center text-gray-700 hover:text-blue-600 focus:outline-none">
                            <span class="user-name mr-2"></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div id="user-dropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg hidden">
                            <a href="#profile" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Profile</a>
                            <a href="#orders" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">My Orders</a>
                            <a href="#" data-action="logout" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Logout</a>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <button id="mobile-menu-button" class="md:hidden text-gray-700 hover:text-blue-600 focus:outline-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div id="mobile-menu" class="md:hidden hidden bg-white border-t">
            <a href="#dashboard" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Dashboard</a>
            <a href="#products" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Products</a>
            <a href="#inventory" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Inventory</a>
            <a href="#orders" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Orders</a>
            <a href="#cart" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">Cart</a>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4">
        <div id="main-content">
            <!-- Content will be loaded here -->
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12">
        <div class="max-w-7xl mx-auto py-8 px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">Warehouse E-Commerce</h3>
                    <p class="text-gray-400">Comprehensive warehouse management solution for modern e-commerce.</p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Products</h4>
                    <ul class="text-gray-400 space-y-2">
                        <li><a href="#products" class="hover:text-white">Browse Products</a></li>
                        <li><a href="#categories" class="hover:text-white">Categories</a></li>
                        <li><a href="#suppliers" class="hover:text-white">Suppliers</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Management</h4>
                    <ul class="text-gray-400 space-y-2">
                        <li><a href="#inventory" class="hover:text-white">Inventory</a></li>
                        <li><a href="#warehouses" class="hover:text-white">Warehouses</a></li>
                        <li><a href="#orders" class="hover:text-white">Orders</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Support</h4>
                    <ul class="text-gray-400 space-y-2">
                        <li><a href="#" class="hover:text-white">Help Center</a></li>
                        <li><a href="#" class="hover:text-white">Contact Us</a></li>
                        <li><a href="#" class="hover:text-white">Documentation</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 Warehouse E-Commerce Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Loading Modal -->
    <div id="loading-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-700">Loading...</p>
        </div>
    </div>

    <script>
        // Toggle mobile menu
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });

        // Toggle user dropdown
        document.getElementById('user-menu-button')?.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('user-dropdown');
            dropdown.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            const dropdown = document.getElementById('user-dropdown');
            if (dropdown) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
</body>
</html>