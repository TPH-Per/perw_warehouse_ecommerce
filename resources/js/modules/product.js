// Product management module
import api from '../utils/api.js';
import { UIHelpers } from '../utils/helpers.js';

export class ProductModule {
    constructor() {
        this.currentPage = 1;
        this.perPage = 15;
        this.filters = {};
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadProducts();
        this.loadCategories();
    }

    bindEvents() {
        // Search products
        const searchInput = document.getElementById('product-search');
        if (searchInput) {
            searchInput.addEventListener('input', UIHelpers.debounce((e) => {
                this.filters.search = e.target.value;
                this.currentPage = 1;
                this.loadProducts();
            }, 300));
        }

        // Category filter
        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', (e) => {
                this.filters.category_id = e.target.value || null;
                this.currentPage = 1;
                this.loadProducts();
            });
        }

        // Sort options
        const sortSelect = document.getElementById('sort-products');
        if (sortSelect) {
            sortSelect.addEventListener('change', (e) => {
                const [sortBy, sortOrder] = e.target.value.split('|');
                this.filters.sort_by = sortBy;
                this.filters.sort_order = sortOrder;
                this.currentPage = 1;
                this.loadProducts();
            });
        }

        // Add product button
        document.addEventListener('click', (e) => {
            if (e.target.id === 'add-product-btn') {
                this.showProductForm();
            } else if (e.target.classList.contains('edit-product-btn')) {
                const productId = e.target.dataset.productId;
                this.showProductForm(productId);
            } else if (e.target.classList.contains('delete-product-btn')) {
                const productId = e.target.dataset.productId;
                this.deleteProduct(productId);
            } else if (e.target.classList.contains('view-product-btn')) {
                const productId = e.target.dataset.productId;
                this.viewProduct(productId);
            }
        });

        // Product form submission
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'product-form') {
                e.preventDefault();
                this.handleProductSubmit(e.target);
            }
        });
    }

    async loadProducts() {
        const container = document.getElementById('products-list');
        if (!container) return;

        UIHelpers.showLoading(container, 'Loading products...');

        try {
            const params = {
                page: this.currentPage,
                per_page: this.perPage,
                ...this.filters
            };

            const response = await api.get('/products', params);
            this.renderProductsList(response.products);
        } catch (error) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <div class="text-red-500 text-lg mb-2">Failed to load products</div>
                    <button onclick="location.reload()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Try Again
                    </button>
                </div>
            `;
        }
    }

    async loadCategories() {
        try {
            const response = await api.get('/products/categories');
            this.renderCategoryFilter(response.categories);
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }

    renderCategoryFilter(categories) {
        const select = document.getElementById('category-filter');
        if (!select) return;

        select.innerHTML = '<option value="">All Categories</option>';
        
        categories.forEach(category => {
            select.innerHTML += `<option value="${category.id}">${category.name}</option>`;
            
            // Add subcategories
            if (category.children && category.children.length > 0) {
                category.children.forEach(child => {
                    select.innerHTML += `<option value="${child.id}">-- ${child.name}</option>`;
                });
            }
        });
    }

    renderProductsList(productsData) {
        const container = document.getElementById('products-list');
        const products = productsData.data || productsData;
        
        if (!products.length) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <div class="text-gray-500 text-lg">No products found</div>
                    <button id="add-product-btn" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Add First Product
                    </button>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <!-- Products Header -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold">Products (${productsData.total || products.length})</h2>
                <button id="add-product-btn" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Add Product
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-lg shadow mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="text" id="product-search" placeholder="Search products..." 
                           class="px-4 py-2 border border-gray-300 rounded-md" value="${this.filters.search || ''}">
                    <select id="category-filter" class="px-4 py-2 border border-gray-300 rounded-md">
                        <!-- Categories will be loaded here -->
                    </select>
                    <select id="sort-products" class="px-4 py-2 border border-gray-300 rounded-md">
                        <option value="created_at|desc">Newest First</option>
                        <option value="created_at|asc">Oldest First</option>
                        <option value="name|asc">Name A-Z</option>
                        <option value="name|desc">Name Z-A</option>
                    </select>
                </div>
            </div>

            <!-- Products Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                ${products.map(product => this.renderProductCard(product)).join('')}
            </div>

            <!-- Pagination -->
            ${this.renderPagination(productsData)}
        `;

        // Reload category filter
        this.loadCategories();
    }

    renderProductCard(product) {
        const primaryImage = product.images && product.images[0] ? product.images[0].image_url : '/images/placeholder.jpg';
        const variantCount = product.variants ? product.variants.length : 0;
        
        return `
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <img src="${primaryImage}" alt="${product.name}" class="w-full h-48 object-cover">
                <div class="p-4">
                    <h3 class="font-semibold text-lg mb-2">${product.name}</h3>
                    <p class="text-gray-600 text-sm mb-3 line-clamp-2">${product.description || 'No description'}</p>
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-sm text-gray-500">${variantCount} variants</span>
                        <span class="px-2 py-1 rounded-full text-xs ${this.getStatusColor(product.status)}">
                            ${product.status}
                        </span>
                    </div>
                    <div class="flex space-x-2">
                        <button class="view-product-btn flex-1 px-3 py-2 bg-gray-100 text-gray-700 rounded hover:bg-gray-200 text-sm" 
                                data-product-id="${product.id}">
                            View
                        </button>
                        <button class="edit-product-btn flex-1 px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm" 
                                data-product-id="${product.id}">
                            Edit
                        </button>
                        <button class="delete-product-btn px-3 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm" 
                                data-product-id="${product.id}">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    getStatusColor(status) {
        const colors = {
            'active': 'bg-green-100 text-green-800',
            'inactive': 'bg-red-100 text-red-800',
            'draft': 'bg-yellow-100 text-yellow-800'
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    }

    renderPagination(data) {
        if (!data.last_page || data.last_page <= 1) return '';
        
        return UIHelpers.createPagination(
            data.current_page,
            data.last_page,
            (page) => {
                this.currentPage = page;
                this.loadProducts();
            }
        );
    }

    async showProductForm(productId = null) {
        const isEdit = productId !== null;
        let product = null;

        if (isEdit) {
            try {
                const response = await api.get(`/admin/products/${productId}`);
                product = response.product;
            } catch (error) {
                UIHelpers.showToast('Failed to load product details', 'error');
                return;
            }
        }

        const modalContent = `
            <form id="product-form" class="space-y-4">
                <input type="hidden" name="product_id" value="${productId || ''}">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Product Name</label>
                    <input type="text" name="name" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md"
                           value="${product?.name || ''}">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                    <select name="category_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="">Select Category</option>
                        <!-- Categories will be loaded here -->
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md">${product?.description || ''}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                        <option value="active" ${product?.status === 'active' ? 'selected' : ''}>Active</option>
                        <option value="inactive" ${product?.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                        <option value="draft" ${product?.status === 'draft' ? 'selected' : ''}>Draft</option>
                    </select>
                </div>
            </form>
        `;

        UIHelpers.createModal(
            isEdit ? 'Edit Product' : 'Add Product',
            modalContent,
            [
                {
                    text: 'Cancel',
                    class: 'bg-gray-300 text-gray-700',
                    onclick: `closeModal('modal-${Date.now()}')`
                },
                {
                    text: isEdit ? 'Update' : 'Create',
                    class: 'bg-blue-600 text-white',
                    onclick: `document.getElementById('product-form').dispatchEvent(new Event('submit'))`
                }
            ]
        );

        // Load categories in modal
        setTimeout(() => {
            this.loadCategoriesInModal(product?.category_id);
        }, 100);
    }

    async loadCategoriesInModal(selectedCategoryId = null) {
        try {
            const response = await api.get('/products/categories');
            const select = document.querySelector('#product-form select[name="category_id"]');
            
            if (select) {
                select.innerHTML = '<option value="">Select Category</option>';
                response.categories.forEach(category => {
                    const selected = category.id == selectedCategoryId ? 'selected' : '';
                    select.innerHTML += `<option value="${category.id}" ${selected}>${category.name}</option>`;
                });
            }
        } catch (error) {
            console.error('Failed to load categories:', error);
        }
    }

    async handleProductSubmit(form) {
        const formData = new FormData(form);
        const productId = formData.get('product_id');
        const isEdit = productId && productId !== '';

        try {
            const data = {
                name: formData.get('name'),
                category_id: formData.get('category_id'),
                description: formData.get('description'),
                status: formData.get('status')
            };

            let response;
            if (isEdit) {
                response = await api.put(`/admin/products/${productId}`, data);
            } else {
                response = await api.post('/admin/products', data);
            }

            UIHelpers.showToast(response.message, 'success');
            this.loadProducts();
            
            // Close modal
            const modal = document.querySelector('[id^="modal-"]');
            if (modal) {
                document.body.removeChild(modal);
            }

        } catch (error) {
            UIHelpers.showToast(error.message, 'error');
            UIHelpers.showFormErrors(form, error.errors || {});
        }
    }

    async deleteProduct(productId) {
        if (!confirm('Are you sure you want to delete this product?')) {
            return;
        }

        try {
            const response = await api.delete(`/admin/products/${productId}`);
            UIHelpers.showToast(response.message, 'success');
            this.loadProducts();
        } catch (error) {
            UIHelpers.showToast(error.message, 'error');
        }
    }

    async viewProduct(productId) {
        try {
            const response = await api.get(`/admin/products/${productId}`);
            const product = response.product;

            const modalContent = `
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <p class="text-gray-900">${product.name}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <span class="px-2 py-1 rounded-full text-xs ${this.getStatusColor(product.status)}">
                                ${product.status}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Category</label>
                            <p class="text-gray-900">${product.category?.name || 'N/A'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Created</label>
                            <p class="text-gray-900">${UIHelpers.formatDate(product.created_at)}</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <p class="text-gray-900">${product.description || 'No description'}</p>
                    </div>
                </div>
            `;

            UIHelpers.createModal('Product Details', modalContent, [
                {
                    text: 'Close',
                    class: 'bg-gray-300 text-gray-700',
                    onclick: `closeModal('modal-${Date.now()}')`
                }
            ]);

        } catch (error) {
            UIHelpers.showToast('Failed to load product details', 'error');
        }
    }
}