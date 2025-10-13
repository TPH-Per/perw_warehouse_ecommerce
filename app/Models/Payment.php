<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'Payments';

    protected $fillable = [
        'order_id',
        'payment_method_id',
        'amount',
        'status',
        'transaction_code',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Lấy đơn hàng của thanh toán này.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'order_id');
    }

    /**
     * Lấy phương thức thanh toán được sử dụng.
     */
    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }
}