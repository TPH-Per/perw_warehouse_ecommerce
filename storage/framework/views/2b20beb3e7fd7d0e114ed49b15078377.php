﻿

<?php $__env->startSection('title', 'Kho hàng'); ?>

<?php
    $user = auth()->user();
    $isWarehouseScopedManager = $user && $user->role->name === 'manager' && $user->warehouse_id;
?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div>
        <h1><i class="bi bi-boxes"></i> Kho hàng</h1>
        <p class="text-muted mb-0">Theo dõi mức tồn kho và ghi nhận hoạt động kho.</p>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <a href="<?php echo e(route('manager.inventory.transactions')); ?>" class="btn btn-success">
        <i class="bi bi-clock-history"></i> Lịch sử giao dịch
    </a>
    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#inboundModal">
        <i class="bi bi-box-arrow-in-down"></i> Phiếu nhập kho mới
    </button>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInventoryModal">
        <i class="bi bi-plus-circle"></i> Tạo bản ghi tồn kho
    </button>
</div>

<?php if($inboundResults = session('inbound_result')): ?>
    <div class="alert alert-info">
        <h5 class="mb-2"><i class="bi bi-check-circle"></i> Đã ghi nhận phiếu nhập kho</h5>
        <ul class="mb-0">
            <?php $__currentLoopData = $inboundResults; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li>
                    <strong><?php echo e($row['variant']['full_label'] ?? ('SKU #' . ($row['variant']['sku'] ?? $row['transaction']['product_variant_id']))); ?></strong>
                    — đã nhập <?php echo e(abs($row['transaction']['quantity'])); ?> đơn vị.
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

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('manager.inventory.index')); ?>" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Tìm kiếm</label>
                <input type="text" class="form-control" name="search"
                       placeholder="Tên sản phẩm hoặc SKU..."
                       value="<?php echo e(request('search')); ?>">
            </div>

            <?php if(!$isWarehouseScopedManager): ?>
                <div class="col-md-3">
                    <label class="form-label">Kho hàng</label>
                    <select class="form-select" name="warehouse_id">
                        <option value="">Tất cả kho hàng</option>
                        <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $warehouse): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($warehouse->id); ?>" <?php echo e(request('warehouse_id') == $warehouse->id ? 'selected' : ''); ?>>
                                <?php echo e($warehouse->name); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-3">
                <label class="form-label">Trạng thái tồn kho</label>
                <select class="form-select" name="stock_status">
                    <option value="">Tất cả</option>
                    <option value="low" <?php echo e(request('stock_status') === 'low' ? 'selected' : ''); ?>>Hàng gần hết</option>
                    <option value="out" <?php echo e(request('stock_status') === 'out' ? 'selected' : ''); ?>>Hết hàng</option>
                </select>
            </div>

            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i> Lọc
                </button>
                <a href="<?php echo e(route('manager.inventory.index')); ?>" class="btn btn-outline-secondary">
                    Xóa
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
  <div class="card-body">
    <div class="table-responsive">
      <?php $colspan = $isWarehouseScopedManager ? 8 : 9; ?>

      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Sản phẩm</th>
            <th>SKU</th>
            <?php if(! $isWarehouseScopedManager): ?>
              <th>Kho hàng</th>
            <?php endif; ?>
            <th>Hiện có</th>
            <th>Đã đặt</th>
            <th>Có sẵn</th>
            <th>Mức đặt hàng lại</th>
            <th>Trạng thái</th>
            <th class="text-end">Hành động</th>
          </tr>
        </thead>

        <tbody>
          <?php $__empty_1 = true; $__currentLoopData = $inventories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inventory): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
              $available    = $inventory->quantity_on_hand - $inventory->quantity_reserved;
              $isLowStock   = $inventory->quantity_on_hand <= $inventory->reorder_level;
              $isOutOfStock = $inventory->quantity_on_hand <= 0;
            ?>

            <tr>
              <td>
                <strong><?php echo e($inventory->productVariant?->product->name ?? 'N/A'); ?></strong>
                <div class="text-muted small"><?php echo e($inventory->productVariant?->name ?? 'N/A'); ?></div>
              </td>

              <td><?php echo e($inventory->productVariant?->sku ?? 'N/A'); ?></td>

              <?php if(! $isWarehouseScopedManager): ?>
                <td><?php echo e($inventory->warehouse->name); ?></td>
              <?php endif; ?>

              <td><strong><?php echo e($inventory->quantity_on_hand); ?></strong></td>
              <td><?php echo e($inventory->quantity_reserved); ?></td>

              <td>
                <strong class="<?php echo e($available > 0 ? 'text-success' : 'text-danger'); ?>">
                  <?php echo e($available); ?>

                </strong>
              </td>

              <td><?php echo e($inventory->reorder_level); ?></td>

              <td>
                <?php if($isOutOfStock): ?>
                  <span class="badge bg-danger">Hết hàng</span>
                <?php elseif($isLowStock): ?>
                  <span class="badge bg-warning text-dark">Hàng gần hết</span>
                <?php else: ?>
                  <span class="badge bg-success">Còn hàng</span>
                <?php endif; ?>
              </td>

              <td class="text-end">
                <div class="btn-group btn-group-sm">
                  <a href="<?php echo e(route('manager.inventory.show', $inventory->id)); ?>" class="btn btn-info" title="Xem chi tiết">
                    <i class="bi bi-eye"></i>
                  </a>
                  <a href="<?php echo e(route('manager.inventory.edit', $inventory->id)); ?>" class="btn btn-primary" title="Chỉnh sửa">
                    <i class="bi bi-pencil"></i>
                  </a>
                </div>
              </td>
            </tr>

          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <tr>
              <td colspan="<?php echo e($colspan); ?>" class="text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ced4da;"></i>
                <p class="text-muted mt-3 mb-0">Không tìm thấy bản ghi tồn kho.</p>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if(method_exists($inventories, 'links')): ?>
      <div class="d-flex justify-content-center mt-4">
        <?php echo e($inventories->links()); ?>

      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Inbound modal -->
<div class="modal fade" id="inboundModal" tabindex="-1" aria-labelledby="inboundModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="<?php echo e(route('manager.inventory.inbound')); ?>" id="inbound-form">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="inboundModalLabel"><i class="bi bi-box-arrow-in-down"></i> Ghi nhận phiếu nhập kho</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <?php if($isWarehouseScopedManager): ?>
                            <input type="hidden" name="warehouse_id" id="inbound-warehouse" value="<?php echo e($user->warehouse_id); ?>">
                            <div class="col-12">
                                <div class="alert alert-secondary mb-0">
                                    <i class="bi bi-info-circle"></i>
                                    Hàng sẽ được nhập vào <strong><?php echo e($user->warehouse?->name ?? 'kho được chỉ định của bạn'); ?></strong>.
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="col-md-6">
                                <label class="form-label" for="inbound-warehouse">Kho đích *</label>
                                <select class="form-select" name="warehouse_id" id="inbound-warehouse" required>
                                    <option value="">Chọn kho...</option>
                                    <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $warehouse): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($warehouse->id); ?>"><?php echo e($warehouse->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-12">
                            <label class="form-label">Tìm kiếm sản phẩm *</label>
                            <div class="position-relative">
                                <input type="text" class="form-control" id="inbound-search" placeholder="Nhập tên sản phẩm hoặc SKU...">
                                <div id="inbound-search-results" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 1056;"></div>
                            </div>
                            <small class="text-muted">Nhập ít nhất 2 ký tự, sau đó chọn sản phẩm để thêm.</small>
                        </div>

                        <div class="col-12">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0" id="inbound-items-table">
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
                                            <td colspan="5">Chưa chọn sản phẩm nào. Dùng ô tìm kiếm phía trên để thêm dòng.</td>
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
                    <span class="text-muted small">Mỗi dòng sẽ tạo một giao dịch nhập kho cho kho được chọn.</span>
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

<!-- Create inventory modal -->
<div class="modal fade" id="createInventoryModal" tabindex="-1" aria-labelledby="createInventoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?php echo e(route('manager.inventory.store')); ?>">
                <?php echo csrf_field(); ?>
                <div class="modal-header">
                    <h5 class="modal-title" id="createInventoryModalLabel"><i class="bi bi-plus-circle"></i> Tạo bản ghi tồn kho</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="product_variant_id">Sản phẩm *</label>
                            <select class="form-select" id="product_variant_id" name="product_variant_id" required>
                                <option value="">Chọn sản phẩm...</option>
                                <?php $__currentLoopData = \App\Models\ProductVariant::with('product')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $variant): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($variant->id); ?>">
                                        <?php echo e($variant->product->name); ?> — <?php echo e($variant->name); ?> (<?php echo e($variant->sku); ?>)
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <?php if($isWarehouseScopedManager): ?>
                            <input type="hidden" name="warehouse_id" value="<?php echo e($user->warehouse_id); ?>">
                        <?php else: ?>
                            <div class="col-md-6">
                                <label class="form-label" for="warehouse_id">Kho hàng *</label>
                                <select class="form-select" id="warehouse_id" name="warehouse_id" required>
                                    <option value="">Chọn kho...</option>
                                    <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $warehouse): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($warehouse->id); ?>"><?php echo e($warehouse->name); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                            <label class="form-label" for="quantity_on_hand">Hiện có *</label>
                            <input type="number" class="form-control" id="quantity_on_hand" name="quantity_on_hand" min="0" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="quantity_reserved">Đã đặt *</label>
                            <input type="number" class="form-control" id="quantity_reserved" name="quantity_reserved" min="0" value="0" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" for="reorder_level">Mức đặt hàng lại *</label>
                            <input type="number" class="form-control" id="reorder_level" name="reorder_level" min="0" value="10" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>
                    <button type="submit" class="btn btn-primary">Tạo bản ghi</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
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
    const searchEndpoint = <?php echo json_encode(route('manager.inventory.variants.search'), 15, 512) ?>;

    let debounceTimer = null;
    let itemIndex = 0;
    const emptyMessage = 'Chưa chọn sản phẩm nào. Dùng ô tìm kiếm phía trên để thêm dòng.';

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
        lineNoteInput.placeholder = 'Ghi chú dòng (tuỳ chọn)';
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
            alert('Vui lòng thêm ít nhất một sản phẩm trước khi lưu phiếu.');
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\temp\perw-project\resources\views/manager/inventory/index.blade.php ENDPATH**/ ?>