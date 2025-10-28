@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Chi tiết Kho hàng</h4>
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-primary">Quay lại Kho hàng</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Thông tin sản phẩm</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th>Sản phẩm:</th>
                                    <td>{{ $inventory->productVariant->product->name }}</td>
                                </tr>
                                <tr>
                                    <th>Mẫu mã:</th>
                                    <td>{{ $inventory->productVariant->name }}</td>
                                </tr>
                                <tr>
                                    <th>SKU:</th>
                                    <td>{{ $inventory->productVariant->sku }}</td>
                                </tr>
                                <tr>
                                    <th>Giá:</th>
                                    <td>{{ number_format($inventory->productVariant->price, 0, ',', '.') }} VND</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Thông tin tồn kho</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th>Kho hàng:</th>
                                    <td>{{ $inventory->warehouse->name }}</td>
                                </tr>
                                <tr>
                                    <th>Vị trí:</th>
                                    <td>{{ $inventory->warehouse->location }}</td>
                                </tr>
                                <tr>
                                    <th>Hiện có:</th>
                                    <td>{{ number_format($inventory->quantity_on_hand) }}</td>
                                </tr>
                                <tr>
                                    <th>Đã đặt:</th>
                                    <td>{{ number_format($inventory->quantity_reserved) }}</td>
                                </tr>
                                <tr>
                                    <th>Có sẵn:</th>
                                    <td>{{ number_format($inventory->quantity_available) }}</td>
                                </tr>
                                <tr>
                                    <th>Mức đặt hàng lại:</th>
                                    <td>{{ number_format($inventory->reorder_level) }}</td>
                                </tr>
                                <tr>
                                    <th>Trạng thái:</th>
                                    <td>
                                        @if($inventory->quantity_on_hand <= 0)
                                            <span class="badge bg-danger">Hết hàng</span>
                                        @elseif($inventory->quantity_on_hand <= $inventory->reorder_level)
                                            <span class="badge bg-warning">Hàng gần hết</span>
                                        @else
                                            <span class="badge bg-success">Còn hàng</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>Giao dịch gần đây</h5>
                            @if($transactions->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
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
                                            <td>{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                                            <td>
                                                <span class="badge bg-{{ $transaction->type === 'inbound' ? 'success' : ($transaction->type === 'outbound' ? 'danger' : 'info') }}">
                                                    {{ ucfirst($transaction->type) }}
                                                </span>
                                            </td>
                                            <td>{{ number_format($transaction->quantity) }}</td>
                                            <td>{{ $transaction->reference_number ?? 'N/A' }}</td>
                                            <td>{{ $transaction->notes ?? 'N/A' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            {{ $transactions->links() }}
                            @else
                            <p>Không tìm thấy giao dịch cho mặt hàng tồn kho này.</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustInventoryModal">Điều chỉnh tồn kho</button>
                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#setReorderLevelModal">Thiết lập mức đặt hàng lại</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Adjust Inventory Modal -->
<div class="modal fade" id="adjustInventoryModal" tabindex="-1" aria-labelledby="adjustInventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="adjustInventoryModalLabel">Điều chỉnh tồn kho</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form action="{{ route('admin.inventory.adjust', $inventory) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="type" class="form-label">Loại giao dịch</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="inbound">Nhập kho (Tăng tồn)</option>
                            <option value="outbound">Xuất kho (Giảm tồn)</option>
                            <option value="adjustment">Điều chỉnh (Đặt số lượng chính xác)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Số lượng</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="reference_number" class="form-label">Số tham chiếu (Tuỳ chọn)</label>
                        <input type="text" class="form-control" id="reference_number" name="reference_number">
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Ghi chú (Tuỳ chọn)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Điều chỉnh tồn kho</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Set Reorder Level Modal -->
<div class="modal fade" id="setReorderLevelModal" tabindex="-1" aria-labelledby="setReorderLevelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="setReorderLevelModalLabel">Thiết lập mức đặt hàng lại</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form action="{{ route('admin.inventory.reorder-level', $inventory) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reorder_level" class="form-label">Mức đặt hàng lại</label>
                        <input type="number" class="form-control" id="reorder_level" name="reorder_level" min="0" value="{{ $inventory->reorder_level }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Thiết lập mức đặt hàng lại</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
