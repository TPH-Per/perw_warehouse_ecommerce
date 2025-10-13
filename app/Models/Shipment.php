<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $table = 'Shipments';

    protected $fillable = [
        'order_id',
        'shipping_method_id',
        'tracking_code',
        'status',
    ];

    /**
     * Lấy đơn hàng của lần vận chuyển này.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'order_id');
    }

    /**
     * Lấy phương thức vận chuyển được sử dụng.
     */
    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }
}