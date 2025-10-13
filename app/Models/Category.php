<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'Categories';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'description',
        'image_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
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

    public function scopeRootCategories(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeWithProductCount(Builder $query): Builder
    {
        return $query->withCount('products');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Lấy danh mục cha.
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Lấy các danh mục con.
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Lấy tất cả sản phẩm thuộc danh mục này.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    /**
     * Get active products only
     */
    public function activeProducts()
    {
        return $this->hasMany(Product::class, 'category_id')
                    ->where('status', Product::STATUS_ACTIVE);
    }

    /**
     * Get all descendants (children, grandchildren, etc.)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all ancestors (parent, grandparent, etc.)
     */
    public function ancestors()
    {
        $ancestors = collect([]);
        $category = $this->parent;
        
        while ($category) {
            $ancestors->push($category);
            $category = $category->parent;
        }
        
        return $ancestors;
    }

    /**
     * Accessors
     */
    public function getProductCountAttribute(): int
    {
        return $this->products()->count();
    }

    public function getActiveProductCountAttribute(): int
    {
        return $this->activeProducts()->count();
    }

    public function getFullPathAttribute(): string
    {
        $path = collect([$this->name]);
        $category = $this->parent;
        
        while ($category) {
            $path->prepend($category->name);
            $category = $category->parent;
        }
        
        return $path->implode(' > ');
    }

    public function getLevelAttribute(): int
    {
        $level = 0;
        $category = $this->parent;
        
        while ($category) {
            $level++;
            $category = $category->parent;
        }
        
        return $level;
    }

    /**
     * Helper Methods
     */
    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function hasProducts(): bool
    {
        return $this->products()->exists();
    }

    public function isActive(): bool
    {
        return $this->is_active === true;
    }

    /**
     * Get all category IDs including this one and all descendants
     */
    public function getAllCategoryIds(): array
    {
        $ids = [$this->id];
        
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->getAllCategoryIds());
        }
        
        return $ids;
    }
}