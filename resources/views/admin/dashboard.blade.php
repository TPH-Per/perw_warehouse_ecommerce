@extends('layout')

@section('content')
<div x-data="adminDashboard()">
    <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Admin Dashboard</h1>
        <div class="flex space-x-3">
            <select x-model="dateFilter" @change="loadDashboard" class="px-4 py-2 border border-gray-300 rounded-md">
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="year">This Year</option>
            </select>
            <button @click="loadDashboard" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Refresh
            </button>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 text-blue-600 text-2xl">📦</div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Orders</p>
                    <p class="text-2xl font-semibold text-blue-600" x-text="stats.orders?.total_orders || 0"></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 text-green-600 text-2xl">💰</div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Revenue</p>
                    <p class="text-2xl font-semibold text-green-600" x-text="formatCurrency(stats.revenue?.total_revenue || 0)"></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 text-purple-600 text-2xl">📋</div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Products</p>
                    <p class="text-2xl font-semibold text-purple-600" x-text="stats.products?.active_products || 0"></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 text-red-600 text-2xl">⚠️</div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Low Stock</p>
                    <p class="text-2xl font-semibold text-red-600" x-text="stats.inventory?.low_stock_count || 0"></p>
                </div>
            </div>
        </div>
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
            <div class="space-y-3">
                <template x-for="order in stats.orders?.recent_orders || []" :key="order.id">
                    <div class="flex justify-between items-center py-3 border-b last:border-b-0">
                        <div>
                            <p class="font-medium" x-text="'Order #' + order.order_code"></p>
                            <p class="text-sm text-gray-500" x-text="order.customer_name"></p>
                        </div>
                        <div class="text-right">
                            <p class="font-medium" x-text="formatCurrency(order.total_amount)"></p>
                            <span class="text-xs px-2 py-1 rounded-full" 
                                  :class="getStatusBadge(order.status)" 
                                  x-text="order.status"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow">
            <h3 class="text-lg font-semibold mb-4">Low Stock Alerts</h3>
            <div class="space-y-3">
                <template x-for="item in stats.inventory?.low_stock_items || []" :key="item.product_id">
                    <div class="flex justify-between items-center py-3 border-b last:border-b-0">
                        <div>
                            <p class="font-medium" x-text="item.product_name"></p>
                            <p class="text-sm text-gray-500" x-text="item.variant_name"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-red-600 font-medium" x-text="item.quantity + ' left'"></p>
                            <p class="text-xs text-gray-500" x-text="'Min: ' + item.minimum_stock"></p>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
    function adminDashboard() {
        return {
            stats: {},
            dateFilter: 'month',
            charts: {},
            
            init() {
                this.loadDashboard();
            },
            
            async loadDashboard() {
                try {
                    const response = await axios.get('/admin/dashboard', {
                        params: { period: this.dateFilter }
                    });
                    this.stats = response.data.stats;
                    this.$nextTick(() => {
                        this.initializeCharts();
                    });
                } catch (error) {
                    console.error('Failed to load dashboard:', error);
                }
            },
            
            initializeCharts() {
                // Sales Chart
                const salesCtx = document.getElementById('sales-chart');
                if (salesCtx && window.Chart) {
                    if (this.charts.sales) this.charts.sales.destroy();
                    
                    this.charts.sales = new Chart(salesCtx, {
                        type: 'line',
                        data: {
                            labels: this.stats.trends?.daily_labels || [],
                            datasets: [{
                                label: 'Revenue',
                                data: this.stats.trends?.daily_revenue || [],
                                borderColor: 'rgb(59, 130, 246)',
                                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }

                // Orders Chart
                const ordersCtx = document.getElementById('orders-chart');
                if (ordersCtx && window.Chart) {
                    if (this.charts.orders) this.charts.orders.destroy();
                    
                    this.charts.orders = new Chart(ordersCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Pending', 'Paid', 'Shipped', 'Delivered', 'Cancelled'],
                            datasets: [{
                                data: [
                                    this.stats.orders?.pending_payment || 0,
                                    this.stats.orders?.paid || 0,
                                    this.stats.orders?.shipped || 0,
                                    this.stats.orders?.delivered || 0,
                                    this.stats.orders?.cancelled || 0
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
            },
            
            formatCurrency(amount) {
                return new Intl.NumberFormat('vi-VN', {
                    style: 'currency',
                    currency: 'VND'
                }).format(amount || 0);
            },
            
            getStatusBadge(status) {
                const badges = {
                    'pending_payment': 'bg-yellow-100 text-yellow-800',
                    'paid': 'bg-green-100 text-green-800',
                    'shipped': 'bg-blue-100 text-blue-800',
                    'delivered': 'bg-green-100 text-green-800',
                    'cancelled': 'bg-red-100 text-red-800'
                };
                return badges[status] || 'bg-gray-100 text-gray-800';
            }
        }
    }
</script>
@endsection
