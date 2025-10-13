<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDetail extends Model
{
    use HasFactory;

    protected $table = 'PurchaseOrderDetails';

    // Bảng này không có created_at và updated_at
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'product_variant_id',
        'quantity',
        'price_at_purchase',
    ];

    protected $casts = [
        'price_at_purchase' => 'decimal:2',
    ];

    /**
     * Lấy đơn hàng chứa chi tiết này.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'order_id');
    }

    /**
     * Lấy thông tin biến thể sản phẩm.
     */
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}