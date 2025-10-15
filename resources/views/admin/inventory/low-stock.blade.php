@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Low Stock Items</h4>
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-primary">Back to Inventory</a>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.inventory.low-stock') }}" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <select name="warehouse_id" class="form-select">
                                    <option value="">All Warehouses</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                            {{ $warehouse->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary">Filter</button>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Warehouse</th>
                                    <th>Current Stock</th>
                                    <th>Reserved</th>
                                    <th>Available</th>
                                    <th>Reorder Level</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventories as $inventory)
                                <tr>
                                    <td>{{ $inventory->productVariant->product->name }}</td>
                                    <td>{{ $inventory->productVariant->sku }}</td>
                                    <td>{{ $inventory->warehouse->name }}</td>
                                    <td>{{ number_format($inventory->quantity_on_hand) }}</td>
                                    <td>{{ number_format($inventory->quantity_reserved) }}</td>
                                    <td>{{ number_format($inventory->quantity_available) }}</td>
                                    <td>{{ number_format($inventory->reorder_level) }}</td>
                                    <td>
                                        @if($inventory->quantity_available <= $inventory->reorder_level)
                                            <span class="badge bg-danger">Low Stock</span>
                                        @else
                                            <span class="badge bg-warning">Near Reorder</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">No low stock items found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $inventories->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
