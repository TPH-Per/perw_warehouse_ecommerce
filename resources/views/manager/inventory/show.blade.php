@extends('layouts.manager')

@section('title', 'Chi tiết Kho hàng')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-box-seam"></i> Chi tiết Kho hàng</h1>
        <p class="text-muted mb-0">{{ $inventory->productVariant->product->name }} - {{ $inventory->productVariant->name }}</p>
    </div>
    <a href="{{ route('manager.inventory.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Quay lại
    </a>
</div>

<div class="row">
    <!-- Inventory Info -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-info-circle"></i> Thông tin tồn kho
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <strong>Sản phẩm:</strong><br>
                        {{ $inventory->productVariant->product->name }}
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Mẫu mã:</strong><br>
                        {{ $inventory->productVariant->name }}
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Mã sản phẩm:</strong><br>
                        {{ $inventory->productVariant->sku }}
                    </div>
                    <div class="col-md-6 mb-3">
                        <strong>Kho hàng:</strong><br>
                        {{ $inventory->warehouse->name }}
                    </div>
                    <div class="col-md-3 mb-3">
                        <strong>Hiện có:</strong><br>
                        <span class="h4">{{ $inventory->quantity_on_hand }}</span>
                    </div>
                    <div class="col-md-3 mb-3">
                        <strong>Đã đặt:</strong><br>
                        <span class="h4">{{ $inventory->quantity_reserved }}</span>
                    </div>
                    <div class="col-md-3 mb-3">
                        <strong>Có sẵn:</strong><br>
                        <span class="h4 text-success">{{ $inventory->quantity_on_hand - $inventory->quantity_reserved }}</span>
                    </div>
                    <div class="col-md-3 mb-3">
                        <strong>Mức đặt hàng lại:</strong><br>
                        <span class="h4">{{ $inventory->reorder_level }}</span>
                    </div>
                </div>

                @php
                    $isLowStock = $inventory->quantity_on_hand <= $inventory->reorder_level;
                    $isOutOfStock = $inventory->quantity_on_hand <= 0;
                @endphp

                @if($isOutOfStock)
                    <div class="alert alert-danger mt-3">
                        <i class="bi bi-exclamation-triangle-fill"></i> <strong>Hết hàng!</strong> Mặt hàng này cần được bổ sung ngay.
                    </div>
                @elseif($isLowStock)
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle"></i> <strong>Hết hàng!</strong> Mức tồn kho ở hoặc dưới mức đặt hàng lại.
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-clock-history"></i> Giao dịch gần đây
            </div>
            <div class="card-body">
                @if($transactions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Loại</th>
                                <th>Số lượng</th>
                                <th>Tham chiếu</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('d M, Y H:i') }}</td>
                                <td>
                                    <span class="badge
                                        @if($transaction->transaction_type == 'inbound') bg-success
                                        @elseif($transaction->transaction_type == 'outbound') bg-danger
                                        @else bg-warning
                                        @endif">
                                        @if($transaction->transaction_type == 'inbound') Nhập kho
                                        @elseif($transaction->transaction_type == 'outbound') Xuất kho
                                        @else Điều chỉnh
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <strong class="{{ $transaction->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $transaction->quantity > 0 ? '+' : '' }}{{ $transaction->quantity }}
                                    </strong>
                                </td>
                                <td><small>{{ $transaction->reference_number ?? 'N/A' }}</small></td>
                                <td><small>{{ $transaction->notes ?? '-' }}</small></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center mt-3">
                    {{ $transactions->links() }}
                </div>
                @else
                <p class="text-muted text-center py-3">Chưa có giao dịch nào</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Actions Sidebar -->
    <div class="col-lg-4">
        <!-- Adjust Inventory -->
        <div class="card mb-3">
            <div class="card-header bg-warning text-white">
                <i class="bi bi-sliders"></i> Điều chỉnh Kho hàng
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('manager.inventory.adjust', $inventory->id) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Loại giao dịch</label>
                        <select class="form-select" name="transaction_type" required>
                            <option value="inbound">Nhập kho (Thêm hàng tồn)</option>
                            <option value="outbound">Xuất kho (Giảm hàng tồn)</option>
                            <option value="adjustment">Điều chỉnh</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số lượng</label>
                        <input type="number" class="form-control" name="quantity" required min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số tham chiếu (Tùy chọn)</label>
                        <input type="text" class="form-control" name="reference_number">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú (Tùy chọn)</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-warning w-100">
                        <i class="bi bi-check-circle"></i> Điều chỉnh
                    </button>
                </form>
            </div>
        </div>

        <!-- Transfer Inventory -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-arrow-left-right"></i> Chuyển đến Kho hàng khác
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('manager.inventory.transfer') }}">
                    @csrf
                    <input type="hidden" name="from_warehouse_id" value="{{ $inventory->warehouse_id }}">
                    <input type="hidden" name="product_variant_id" value="{{ $inventory->product_variant_id }}">

                    <div class="mb-3">
                        <label class="form-label">Đến Kho hàng</label>
                        <select class="form-select" name="to_warehouse_id" required>
                            <option value="">Chọn kho hàng...</option>
                            @foreach(\App\Models\Warehouse::where('id', '!=', $inventory->warehouse_id)->get() as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Số lượng</label>
                        <input type="number" class="form-control" name="quantity"
                               required min="1" max="{{ $inventory->quantity_on_hand }}">
                        <small class="text-muted">Tối đa: {{ $inventory->quantity_on_hand }}</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ghi chú (Tùy chọn)</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-arrow-right"></i> Chuyển
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
