@extends('layouts.manager')

@section('title', 'Quản lý Kho hàng')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-boxes"></i> Quản lý Kho hàng</h1>
    <p class="text-muted mb-0">Quản lý mức tồn kho và chuyển kho</p>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('manager.inventory.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" class="form-control" name="search"
                       placeholder="Tên sản phẩm hoặc mã sản phẩm..."
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Kho hàng</label>
                <select class="form-select" name="warehouse_id">
                    <option value="">Tất cả các kho</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}"
                                {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Trạng thái tồn kho</label>
                <select class="form-select" name="stock_status">
                    <option value="">Tất cả</option>
                    <option value="low" {{ request('stock_status') == 'low' ? 'selected' : '' }}>Hết hàng</option>
                    <option value="out" {{ request('stock_status') == 'out' ? 'selected' : '' }}>Hết hàng</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Lọc
                </button>
                <a href="{{ route('manager.inventory.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Xóa
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Inventory Table -->
<div class="card">
    <div class="card-body">
        @if($inventories->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Mã sản phẩm</th>
                        <th>Kho hàng</th>
                        <th>Hiện có</th>
                        <th>Đã đặt</th>
                        <th>Có sẵn</th>
                        <th>Mức đặt hàng lại</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inventories as $inventory)
                    @php
                        $available = $inventory->quantity_on_hand - $inventory->quantity_reserved;
                        $isLowStock = $inventory->quantity_on_hand <= $inventory->reorder_level;
                        $isOutOfStock = $inventory->quantity_on_hand <= 0;
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $inventory->productVariant->product->name }}</strong>
                            <br>
                            <small class="text-muted">{{ $inventory->productVariant->name }}</small>
                        </td>
                        <td>{{ $inventory->productVariant->sku }}</td>
                        <td>{{ $inventory->warehouse->name }}</td>
                        <td><strong>{{ $inventory->quantity_on_hand }}</strong></td>
                        <td>{{ $inventory->quantity_reserved }}</td>
                        <td>
                            <strong class="{{ $available > 0 ? 'text-success' : 'text-danger' }}">
                                {{ $available }}
                            </strong>
                        </td>
                        <td>{{ $inventory->reorder_level }}</td>
                        <td>
                            @if($isOutOfStock)
                                <span class="badge bg-danger">Hết hàng</span>
                            @elseif($isLowStock)
                                <span class="badge bg-warning">Hết hàng</span>
                            @else
                                <span class="badge bg-success">Còn hàng</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('manager.inventory.show', $inventory->id) }}"
                               class="btn btn-sm btn-info">
                                <i class="bi bi-eye"></i> Xem
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $inventories->links() }}
        </div>
        @else
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: var(--light-sky);"></i>
            <p class="text-muted mt-3">Không tìm thấy kho hàng</p>
        </div>
        @endif
    </div>
</div>
@endsection
