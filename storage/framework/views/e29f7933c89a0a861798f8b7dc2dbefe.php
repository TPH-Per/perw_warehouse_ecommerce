<?php $__env->startSection('title', 'Tạo đơn bán hàng trực tiếp mới'); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <h1><i class="bi bi-cart-plus"></i> Tạo đơn bán hàng trực tiếp mới</h1>
    <p class="text-muted mb-0">Xử lý đơn mua hàng của khách hàng tại chỗ</p>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-basket"></i> Chi tiết đơn bán hàng
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo e(route('manager.sales.store')); ?>" id="saleForm">
                    <?php echo csrf_field(); ?>

                    <!-- Warehouse Selection -->
                    <div class="mb-4">
                        <label for="warehouse_id" class="form-label">
                            <i class="bi bi-building"></i> Kho hàng *
                        </label>
                        <select class="form-select <?php $__errorArgs = ['warehouse_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="warehouse_id" name="warehouse_id" required>
                            <option value="">Chọn kho hàng...</option>
                            <?php $__currentLoopData = $warehouses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $warehouse): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($warehouse->id); ?>" <?php echo e((old('warehouse_id') ?? (auth()->user()->warehouse_id ?? null)) == $warehouse->id ? 'selected' : ''); ?>>
                                    <?php echo e($warehouse->name); ?> - <?php echo e($warehouse->location); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['warehouse_id'];
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

                    <!-- Customer Information -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="customer_name" class="form-label">
                                <i class="bi bi-person"></i> Tên khách hàng (Tùy chọn)
                            </label>
                            <input type="text" class="form-control <?php $__errorArgs = ['customer_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   id="customer_name" name="customer_name"
                                   value="<?php echo e(old('customer_name')); ?>"
                                   placeholder="Khách hàng tại chỗ">
                            <?php $__errorArgs = ['customer_name'];
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
                        <div class="col-md-6">
                            <label for="customer_phone" class="form-label">
                                <i class="bi bi-telephone"></i> Số điện thoại (Tùy chọn)
                            </label>
                            <input type="text" class="form-control <?php $__errorArgs = ['customer_phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   id="customer_phone" name="customer_phone"
                                   value="<?php echo e(old('customer_phone')); ?>"
                                   placeholder="Số điện thoại">
                            <?php $__errorArgs = ['customer_phone'];
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
                    </div>

                    <!-- Product Selection -->
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="bi bi-box-seam"></i> Sản phẩm *
                        </label>
                        <div id="productList">
                            <!-- Products will be loaded here via JavaScript -->
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Vui lòng chọn kho hàng trước để tải các sản phẩm có sẵn
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addItemBtn" disabled>
                            <i class="bi bi-plus-circle"></i> Thêm mặt hàng
                        </button>
                    </div>

                    <!-- Items Table -->
                    <div id="itemsContainer" style="display: none;">
                        <h5 class="mb-3">Các mặt hàng đã chọn</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th>Sản phẩm</th>
                                        <th>Mã sản phẩm</th>
                                        <th>Giá</th>
                                        <th>Số lượng</th>
                                        <th>Tổng phụ</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody id="itemsTableBody">
                                    <!-- Items will be added here via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-4">
                        <label for="payment_method_id" class="form-label">
                            <i class="bi bi-credit-card"></i> Phương thức thanh toán *
                        </label>
                        <select class="form-select <?php $__errorArgs = ['payment_method_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                id="payment_method_id" name="payment_method_id" required>
                            <option value="">Chọn phương thức thanh toán...</option>
                            <?php $__currentLoopData = $paymentMethods; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $method): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($method->id); ?>" <?php echo e(old('payment_method_id') == $method->id ? 'selected' : ''); ?>>
                                    <?php echo e($method->name); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['payment_method_id'];
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

                    <!-- Form Actions -->
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success btn-lg" id="submitBtn" disabled>
                            <i class="bi bi-check-circle"></i> Hoàn thành đơn bán hàng
                        </button>
                        <a href="<?php echo e(route('manager.sales.index')); ?>" class="btn btn-secondary btn-lg">
                            <i class="bi bi-x-circle"></i> Hủy
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary Sidebar -->
    <div class="col-lg-4">
        <div class="card sticky-top" style="top: 20px;">
            <div class="card-header bg-success text-white">
                <i class="bi bi-calculator"></i> Tóm tắt đơn bán hàng
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Mặt hàng:</span>
                    <strong id="summaryItemCount">0</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Tổng phụ:</span>
                    <strong id="summarySubtotal">₫0.00</strong>
                </div>
                <hr>
                <div class="d-flex justify-content-between">
                    <h5>Tổng cộng:</h5>
                    <h5 class="text-success" id="summaryTotal">₫0.00</h5>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h6><i class="bi bi-info-circle"></i> Thông tin bán hàng trực tiếp</h6>
                <ul class="small mb-0">
                    <li>Không giao hàng - khách hàng nhận hàng ngay</li>
                    <li>Thanh toán phải được hoàn thành tại chỗ</li>
                    <li>Kho hàng sẽ được cập nhật tự động</li>
                    <li>Đơn bán hàng sẽ được đánh dấu là đã giao ngay lập tức</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
let availableProducts = [];
let selectedItems = [];
let itemCounter = 0;

// Load products when warehouse is selected
document.getElementById('warehouse_id').addEventListener('change', function() {
    console.log('Warehouse change event triggered, warehouseId:', this.value);
    const warehouseId = this.value;
    if (!warehouseId) {
        document.getElementById('productList').innerHTML = '<div class="alert alert-info"><i class="bi bi-info-circle"></i> Vui lòng chọn kho hàng trước</div>';
        document.getElementById('addItemBtn').disabled = true;
        // Reset selections when warehouse is cleared
        selectedItems = [];
        itemCounter = 0;
        document.getElementById('itemsTableBody').innerHTML = '';
        document.getElementById('itemsContainer').style.display = 'none';
        updateSummary();
        return;
    }

    const baseUrl = '<?php echo e(route('manager.sales.warehouse-products')); ?>';
    const url = baseUrl + '?warehouse_id=' + encodeURIComponent(warehouseId);

    // Get CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            console.log('Data type:', typeof data);
            console.log('Is array:', Array.isArray(data));
            
            // Ensure data is an array
            if (Array.isArray(data)) {
                availableProducts = data;
            } else if (data && typeof data === 'object') {
                // Try to convert object to array
                const dataArray = Object.values(data);
                if (Array.isArray(dataArray)) {
                    console.log('Converted object to array with', dataArray.length, 'items');
                    availableProducts = dataArray;
                } else {
                    console.error('Could not convert object to array:', data);
                    availableProducts = [];
                }
            } else {
                console.error('Expected array but received:', data);
                availableProducts = [];
            }

            console.log('Available products count:', availableProducts.length);

            const productListElement = document.getElementById('productList');
            const addItemBtnElement = document.getElementById('addItemBtn');

            if (!productListElement) {
                console.error('Product list element not found');
                return;
            }

            if (availableProducts.length === 0) {
                productListElement.innerHTML = '<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Không có sản phẩm nào trong kho hàng này</div>';
                if (addItemBtnElement) {
                    addItemBtnElement.disabled = true;
                }
            } else {
                productListElement.innerHTML = '<div class="alert alert-success"><i class="bi bi-check-circle"></i> ' + availableProducts.length + ' sản phẩm có sẵn</div>';
                if (addItemBtnElement) {
                    addItemBtnElement.disabled = false;
                }
            }
        })
        .catch(error => {
            console.error('Error in AJAX request:', error);
            availableProducts = [];
            let errorMessage = 'Lỗi khi tải sản phẩm';
            if (error.message) {
                errorMessage += ': ' + error.message;
            }

            const productListElement = document.getElementById('productList');
            if (productListElement) {
                productListElement.innerHTML = '<div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ' + errorMessage + '</div>';
            } else {
                console.error('Product list element not found in error handler');
            }
        });
});

// Auto-load products if a warehouse is already selected (e.g., after validation redirect)
document.addEventListener('DOMContentLoaded', function() {
    const wh = document.getElementById('warehouse_id');
    if (wh && wh.value) {
        // Add a small delay to ensure the page is fully loaded
        setTimeout(() => {
            wh.dispatchEvent(new Event('change'));
        }, 100);
    }
});

// Add item button
document.getElementById('addItemBtn').addEventListener('click', function() {
    // Ensure availableProducts is an array and has items
    if (!Array.isArray(availableProducts) || availableProducts.length === 0) {
        console.warn('availableProducts is not a valid array or is empty:', availableProducts);
        return;
    }

    // Additional safety check
    try {
        const selectHtml = `
            <select class="form-select product-select mb-2" data-item="${itemCounter}">
                <option value="">Chọn sản phẩm...</option>
                ${availableProducts.map(p => `
                    <option value="${p.variant_id}"
                            data-price="${p.price}"
                            data-sku="${p.sku}"
                            data-pname="${p.product_name}"
                            data-vname="${p.variant_name}"
                            data-available="${p.available_quantity}">
                        ${p.product_name} - ${p.variant_name} (SKU: ${p.sku}) - ₫${parseFloat(p.price).toFixed(2)} (${p.available_quantity} có sẵn)
                    </option>
                `).join('')}
            </select>
        `;

        document.getElementById('productList').insertAdjacentHTML('beforeend', selectHtml);
        itemCounter++;
    } catch (error) {
        console.error('Error creating product selection:', error);
        alert('Có lỗi xảy ra khi tạo danh sách sản phẩm. Vui lòng thử lại.');
    }
});

// Handle product selection
document.getElementById('productList').addEventListener('change', function(e) {
    if (e.target.classList.contains('product-select')) {
        const selectedOption = e.target.options[e.target.selectedIndex];
        if (!selectedOption.value) return;

        const variantId = selectedOption.value;
        const price = parseFloat(selectedOption.dataset.price);
        const sku = selectedOption.dataset.sku;
        const name = selectedOption.dataset.pname;
        const variant = selectedOption.dataset.vname;

        // Check if this product is already selected
        if (selectedItems.some(item => item.variantId === variantId)) {
            alert('Sản phẩm này đã được chọn');
            e.target.value = '';
            return;
        }

        // Add to selected items
        selectedItems.push({
            rowKey: itemCounter,
            variantId: variantId,
            name: name,
            variant: variant,
            sku: sku,
            price: price,
            quantity: 1
        });

        // Add to table
        addItemToTable(itemCounter, variantId, name, variant, sku, price, 1);

        // Remove the select
        e.target.remove();

        // Show items container if hidden
        document.getElementById('itemsContainer').style.display = 'block';

        // Enable submit button
        updateSummary();
        document.getElementById('submitBtn').disabled = false;
    }
});

// Add item to table
function addItemToTable(rowKey, variantId, name, variant, sku, price, quantity) {
    const rowHtml = `
        <tr id="item-row-${rowKey}">
            <td>
                <strong>${name}</strong>
                <br><small class="text-muted">${variant}</small>
            </td>
            <td>${sku}</td>
            <td>₫${parseFloat(price).toFixed(2)}</td>
            <td>
                <input type="number" class="form-control quantity-input"
                       data-item="${rowKey}" value="${quantity}" min="1" style="width: 80px;">
            </td>
            <td class="subtotal">₫${(price * quantity).toFixed(2)}</td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-item" data-item="${rowKey}">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
            <input type="hidden" name="items[${rowKey}][variant_id]" value="${variantId}">
            <input type="hidden" name="items[${rowKey}][quantity]" class="quantity-hidden" value="${quantity}">
        </tr>
    `;

    document.getElementById('itemsTableBody').insertAdjacentHTML('beforeend', rowHtml);
}

// Handle quantity change
document.getElementById('itemsTableBody').addEventListener('input', function(e) {
    if (e.target.classList.contains('quantity-input')) {
        const rowKey = e.target.dataset.item;
        const quantity = parseInt(e.target.value) || 1;
        const item = selectedItems.find(i => i.rowKey == rowKey);

        if (item) {
            item.quantity = quantity;
            e.target.closest('tr').querySelector('.quantity-hidden').value = quantity;
            e.target.closest('tr').querySelector('.subtotal').textContent = '₫' + (item.price * quantity).toFixed(2);
            updateSummary();
        }
    }
});

// Handle item removal
document.getElementById('itemsTableBody').addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-item') || e.target.closest('.remove-item')) {
        const button = e.target.classList.contains('remove-item') ? e.target : e.target.closest('.remove-item');
        const rowKey = button.dataset.item;

        // Remove from selected items
        selectedItems = selectedItems.filter(item => item.rowKey != rowKey);

        // Remove from table
        document.getElementById(`item-row-${rowKey}`).remove();

        // Hide container if no items
        if (selectedItems.length === 0) {
            document.getElementById('itemsContainer').style.display = 'none';
            document.getElementById('submitBtn').disabled = true;
        }

        updateSummary();
    }
});

// Update summary
function updateSummary() {
    const itemCount = selectedItems.length;
    const subtotal = selectedItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    document.getElementById('summaryItemCount').textContent = itemCount;
    document.getElementById('summarySubtotal').textContent = '₫' + subtotal.toFixed(2);
    document.getElementById('summaryTotal').textContent = '₫' + subtotal.toFixed(2);
}

// Handle form submission
document.getElementById('saleForm').addEventListener('submit', function(e) {
    if (selectedItems.length === 0) {
        e.preventDefault();
        alert('Vui lòng chọn ít nhất một sản phẩm');
        return;
    }

    // Validate quantities
    for (let item of selectedItems) {
        if (item.quantity < 1) {
            e.preventDefault();
            alert('Số lượng phải lớn hơn 0');
            return;
        }
    }
});
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.manager', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\temp\perw-project\resources\views/manager/sales/create.blade.php ENDPATH**/ ?>