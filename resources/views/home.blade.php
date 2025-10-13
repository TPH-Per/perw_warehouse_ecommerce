@extends('layout')

@section('content')
<div x-data="homePage()">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg shadow-xl p-12 text-white mb-12">
        <div class="max-w-3xl">
            <h1 class="text-5xl font-bold mb-4">Welcome to Warehouse E-Commerce</h1>
            <p class="text-xl mb-8">Discover amazing products at unbeatable prices. Shop now and enjoy fast, reliable delivery!</p>
            <div class="flex space-x-4">
                <a href="/products" class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                    Shop Now
                </a>
                <a href="/categories" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition">
                    Browse Categories
                </a>
            </div>
        </div>
    </div>

    <!-- Featured Products -->
    <div class="mb-12">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-3xl font-bold text-gray-900">Featured Products</h2>
            <a href="/products" class="text-blue-600 hover:text-blue-800">View All →</a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <template x-for="product in featuredProducts" :key="product.id">
                <div class="bg-white rounded-lg shadow hover:shadow-xl transition cursor-pointer">
                    <img :src="product.primary_image || '/images/placeholder.jpg'" :alt="product.name" class="w-full h-48 object-cover rounded-t-lg">
                    <div class="p-4">
                        <h3 class="font-semibold text-lg mb-2" x-text="product.name"></h3>
                        <p class="text-gray-600 text-sm mb-3 line-clamp-2" x-text="product.description"></p>
                        <div class="flex justify-between items-center">
                            <span class="text-blue-600 font-bold" x-text="formatCurrency(product.min_price)"></span>
                            <button @click="addToCart(product)" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- Categories -->
    <div class="mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">Shop by Category</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <template x-for="category in categories" :key="category.id">
                <a :href="`/categories/${category.id}/products`" class="bg-white rounded-lg shadow p-6 hover:shadow-xl transition text-center">
                    <div class="text-4xl mb-3">📦</div>
                    <h3 class="font-semibold" x-text="category.name"></h3>
                    <p class="text-gray-600 text-sm" x-text="`${category.product_count} products`"></p>
                </a>
            </template>
        </div>
    </div>

    <!-- Why Choose Us -->
    <div class="bg-white rounded-lg shadow-lg p-8 mb-12">
        <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">Why Choose Us?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="text-5xl mb-4">🚚</div>
                <h3 class="font-semibold text-xl mb-2">Fast Delivery</h3>
                <p class="text-gray-600">Get your orders delivered quickly and safely</p>
            </div>
            <div class="text-center">
                <div class="text-5xl mb-4">💰</div>
                <h3 class="font-semibold text-xl mb-2">Best Prices</h3>
                <p class="text-gray-600">Competitive prices on all products</p>
            </div>
            <div class="text-center">
                <div class="text-5xl mb-4">⭐</div>
                <h3 class="font-semibold text-xl mb-2">Quality Assured</h3>
                <p class="text-gray-600">Only the best products for our customers</p>
            </div>
        </div>
    </div>
</div>

<script>
    function homePage() {
        return {
            featuredProducts: [],
            categories: [],
            
            init() {
                this.loadFeaturedProducts();
                this.loadCategories();
            },
            
            async loadFeaturedProducts() {
                try {
                    const response = await axios.get('/products?featured=1&limit=8');
                    this.featuredProducts = response.data.products.data || response.data.products;
                } catch (error) {
                    console.error('Failed to load featured products:', error);
                }
            },
            
            async loadCategories() {
                try {
                    const response = await axios.get('/categories?limit=8');
                    this.categories = response.data.categories;
                } catch (error) {
                    console.error('Failed to load categories:', error);
                }
            },
            
            formatCurrency(amount) {
                return new Intl.NumberFormat('vi-VN', { 
                    style: 'currency', 
                    currency: 'VND' 
                }).format(amount);
            },
            
            async addToCart(product) {
                if (!this.checkAuth()) {
                    window.location.href = '/login';
                    return;
                }
                
                try {
                    const variant = product.variants?.[0];
                    if (!variant) {
                        this.showToast('Product variant not available', 'error');
                        return;
                    }
                    
                    await axios.post('/cart', {
                        product_variant_id: variant.id,
                        quantity: 1
                    });
                    
                    this.showToast('Added to cart successfully!', 'success');
                    // Reload cart count
                    window.location.reload();
                } catch (error) {
                    this.showToast(error.response?.data?.message || 'Failed to add to cart', 'error');
                }
            },
            
            checkAuth() {
                return !!localStorage.getItem('auth_token');
            },
            
            showToast(message, type) {
                // Simple toast implementation
                const toast = document.createElement('div');
                toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white ${
                    type === 'success' ? 'bg-green-500' : 'bg-red-500'
                } z-50`;
                toast.textContent = message;
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            }
        }
    }
</script>
@endsection
