// Inventory management module
import api from '../utils/api.js';
import { UIHelpers } from '../utils/helpers.js';

export class InventoryModule {
    constructor() {
        this.currentPage = 1;
        this.perPage = 15;
        this.filters = {};
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadInventory();
    }

    bindEvents() {
        // Search and filters
        const searchInput = document.getElementById('inventory-search');
        if (searchInput) {
            searchInput.addEventListener('input', UIHelpers.debounce((e) => {
                this.filters.search = e.target.value;
                this.currentPage = 1;
                this.loadInventory();
            }, 300));
        }

        // Low stock filter
        document.addEventListener('change', (e) => {
            if (e.target.id === 'low-stock-filter') {
                this.filters.low_stock = e.target.checked;
                this.currentPage = 1;
                this.loadInventory();
            }
        });

        // Stock adjustment
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('adjust-stock-btn')) {
                const inventoryId = e.target.dataset.inventoryId;
                this.showStockAdjustmentForm(inventoryId);
            } else if (e.target.classList.contains('view-stock-history-btn')) {
                const inventoryId = e.target.dataset.inventoryId;
                this.viewStockHistory(inventoryId);
            }
        });

        // Stock adjustment form
        document.addEventListener('submit', (e) => {
            if (e.target.id === 'stock-adjustment-form') {
                e.preventDefault();
                this.handleStockAdjustment(e.target);
            }
        });
    }

    async loadInventory() {
        const container = document.getElementById('inventory-list');
        if (!container) return;

        UIHelpers.showLoading(container, 'Loading inventory...');

        try {
            const params = {
                page: this.currentPage,
                per_page: this.perPage,
                ...this.filters
            };

            const response = await api.get('/admin/inventory', params);
            this.renderInventoryList(response.inventory);
        } catch (error) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <div class="text-red-500 text-lg mb-2">Failed to load inventory</div>
                    <button onclick="location.reload()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Try Again
                    </button>
                </div>
            `;
        }
    }

    renderInventoryList(inventoryData) {
        const container = document.getElementById('inventory-list');
        const inventory = inventoryData.data || inventoryData;

        if (!inventory.length) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <div class="text-gray-500 text-lg">No inventory items found</div>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <!-- Inventory Header -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold">Inventory Management</h2>
                <div class="flex space-x-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="low-stock-filter" class="mr-2" ${this.filters.low_stock ? 'checked' : ''}>
                        Show Low Stock Only
                    </label>
                </div>
            </div>

            <!-- Search and Filters -->
            <div class="bg-white p-4 rounded-lg shadow mb-6">
                <div class="flex space-x-4">
                    <input type="text" id="inventory-search" placeholder="Search products..." 
                           class="flex-1 px-4 py-2 border border-gray-300 rounded-md" value="${this.filters.search || ''}">
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reserved</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Available</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${inventory.map(item => this.renderInventoryRow(item)).join('')}
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            ${this.renderPagination(inventoryData)}
        `;
    }

    renderInventoryRow(item) {
        const variant = item.product_variant;
        const product = variant?.product;
        const isLowStock = item.quantity_on_hand <= item.minimum_stock;
        const availableStock = item.quantity_on_hand - item.quantity_reserved;

        return `
            <tr class="${isLowStock ? 'bg-red-50' : ''}">
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <img class="h-10 w-10 rounded-full object-cover" 
                             src="${product?.images?.[0]?.image_url || '/images/placeholder.jpg'}" 
                             alt="${product?.name || 'Product'}">
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">${product?.name || 'N/A'}</div>
                            <div class="text-sm text-gray-500">${variant?.variant_name || 'Default'}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${variant?.sku || 'N/A'}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm text-gray-900">${item.quantity_on_hand}</div>
                    <div class="text-xs text-gray-500">Min: ${item.minimum_stock}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    ${item.quantity_reserved}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm ${availableStock <= 0 ? 'text-red-600' : 'text-gray-900'}">${availableStock}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    ${isLowStock ? 
                        '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Low Stock</span>' :
                        '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">In Stock</span>'
                    }
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button class="adjust-stock-btn text-blue-600 hover:text-blue-900 mr-3"
                            data-inventory-id="${item.id}">
                        Adjust
                    </button>
                    <button class="view-stock-history-btn text-gray-600 hover:text-gray-900"
                            data-inventory-id="${item.id}">
                        History
                    </button>
                </td>
            </tr>
        `;
    }

    renderPagination(data) {
        if (!data.last_page || data.last_page <= 1) return '';
        
        return UIHelpers.createPagination(
            data.current_page,
            data.last_page,
            (page) => {
                this.currentPage = page;
                this.loadInventory();
            }
        );
    }

    async showStockAdjustmentForm(inventoryId) {
        try {
            const response = await api.get(`/admin/inventory/${inventoryId}`);
            const inventory = response.inventory;

            const modalContent = `
                <form id="stock-adjustment-form" class="space-y-4">
                    <input type="hidden" name="inventory_id" value="${inventoryId}">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Product</label>
                            <p class="text-gray-900">${inventory.product_variant?.product?.name || 'N/A'}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Current Stock</label>
                            <p class="text-gray-900">${inventory.quantity_on_hand}</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Adjustment Type</label>
                        <select name="adjustment_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            <option value="">Select Type</option>
                            <option value="increase">Increase Stock</option>
                            <option value="decrease">Decrease Stock</option>
                            <option value="set">Set Stock Level</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                        <input type="number" name="quantity" required min="1" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Reason</label>
                        <textarea name="reason" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md"
                                  placeholder="Reason for stock adjustment..."></textarea>
                    </div>
                </form>
            `;

            UIHelpers.createModal(
                'Adjust Stock',
                modalContent,
                [
                    {
                        text: 'Cancel',
                        class: 'bg-gray-300 text-gray-700',
                        onclick: `closeModal('modal-${Date.now()}')`
                    },
                    {
                        text: 'Adjust Stock',
                        class: 'bg-blue-600 text-white',
                        onclick: `document.getElementById('stock-adjustment-form').dispatchEvent(new Event('submit'))`
                    }
                ]
            );

        } catch (error) {
            UIHelpers.showToast('Failed to load inventory details', 'error');
        }
    }

    async handleStockAdjustment(form) {
        const formData = new FormData(form);
        const inventoryId = formData.get('inventory_id');

        try {
            const data = {
                adjustment_type: formData.get('adjustment_type'),
                quantity: parseInt(formData.get('quantity')),
                reason: formData.get('reason')
            };

            const response = await api.post(`/admin/inventory/${inventoryId}/adjust`, data);
            UIHelpers.showToast(response.message, 'success');
            this.loadInventory();
            
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

    async viewStockHistory(inventoryId) {
        try {
            const response = await api.get(`/admin/inventory/${inventoryId}/history`);
            const history = response.history;

            const modalContent = `
                <div class="space-y-4">
                    <div class="max-h-96 overflow-y-auto">
                        ${history.length ? history.map(entry => `
                            <div class="border-b pb-3 mb-3 last:border-b-0">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium">${entry.adjustment_type.toUpperCase()}</p>
                                        <p class="text-sm text-gray-600">${entry.reason}</p>
                                        <p class="text-xs text-gray-500">${UIHelpers.formatDate(entry.created_at, 'long')}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium ${entry.quantity_change > 0 ? 'text-green-600' : 'text-red-600'}">
                                            ${entry.quantity_change > 0 ? '+' : ''}${entry.quantity_change}
                                        </p>
                                        <p class="text-sm text-gray-600">Stock: ${entry.quantity_after}</p>
                                    </div>
                                </div>
                            </div>
                        `).join('') : '<p class="text-gray-500 text-center py-4">No stock history found</p>'}
                    </div>
                </div>
            `;

            UIHelpers.createModal('Stock History', modalContent, [
                {
                    text: 'Close',
                    class: 'bg-gray-300 text-gray-700',
                    onclick: `closeModal('modal-${Date.now()}')`
                }
            ]);

        } catch (error) {
            UIHelpers.showToast('Failed to load stock history', 'error');
        }
    }
}