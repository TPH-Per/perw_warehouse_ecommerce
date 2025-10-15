<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PurchaseOrder extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'order_code',
        'status',
        'shipping_recipient_name',
        'shipping_recipient_phone',
        'shipping_address',
        'sub_total',
        'shipping_fee',
        'discount_amount',
        'total_amount',
    ];

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order details for the order.
     */
    public function orderDetails(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'order_id');
    }

    /**
     * Get the payment for the order.
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class, 'order_id');
    }

    /**
     * Get the shipment for the order.
     */
    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class, 'order_id');
    }

    /**
     * Get the inventory transactions for the order.
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'order_id');
    }
}
