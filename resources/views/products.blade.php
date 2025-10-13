@extends('layout')

@section('content')
<div x-data="productsPage()">
    <h1 class="text-3xl font-bold mb-6">Products</h1>
    
    <!-- Filters -->
    <div class="bg-white p-6 rounded-lg shadow mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text" x-model="filters.search" @input.debounce.500ms="loadProducts" placeholder="Search products..." 
                   class="px-4 py-2 border border-gray-300 rounded-md">
            
            <select x-model="filters.category_id" @change="loadProducts" class="px-4 py-2 border border-gray-300 rounded-md">
                <option value="">All Categories</option>
                <template x-for="category in categories" :key="category.id">
                    <option :value="category.id" x-text="category.name"></option>
                </template>
            </select>
            
            <select x-model="filters.sort_by" @change="loadProducts" class="px-4 py-2 border border-gray-300 rounded-md">
                <option value="created_at|desc">Newest First</option>
                <option value="created_at|asc">Oldest First</option>
                <option value="name|asc">Name A-Z</option>
                <option value="name|desc">Name Z-A</option>
            </select>
            
            <select x-model="filters.status" @change="loadProducts" class="px-4 py-2 border border-gray-300 rounded-md">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="featured">Featured</option>
            </select>
        </div>
    </div>
    
    <!-- Products Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <template x-for="product in products" :key="product.id">
            <div class="bg-white rounded-lg shadow hover:shadow-xl transition">
                <div class="relative">
                    <img :src="product.primary_image || '/images/placeholder.jpg'" :alt="product.name" class="w-full h-48 object-cover rounded-t-lg">
                    <button @click="toggleWishlist(product.id)" class="absolute top-2 right-2 p-2 bg-white rounded-full shadow">
                        <svg class="w-5 h-5" :class="isInWishlist(product.id) ? 'text-red-500 fill-current' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </button>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold text-lg mb-2" x-text="product.name"></h3>
                    <p class="text-gray-600 text-sm mb-3 line-clamp-2" x-text="product.description || 'No description'"></p>
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-blue-600 font-bold" x-text="product.price_range + ' VND'"></span>
                        <template x-if="product.average_rating > 0">
                            <div class="flex items-center">
                                <span class="text-yellow-500">⭐</span>
                                <span class="text-sm ml-1" x-text="product.average_rating"></span>
                            </div>
                        </template>
                    </div>
                    <div class="flex space-x-2">
                        <button @click="viewProduct(product.id)" class="flex-1 px-3 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 text-sm">
                            View
                        </button>
                        <button @click="addToCart(product)" :disabled="!product.has_stock" class="flex-1 px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm disabled:bg-gray-300">
                            <span x-text="product.has_stock ? 'Add to Cart' : 'Out of Stock'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
    
    <!-- Empty State -->
    <template x-if="products.length === 0">
        <div class="text-center py-12">
            <div class="text-6xl mb-4">📦</div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">No products found</h3>
            <p class="text-gray-600">Try adjusting your filters</p>
        </div>
    </template>
    
    <!-- Pagination -->
    <div x-show="pagination.last_page > 1" class="flex justify-center space-x-2">
        <button @click="changePage(pagination.current_page - 1)" :disabled="pagination.current_page === 1" 
                class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50">
            Previous
        </button>
        <template x-for="page in paginationPages" :key="page">
            <button @click="changePage(page)" :class="page === pagination.current_page ? 'bg-blue-600 text-white' : 'bg-gray-200'" 
                    class="px-4 py-2 rounded hover:bg-blue-700">
                <span x-text="page"></span>
            </button>
        </template>
        <button @click="changePage(pagination.current_page + 1)" :disabled="pagination.current_page === pagination.last_page" 
                class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50">
            Next
        </button>
    </div>
</div>

<script>
    function productsPage() {
        return {
            products: [],
            categories: [],
            wishlist: [],
            filters: {
                search: '',
                category_id: '',
                sort_by: 'created_at|desc',
                status: ''
            },
            pagination: {
                current_page: 1,
                last_page: 1,
                per_page: 16
            },
            
            init() {
                this.loadProducts();
                this.loadCategories();
                if (this.checkAuth()) {
                    this.loadWishlist();
                }
            },
            
            async loadProducts() {
                try {
                    const params = {
                        ...this.filters,
                        page: this.pagination.current_page,
                        per_page: this.pagination.per_page
                    };
                    
                    const response = await axios.get('/products', { params });
                    this.products = response.data.products.data || response.data.products;
                    this.pagination = {
                        current_page: response.data.products.current_page || 1,
                        last_page: response.data.products.last_page || 1,
                        per_page: response.data.products.per_page || 16
                    };
                } catch (error) {
                    console.error('Failed to load products:', error);
                }
            },
            
            async loadCategories() {
                try {
                    const response = await axios.get('/categories');
                    this.categories = response.data.categories;
                } catch (error) {
                    console.error('Failed to load categories:', error);
                }
            },
            
            async loadWishlist() {
                try {
                    const response = await axios.get('/wishlist');
                    this.wishlist = response.data.wishlist.map(item => item.product_id);
                } catch (error) {
                    console.error('Failed to load wishlist:', error);
                }
            },
            
            isInWishlist(productId) {
                return this.wishlist.includes(productId);
            },
            
            async toggleWishlist(productId) {
                if (!this.checkAuth()) {
                    window.location.href = '/login';
                    return;
                }
                
                try {
                    await axios.post('/wishlist/toggle', { product_id: productId });
                    await this.loadWishlist();
                } catch (error) {
                    console.error('Failed to toggle wishlist:', error);
                }
            },
            
            async addToCart(product) {
                if (!this.checkAuth()) {
                    window.location.href = '/login';
                    return;
                }
                
                try {
                    const variant = product.variants?.[0];
                    if (!variant) {
                        alert('Product variant not available');
                        return;
                    }
                    
                    await axios.post('/cart', {
                        product_variant_id: variant.id,
                        quantity: 1
                    });
                    
                    alert('Added to cart successfully!');
                    window.location.reload();
                } catch (error) {
                    alert(error.response?.data?.message || 'Failed to add to cart');
                }
            },
            
            viewProduct(productId) {
                window.location.href = `/products/${productId}`;
            },
            
            changePage(page) {
                if (page < 1 || page > this.pagination.last_page) return;
                this.pagination.current_page = page;
                this.loadProducts();
            },
            
            get paginationPages() {
                const pages = [];
                const start = Math.max(1, this.pagination.current_page - 2);
                const end = Math.min(this.pagination.last_page, this.pagination.current_page + 2);
                
                for (let i = start; i <= end; i++) {
                    pages.push(i);
                }
                return pages;
            },
            
            checkAuth() {
                return !!localStorage.getItem('auth_token');
            }
        }
    }
</script>
@endsection
