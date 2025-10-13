import './bootstrap';
import '../css/app.css';

// Import all modules
import { Dashboard } from './modules/dashboard.js';
import { ProductModule } from './modules/product.js';
import { InventoryModule } from './modules/inventory.js';
import { OrderModule } from './modules/order.js';
import { CartModule } from './modules/cart.js';
import { CategoryModule } from './modules/category.js';
import { AuthModule } from './modules/auth.js';

// Main Application Class
class WarehouseECommerceApp {
    constructor() {
        this.apiBase = '/api';
        this.currentUser = null;
        this.token = localStorage.getItem('auth_token');
        this.currentModule = null;
        this.init();
    }

    async init() {
        this.setupRouting();
        this.bindGlobalEvents();
        
        // Initialize auth module first
        this.authModule = new AuthModule();
        
        // Initialize cart module for global cart functionality
        this.cartModule = new CartModule();
    }

    setupRouting() {
        // Simple SPA routing
        window.addEventListener('hashchange', () => this.handleRoute());
        this.handleRoute();
    }

    handleRoute() {
        const hash = window.location.hash.slice(1) || 'dashboard';
        this.loadPage(hash);
    }

    async loadPage(page) {
        const content = document.getElementById('main-content');
        if (!content) return;

        // Show loading state
        content.innerHTML = '<div class="flex justify-center items-center h-64"><div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div></div>';

        try {
            // Destroy current module if exists
            if (this.currentModule && typeof this.currentModule.destroy === 'function') {
                this.currentModule.destroy();
            }

            // Load page content and initialize module
            switch (page) {
                case 'dashboard':
                    content.innerHTML = this.renderDashboardPage();
                    this.currentModule = new Dashboard();
                    break;
                case 'products':
                    content.innerHTML = this.renderProductsPage();
                    this.currentModule = new ProductModule();
                    break;
                case 'inventory':
                    content.innerHTML = this.renderInventoryPage();
                    this.currentModule = new InventoryModule();
                    break;
                case 'orders':
                    content.innerHTML = this.renderOrdersPage();
                    this.currentModule = new OrderModule();
                    break;
                case 'cart':
                    content.innerHTML = this.renderCartPage();
                    // Cart module is already initialized globally
                    this.cartModule.loadCartData();
                    break;
                case 'categories':
                    content.innerHTML = this.renderCategoriesPage();
                    this.currentModule = new CategoryModule();
                    break;
                case 'login':
                    content.innerHTML = this.authModule.renderLoginForm();
                    break;
                case 'register':
                    content.innerHTML = this.authModule.renderRegisterForm();
                    break;
                default:
                    content.innerHTML = '<div class="text-center text-gray-500 py-8">Page not found</div>';
            }

        } catch (error) {
            content.innerHTML = '<div class="text-center text-red-500 py-8">Error loading page</div>';
            console.error('Page load error:', error);
        }
    }

    // Page templates
    renderDashboardPage() {
        return `
            <div class="container mx-auto px-4 py-6">
                <div id="dashboard-content">
                    <!-- Dashboard content will be loaded here -->
                </div>
            </div>
        `;
    }

    renderProductsPage() {
        return `
            <div class="container mx-auto px-4 py-6">
                <div id="products-list">
                    <!-- Products list will be loaded here -->
                </div>
            </div>
        `;
    }

    renderInventoryPage() {
        return `
            <div class="container mx-auto px-4 py-6">
                <div id="inventory-list">
                    <!-- Inventory list will be loaded here -->
                </div>
            </div>
        `;
    }

    renderOrdersPage() {
        return `
            <div class="container mx-auto px-4 py-6">
                <div id="orders-list">
                    <!-- Orders list will be loaded here -->
                </div>
            </div>
        `;
    }

    renderCartPage() {
        return `
            <div class="container mx-auto px-4 py-6">
                <div id="cart-content">
                    <!-- Cart content will be loaded here -->
                </div>
            </div>
        `;
    }

    renderCategoriesPage() {
        return `
            <div class="container mx-auto px-4 py-6">
                <div id="categories-list">
                    <!-- Categories list will be loaded here -->
                </div>
            </div>
        `;
    }

    bindGlobalEvents() {
        // Mobile menu toggle
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-action="toggle-mobile-menu"]')) {
                const menu = document.getElementById('mobile-menu');
                if (menu) {
                    menu.classList.toggle('hidden');
                }
            }
        });

        // Navigation links
        document.addEventListener('click', (e) => {
            if (e.target.matches('a[href^="#"]')) {
                e.preventDefault();
                window.location.hash = e.target.getAttribute('href');
            }
        });
    }

    // Utility methods
    showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-lg z-50 ${
            type === 'success' ? 'bg-green-500' : 'bg-red-500'
        } text-white`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.remove();
        }, 3000);
    }
}

// Initialize the application
document.addEventListener('DOMContentLoaded', () => {
    new WarehouseECommerceApp();
});