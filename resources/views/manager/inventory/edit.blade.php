@extends('layouts.manager')

@section('title', 'Chỉnh sửa tồn kho')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-box"></i> Chỉnh sửa tồn kho</h1>
        <p class="text-muted mb-0">Cập nhật thông tin bản ghi tồn kho.</p>
    </div>
    <a href="{{ route('manager.inventory.show', $inventory->id) }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Quay lại
    </a>
</div>

@php
    $user = auth()->user();
    $isWarehouseSpecificManager = $user && $user->role->name === 'manager' && $user->warehouse_id;
@endphp

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-info-circle"></i> Chỉnh sửa thông tin tồn kho
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('manager.inventory.update', $inventory->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sản phẩm</label>
                            <input type="text" class="form-control" value="{{ $inventory->productVariant->product->name }}" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mẫu mã</label>
                            <input type="text" class="form-control" value="{{ $inventory->productVariant->name }}" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mã sản phẩm</label>
                            <input type="text" class="form-control" value="{{ $inventory->productVariant->sku }}" disabled>
                        </div>
                        @if(!$isWarehouseSpecificManager)
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kho hàng</label>
                            <select class="form-select" name="warehouse_id" required>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ $inventory->warehouse_id == $warehouse->id ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        @else
                            <input type="hidden" name="warehouse_id" value="{{ $user->warehouse_id }}">
                        @endif
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hiện có *</label>
                            <input type="number" class="form-control" name="quantity_on_hand" value="{{ old('quantity_on_hand', $inventory->quantity_on_hand) }}" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Đã đặt *</label>
                            <input type="number" class="form-control" name="quantity_reserved" value="{{ old('quantity_reserved', $inventory->quantity_reserved) }}" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mức đặt hàng lại *</label>
                            <input type="number" class="form-control" name="reorder_level" value="{{ old('reorder_level', $inventory->reorder_level) }}" min="0" required>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Cập nhật
                        </button>
                        <a href="{{ route('manager.inventory.show', $inventory->id) }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-lightbulb"></i> Thông tin
            </div>
            <div class="card-body">
                <p>Sử dụng chức năng này để chỉnh sửa thông tin tồn kho cơ bản.</p>
                <p>Để thực hiện xuất nhập kho, vui lòng sử dụng chức năng "Xuất Nhập Kho" trong trang chi tiết kho hàng.</p>
            </div>
        </div>
    </div>
</div>
@endsection
