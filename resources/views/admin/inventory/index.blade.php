@extends('layouts.admin')

@section('title', 'Inventory Management')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="bi bi-boxes"></i> Inventory Management</h1>
        <p class="text-muted mb-0">Track and manage product inventory across warehouses</p>
    </div>
</div>

<!-- Inventory Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon">
                <i class="bi bi-box-seam"></i>
            </div>
            <h5>Total Items</h5>
            <div class="value">{{ $inventories->total() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-warning">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h5>Low Stock</h5>
            <div class="value text-warning">{{ $inventories->filter(fn($i) => $i->quantity_on_hand <= $i->reorder_level)->count() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-danger">
                <i class="bi bi-x-circle"></i>
            </div>
            <h5>Out of Stock</h5>
            <div class="value text-danger">{{ $inventories->where('quantity_on_hand', 0)->count() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-success">
                <i class="bi bi-check-circle"></i>
            </div>
            <h5>In Stock</h5>
            <div class="value text-success">{{ $inventories->filter(fn($i) => $i->quantity_on_hand > $i->reorder_level)->count() }}</div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Product name, SKU..."
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Warehouse</label>
                <select name="warehouse_id" class="form-select">
                    <option value="">All Warehouses</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}"
                                {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Stock Level</label>
                <select name="stock_level" class="form-select">
                    <option value="">All Levels</option>
                    <option value="out_of_stock" {{ request('stock_level') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                    <option value="low_stock" {{ request('stock_level') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                    <option value="in_stock" {{ request('stock_level') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="{{ route('admin.inventory.low-stock') }}" class="btn btn-warning" title="View Low Stock">
                        <i class="bi bi-exclamation-triangle"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Inventory Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list"></i> Inventory Items ({{ $inventories->total() }} total)</span>
        <div class="btn-group">
            <a href="{{ route('admin.inventory.transactions') }}" class="btn btn-sm btn-info">
                <i class="bi bi-clock-history"></i> Transactions
            </a>
            <a href="{{ route('admin.inventory.export') }}" class="btn btn-sm btn-success">
                <i class="bi bi-download"></i> Export
            </a>
        </div>
    </div>
    <div class="card-body">
        @if($inventories->count() > 0)
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Variant (SKU)</th>
                        <th>Warehouse</th>
                        <th>On Hand</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inventories as $inventory)
                    <tr class="{{ $inventory->quantity_on_hand == 0 ? 'table-danger' : ($inventory->quantity_on_hand <= $inventory->reorder_level ? 'table-warning' : '') }}">
                        <td>
                            <strong>{{ $inventory->productVariant->product->name }}</strong>
                        </td>
                        <td>
                            {{ $inventory->productVariant->variant_name }}
                            <br>
                            <small class="text-muted">{{ $inventory->productVariant->sku }}</small>
                        </td>
                        <td>{{ $inventory->warehouse->name }}</td>
                        <td>
                            <strong class="{{ $inventory->quantity_on_hand == 0 ? 'text-danger' : ($inventory->quantity_on_hand <= $inventory->reorder_level ? 'text-warning' : 'text-success') }}">
                                {{ $inventory->quantity_on_hand }}
                            </strong>
                        </td>
                        <td>{{ $inventory->reorder_level }}</td>
                        <td>
                            @if($inventory->quantity_on_hand == 0)
                                <span class="badge bg-danger">Out of Stock</span>
                            @elseif($inventory->quantity_on_hand <= $inventory->reorder_level)
                                <span class="badge bg-warning">Low Stock</span>
                            @else
                                <span class="badge bg-success">In Stock</span>
                            @endif
                        </td>
                        <td>{{ $inventory->updated_at->format('M d, Y') }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.inventory.show', $inventory->id) }}"
                                   class="btn btn-info" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-primary" title="Adjust Quantity"
                                        data-bs-toggle="modal" data-bs-target="#adjustModal{{ $inventory->id }}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </div>

                            <!-- Adjust Inventory Modal -->
                            <div class="modal fade" id="adjustModal{{ $inventory->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Adjust Inventory</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.inventory.adjust', $inventory->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Current Quantity</label>
                                                    <input type="text" class="form-control" value="{{ $inventory->quantity_on_hand }}" disabled>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Adjustment Type</label>
                                                    <select name="adjustment_type" class="form-select" required>
                                                        <option value="addition">Addition (+)</option>
                                                        <option value="subtraction">Subtraction (-)</option>
                                                        <option value="set">Set to Specific Value</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Quantity</label>
                                                    <input type="number" name="quantity" class="form-control" min="0" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Reason</label>
                                                    <textarea name="reason" class="form-control" rows="2" required></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <button type="submit" class="btn btn-primary">Adjust Inventory</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $inventories->links() }}
        </div>
        @else
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 3em; color: #ccc;"></i>
            <p class="text-muted mt-3">No inventory items found</p>
        </div>
        @endif
    </div>
</div>
@endsection
