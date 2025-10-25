@extends('layouts.manager')

@section('title', 'Inventory')

@php
    $user = auth()->user();
    $isWarehouseScopedManager = $user && $user->role->name === 'Inventory Manager' && $user->warehouse_id;
@endphp

@section('content')
<div class="page-header">
    <div>
        <h1><i class="bi bi-boxes"></i> Inventory</h1>
        <p class="text-muted mb-0">Monitor stock levels and capture warehouse activity.</p>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="{{ route('manager.inventory.transactions') }}" class="btn btn-success">
        <i class="bi bi-clock-history"></i> Transaction History
    </a>
    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#inboundModal">
        <i class="bi bi-box-arrow-in-down"></i> New Inbound Receipt
    </button>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInventoryModal">
        <i class="bi bi-plus-circle"></i> Create Inventory Record
    </button>
</div>

@if ($inboundResults = session('inbound_result'))
    <div class="alert alert-info">
        <h5 class="mb-2"><i class="bi bi-check-circle"></i> Inbound receipt recorded</h5>
        <ul class="mb-0">
            @foreach($inboundResults as $row)
                <li>
                    <strong>{{ $row['variant']['full_label'] ?? ('SKU #' . ($row['variant']['sku'] ?? $row['transaction']['product_variant_id'])) }}</strong>
                    — received {{ abs($row['transaction']['quantity']) }} units.
                    <span class="text-muted">
                        (On-hand: {{ $row['inventory']['quantity_on_hand'] }},
                        Reserved: {{ $row['inventory']['quantity_reserved'] }},
                        Available: {{ $row['inventory']['available_quantity'] }})
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('manager.inventory.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search"
                       placeholder="Product name or SKU..."
                       value="{{ request('search') }}">
            </div>

            @if(!$isWarehouseScopedManager)
                <div class="col-md-3">
                    <label class="form-label">Warehouse</label>
                    <select class="form-select" name="warehouse_id">
                        <option value="">All warehouses</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-3">
                <label class="form-label">Stock status</label>
                <select class="form-select" name="stock_status">
                    <option value="">All</option>
                    <option value="low" {{ request('stock_status') === 'low' ? 'selected' : '' }}>Low stock</option>
                    <option value="out" {{ request('stock_status') === 'out' ? 'selected' : '' }}>Out of stock</option>
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Apply
                </button>
                <a href="{{ route('manager.inventory.index') }}" class="btn btn-outline-secondary">
                    Clear
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      @php $colspan = $isWarehouseScopedManager ? 8 : 9; @endphp

      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Product</th>
            <th>SKU</th>
            @if (! $isWarehouseScopedManager)
              <th>Warehouse</th>
            @endif
            <th>On hand</th>
            <th>Reserved</th>
            <th>Available</th>
            <th>Reorder level</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>

        <tbody>
          @forelse ($inventories as $inventory)
            @php
              $available    = $inventory->quantity_on_hand - $inventory->quantity_reserved;
              $isLowStock   = $inventory->quantity_on_hand <= $inventory->reorder_level;
              $isOutOfStock = $inventory->quantity_on_hand <= 0;
            @endphp

            <tr>
              <td>
                <strong>{{ $inventory->productVariant->product->name }}</strong>
                <div class="text-muted small">{{ $inventory->productVariant->name }}</div>
              </td>

              <td>{{ $inventory->productVariant->sku }}</td>

              @if (! $isWarehouseScopedManager)
                <td>{{ $inventory->warehouse->name }}</td>
              @endif

              <td><strong>{{ $inventory->quantity_on_hand }}</strong></td>
              <td>{{ $inventory->quantity_reserved }}</td>

              <td>
                <strong class="{{ $available > 0 ? 'text-success' : 'text-danger' }}">
                  {{ $available }}
                </strong>
              </td>

              <td>{{ $inventory->reorder_level }}</td>

              <td>
                @if ($isOutOfStock)
                  <span class="badge bg-danger">Out of stock</span>
                @elseif ($isLowStock)
                  <span class="badge bg-warning text-dark">Low stock</span>
                @else
                  <span class="badge bg-success">Healthy</span>
                @endif
              </td>

              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('manager.inventory.show', $inventory->id) }}" class="btn btn-info" title="View details">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="{{ route('manager.inventory.edit', $inventory->id) }}" class="btn btn-primary" title="Edit">
                    <i class="bi bi-pencil"></i>
                  </a>
                </div>
              </td>
            </tr>

          @empty
            <tr>
              <td colspan="{{ $colspan }}" class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ced4da;"></i>
                <p class="text-muted mt-3 mb-0">No inventory records found.</p>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if (method_exists($inventories, 'links'))
      <div class="d-flex justify-content-center mt-4">
        {{ $inventories->links() }}
      </div>
    @endif
  </div>
</div>

<!-- Inbound modal -->
<div class="modal fade" id="inboundModal" tabindex="-1" aria-labelledby="inboundModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('manager.inventory.inbound') }}" id="inbound-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="inboundModalLabel"><i class="bi bi-box-arrow-in-down"></i> Record inbound receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        @if($isWarehouseScopedManager)
                            <input type="hidden" name="warehouse_id" id="inbound-warehouse" value="{{ $user->warehouse_id }}">
                            <div class="col-12">
                                <div class="alert alert-secondary mb-0">
                                    <i class="bi bi-info-circle"></i>
                                    Items will be received into <strong>{{ $user->warehouse?->name ?? 'your assigned warehouse' }}</strong>.
                                </div>
                            </div>
                        @else
                            <div class="col-md-6">
                                <label class="form-label" for="inbound-warehouse">Destination warehouse *</label>
                                <select class="form-select" name="warehouse_id" id="inbound-warehouse" required>
                                    <option value="">Select warehouse...</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-12">
                            <label class="form-label">Search products *</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" id="inbound-search" placeholder="Type product name or SKU...">
                                <div id="inbound-search-results" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 1056;"></div>
                            </div>
                            <small class="text-muted">Enter at least 2 characters, then choose the products you want to add.</small>
                        </div>

                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0" id="inbound-items-table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th width="15%">Quantity</th>
                                            <th width="30%">Line notes (optional)</th>
                                            <th class="text-end" width="5%">#</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr id="inbound-empty-row" class="text-muted text-center">
                                            <td colspan="5">No products selected yet. Use the search box above to add lines.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Receipt notes (optional)</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="PO number, supplier, transfer reference..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <span class="text-muted small">Each line will create an inbound transaction for the selected warehouse.</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save receipt
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create inventory modal -->
<div class="modal fade" id="createInventoryModal" tabindex="-1" aria-labelledby="createInventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('manager.inventory.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="createInventoryModalLabel"><i class="bi bi-plus-circle"></i> Create inventory record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="product_variant_id">Product *</label>
                            <select class="form-select" id="product_variant_id" name="product_variant_id" required>
                                <option value="">Select product...</option>
                                @foreach(\App\Models\ProductVariant::with('product')->get() as $variant)
                                    <option value="{{ $variant->id }}">
                                        {{ $variant->product->name }} — {{ $variant->name }} ({{ $variant->sku }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        @if($isWarehouseScopedManager)
                            <input type="hidden" name="warehouse_id" value="{{ $user->warehouse_id }}">
                        @else
                            <div class="col-md-6">
                                <label class="form-label" for="warehouse_id">Warehouse *</label>
                                <select class="form-select" id="warehouse_id" name="warehouse_id" required>
                                    <option value="">Select warehouse...</option>
                                    @foreach($warehouses as $warehouse)
                                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-md-6">
                            <label class="form-label" for="quantity_on_hand">Quantity on hand *</label>
                            <input type="number" class="form-control" id="quantity_on_hand" name="quantity_on_hand" min="0" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="quantity_reserved">Reserved quantity *</label>
                            <input type="number" class="form-control" id="quantity_reserved" name="quantity_reserved" min="0" value="0" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="reorder_level">Reorder level *</label>
                            <input type="number" class="form-control" id="reorder_level" name="reorder_level" min="0" value="10" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create record</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push ('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const inboundModal = document.getElementById('inboundModal');
    if (!inboundModal) {
        return;
    }

    const inboundForm = document.getElementById('inbound-form');
    const searchInput = document.getElementById('inbound-search');
    const resultsBox = document.getElementById('inbound-search-results');
    const itemsTableBody = document.querySelector('#inbound-items-table tbody');
    const searchEndpoint = @json(route('manager.inventory.variants.search'));

    let debounceTimer = null;
    let itemIndex = 0;
    const emptyMessage = 'No products selected yet. Use the search box above to add lines.';

    function resetResults() {
        resultsBox.innerHTML = '';
        resultsBox.classList.add('d-none');
    }

    function ensureEmptyRow() {
        if (!itemsTableBody.querySelector('tr[data-variant-id]')) {
            const row = document.createElement('tr');
            row.id = 'inbound-empty-row';
            row.className = 'text-muted text-center';
            const cell = document.createElement('td');
            cell.colSpan = 5;
            cell.textContent = emptyMessage;
            row.appendChild(cell);
            itemsTableBody.appendChild(row);
        }
    }

    function addVariantRow(variant) {
        if (itemsTableBody.querySelector(`tr[data-variant-id="${variant.id}"]`)) {
            const existingRow = itemsTableBody.querySelector(`tr[data-variant-id="${variant.id}"]`);
            const quantityInput = existingRow.querySelector('input[name*="[quantity]"]');
            if (quantityInput) {
                quantityInput.focus();
            }
            existingRow.classList.add('table-success');
            setTimeout(() => existingRow.classList.remove('table-success'), 600);
            return;
        }

        const placeholder = document.getElementById('inbound-empty-row');
        if (placeholder) {
            placeholder.remove();
        }

        const row = document.createElement('tr');
        row.dataset.variantId = variant.id;

        const productCell = document.createElement('td');
        productCell.innerHTML = `<strong>${variant.label || variant.product_name || ('SKU ' + variant.sku)}</strong>`;
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = `items[${itemIndex}][product_variant_id]`;
        hiddenInput.value = variant.id;
        productCell.appendChild(hiddenInput);
        row.appendChild(productCell);

        const skuCell = document.createElement('td');
        skuCell.innerHTML = `<span class="badge bg-light text-dark">${variant.sku || 'N/A'}</span>`;
        row.appendChild(skuCell);

        const quantityCell = document.createElement('td');
        const quantityInput = document.createElement('input');
        quantityInput.type = 'number';
        quantityInput.name = `items[${itemIndex}][quantity]`;
        quantityInput.className = 'form-control';
        quantityInput.min = '1';
        quantityInput.value = '1';
        quantityInput.required = true;
        quantityCell.appendChild(quantityInput);
        row.appendChild(quantityCell);

        const lineNoteCell = document.createElement('td');
        const lineNoteInput = document.createElement('input');
        lineNoteInput.type = 'text';
        lineNoteInput.name = `items[${itemIndex}][notes]`;
        lineNoteInput.className = 'form-control';
        lineNoteInput.placeholder = 'Line notes (optional)';
        lineNoteCell.appendChild(lineNoteInput);
        row.appendChild(lineNoteCell);

        const actionCell = document.createElement('td');
        actionCell.className = 'text-end';
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className = 'btn btn-link text-danger p-0 remove-inbound-item';
        removeButton.innerHTML = '<i class="bi bi-x-circle"></i>';
        actionCell.appendChild(removeButton);
        row.appendChild(actionCell);

        itemsTableBody.appendChild(row);
        itemIndex += 1;
        quantityInput.focus();
    }

    async function searchVariants(term) {
        try {
            const response = await fetch(`${searchEndpoint}?q=${encodeURIComponent(term)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            if (!response.ok) {
                throw new Error('Request failed');
            }

            const data = await response.json();
            renderResults(Array.isArray(data) ? data : []);
        } catch (error) {
            console.warn('Unable to search product variants', error);
            renderResults([]);
        }
    }

    function renderResults(variants) {
        resultsBox.innerHTML = '';
        if (!variants.length) {
            resetResults();
            return;
        }

        variants.forEach((variant) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'list-group-item list-group-item-action inbound-result-item';
            button.dataset.id = variant.id;
            button.dataset.label = variant.label || '';
            button.dataset.sku = variant.sku || '';
            button.dataset.productName = variant.product_name || '';
            button.dataset.variantName = variant.variant_name || '';

            const title = document.createElement('div');
            title.className = 'fw-semibold';
            title.textContent = variant.label || variant.sku;

            const subtitle = document.createElement('div');
            subtitle.className = 'small text-muted';
            subtitle.textContent = 'SKU: ' + (variant.sku || 'N/A');

            button.appendChild(title);
            button.appendChild(subtitle);
            resultsBox.appendChild(button);
        });

        resultsBox.classList.remove('d-none');
    }

    searchInput.addEventListener('input', (event) => {
        const term = event.target.value.trim();
        clearTimeout(debounceTimer);

        if (term.length < 2) {
            resetResults();
            return;
        }

        debounceTimer = setTimeout(() => searchVariants(term), 250);
    });

    resultsBox.addEventListener('click', (event) => {
        const option = event.target.closest('.inbound-result-item');
        if (!option) {
            return;
        }

        addVariantRow({
            id: option.dataset.id,
            label: option.dataset.label,
            sku: option.dataset.sku,
            product_name: option.dataset.productName,
            variant_name: option.dataset.variantName
        });

        resetResults();
        searchInput.value = '';
        searchInput.focus();
    });

    itemsTableBody.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.remove-inbound-item');
        if (!removeButton) {
            return;
        }

        removeButton.closest('tr')?.remove();
        ensureEmptyRow();
    });

    document.addEventListener('click', (event) => {
        if (!resultsBox.contains(event.target) && event.target !== searchInput) {
            resetResults();
        }
    });

    inboundForm.addEventListener('submit', (event) => {
        const hasItems = itemsTableBody.querySelectorAll('tr[data-variant-id]').length > 0;
        if (!hasItems) {
            event.preventDefault();
            alert('Add at least one product before saving the receipt.');
        }
    });

    inboundModal.addEventListener('hidden.bs.modal', () => {
        inboundForm.reset();
        resultsBox.innerHTML = '';
        resultsBox.classList.add('d-none');
        itemsTableBody.innerHTML = '';
        itemIndex = 0;
        ensureEmptyRow();
    });

    ensureEmptyRow();
});
</script>
@endpush
