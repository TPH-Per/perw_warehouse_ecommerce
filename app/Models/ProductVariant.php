<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ProductVariants';

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'price',
        'original_price',
        'cost_price',
        'weight',
        'dimensions',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Query Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->whereHas('inventories', function ($q) {
            $q->whereRaw('quantity_on_hand > quantity_reserved');
        });
    }

    public function scopeByProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Lấy sản phẩm cha của biến thể này.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Lấy thông tin tồn kho của biến thể này.
     */
    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'product_variant_id');
    }

    /**
     * Get cart details containing this variant
     */
    public function cartDetails()
    {
        return $this->hasMany(CartDetail::class, 'product_variant_id');
    }

    /**
     * Get purchase order details for this variant
     */
    public function purchaseOrderDetails()
    {
        return $this->hasMany(PurchaseOrderDetail::class, 'product_variant_id');
    }

    /**
     * Get inventory transactions
     */
    public function inventoryTransactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'product_variant_id');
    }

    /**
     * Accessors
     */
    public function getTotalStockAttribute(): int
    {
        return $this->inventories->sum('quantity_on_hand');
    }

    public function getAvailableStockAttribute(): int
    {
        return $this->inventories->sum(function ($inventory) {
            return $inventory->quantity_on_hand - $inventory->quantity_reserved;
        });
    }

    public function getReservedStockAttribute(): int
    {
        return $this->inventories->sum('quantity_reserved');
    }

    public function getDiscountPercentageAttribute(): ?float
    {
        if (!$this->original_price || $this->original_price <= $this->price) {
            return null;
        }
        
        return round((($this->original_price - $this->price) / $this->original_price) * 100, 1);
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->original_price && $this->original_price > $this->price;
    }

    public function getFullNameAttribute(): string
    {
        return $this->product->name . ($this->name ? ' - ' . $this->name : '');
    }

    public function getProfitMarginAttribute(): ?float
    {
        if (!$this->cost_price || $this->cost_price <= 0) {
            return null;
        }
        
        return round((($this->price - $this->cost_price) / $this->cost_price) * 100, 2);
    }

    /**
     * Helper Methods
     */
    public function isInStock(): bool
    {
        return $this->available_stock > 0;
    }

    public function isActive(): bool
    {
        return $this->is_active === true && $this->product->isActive();
    }

    public function isDefault(): bool
    {
        return $this->is_default === true;
    }

    public function canPurchase(int $quantity = 1): bool
    {
        return $this->isActive() && $this->available_stock >= $quantity;
    }

    public function isLowStock(int $threshold = 10): bool
    {
        return $this->available_stock <= $threshold && $this->available_stock > 0;
    }

    public function getStockStatus(): string
    {
        if ($this->available_stock <= 0) {
            return 'out_of_stock';
        } elseif ($this->isLowStock()) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    public function getStockStatusBadgeClass(): string
    {
        return match($this->getStockStatus()) {
            'in_stock' => 'bg-green-100 text-green-800',
            'low_stock' => 'bg-yellow-100 text-yellow-800',
            'out_of_stock' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}