<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'original_price',
        'size',
        'color',
        'stock_quantity',
    ];

    /**
     * Get the product that owns the variant.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the inventory records for the variant.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'product_variant_id');
    }

    /**
     * Get the cart details for the variant.
     */
    public function cartDetails(): HasMany
    {
        return $this->hasMany(CartDetail::class, 'product_variant_id');
    }

    /**
     * Get the order details for the variant.
     */
    public function orderDetails(): HasMany
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'product_variant_id');
    }

    /**
     * Get the inventory transactions for the variant.
     */
    public function inventoryTransactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'product_variant_id');
    }
}
