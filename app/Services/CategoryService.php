<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    /**
     * Get all categories with hierarchy
     */
    public function getCategories(): Collection
    {
        return Category::whereNull('parent_id')
                      ->with(['children' => function ($query) {
                          $query->withCount('products');
                      }])
                      ->withCount('products')
                      ->orderBy('name')
                      ->get();
    }

    /**
     * Get category by ID with relationships
     */
    public function getCategoryById(int $id): Category
    {
        return Category::with(['parent', 'children', 'products'])
                      ->withCount('products')
                      ->findOrFail($id);
    }

    /**
     * Create a new category
     */
    public function createCategory(array $data): Category
    {
        $data['slug'] = $this->generateUniqueSlug($data['name']);

        return Category::create($data);
    }

    /**
     * Update an existing category
     */
    public function updateCategory(Category $category, array $data): Category
    {
        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name']);
        }

        $category->update($data);
        return $category->fresh(['parent']);
    }

    /**
     * Delete a category
     */
    public function deleteCategory(Category $category): bool
    {
        // Check if category can be deleted
        $canDelete = $this->canDeleteCategory($category);
        
        if (!$canDelete['can_delete']) {
            throw new \Exception($canDelete['reason']);
        }

        return $category->delete();
    }

    /**
     * Get category tree structure
     */
    public function getCategoryTree(): Collection
    {
        return Category::whereNull('parent_id')
                      ->with(['children' => function ($query) {
                          $query->with('children')->orderBy('name');
                      }])
                      ->orderBy('name')
                      ->get();
    }

    /**
     * Get categories by parent ID
     */
    public function getCategoriesByParent(?int $parentId = null): Collection
    {
        return Category::where('parent_id', $parentId)
                      ->withCount('products')
                      ->orderBy('name')
                      ->get();
    }

    /**
     * Search categories
     */
    public function searchCategories(string $query): Collection
    {
        return Category::where('name', 'like', "%{$query}%")
                      ->with(['parent', 'children'])
                      ->withCount('products')
                      ->orderBy('name')
                      ->get();
    }

    /**
     * Get categories with products count
     */
    public function getCategoriesWithProductCount(): Collection
    {
        return Category::withCount('products')
                      ->orderBy('name')
                      ->get();
    }

    /**
     * Get popular categories (with most products)
     */
    public function getPopularCategories(int $limit = 10): Collection
    {
        return Category::withCount('products')
                      ->orderBy('products_count', 'desc')
                      ->limit($limit)
                      ->get();
    }

    /**
     * Get category hierarchy path
     */
    public function getCategoryPath(Category $category): array
    {
        $path = [];
        $current = $category;

        while ($current) {
            array_unshift($path, [
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
            ]);
            $current = $current->parent;
        }

        return $path;
    }

    /**
     * Check if category can be deleted
     */
    public function canDeleteCategory(Category $category): array
    {
        // Check if category has products
        if ($category->products()->exists()) {
            return [
                'can_delete' => false,
                'reason' => 'Cannot delete category with products. This category contains products and cannot be deleted.'
            ];
        }

        // Check if category has children
        if ($category->children()->exists()) {
            return [
                'can_delete' => false,
                'reason' => 'Cannot delete category with subcategories. This category has subcategories and cannot be deleted.'
            ];
        }

        return [
            'can_delete' => true,
            'reason' => null
        ];
    }

    /**
     * Generate unique slug for category
     */
    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Bulk update categories
     */
    public function bulkUpdateCategories(array $categoryIds, array $data): int
    {
        return Category::whereIn('id', $categoryIds)->update($data);
    }

    /**
     * Get category statistics
     */
    public function getCategoryStatistics(): array
    {
        $totalCategories = Category::count();
        $parentCategories = Category::whereNull('parent_id')->count();
        $childCategories = Category::whereNotNull('parent_id')->count();
        
        $categoriesWithProducts = Category::has('products')->count();
        $emptycategories = Category::doesntHave('products')->count();

        $topCategories = Category::withCount('products')
                               ->orderBy('products_count', 'desc')
                               ->limit(5)
                               ->get();

        return [
            'total_categories' => $totalCategories,
            'parent_categories' => $parentCategories,
            'child_categories' => $childCategories,
            'categories_with_products' => $categoriesWithProducts,
            'empty_categories' => $emptycategories,
            'top_categories' => $topCategories,
        ];
    }

    /**
     * Move category to different parent
     */
    public function moveCategory(Category $category, ?int $newParentId): Category
    {
        // Validate that we're not creating a circular reference
        if ($newParentId && $this->wouldCreateCircularReference($category, $newParentId)) {
            throw new \Exception('Cannot move category: would create circular reference.');
        }

        $category->update(['parent_id' => $newParentId]);
        return $category->fresh(['parent', 'children']);
    }

    /**
     * Check if moving would create circular reference
     */
    private function wouldCreateCircularReference(Category $category, int $newParentId): bool
    {
        // Get all descendant IDs of the category being moved
        $descendantIds = $this->getDescendantIds($category);
        
        // If new parent is among descendants, it would create a circular reference
        return in_array($newParentId, $descendantIds);
    }

    /**
     * Get all descendant category IDs
     */
    private function getDescendantIds(Category $category): array
    {
        $descendants = [];
        
        foreach ($category->children as $child) {
            $descendants[] = $child->id;
            $descendants = array_merge($descendants, $this->getDescendantIds($child));
        }
        
        return $descendants;
    }
}