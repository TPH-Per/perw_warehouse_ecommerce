@extends('layouts.manager')

@section('title', 'Giao dịch Kho hàng')

@section('content')
<div class="page-header">
    <h1><i class="bi bi-clock-history"></i> Giao dịch Kho hàng</h1>
    <p class="text-muted mb-0">Lịch sử đầy đủ về các giao dịch tồn kho</p>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('manager.inventory.transactions') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Kho hàng</label>
                <select class="form-select" name="warehouse_id">
                    <option value="">Tất cả các kho</option>
                    @foreach(\App\Models\Warehouse::all() as $warehouse)
                        <option value="{{ $warehouse->id }}"
                                {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Loại</label>
                <select class="form-select" name="transaction_type">
                    <option value="">Tất cả các loại</option>
                    <option value="inbound" {{ request('transaction_type') == 'inbound' ? 'selected' : '' }}>Nhập kho</option>
                    <option value="outbound" {{ request('transaction_type') == 'outbound' ? 'selected' : '' }}>Xuất kho</option>
                    <option value="adjustment" {{ request('transaction_type') == 'adjustment' ? 'selected' : '' }}>Điều chỉnh</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Từ ngày</label>
                <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Đến ngày</label>
                <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Lọc
                </button>
                <a href="{{ route('manager.inventory.transactions') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Xóa
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Transactions Table -->
<div class="card">
    <div class="card-body">
        @if($transactions->count() > 0)
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead>
                    <tr>
                        <th>Ngày & Giờ</th>
                        <th>Sản phẩm</th>
                        <th>Kho hàng</th>
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
                            <strong>{{ $transaction->inventory->productVariant->product->name }}</strong>
                            <br>
                            <small class="text-muted">{{ $transaction->inventory->productVariant->sku }}</small>
                        </td>
                        <td>{{ $transaction->inventory->warehouse->name }}</td>
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

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $transactions->links() }}
        </div>
        @else
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 4rem; color: var(--light-sky);"></i>
            <p class="text-muted mt-3">Không tìm thấy giao dịch nào</p>
        </div>
        @endif
    </div>
</div>
@endsection
