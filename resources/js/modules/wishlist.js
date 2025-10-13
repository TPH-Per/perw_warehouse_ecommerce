// Wishlist module for managing user wishlists
import api from '../utils/api.js';
import { UIHelpers } from '../utils/helpers.js';

export class WishlistModule {
    constructor() {
        this.wishlistItems = [];
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadWishlist();
        this.updateWishlistCount();
    }

    bindEvents() {
        // Toggle wishlist
        document.addEventListener('click', (e) => {
            if (e.target.closest('.wishlist-toggle-btn')) {
                e.preventDefault();
                const btn = e.target.closest('.wishlist-toggle-btn');
                const productId = btn.dataset.productId;
                this.toggleWishlist(productId, btn);
            } else if (e.target.classList.contains('remove-from-wishlist-btn')) {
                const productId = e.target.dataset.productId;
                this.removeFromWishlist(productId);
            } else if (e.target.id === 'move-all-to-cart-btn') {
                this.moveAllToCart();
            } else if (e.target.classList.contains('move-to-cart-btn')) {
                const productId = e.target.dataset.productId;
                this.moveToCart([productId]);
            } else if (e.target.id === 'clear-wishlist-btn') {
                this.clearWishlist();
            }
        });
    }

    async loadWishlist() {
        const container = document.getElementById('wishlist-items');
        if (!container) return;

        UIHelpers.showLoading(container, 'Loading wishlist...');

        try {
            const response = await api.get('/wishlist');
            this.wishlistItems = response.wishlist;
            this.renderWishlist();
            this.updateWishlistCount();
        } catch (error) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <div class="text-red-500 text-lg mb-2">Failed to load wishlist</div>
                    <button onclick="location.reload()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Try Again
                    </button>
                </div>
            `;
        }
    }

    renderWishlist() {
        const container = document.getElementById('wishlist-items');
        if (!container) return;

        if (!this.wishlistItems.length) {
            container.innerHTML = `
                <div class="text-center py-12">
                    <div class="text-6xl mb-4">❤️</div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Your wishlist is empty</h3>
                    <p class="text-gray-600 mb-6">Start adding products you love!</p>
                    <a href="/products" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Browse Products
                    </a>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <!-- Wishlist Header -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">My Wishlist (${this.wishlistItems.length})</h2>
                <div class="flex space-x-3">
                    <button id="move-all-to-cart-btn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Move All to Cart
                    </button>
                    <button id="clear-wishlist-btn" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                        Clear Wishlist
                    </button>
                </div>
            </div>

            <!-- Wishlist Items Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                ${this.wishlistItems.map(item => this.renderWishlistItem(item)).join('')}
            </div>
        `;
    }

    renderWishlistItem(item) {
        const stockStatus = item.is_in_stock ? 'In Stock' : 'Out of Stock';
        const stockClass = item.is_in_stock ? 'text-green-600' : 'text-red-600';

        return `
            <div class="bg-white rounded-lg shadow overflow-hidden hover:shadow-lg transition-shadow">
                <div class="relative">
                    <img src="${item.product_image || '/images/placeholder.jpg'}" 
                         alt="${item.product_name}" 
                         class="w-full h-48 object-cover">
                    <button class="remove-from-wishlist-btn absolute top-2 right-2 p-2 bg-white rounded-full shadow hover:bg-red-50"
                            data-product-id="${item.product_id}">
                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                    ${item.average_rating > 0 ? `
                        <div class="absolute bottom-2 left-2 px-2 py-1 bg-white bg-opacity-90 rounded-full text-sm">
                            ⭐ ${item.average_rating} (${item.review_count})
                        </div>
                    ` : ''}
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-lg mb-2 line-clamp-2">
                        <a href="/products/${item.product_slug}" class="hover:text-blue-600">
                            ${item.product_name}
                        </a>
                    </h3>
                    <p class="text-gray-600 text-sm mb-2">${item.category || 'Uncategorized'}</p>
                    <div class="flex justify-between items-center mb-3">
                        <div class="text-lg font-bold text-blue-600">
                            ${item.price_range} VND
                        </div>
                        <span class="text-sm ${stockClass} font-medium">
                            ${stockStatus}
                        </span>
                    </div>
                    <div class="flex space-x-2">
                        ${item.is_in_stock ? `
                            <button class="move-to-cart-btn flex-1 px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                    data-product-id="${item.product_id}">
                                Add to Cart
                            </button>
                        ` : `
                            <button class="flex-1 px-3 py-2 bg-gray-300 text-gray-600 rounded cursor-not-allowed" disabled>
                                Out of Stock
                            </button>
                        `}
                        <a href="/products/${item.product_slug}" 
                           class="px-3 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 text-center">
                            View
                        </a>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        Added ${UIHelpers.formatDate(item.added_at, 'relative')}
                    </p>
                </div>
            </div>
        `;
    }

    async toggleWishlist(productId, button) {
        const originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<span class="inline-block animate-spin">⏳</span>';

        try {
            const response = await api.post('/wishlist/toggle', { product_id: productId });
            
            if (response.in_wishlist) {
                button.innerHTML = '❤️';
                button.classList.add('text-red-500');
                button.classList.remove('text-gray-400');
            } else {
                button.innerHTML = '🤍';
                button.classList.add('text-gray-400');
                button.classList.remove('text-red-500');
            }

            UIHelpers.showToast(response.message, 'success');
            this.updateWishlistCount();
            
            // Reload wishlist if on wishlist page
            if (document.getElementById('wishlist-items')) {
                this.loadWishlist();
            }
        } catch (error) {
            button.innerHTML = originalHtml;
            UIHelpers.showToast(error.message || 'Failed to update wishlist', 'error');
        } finally {
            button.disabled = false;
        }
    }

    async removeFromWishlist(productId) {
        try {
            const response = await api.delete(`/wishlist/${productId}`);
            
            // Remove from local array
            this.wishlistItems = this.wishlistItems.filter(item => item.product_id != productId);
            
            this.renderWishlist();
            this.updateWishlistCount();
            UIHelpers.showToast(response.message, 'success');
        } catch (error) {
            UIHelpers.showToast('Failed to remove from wishlist', 'error');
        }
    }

    async moveToCart(productIds) {
        try {
            const response = await api.post('/wishlist/move-to-cart', { product_ids: productIds });
            
            if (response.moved_count > 0) {
                // Remove moved items from wishlist
                this.wishlistItems = this.wishlistItems.filter(
                    item => !productIds.includes(item.product_id.toString())
                );
                
                this.renderWishlist();
                this.updateWishlistCount();
                
                // Update cart count if cart module exists
                if (window.cartModule) {
                    window.cartModule.updateCartCount();
                }
            }
            
            UIHelpers.showToast(response.message, response.success ? 'success' : 'warning');
        } catch (error) {
            UIHelpers.showToast('Failed to move items to cart', 'error');
        }
    }

    async moveAllToCart() {
        if (!confirm('Move all items to cart?')) {
            return;
        }

        const productIds = this.wishlistItems
            .filter(item => item.is_in_stock)
            .map(item => item.product_id);

        if (productIds.length === 0) {
            UIHelpers.showToast('No items available to move', 'warning');
            return;
        }

        await this.moveToCart(productIds);
    }

    async clearWishlist() {
        if (!confirm('Are you sure you want to clear your entire wishlist?')) {
            return;
        }

        try {
            const response = await api.delete('/wishlist/clear');
            
            this.wishlistItems = [];
            this.renderWishlist();
            this.updateWishlistCount();
            
            UIHelpers.showToast(response.message, 'success');
        } catch (error) {
            UIHelpers.showToast('Failed to clear wishlist', 'error');
        }
    }

    async updateWishlistCount() {
        try {
            const response = await api.get('/wishlist/count');
            this.setWishlistBadge(response.count);
        } catch (error) {
            console.error('Failed to update wishlist count:', error);
        }
    }

    setWishlistBadge(count) {
        const badge = document.getElementById('wishlist-count');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    }

    getWishlistCount() {
        return this.wishlistItems.length;
    }

    isInWishlist(productId) {
        return this.wishlistItems.some(item => item.product_id == productId);
    }
}

// Auto-initialize if wishlist page
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('wishlist-items')) {
        window.wishlistModule = new WishlistModule();
    }
});
