@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Giao dịch tồn kho</h4>
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-primary">Quay lại Kho hàng</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Ngày</th>
                                    <th>Sản phẩm</th>
                                    <th>SKU</th>
                                    <th>Kho hàng</th>
                                    <th>Loại</th>
                                    <th>Số lượng</th>
                                    <th>Tham chiếu</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                                    <td>{{ $transaction->productVariant?->product?->name ?? 'N/A' }}</td>
                                    <td>{{ $transaction->productVariant?->sku ?? 'N/A' }}</td>
                                    <td>{{ $transaction->warehouse?->name ?? 'N/A' }}</td>
                                    <td>
                                        @php
                                            $type = $transaction->type;
                                            $badge = $type === 'inbound' ? 'success' : ($type === 'outbound' ? 'danger' : 'warning');
                                            $label = $type === 'inbound' ? 'Nhập' : ($type === 'outbound' ? 'Xuất' : 'Điều chỉnh');
                                        @endphp
                                        <span class="badge bg-{{ $badge }}">
                                            {{ $label }}
                                        </span>
                                    </td>
                                    <td>{{ number_format($transaction->quantity) }}</td>
                                    <td>
                                        @if($transaction->order)
                                            Đơn {{ $transaction->order->order_code ?? ('#' . $transaction->order->id) }}
                                        @else
                                            {{ $transaction->notes ?? '-' }}
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">Không tìm thấy giao dịch</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
