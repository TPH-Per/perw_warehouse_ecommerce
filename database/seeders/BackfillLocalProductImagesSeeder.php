<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;

class BackfillLocalProductImagesSeeder extends Seeder
{
    public function run(): void
    {
        $paths = collect(range(1, 12))->map(fn ($i) => "/images/products/product-{$i}.svg");

        // Ensure each product has at least one local primary image
        Product::with('images')->chunk(200, function ($products) use ($paths) {
            foreach ($products as $product) {
                if ($product->images->count() === 0) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => $paths->random(),
                        'is_primary' => true,
                        'sort_order' => 0,
                    ]);
                } else {
                    // Normalize existing images: if none are local, add one local primary
                    $hasLocal = $product->images->contains(function ($img) {
                        return is_string($img->image_url) && str_starts_with($img->image_url, '/images/products/');
                    });
                    if (!$hasLocal) {
                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_url' => $paths->random(),
                            'is_primary' => true,
                            'sort_order' => 0,
                        ]);
                    }
                }
            }
        });
    }
}

