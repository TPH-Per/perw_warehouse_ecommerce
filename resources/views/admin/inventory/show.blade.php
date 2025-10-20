@extends('layouts.admin')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Inventory Details</h4>
                    <a href="{{ route('admin.inventory.index') }}" class="btn btn-primary">Back to Inventory</a>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Product Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th>Product:</th>
                                    <td>{{ $inventory->productVariant->product->name }}</td>
                                </tr>
                                <tr>
                                    <th>Variant:</th>
                                    <td>{{ $inventory->productVariant->name }}</td>
                                </tr>
                                <tr>
                                    <th>SKU:</th>
                                    <td>{{ $inventory->productVariant->sku }}</td>
                                </tr>
                                <tr>
                                    <th>Price:</th>
                                    <td>{{ number_format($inventory->productVariant->price, 0, ',', '.') }} VND</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Inventory Information</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th>Warehouse:</th>
                                    <td>{{ $inventory->warehouse->name }}</td>
                                </tr>
                                <tr>
                                    <th>Location:</th>
                                    <td>{{ $inventory->warehouse->location }}</td>
                                </tr>
                                <tr>
                                    <th>On Hand:</th>
                                    <td>{{ number_format($inventory->quantity_on_hand) }}</td>
                                </tr>
                                <tr>
                                    <th>Reserved:</th>
                                    <td>{{ number_format($inventory->quantity_reserved) }}</td>
                                </tr>
                                <tr>
                                    <th>Available:</th>
                                    <td>{{ number_format($inventory->quantity_available) }}</td>
                                </tr>
                                <tr>
                                    <th>Reorder Level:</th>
                                    <td>{{ number_format($inventory->reorder_level) }}</td>
                                </tr>
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        @if($inventory->quantity_on_hand <= 0)
                                            <span class="badge bg-danger">Out of Stock</span>
                                        @elseif($inventory->quantity_on_hand <= $inventory->reorder_level)
                                            <span class="badge bg-warning">Low Stock</span>
                                        @else
                                            <span class="badge bg-success">In Stock</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h5>Recent Transactions</h5>
                            @if($transactions->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Quantity</th>
                                            <th>Reference</th>
                                            <th>Notes</th>
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
                            <p>No transactions found for this inventory item.</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adjustInventoryModal">Adjust Inventory</button>
                    <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#setReorderLevelModal">Set Reorder Level</button>
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
                <h5 class="modal-title" id="adjustInventoryModalLabel">Adjust Inventory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.inventory.adjust', $inventory) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="type" class="form-label">Transaction Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="inbound">Inbound (Add to inventory)</option>
                            <option value="outbound">Outbound (Remove from inventory)</option>
                            <option value="adjustment">Adjustment (Set exact quantity)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label for="reference_number" class="form-label">Reference Number (Optional)</label>
                        <input type="text" class="form-control" id="reference_number" name="reference_number">
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
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

<!-- Set Reorder Level Modal -->
<div class="modal fade" id="setReorderLevelModal" tabindex="-1" aria-labelledby="setReorderLevelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="setReorderLevelModalLabel">Set Reorder Level</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.inventory.reorder-level', $inventory) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reorder_level" class="form-label">Reorder Level</label>
                        <input type="number" class="form-control" id="reorder_level" name="reorder_level" min="0" value="{{ $inventory->reorder_level }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Set Reorder Level</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
