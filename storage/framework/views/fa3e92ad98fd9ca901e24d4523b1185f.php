<?php $__env->startSection('title', 'Quản lý kho hàng'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div>
        <h1><i class="bi bi-boxes"></i> Quản lý kho hàng</h1>
        <p class="text-muted mb-0">Theo dõi và quản lý tồn kho sản phẩm tại các kho</p>
    </div>
</div>

<?php if(session('inbound_result')): ?>
    <?php ($inboundResults = session('inbound_result')); ?>
    <div class="alert alert-info">
        <h5 class="mb-2"><i class="bi bi-check-circle"></i> Đã ghi nhận phiếu nhập kho</h5>
        <ul class="mb-0">
            <?php $__currentLoopData = $inboundResults; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li>
                    <strong><?php echo e($row['variant']['full_label'] ?? ('SKU #' . ($row['variant']['sku'] ?? $row['transaction']['product_variant_id']))); ?></strong>
                    — Đã nhập <?php echo e(abs($row['transaction']['quantity'])); ?> đơn vị.
                    <span class="text-muted">
                        (Hiện có: <?php echo e($row['inventory']['quantity_on_hand']); ?>,
                        Đã đặt: <?php echo e($row['inventory']['quantity_reserved']); ?>,
                        Có sẵn: <?php echo e($row['inventory']['available_quantity']); ?>)
                    </span>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>

<!-- Inventory Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon">
                <i class="bi bi-box-seam"></i>
            </div>
            <h5>Tổng số mặt hàng</h5>
            <div class="value"><?php echo e($inventories->total()); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-warning">
                <i class="bi bi-exclamation-triangle"></i>
            </div>
            <h5>Hàng gần hết</h5>
            <div class="value text-warning"><?php echo e($inventories->filter(fn($i) => $i->quantity_on_hand <= $i->reorder_level)->count()); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-danger">
                <i class="bi bi-x-circle"></i>
            </div>
            <h5>Hết hàng</h5>
            <div class="value text-danger"><?php echo e($inventories->where('quantity_on_hand', 0)->count()); ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon bg-success">
                <i class="bi bi-check-circle"></i>
            </div>
            <h5>Còn hàng</h5>
            <div class="value text-success"><?php echo e($inventories->filter(fn($i) => $i->quantity_on_hand > $i->reorder_level)->count()); ?></div>
        </div>
    </div>
</div>

<!-- Create Warehouse (Admin only) -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-building"></i> Thêm kho hàng mới
    </div>
    <div class="card-body">
        <form method="POST" action="<?php echo e(route('admin.inventory.warehouses.store')); ?>" class="row g-3">
            <?php echo csrf_field(); ?>
            <div class="col-md-6">
                <label class="form-label">Tên kho *</label>
                <input type="text" name="name" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" placeholder="VD: Kho TP. Hồ Chí Minh" value="<?php echo e(old('name')); ?>" required>
                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="col-md-4">
                <label class="form-label">Vị trí (tuỳ chọn)</label>
                <input type="text" name="location" class="form-control" placeholder="Địa chỉ hoặc khu vực" value="<?php echo e(old('location')); ?>">
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
        <form method="GET" action="<?php echo e(route('admin.inventory.index')); ?>" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" name="search" class="form-control" placeholder="Tên sản phẩm, SKU..."
                       value="<?php echo e(request('search')); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Kho hàng</label>
                <select name="warehouse_id" class="form-select">
                    <option value="">Tất cả kho hàng</option>
                    <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $warehouse): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($warehouse->id); ?>"
                                <?php echo e(request('warehouse_id') == $warehouse->id ? 'selected' : ''); ?>>
                            <?php echo e($warehouse->name); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Mức tồn kho</label>
                <select name="stock_level" class="form-select">
                    <option value="">Tất cả mức</option>
                    <option value="out_of_stock" <?php echo e(request('stock_level') == 'out_of_stock' ? 'selected' : ''); ?>>Hết hàng</option>
                    <option value="low_stock" <?php echo e(request('stock_level') == 'low_stock' ? 'selected' : ''); ?>>Hàng gần hết</option>
                    <option value="in_stock" <?php echo e(request('stock_level') == 'in_stock' ? 'selected' : ''); ?>>Còn hàng</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                    <a href="<?php echo e(route('admin.inventory.low-stock')); ?>" class="btn btn-warning" title="Xem hàng gần hết">
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
        <span><i class="bi bi-list"></i> Danh sách tồn kho (<?php echo e($inventories->total()); ?> tổng)</span>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#inboundModal">
                <i class="bi bi-box-arrow-in-down"></i> Nhập kho mới
            </button>
            <a href="<?php echo e(route('admin.inventory.transactions')); ?>" class="btn btn-sm btn-info">
                <i class="bi bi-clock-history"></i> Giao dịch
            </a>
            <a href="<?php echo e(route('admin.inventory.export')); ?>" class="btn btn-sm btn-success">
                <i class="bi bi-download"></i> Xuất
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if($inventories->count() > 0): ?>
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
                    <?php $__currentLoopData = $inventories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inventory): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="<?php echo e($inventory->quantity_on_hand == 0 ? 'table-danger' : ($inventory->quantity_on_hand <= $inventory->reorder_level ? 'table-warning' : '')); ?>">
                        <td>
                            <strong><?php echo e($inventory->productVariant?->product?->name ?? 'N/A'); ?></strong>
                        </td>
                        <td>
                            <?php echo e($inventory->productVariant?->variant_name ?? 'N/A'); ?>

                            <br>
                            <small class="text-muted"><?php echo e($inventory->productVariant?->sku ?? 'N/A'); ?></small>
                        </td>
                        <td><?php echo e($inventory->warehouse->name); ?></td>
                        <td>
                            <strong class="<?php echo e($inventory->quantity_on_hand == 0 ? 'text-danger' : ($inventory->quantity_on_hand <= $inventory->reorder_level ? 'text-warning' : 'text-success')); ?>">
                                <?php echo e($inventory->quantity_on_hand); ?>

                            </strong>
                        </td>
                        <td><?php echo e($inventory->reorder_level); ?></td>
                        <td>
                            <?php if($inventory->quantity_on_hand == 0): ?>
                                <span class="badge bg-danger">Hết hàng</span>
                            <?php elseif($inventory->quantity_on_hand <= $inventory->reorder_level): ?>
                                <span class="badge bg-warning">Hàng gần hết</span>
                            <?php else: ?>
                                <span class="badge bg-success">Còn hàng</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($inventory->updated_at->format('M d, Y')); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?php echo e(route('admin.inventory.show', $inventory->id)); ?>"
                                   class="btn btn-info" title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-primary" title="Điều chỉnh số lượng"
                                        data-bs-toggle="modal" data-bs-target="#adjustModal<?php echo e($inventory->id); ?>">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </div>

                            <!-- Adjust Inventory Modal -->
                            <div class="modal fade" id="adjustModal<?php echo e($inventory->id); ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Điều chỉnh tồn kho</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="<?php echo e(route('admin.inventory.adjust', $inventory->id)); ?>" method="POST">
                                            <?php echo csrf_field(); ?>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Số lượng hiện có</label>
                                                    <input type="text" class="form-control" value="<?php echo e($inventory->quantity_on_hand); ?>" disabled>
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
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            <?php echo e($inventories->links()); ?>

        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 3em; color: #ccc;"></i>
            <p class="text-muted mt-3">Không tìm thấy mặt hàng tồn kho</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Inbound Modal -->
<div class="modal fade" id="inboundModal" tabindex="-1" aria-labelledby="inboundModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="<?php echo e(route('admin.inventory.inbound')); ?>" id="inbound-form">
                <?php echo csrf_field(); ?>
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
                                <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $warehouse): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($warehouse->id); ?>"><?php echo e($warehouse->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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

<?php $__env->startPush('scripts'); ?>
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
    const searchEndpoint = <?php echo json_encode(route('admin.inventory.variants.search'), 15, 512) ?>;
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
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\temp\perw-project\resources\views/admin/inventory/index.blade.php ENDPATH**/ ?>