// Dashboard module for admin analytics and overview
import api from '../utils/api.js';
import { UIHelpers } from '../utils/helpers.js';

export class Dashboard {
    constructor() {
        this.charts = {};
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadDashboardData();
    }

    bindEvents() {
        // Refresh dashboard data
        document.addEventListener('click', (e) => {
            if (e.target.id === 'refresh-dashboard') {
                this.loadDashboardData();
            }
        });

        // Date range filter
        const dateFilter = document.getElementById('dashboard-date-filter');
        if (dateFilter) {
            dateFilter.addEventListener('change', () => {
                this.loadDashboardData();
            });
        }
    }

    async loadDashboardData() {
        const container = document.getElementById('dashboard-content');
        if (!container) return;

        UIHelpers.showLoading(container, 'Loading dashboard...');

        try {
            const [orderStats, productStats, inventoryStats, salesStats] = await Promise.all([
                api.get('/admin/orders/statistics'),
                api.get('/admin/products/statistics'),
                api.get('/admin/inventory/statistics'),
                api.get('/admin/sales/statistics')
            ]);

            this.renderDashboard({
                orders: orderStats,
                products: productStats,
                inventory: inventoryStats,
                sales: salesStats
            });

        } catch (error) {
            container.innerHTML = `
                <div class="text-center py-8">
                    <div class="text-red-500 text-lg mb-2">Failed to load dashboard</div>
                    <button id="refresh-dashboard" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Try Again
                    </button>
                </div>
            `;
        }
    }

    renderDashboard(data) {
        const container = document.getElementById('dashboard-content');
        
        container.innerHTML = `
            <!-- Dashboard Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <h1 class="text-3xl font-bold text-gray-900">Dashboard</h1>
                    <div class="flex space-x-4">
                        <select id="dashboard-date-filter" class="px-4 py-2 border border-gray-300 rounded-md">
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month" selected>This Month</option>
                            <option value="year">This Year</option>
                        </select>
                        <button id="refresh-dashboard" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            Refresh
                        </button>
                    </div>
                </div>
            </div>

            <!-- Key Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                ${this.renderMetricCard('Total Orders', data.orders.total_orders, 'text-blue-600', '📦')}
                ${this.renderMetricCard('Revenue', UIHelpers.formatCurrency(data.sales.total_revenue), 'text-green-600', '💰')}
                ${this.renderMetricCard('Products', data.products.active_products, 'text-purple-600', '📋')}
                ${this.renderMetricCard('Low Stock', data.inventory.low_stock_count, 'text-red-600', '⚠️')}
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold mb-4">Sales Overview</h3>
                    <canvas id="sales-chart" width="400" height="200"></canvas>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold mb-4">Order Status</h3>
                    <canvas id="orders-chart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold mb-4">Recent Orders</h3>
                    <div id="recent-orders">
                        ${this.renderRecentOrders(data.orders.recent_orders || [])}
                    </div>
                </div>
                <div class="bg-white p-6 rounded-lg shadow">
                    <h3 class="text-lg font-semibold mb-4">Low Stock Alerts</h3>
                    <div id="low-stock-alerts">
                        ${this.renderLowStockAlerts(data.inventory.low_stock_items || [])}
                    </div>
                </div>
            </div>
        `;

        // Initialize charts
        this.initializeCharts(data);
    }

    renderMetricCard(title, value, colorClass, icon) {
        return `
            <div class="bg-white p-6 rounded-lg shadow">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 ${colorClass} text-2xl">${icon}</div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">${title}</p>
                        <p class="text-2xl font-semibold ${colorClass}">${value}</p>
                    </div>
                </div>
            </div>
        `;
    }

    renderRecentOrders(orders) {
        if (!orders.length) {
            return '<p class="text-gray-500 text-center py-4">No recent orders</p>';
        }

        return orders.map(order => `
            <div class="flex justify-between items-center py-3 border-b last:border-b-0">
                <div>
                    <p class="font-medium">#${order.id}</p>
                    <p class="text-sm text-gray-500">${order.customer_name}</p>
                </div>
                <div class="text-right">
                    <p class="font-medium">${UIHelpers.formatCurrency(order.total_amount)}</p>
                    <span class="text-xs px-2 py-1 rounded-full ${this.getStatusColor(order.status)}">
                        ${order.status}
                    </span>
                </div>
            </div>
        `).join('');
    }

    renderLowStockAlerts(items) {
        if (!items.length) {
            return '<p class="text-gray-500 text-center py-4">No low stock alerts</p>';
        }

        return items.map(item => `
            <div class="flex justify-between items-center py-3 border-b last:border-b-0">
                <div>
                    <p class="font-medium">${item.product_name}</p>
                    <p class="text-sm text-gray-500">${item.variant_name}</p>
                </div>
                <div class="text-right">
                    <p class="text-red-600 font-medium">${item.quantity} left</p>
                    <p class="text-xs text-gray-500">Min: ${item.minimum_stock}</p>
                </div>
            </div>
        `).join('');
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

    initializeCharts(data) {
        // Sales Chart
        const salesCtx = document.getElementById('sales-chart');
        if (salesCtx && window.Chart) {
            this.charts.sales = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: data.sales.daily_labels || [],
                    datasets: [{
                        label: 'Revenue',
                        data: data.sales.daily_revenue || [],
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return UIHelpers.formatCurrency(value);
                                }
                            }
                        }
                    }
                }
            });
        }

        // Orders Chart
        const ordersCtx = document.getElementById('orders-chart');
        if (ordersCtx && window.Chart) {
            this.charts.orders = new Chart(ordersCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Pending', 'Paid', 'Shipped', 'Delivered', 'Cancelled'],
                    datasets: [{
                        data: [
                            data.orders.pending_payment || 0,
                            data.orders.paid || 0,
                            data.orders.shipped || 0,
                            data.orders.delivered || 0,
                            data.orders.cancelled || 0
                        ],
                        backgroundColor: [
                            '#FCD34D',
                            '#10B981',
                            '#3B82F6',
                            '#059669',
                            '#EF4444'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    }

    destroy() {
        // Clean up charts
        Object.values(this.charts).forEach(chart => {
            if (chart && typeof chart.destroy === 'function') {
                chart.destroy();
            }
        });
        this.charts = {};
    }
}