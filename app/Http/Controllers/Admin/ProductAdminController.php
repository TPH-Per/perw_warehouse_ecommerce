<?php

namespace App\Http\Controllers\Admin;

use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\ProductVariant;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductAdminController extends AdminController
{
    /**
     * Display a listing of products
     */
    public function index(Request $request)
    {
        $query = Product::with(['category', 'supplier', 'variants']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by supplier
        if ($request->has('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter products by user's assigned warehouse if they are a warehouse-specific manager
        $user = Auth::user();
        if ($user && $user->role->name === 'Inventory Manager' && $user->warehouse_id) {
            // Only show products that have inventory in the user's assigned warehouse
            $query->whereHas('variants.inventories', function($q) use ($user) {
                $q->where('warehouse_id', $user->warehouse_id);
            });
        }

        $products = $query->paginate(20);
        $categories = Category::all();
        $suppliers = Supplier::all();

        // Return appropriate view based on user role
        $viewPrefix = ($user && ($user->role->name === 'Manager' || $user->role->name === 'Inventory Manager')) ? 'manager' : 'admin';
        return view("{$viewPrefix}.products.index", compact('products', 'categories', 'suppliers'));
    }

    /**
     * Display the specified product
     */
    public function show(Product $product)
    {
        // Check if user has access to this product
        $user = Auth::user();
        if ($user && $user->role->name === 'Inventory Manager' && $user->warehouse_id) {
            // Check if this product has inventory in the user's assigned warehouse
            $hasInventoryInWarehouse = $product->variants()->whereHas('inventories', function($q) use ($user) {
                $q->where('warehouse_id', $user->warehouse_id);
            })->exists();

            if (!$hasInventoryInWarehouse) {
                abort(403, 'Unauthorized access to this product.');
            }
        }

        $product->load(['category', 'supplier', 'variants.inventories.warehouse', 'images']);

        // Return appropriate view based on user role
        $viewPrefix = ($user && ($user->role->name === 'Manager' || $user->role->name === 'Inventory Manager')) ? 'manager' : 'admin';
        return view("{$viewPrefix}.products.show", compact('product'));
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        $categories = Category::all();
        $suppliers = Supplier::all();

        return view('admin.products.create', compact('categories', 'suppliers'));
    }

    /**
     * Store a newly created product
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'status' => 'required|in:draft,published,archived',
            'variants' => 'required|array|min:1',
            'variants.*.sku' => 'required|string|unique:product_variants,sku',
            'variants.*.variant_name' => 'required|string|max:100',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.weight' => 'nullable|numeric|min:0',
            'variants.*.dimensions' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorRedirect($validator->errors()->first());
        }

        try {
            DB::beginTransaction();

            $product = Product::create([
                'category_id' => $request->category_id,
                'supplier_id' => $request->supplier_id,
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            // Create variants
            foreach ($request->variants as $variantData) {
                ProductVariant::create([
                    'product_id' => $product->id,
                    'name' => $variantData['variant_name'],
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'weight' => $variantData['weight'] ?? null,
                    'dimensions' => $variantData['dimensions'] ?? null,
                ]);
            }

            // Handle image uploads if present
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('products', 'public');
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $path,
                        'is_primary' => $index === 0,
                    ]);
                }
            }

            DB::commit();

            return $this->successRedirect('admin.products.index', 'Product created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorRedirect('Failed to create product: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified product
     */
    public function edit(Product $product)
    {
        $product->load(['variants', 'images']);
        $categories = Category::all();
        $suppliers = Supplier::all();

        return view('admin.products.edit', compact('product', 'categories', 'suppliers'));
    }

    /**
     * Update the specified product
     */
    public function update(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'status' => 'required|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return $this->errorRedirect($validator->errors()->first());
        }

        try {
            $product->update([
                'category_id' => $request->category_id,
                'supplier_id' => $request->supplier_id,
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status,
            ]);

            return $this->successRedirect('admin.products.show', 'Product updated successfully!', ['product' => $product->id]);
        } catch (\Exception $e) {
            return $this->errorRedirect('Failed to update product: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(Product $product)
    {
        try {
            // Check if product has any purchase order details
            $hasOrderDetails = $product->variants()->whereHas('orderDetails')->exists();

            if ($hasOrderDetails) {
                return $this->errorRedirect('Cannot delete product because it has been ordered. You can archive the product instead.');
            }

            DB::beginTransaction();

            // Delete associated images from storage
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->image_url);
                $image->delete();
            }

            // Delete variants
            $product->variants()->delete();

            // Delete product
            $product->delete();

            DB::commit();

            return $this->successRedirect('admin.products.index', 'Product deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorRedirect('Failed to delete product: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update product status
     */
    public function bulkUpdateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
            'status' => 'required|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), $validator->errors()->toArray());
        }

        try {
            Product::whereIn('id', $request->product_ids)->update(['status' => $request->status]);

            return $this->successResponse('Products status updated successfully!');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update products: ' . $e->getMessage());
        }
    }

    /**
     * Add variant to product
     */
    public function addVariant(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'sku' => 'required|string|unique:product_variants,sku',
            'variant_name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), $validator->errors()->toArray());
        }

        try {
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'name' => $request->variant_name,
                'sku' => $request->sku,
                'price' => $request->price,
                'weight' => $request->weight ?? null,
                'dimensions' => $request->dimensions ?? null,
            ]);

            return $this->successResponse('Variant added successfully!', ['variant' => $variant]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add variant: ' . $e->getMessage());
        }
    }

    /**
     * Upload images for product
     */
    public function uploadImages(Request $request, Product $product)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), $validator->errors()->toArray());
        }

        try {
            $images = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                $productImage = ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $path,
                    'is_primary' => false, // Will be set when user selects primary image
                ]);
                $images[] = $productImage;
            }

            return $this->successResponse('Images uploaded successfully!', ['images' => $images]);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to upload images: ' . $e->getMessage());
        }
    }

    /**
     * Set primary image for product
     */
    public function setPrimaryImage(Request $request, Product $product, ProductImage $image)
    {
        try {
            // Remove primary flag from all images
            ProductImage::where('product_id', $product->id)->update(['is_primary' => false]);

            // Set this image as primary
            $image->update(['is_primary' => true]);

            return $this->successResponse('Primary image set successfully!');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to set primary image: ' . $e->getMessage());
        }
    }

    /**
     * Delete product image
     */
    public function deleteImage(Request $request, Product $product, ProductImage $image)
    {
        try {
            // Delete image from storage
            Storage::disk('public')->delete($image->image_url);

            // Delete image record
            $image->delete();

            return $this->successResponse('Image deleted successfully!');
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete image: ' . $e->getMessage());
        }
    }
}
