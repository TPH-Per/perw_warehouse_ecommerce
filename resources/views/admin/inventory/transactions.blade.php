@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Inventory Transactions</h4>
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-primary">Back to Inventory</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Warehouse</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transactions as $transaction)
                                <tr>
                                    <td>{{ $transaction->created_at->format('M d, Y H:i') }}</td>
                                    <td>{{ $transaction->inventory->productVariant->product->name }}</td>
                                    <td>{{ $transaction->inventory->productVariant->sku }}</td>
                                    <td>{{ $transaction->inventory->warehouse->name }}</td>
                                    <td>
                                        <span class="badge bg-{{ $transaction->type === 'in' ? 'success' : 'danger' }}">
                                            {{ ucfirst($transaction->type) }}
                                        </span>
                                    </td>
                                    <td>{{ number_format($transaction->quantity) }}</td>
                                    <td>{{ $transaction->reference_type }} #{{ $transaction->reference_id }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">No transactions found</td>
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
