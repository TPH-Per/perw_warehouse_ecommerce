@extends('layouts.admin')

@section('title', 'Quản lý kho hàng')

@section('content')
<div class="page-header">
    <div>
        <h1><i class="bi bi-boxes"></i> Quản lý kho hàng</h1>
        <p class="text-muted mb-0">Theo dõi và quản lý tồn kho sản phẩm tại các kho</p>
    </div>
</div>

@if(session('inbound_result'))
    @php($inboundResults = session('inbound_result'))
    <div class="alert alert-info">
        <h5 class="mb-2"><i class="bi bi-check-circle"></i> Đã ghi nhận phiếu nhập kho</h5>
        <ul class="mb-0">
            @foreach($inboundResults as $row)
                <li>
                    <strong>{{ $row['variant']['full_label'] ?? ('SKU #' . ($row['variant']['sku'] ?? $row['transaction']['product_variant_id'])) }}</strong>
                    — Đã nhập {{ abs($row['transaction']['quantity']) }} đơn vị.
                    <span class="text-muted">
                        (Hiện có: {{ $row['inventory']['quantity_on_hand'] }},
                        Đã đặt: {{ $row['inventory']['quantity_reserved'] }},
                        Có sẵn: {{ $row['inventory']['available_quantity'] }})
                    </span>
                </li>
            @endforeach
        </ul>
    </div>
@endif

<!-- Inventory Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon">
                <i class="bi bi-box-seam"></i>
            </div>
            <h5>Tổng số mặt hàng</h5>
            <div class="value">{{ $inventories->total() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-warning">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h5>Hàng gần hết</h5>
            <div class="value text-warning">{{ $inventories->filter(fn($i) => $i->quantity_on_hand <= $i->reorder_level)->count() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-danger">
                <i class="bi bi-x-circle"></i>
            </div>
            <h5>Hết hàng</h5>
            <div class="value text-danger">{{ $inventories->where('quantity_on_hand', 0)->count() }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-success">
                <i class="bi bi-check-circle"></i>
            </div>
            <h5>Còn hàng</h5>
            <div class="value text-success">{{ $inventories->filter(fn($i) => $i->quantity_on_hand > $i->reorder_level)->count() }}</div>
        </div>
    </div>
</div>

<!-- Create Warehouse (Admin only) -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-building"></i> Thêm kho hàng mới
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.inventory.warehouses.store') }}" class="row g-3">
            @csrf
            <div class="col-md-6">
                <label class="form-label">Tên kho *</label>
                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" placeholder="VD: Kho TP. Hồ Chí Minh" value="{{ old('name') }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-4">
                <label class="form-label">Vị trí (tuỳ chọn)</label>
                <input type="text" name="location" class="form-control" placeholder="Địa chỉ hoặc khu vực" value="{{ old('location') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-success w-100">
                    <i class="bi bi-plus-circle"></i> Thêm kho
                </button>
            </div>
        </form>
    </div>
    <div class="card-footer small text-muted">
        Chỉ Admin được phép thêm kho.
    </div>
    </div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('admin.inventory.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" name="search" class="form-control" placeholder="Tên sản phẩm, SKU..."
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label">Kho hàng</label>
                <select name="warehouse_id" class="form-select">
                    <option value="">Tất cả kho hàng</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}"
                                {{ request('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Mức tồn kho</label>
                <select name="stock_level" class="form-select">
                    <option value="">Tất cả mức</option>
                    <option value="out_of_stock" {{ request('stock_level') == 'out_of_stock' ? 'selected' : '' }}>Hết hàng</option>
                    <option value="low_stock" {{ request('stock_level') == 'low_stock' ? 'selected' : '' }}>Hàng gần hết</option>
                    <option value="in_stock" {{ request('stock_level') == 'in_stock' ? 'selected' : '' }}>Còn hàng</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="{{ route('admin.inventory.low-stock') }}" class="btn btn-warning" title="Xem hàng gần hết">
                        <i class="bi bi-exclamation-triangle"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Inventory Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list"></i> Danh sách tồn kho ({{ $inventories->total() }} tổng)</span>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#inboundModal">
                <i class="bi bi-box-arrow-in-down"></i> Nhập kho mới
            </button>
            <a href="{{ route('admin.inventory.transactions') }}" class="btn btn-sm btn-info">
                <i class="bi bi-clock-history"></i> Giao dịch
            </a>
            <a href="{{ route('admin.inventory.export') }}" class="btn btn-sm btn-success">
                <i class="bi bi-download"></i> Xuất
            </a>
        </div>
    </div>
    <div class="card-body">
        @if($inventories->count() > 0)
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th>Mẫu mã (SKU)</th>
                        <th>Kho hàng</th>
                        <th>Hiện có</th>
                        <th>Mức đặt hàng lại</th>
                        <th>Trạng thái</th>
                        <th>Cập nhật gần nhất</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($inventories as $inventory)
                    <tr class="{{ $inventory->quantity_on_hand == 0 ? 'table-danger' : ($inventory->quantity_on_hand <= $inventory->reorder_level ? 'table-warning' : '') }}">
                        <td>
                            <strong>{{ $inventory->productVariant?->product?->name ?? 'N/A' }}</strong>
                        </td>
                        <td>
                            {{ $inventory->productVariant?->variant_name ?? 'N/A' }}
                            <br>
                            <small class="text-muted">{{ $inventory->productVariant?->sku ?? 'N/A' }}</small>
                        </td>
                        <td>{{ $inventory->warehouse->name }}</td>
                        <td>
                            <strong class="{{ $inventory->quantity_on_hand == 0 ? 'text-danger' : ($inventory->quantity_on_hand <= $inventory->reorder_level ? 'text-warning' : 'text-success') }}">
                                {{ $inventory->quantity_on_hand }}
                            </strong>
                        </td>
                        <td>{{ $inventory->reorder_level }}</td>
                        <td>
                            @if($inventory->quantity_on_hand == 0)
                                <span class="badge bg-danger">Hết hàng</span>
                            @elseif($inventory->quantity_on_hand <= $inventory->reorder_level)
                                <span class="badge bg-warning">Hàng gần hết</span>
                            @else
                                <span class="badge bg-success">Còn hàng</span>
                            @endif
                        </td>
                        <td>{{ $inventory->updated_at->format('M d, Y') }}</td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.inventory.show', $inventory->id) }}"
                                   class="btn btn-info" title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-primary" title="Điều chỉnh số lượng"
                                        data-bs-toggle="modal" data-bs-target="#adjustModal{{ $inventory->id }}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </div>

                            <!-- Adjust Inventory Modal -->
                            <div class="modal fade" id="adjustModal{{ $inventory->id }}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Điều chỉnh tồn kho</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="{{ route('admin.inventory.adjust', $inventory->id) }}" method="POST">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Số lượng hiện có</label>
                                                    <input type="text" class="form-control" value="{{ $inventory->quantity_on_hand }}" disabled>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Loại điều chỉnh</label>
                                                    <select name="adjustment_type" class="form-select" required>
                                                        <option value="addition">Cộng (+)</option>
                                                        <option value="subtraction">Trừ (-)</option>
                                                        <option value="set">Đặt giá trị cụ thể</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Số lượng</label>
                                                    <input type="number" name="quantity" class="form-control" min="0" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Lý do</label>
                                                    <textarea name="reason" class="form-control" rows="2" required></textarea>
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
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $inventories->links() }}
        </div>
        @else
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 3em; color: #ccc;"></i>
            <p class="text-muted mt-3">Không tìm thấy mặt hàng tồn kho</p>
        </div>
        @endif
    </div>
</div>

<!-- Inbound Modal -->
<div class="modal fade" id="inboundModal" tabindex="-1" aria-labelledby="inboundModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.inventory.inbound') }}" id="inbound-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="inboundModalLabel">
                        <i class="bi bi-box-arrow-in-down"></i> Ghi nhận phiếu nhập kho
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="inbound-warehouse" class="form-label">Kho đích *</label>
                            <select class="form-select" name="warehouse_id" id="inbound-warehouse" required>
                                <option value="">Chọn kho...</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tìm kiếm sản phẩm *</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" id="inbound-search" placeholder="Nhập tên sản phẩm hoặc SKU...">
                                <div class="list-group position-absolute w-100 shadow-sm d-none" id="inbound-search-results" style="z-index: 1056;"></div>
                            </div>
                            <small class="text-muted">Nhập ít nhất 2 ký tự, sau đó chọn sản phẩm để thêm vào phiếu.</small>
                        </div>
                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table align-middle" id="inbound-items-table">
                                    <thead>
                                        <tr>
                                            <th>Sản phẩm</th>
                                            <th>SKU</th>
                                            <th width="15%">Số lượng</th>
                                            <th width="30%">Ghi chú dòng (tuỳ chọn)</th>
                                            <th class="text-end" width="5%">#</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr id="inbound-empty-row" class="text-muted text-center">
                                            <td colspan="5">Chưa có sản phẩm nào. Dùng ô tìm kiếm để thêm sản phẩm.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Ghi chú phiếu (tuỳ chọn)</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Số PO, nhà cung cấp, tham chiếu chuyển kho..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <span class="text-muted small">
                        Mỗi dòng sẽ được ghi nhận như một giao dịch nhập kho.
                    </span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Lưu phiếu
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const inboundModal = document.getElementById('inboundModal');
    if (!inboundModal) {
        return;
    }

    const inboundForm = document.getElementById('inbound-form');
    const searchInput = document.getElementById('inbound-search');
    const resultsBox = document.getElementById('inbound-search-results');
    const itemsTableBody = document.querySelector('#inbound-items-table tbody');
    const searchEndpoint = @json(route('admin.inventory.variants.search'));
    let debounceTimer;
    let itemIndex = 0;

    const placeholderMessage = 'Chưa có sản phẩm nào. Dùng ô tìm kiếm để thêm sản phẩm.';

    function resetResults() {
        resultsBox.innerHTML = '';
        resultsBox.classList.add('d-none');
    }

    function ensurePlaceholderRow() {
        if (!itemsTableBody.querySelector('tr')) {
            const row = document.createElement('tr');
            row.id = 'inbound-empty-row';
            row.className = 'text-muted text-center';
            const cell = document.createElement('td');
            cell.colSpan = 5;
            cell.textContent = placeholderMessage;
            row.appendChild(cell);
            itemsTableBody.appendChild(row);
        }
    }

    function addItem(variant) {
        const existingRow = itemsTableBody.querySelector(`tr[data-variant-id="${variant.id}"]`);
        if (existingRow) {
            const quantityInput = existingRow.querySelector('input[name*="[quantity]"]');
            if (quantityInput) {
                quantityInput.focus();
            }
            existingRow.classList.add('table-success');
            setTimeout(() => existingRow.classList.remove('table-success'), 800);
            return;
        }

        const placeholder = itemsTableBody.querySelector('#inbound-empty-row');
        if (placeholder) {
            placeholder.remove();
        }

        const row = document.createElement('tr');
        row.dataset.variantId = variant.id;

        const productCell = document.createElement('td');
        const nameStrong = document.createElement('strong');
        nameStrong.textContent = variant.label || variant.product_name || ('SKU ' + variant.sku);
        productCell.appendChild(nameStrong);

        if (variant.variant_name) {
            const variantLine = document.createElement('div');
            variantLine.className = 'text-muted small';
            variantLine.textContent = variant.variant_name;
            productCell.appendChild(variantLine);
        }

        const hiddenField = document.createElement('input');
        hiddenField.type = 'hidden';
        hiddenField.name = `items[${itemIndex}][product_variant_id]`;
        hiddenField.value = variant.id;
        productCell.appendChild(hiddenField);
        row.appendChild(productCell);

        const skuCell = document.createElement('td');
        const skuBadge = document.createElement('span');
        skuBadge.className = 'badge bg-light text-dark';
        skuBadge.textContent = variant.sku || 'N/A';
        skuCell.appendChild(skuBadge);
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

        const noteCell = document.createElement('td');
        const noteInput = document.createElement('input');
        noteInput.type = 'text';
        noteInput.name = `items[${itemIndex}][notes]`;
        noteInput.className = 'form-control';
        noteInput.placeholder = 'Ghi chú dòng (tuỳ chọn)';
        noteCell.appendChild(noteInput);
        row.appendChild(noteCell);

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
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Response not OK');
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
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'list-group-item list-group-item-action inbound-result-item';
            option.dataset.id = variant.id;
            option.dataset.label = variant.label || '';
            option.dataset.sku = variant.sku || '';
            option.dataset.productName = variant.product_name || '';
            option.dataset.variantName = variant.variant_name || '';

            const title = document.createElement('div');
            title.className = 'fw-semibold';
            title.textContent = variant.label || variant.sku;

            const subtitle = document.createElement('div');
            subtitle.className = 'small text-muted';
            subtitle.textContent = 'SKU: ' + (variant.sku || 'N/A');

            option.appendChild(title);
            option.appendChild(subtitle);
            resultsBox.appendChild(option);
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

        debounceTimer = setTimeout(() => {
            searchVariants(term);
        }, 250);
    });

    resultsBox.addEventListener('click', (event) => {
        const option = event.target.closest('.inbound-result-item');
        if (!option) {
            return;
        }

        const variant = {
            id: option.dataset.id,
            label: option.dataset.label,
            sku: option.dataset.sku,
            product_name: option.dataset.productName,
            variant_name: option.dataset.variantName
        };

        addItem(variant);
        resetResults();
        searchInput.value = '';
        searchInput.focus();
    });

    itemsTableBody.addEventListener('click', (event) => {
        if (event.target.closest('.remove-inbound-item')) {
            event.preventDefault();
            const row = event.target.closest('tr');
            if (row) {
                row.remove();
            }
            if (!itemsTableBody.querySelector('tr[data-variant-id]')) {
                itemsTableBody.innerHTML = '';
                ensurePlaceholderRow();
            }
        }
    });

    document.addEventListener('click', (event) => {
        if (resultsBox.classList.contains('d-none')) {
            return;
        }
        if (event.target === searchInput || resultsBox.contains(event.target)) {
            return;
        }
        resetResults();
    });

    inboundForm.addEventListener('submit', (event) => {
        const hasItems = itemsTableBody.querySelectorAll('tr[data-variant-id]').length > 0;
        if (!hasItems) {
            event.preventDefault();
            alert('Vui lòng thêm ít nhất một sản phẩm vào phiếu nhập.');
        }
    });

    inboundModal.addEventListener('hidden.bs.modal', () => {
        inboundForm.reset();
        searchInput.value = '';
        resetResults();
        itemsTableBody.innerHTML = '';
        itemIndex = 0;
        ensurePlaceholderRow();
    });

    ensurePlaceholderRow();
});
</script>
@endpush
@endsection
