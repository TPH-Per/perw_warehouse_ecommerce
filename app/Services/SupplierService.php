<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SupplierService
{
    /**
     * Get all suppliers with pagination and filters
     */
    public function getSuppliers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Supplier::with('products');

        // Search filter
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_info', 'like', "%{$search}%");
            });
        }

        // Sort options
        $sortBy = $filters['sort_by'] ?? 'name';
        $sortOrder = $filters['sort_order'] ?? 'asc';
        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Get supplier by ID
     */
    public function getSupplierById(int $id): Supplier
    {
        return Supplier::with(['products.variants'])->findOrFail($id);
    }

    /**
     * Create a new supplier
     */
    public function createSupplier(array $data): Supplier
    {
        return Supplier::create($data);
    }

    /**
     * Update an existing supplier
     */
    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);
        return $supplier->fresh(['products']);
    }

    /**
     * Delete a supplier (soft delete)
     */
    public function deleteSupplier(Supplier $supplier): bool
    {
        // Check if supplier can be deleted
        $canDelete = $this->canDeleteSupplier($supplier);
        
        if (!$canDelete['can_delete']) {
            throw new \Exception($canDelete['reason']);
        }

        return $supplier->delete();
    }

    /**
     * Get all suppliers for dropdown/select
     */
    public function getAllSuppliersForSelect(): Collection
    {
        return Supplier::select('id', 'name')
                      ->orderBy('name')
                      ->get();
    }

    /**
     * Search suppliers
     */
    public function searchSuppliers(string $query): Collection
    {
        return Supplier::where('name', 'like', "%{$query}%")
                      ->orWhere('contact_info', 'like', "%{$query}%")
                      ->withCount('products')
                      ->orderBy('name')
                      ->get();
    }

    /**
     * Get supplier statistics
     */
    public function getSupplierStatistics(): array
    {
        $totalSuppliers = Supplier::count();
        $suppliersWithProducts = Supplier::has('products')->count();
        $suppliersWithoutProducts = Supplier::doesntHave('products')->count();

        $topSuppliers = Supplier::withCount('products')
                              ->orderBy('products_count', 'desc')
                              ->limit(10)
                              ->get();

        return [
            'total_suppliers' => $totalSuppliers,
            'suppliers_with_products' => $suppliersWithProducts,
            'suppliers_without_products' => $suppliersWithoutProducts,
            'top_suppliers' => $topSuppliers,
        ];
    }

    /**
     * Check if supplier can be deleted
     */
    public function canDeleteSupplier(Supplier $supplier): array
    {
        // Check if supplier has products
        if ($supplier->products()->exists()) {
            return [
                'can_delete' => false,
                'reason' => 'Cannot delete supplier with products. This supplier has associated products and cannot be deleted.'
            ];
        }

        return [
            'can_delete' => true,
            'reason' => null
        ];
    }

    /**
     * Get suppliers with product count
     */
    public function getSuppliersWithProductCount(): Collection
    {
        return Supplier::withCount('products')
                      ->orderBy('name')
                      ->get();
    }

    /**
     * Bulk update suppliers
     */
    public function bulkUpdateSuppliers(array $supplierIds, array $data): int
    {
        return Supplier::whereIn('id', $supplierIds)->update($data);
    }

    /**
     * Get supplier performance metrics
     */
    public function getSupplierPerformance(int $supplierId): array
    {
        $supplier = Supplier::with(['products.variants.inventories'])->findOrFail($supplierId);
        
        $totalProducts = $supplier->products->count();
        $totalVariants = $supplier->products->sum(function ($product) {
            return $product->variants->count();
        });
        
        $totalStock = $supplier->products->sum(function ($product) {
            return $product->variants->sum(function ($variant) {
                return $variant->inventories->sum('quantity_on_hand');
            });
        });

        return [
            'supplier' => $supplier,
            'total_products' => $totalProducts,
            'total_variants' => $totalVariants,
            'total_stock' => $totalStock,
        ];
    }
}