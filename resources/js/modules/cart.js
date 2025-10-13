// Shopping cart module for customer interactions
import api from '../utils/api.js';
import { UIHelpers } from '../utils/helpers.js';

export class CartModule {
    constructor() {
        this.cartCount = 0;
        this.cartTotal = 0;
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadCartData();
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-cart-btn')) {
                const productVariantId = e.target.dataset.variantId;
                const quantity = parseInt(e.target.dataset.quantity) || 1;
                this.addToCart(productVariantId, quantity);
            } else if (e.target.classList.contains('remove-cart-item-btn')) {
                const variantId = e.target.dataset.variantId;
                this.removeFromCart(variantId);
            } else if (e.target.id === 'checkout-btn') {
                this.proceedToCheckout();
            }
        });

        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('cart-quantity-input')) {
                const variantId = e.target.dataset.variantId;
                const quantity = parseInt(e.target.value);
                if (quantity > 0) {
                    this.updateCartItem(variantId, quantity);
                }
            }
        });
    }

    async loadCartData() {
        try {
            const response = await api.get('/cart');
            this.renderCart(response.cart);
            this.updateCartBadge(response.cart);
        } catch (error) {
            console.error('Failed to load cart:', error);
        }
    }

    async addToCart(productVariantId, quantity = 1) {
        try {
            const response = await api.post('/cart/add', {
                product_variant_id: productVariantId,
                quantity: quantity
            });
            UIHelpers.showToast(response.message, 'success');
            this.loadCartData();
        } catch (error) {
            UIHelpers.showToast(error.message, 'error');
        }
    }

    async updateCartItem(variantId, quantity) {
        try {
            await api.put(`/cart/update/${variantId}`, { quantity });
            this.loadCartData();
        } catch (error) {
            UIHelpers.showToast(error.message, 'error');
            this.loadCartData();
        }
    }

    async removeFromCart(variantId) {
        if (!confirm('Remove this item from cart?')) return;
        
        try {
            const response = await api.delete(`/cart/remove/${variantId}`);
            UIHelpers.showToast(response.message, 'success');
            this.loadCartData();
        } catch (error) {
            UIHelpers.showToast(error.message, 'error');
        }
    }

    renderCart(cart) {
        const container = document.getElementById('cart-content');
        if (!container) return;

        if (!cart?.cart_details?.length) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <div class="text-gray-500 text-lg mb-4">Your cart is empty</div>
                    <a href="/products" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Continue Shopping
                    </a>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-semibold">Shopping Cart</h2>
            </div>
            
            <div class="space-y-4 mb-6">
                ${cart.cart_details.map(item => this.renderCartItem(item)).join('')}
            </div>

            <div class="border-t pt-6">
                <div class="flex justify-between font-semibold text-lg mb-4">
                    <span>Total:</span>
                    <span>${UIHelpers.formatCurrency(cart.total_amount || 0)}</span>
                </div>
                <button id="checkout-btn" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700">
                    Proceed to Checkout
                </button>
            </div>
        `;
    }

    renderCartItem(item) {
        const variant = item.product_variant;
        const product = variant.product;
        
        return `
            <div class="flex items-center space-x-4 p-4 border rounded-lg">
                <img src="${product.images?.[0]?.image_url || '/images/placeholder.jpg'}" 
                     alt="${product.name}" class="w-16 h-16 object-cover rounded">
                <div class="flex-1">
                    <h3 class="font-medium">${product.name}</h3>
                    <p class="text-sm text-gray-600">${variant.variant_name || 'Default'}</p>
                    <p class="font-medium">${UIHelpers.formatCurrency(variant.price)}</p>
                </div>
                <input type="number" value="${item.quantity}" min="1" max="99"
                       class="cart-quantity-input w-16 px-2 py-1 border rounded text-center"
                       data-variant-id="${variant.id}">
                <div class="text-right">
                    <p class="font-medium">${UIHelpers.formatCurrency(item.total_price)}</p>
                    <button class="remove-cart-item-btn text-red-600 hover:text-red-800 text-sm mt-1"
                            data-variant-id="${variant.id}">Remove</button>
                </div>
            </div>
        `;
    }

    updateCartBadge(cart) {
        const badge = document.getElementById('cart-badge');
        if (badge && cart?.cart_details) {
            const count = cart.cart_details.reduce((sum, item) => sum + item.quantity, 0);
            badge.textContent = count;
            badge.style.display = count > 0 ? 'block' : 'none';
        }
    }

    async proceedToCheckout() {
        // Redirect to checkout page or show checkout form
        window.location.href = '/checkout';
    }
}