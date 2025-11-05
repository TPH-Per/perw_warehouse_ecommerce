<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductImage;

class ProductFromImagesSeeder extends Seeder
{
    /**
     * Create products and variants for each image in storage/app/public/products.
     */
    public function run(): void
    {
        $dir = storage_path('app/public/products');

        if (!is_dir($dir)) {
            echo "Directory not found: {$dir}\n";
            return;
        }

        // Ensure a default category and supplier exist
        $category = Category::firstOrCreate(
            ['slug' => 'imported-images'],
            ['name' => 'Imported from Images']
        );

        $supplier = Supplier::firstOrCreate(
            ['name' => 'Local Uploads'],
            ['contact_info' => 'Seeded from storage/app/public/products']
        );

        $files = collect(File::files($dir))
            ->filter(function ($file) {
                $ext = strtolower($file->getExtension());
                return in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']);
            })
            ->values();

        if ($files->isEmpty()) {
            echo "No image files found in {$dir}.\n";
            return;
        }

        $created = 0;
        foreach ($files as $file) {
            $base = $file->getBasename('.' . $file->getExtension());
            $humanName = trim(ucwords(str_replace(['_', '-', '.'], ' ', $base)));
            $slugBase = Str::slug($base);

            // Ensure unique slug if needed
            $slug = $slugBase;
            $suffix = 2;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $slugBase . '-' . $suffix;
                $suffix++;
            }

            // Create or fetch product
            $product = Product::firstOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $category->id,
                    'supplier_id' => $supplier->id,
                    'name' => $humanName ?: 'Product ' . strtoupper(Str::random(5)),
                    'description' => 'Product imported from local image',
                    'status' => 'published',
                ]
            );

            // Create a default variant if none exists
            if ($product->variants()->count() === 0) {
                $price = fake()->randomFloat(2, 50000, 3000000);
                $orig = fake()->boolean(40) ? $price + fake()->randomFloat(2, 10000, 300000) : $price;
                ProductVariant::create([
                    'product_id' => $product->id,
                    'name' => 'Default',
                    'sku' => 'IMG-' . strtoupper(Str::random(8)),
                    'price' => $price,
                    'original_price' => $orig,
                    'stock_quantity' => fake()->numberBetween(0, 50),
                ]);
            }

            // Attach image as primary
            $url = '/storage/products/' . $file->getFilename();
            ProductImage::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'image_url' => $url,
                ],
                [
                    'is_primary' => true,
                    'sort_order' => 0,
                ]
            );

            // Demote any others
            ProductImage::where('product_id', $product->id)
                ->where('image_url', '!=', $url)
                ->update(['is_primary' => false]);

            $created++;
        }

        echo "Seeded/linked products from images: {$created}\n";
    }
}

