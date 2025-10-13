<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'Products';

    protected $fillable = [
        'category_id',
        'supplier_id',
        'name',
        'description',
        'slug',
        'status',
        'is_featured',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_DRAFT = 'draft';

    /**
     * Query Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeBySupplier(Builder $query, int $supplierId): Builder
    {
        return $query->where('supplier_id', $supplierId);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%")
              ->orWhere('slug', 'like', "%{$search}%");
        });
    }

    /**
     * Accessors
     */
    public function getAverageRatingAttribute(): float
    {
        return round($this->reviews()->where('is_approved', true)->avg('rating') ?? 0, 1);
    }

    public function getReviewCountAttribute(): int
    {
        return $this->reviews()->where('is_approved', true)->count();
    }

    public function getMinPriceAttribute(): ?float
    {
        return $this->variants()->min('price');
    }

    public function getMaxPriceAttribute(): ?float
    {
        return $this->variants()->max('price');
    }

    public function getPriceRangeAttribute(): string
    {
        $min = $this->min_price;
        $max = $this->max_price;
        
        if ($min === $max) {
            return number_format($min, 0, ',', '.');
        }
        
        return number_format($min, 0, ',', '.') . ' - ' . number_format($max, 0, ',', '.');
    }

    public function getTotalStockAttribute(): int
    {
        return $this->variants->sum(function ($variant) {
            return $variant->inventories->sum('quantity_on_hand');
        });
    }

    public function getPrimaryImageAttribute(): ?string
    {
        return $this->images()->orderBy('sort_order')->first()?->image_url;
    }

    /**
     * Helper Methods
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isFeatured(): bool
    {
        return $this->is_featured === true;
    }

    public function hasStock(): bool
    {
        return $this->total_stock > 0;
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'bg-green-100 text-green-800',
            self::STATUS_INACTIVE => 'bg-red-100 text-red-800',
            self::STATUS_DRAFT => 'bg-yellow-100 text-yellow-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Lấy danh mục của sản phẩm.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Lấy nhà cung cấp của sản phẩm.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Lấy tất cả các biến thể của sản phẩm.
     */
    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    /**
     * Lấy tất cả hình ảnh của sản phẩm.
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id');
    }

    /**
     * Lấy tất cả đánh giá của sản phẩm.
     */
    public function reviews()
    {
        return $this->hasMany(ProductReview::class, 'product_id');
    }

    /**
     * Get approved reviews only
     */
    public function approvedReviews()
    {
        return $this->hasMany(ProductReview::class, 'product_id')
                    ->where('is_approved', true)
                    ->latest();
    }

    /**
     * Get purchase order details for this product
     */
    public function orderDetails()
    {
        return $this->hasManyThrough(
            PurchaseOrderDetail::class,
            ProductVariant::class,
            'product_id',
            'product_variant_id'
        );
    }

    /**
     * Get total sales count
     */
    public function getTotalSalesAttribute(): int
    {
        return $this->orderDetails()->sum('quantity');
    }

    /**
     * Check if product is low on stock
     */
    public function isLowStock(int $threshold = 10): bool
    {
        return $this->total_stock <= $threshold && $this->total_stock > 0;
    }

    /**
     * Check if product is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->total_stock <= 0;
    }
}