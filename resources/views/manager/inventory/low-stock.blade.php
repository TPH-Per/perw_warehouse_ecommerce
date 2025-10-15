@extends('layouts.manager')

@section('title', 'Mặt hàng gần hết')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-exclamation-triangle text-warning"></i> Mặt hàng gần hết</h1>
    <p class="text-muted mb-0">Các mặt hàng ở hoặc dưới mức đặt hàng lại</p>
</div>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('manager.inventory.low-stock') }}" class="row g-3">
            <div class="col-md-4">
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
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Lọc
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Low Stock Table -->
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
                        <th>Mức đặt hàng lại</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inventories as $inventory)
                    <tr>
                        <td>
                            <strong>{{ $inventory->productVariant->product->name }}</strong>
                            <br>
                            <small class="text-muted">{{ $inventory->productVariant->name }}</small>
                        </td>
                        <td>{{ $inventory->productVariant->sku }}</td>
                        <td>{{ $inventory->warehouse->name }}</td>
                        <td>
                            <strong class="{{ $inventory->quantity_on_hand <= 0 ? 'text-danger' : 'text-warning' }}">
                                {{ $inventory->quantity_on_hand }}
                            </strong>
                        </td>
                        <td>{{ $inventory->reorder_level }}</td>
                        <td>
                            @if($inventory->quantity_on_hand <= 0)
                                <span class="badge bg-danger">Hết hàng</span>
                            @else
                                <span class="badge bg-warning">Hết hàng</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('manager.inventory.show', $inventory->id) }}"
                               class="btn btn-sm btn-warning">
                                <i class="bi bi-sliders"></i> Điều chỉnh
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
            <i class="bi bi-check-circle text-success" style="font-size: 4rem;"></i>
            <p class="text-muted mt-3">Tất cả các mặt hàng đều được cung cấp đầy đủ!</p>
        </div>
        @endif
    </div>
</div>
@endsection
