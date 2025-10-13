<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $table = 'InventoryTransactions';

    // Bảng này không có cột updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'product_variant_id',
        'warehouse_id',
        'order_id',
        'quantity',
        'type',
        'notes',
    ];

    /**
     * Lấy biến thể sản phẩm của giao dịch.
     */
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Lấy kho hàng của giao dịch.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Lấy đơn hàng liên quan (nếu có).
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'order_id');
    }
}