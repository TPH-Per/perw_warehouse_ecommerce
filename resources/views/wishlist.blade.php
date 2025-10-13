@extends('layout')

@section('content')
<div x-data="wishlistPage()" id="wishlist-page">
    <h1 class="text-3xl font-bold mb-6">My Wishlist</h1>
    
    <!-- Wishlist Header -->
    <div x-show="wishlistItems.length > 0" class="bg-white p-4 rounded-lg shadow mb-6">
        <div class="flex justify-between items-center">
            <p class="text-gray-700"><span class="font-semibold" x-text="wishlistItems.length"></span> items in your wishlist</p>
            <div class="flex space-x-3">
                <button @click="moveAllToCart" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Move All to Cart
                </button>
                <button @click="clearWishlist" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Clear Wishlist
                </button>
            </div>
        </div>
    </div>
    
    <!-- Wishlist Items -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6" id="wishlist-items">
        <template x-for="item in wishlistItems" :key="item.wishlist_id">
            <div class="bg-white rounded-lg shadow hover:shadow-xl transition">
                <div class="relative">
                    <img :src="item.product_image || '/images/placeholder.jpg'" :alt="item.product_name" class="w-full h-48 object-cover rounded-t-lg">
                    <button @click="removeItem(item.product_id)" class="absolute top-2 right-2 p-2 bg-white rounded-full shadow hover:bg-red-50">
                        <svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                    <template x-if="item.average_rating > 0">
                        <div class="absolute bottom-2 left-2 px-2 py-1 bg-white bg-opacity-90 rounded-full text-sm">
                            <span class="text-yellow-500">⭐</span>
                            <span x-text="item.average_rating"></span>
                        </div>
                    </template>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-lg mb-2 line-clamp-2" x-text="item.product_name"></h3>
                    <p class="text-gray-600 text-sm mb-2" x-text="item.category || 'Uncategorized'"></p>
                    <div class="flex justify-between items-center mb-3">
                        <div class="text-lg font-bold text-blue-600" x-text="item.price_range + ' VND'"></div>
                        <span :class="item.is_in_stock ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                            <span x-text="item.is_in_stock ? 'In Stock' : 'Out of Stock'"></span>
                        </span>
                    </div>
                    <div class="flex space-x-2">
                        <button x-show="item.is_in_stock" @click="moveToCart(item.product_id)" 
                                class="flex-1 px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Add to Cart
                        </button>
                        <button x-show="!item.is_in_stock" disabled 
                                class="flex-1 px-3 py-2 bg-gray-300 text-gray-600 rounded cursor-not-allowed">
                            Out of Stock
                        </button>
                        <a :href="`/products/${item.product_slug}`" class="px-3 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 text-center">
                            View
                        </a>
                    </div>
                    <p class="text-xs text-gray-500 mt-2" x-text="`Added ${formatDate(item.added_at)}`"></p>
                </div>
            </div>
        </template>
    </div>
    
    <!-- Empty State -->
    <template x-if="wishlistItems.length === 0">
        <div class="text-center py-12">
            <div class="text-6xl mb-4">❤️</div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">Your wishlist is empty</h3>
            <p class="text-gray-600 mb-6">Start adding products you love!</p>
            <a href="/products" class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Browse Products
            </a>
        </div>
    </template>
</div>

<script>
    function wishlistPage() {
        return {
            wishlistItems: [],
            
            init() {
                this.loadWishlist();
            },
            
            async loadWishlist() {
                try {
                    const response = await axios.get('/wishlist');
                    this.wishlistItems = response.data.wishlist;
                } catch (error) {
                    console.error('Failed to load wishlist:', error);
                }
            },
            
            async removeItem(productId) {
                try {
                    await axios.delete(`/wishlist/${productId}`);
                    this.wishlistItems = this.wishlistItems.filter(item => item.product_id !== productId);
                    this.showToast('Removed from wishlist', 'success');
                } catch (error) {
                    this.showToast('Failed to remove item', 'error');
                }
            },
            
            async moveToCart(productId) {
                try {
                    await axios.post('/wishlist/move-to-cart', { product_ids: [productId] });
                    this.wishlistItems = this.wishlistItems.filter(item => item.product_id !== productId);
                    this.showToast('Moved to cart successfully!', 'success');
                    window.location.reload();
                } catch (error) {
                    this.showToast(error.response?.data?.message || 'Failed to move to cart', 'error');
                }
            },
            
            async moveAllToCart() {
                if (!confirm('Move all items to cart?')) return;
                
                const productIds = this.wishlistItems.filter(item => item.is_in_stock).map(item => item.product_id);
                if (productIds.length === 0) {
                    this.showToast('No items available to move', 'warning');
                    return;
                }
                
                try {
                    await axios.post('/wishlist/move-to-cart', { product_ids: productIds });
                    this.showToast('All items moved to cart!', 'success');
                    await this.loadWishlist();
                    window.location.reload();
                } catch (error) {
                    this.showToast('Failed to move items to cart', 'error');
                }
            },
            
            async clearWishlist() {
                if (!confirm('Are you sure you want to clear your entire wishlist?')) return;
                
                try {
                    await axios.delete('/wishlist/clear');
                    this.wishlistItems = [];
                    this.showToast('Wishlist cleared successfully', 'success');
                } catch (error) {
                    this.showToast('Failed to clear wishlist', 'error');
                }
            },
            
            formatDate(date) {
                const d = new Date(date);
                const now = new Date();
                const diffMs = now - d;
                const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
                
                if (diffDays === 0) return 'today';
                if (diffDays === 1) return 'yesterday';
                if (diffDays < 7) return `${diffDays} days ago`;
                if (diffDays < 30) return `${Math.floor(diffDays / 7)} weeks ago`;
                return `${Math.floor(diffDays / 30)} months ago`;
            },
            
            showToast(message, type) {
                const toast = document.createElement('div');
                toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white ${
                    type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-yellow-500'
                } z-50`;
                toast.textContent = message;
                document.body.appendChild(toast);
                
                setTimeout(() => toast.remove(), 3000);
            }
        }
    }
</script>
@endsection
