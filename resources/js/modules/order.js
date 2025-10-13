// Order management module
import api from '../utils/api.js';
import { UIHelpers } from '../utils/helpers.js';

export class OrderModule {
    constructor() {
        this.currentPage = 1;
        this.perPage = 10;
        this.filters = {};
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadOrders();
    }

    bindEvents() {
        // Filter orders
        document.addEventListener('change', (e) => {
            if (e.target.id === 'order-status-filter') {
                this.filters.status = e.target.value || null;
                this.currentPage = 1;
                this.loadOrders();
            }
        });

        // View order details
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('view-order-btn')) {
                const orderId = e.target.dataset.orderId;
                this.viewOrderDetails(orderId);
            } else if (e.target.classList.contains('cancel-order-btn')) {
                const orderId = e.target.dataset.orderId;
                this.cancelOrder(orderId);
            }
        });
    }

    async loadOrders() {
        const container = document.getElementById('orders-list');
        if (!container) return;

        UIHelpers.showLoading(container, 'Loading orders...');

        try {
            const params = {
                page: this.currentPage,
                per_page: this.perPage,
                ...this.filters
            };

            const response = await api.get('/orders', params);
            this.renderOrdersList(response.orders);
        } catch (error) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <div class="text-red-500 text-lg mb-2">Failed to load orders</div>
                    <button onclick="location.reload()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Try Again
                    </button>
                </div>
            `;
        }
    }

    renderOrdersList(ordersData) {
        const container = document.getElementById('orders-list');
        const orders = ordersData.data || ordersData;

        if (!orders.length) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <div class="text-gray-500 text-lg">No orders found</div>
                    <a href="/products" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Start Shopping
                    </a>
                </div>
            `;
            return;
        }

        container.innerHTML = `
            <!-- Orders Header -->
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold">My Orders (${ordersData.total || orders.length})</h2>
                <select id="order-status-filter" class="px-4 py-2 border border-gray-300 rounded-md">
                    <option value="">All Orders</option>
                    <option value="pending_payment">Pending Payment</option>
                    <option value="paid">Paid</option>
                    <option value="shipped">Shipped</option>
                    <option value="delivered">Delivered</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <!-- Orders List -->
            <div class="space-y-4">
                ${orders.map(order => this.renderOrderCard(order)).join('')}
            </div>

            <!-- Pagination -->
            ${this.renderPagination(ordersData)}
        `;
    }

    renderOrderCard(order) {
        return `
            <div class="bg-white p-6 rounded-lg shadow border">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-semibold text-lg">Order #${order.id}</h3>
                        <p class="text-gray-600">${UIHelpers.formatDate(order.created_at)}</p>
                    </div>
                    <div class="text-right">
                        <span class="px-3 py-1 rounded-full text-sm ${this.getStatusColor(order.status)}">
                            ${order.status.replace('_', ' ').toUpperCase()}
                        </span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Shipping Address</h4>
                        <p class="text-gray-600 text-sm">
                            ${order.shipping_recipient_name}<br>
                            ${order.shipping_address}<br>
                            ${order.shipping_recipient_phone}
                        </p>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Order Summary</h4>
                        <p class="text-gray-600 text-sm">
                            Items: ${order.order_details?.length || 0}<br>
                            Total: <span class="font-semibold">${UIHelpers.formatCurrency(order.total_amount)}</span>
                        </p>
                    </div>
                </div>

                <div class="flex justify-between items-center">
                    <button class="view-order-btn px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                            data-order-id="${order.id}">
                        View Details
                    </button>
                    ${order.status === 'pending_payment' ? `
                        <button class="cancel-order-btn px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                                data-order-id="${order.id}">
                            Cancel Order
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    }

    getStatusColor(status) {
        const colors = {
            'pending_payment': 'bg-yellow-100 text-yellow-800',
            'paid': 'bg-green-100 text-green-800',
            'shipped': 'bg-blue-100 text-blue-800',
            'delivered': 'bg-green-100 text-green-800',
            'cancelled': 'bg-red-100 text-red-800'
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
                this.loadOrders();
            }
        );
    }

    async viewOrderDetails(orderId) {
        try {
            const response = await api.get(`/orders/${orderId}`);
            const order = response.order;

            const modalContent = `
                <div class="space-y-6">
                    <!-- Order Info -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Order ID</label>
                            <p class="text-gray-900">#${order.id}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <span class="px-2 py-1 rounded-full text-xs ${this.getStatusColor(order.status)}">
                                ${order.status.replace('_', ' ').toUpperCase()}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Order Date</label>
                            <p class="text-gray-900">${UIHelpers.formatDate(order.created_at, 'long')}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Total Amount</label>
                            <p class="text-gray-900 font-semibold">${UIHelpers.formatCurrency(order.total_amount)}</p>
                        </div>
                    </div>

                    <!-- Shipping Info -->
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Shipping Information</h4>
                        <div class="bg-gray-50 p-3 rounded">
                            <p class="font-medium">${order.shipping_recipient_name}</p>
                            <p class="text-gray-600">${order.shipping_recipient_phone}</p>
                            <p class="text-gray-600">${order.shipping_address}</p>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Order Items</h4>
                        <div class="space-y-2">
                            ${(order.order_details || []).map(item => `
                                <div class="flex justify-between items-center py-2 border-b">
                                    <div>
                                        <p class="font-medium">${item.product_variant?.product?.name || 'Product'}</p>
                                        <p class="text-sm text-gray-600">Qty: ${item.quantity}</p>
                                    </div>
                                    <p class="font-medium">${UIHelpers.formatCurrency(item.total_price)}</p>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;

            UIHelpers.createModal('Order Details', modalContent, [
                {
                    text: 'Close',
                    class: 'bg-gray-300 text-gray-700',
                    onclick: `closeModal('modal-${Date.now()}')`
                }
            ]);

        } catch (error) {
            UIHelpers.showToast('Failed to load order details', 'error');
        }
    }

    async cancelOrder(orderId) {
        if (!confirm('Are you sure you want to cancel this order?')) {
            return;
        }

        try {
            const response = await api.post(`/orders/${orderId}/cancel`);
            UIHelpers.showToast(response.message, 'success');
            this.loadOrders();
        } catch (error) {
            UIHelpers.showToast(error.message, 'error');
        }
    }
}