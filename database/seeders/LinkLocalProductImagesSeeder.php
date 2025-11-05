<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\Product;
use App\Models\ProductImage;

class LinkLocalProductImagesSeeder extends Seeder
{
    /**
     * Normalize filenames under storage/app/public/products and link them to products.
     */
    public function run(): void
    {
        $dir = storage_path('app/public/products');

        if (!is_dir($dir)) {
            echo "Directory not found: {$dir}\n";
            return;
        }

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

        $normalizedPaths = [];
        $counter = 1;
        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            $newName = sprintf('product-%03d.%s', $counter, $ext);
            $newPath = $file->getPath() . DIRECTORY_SEPARATOR . $newName;

            if ($file->getFilename() !== $newName) {
                // Avoid overwriting an existing normalized file
                if (!file_exists($newPath)) {
                    @rename($file->getPathname(), $newPath);
                } else {
                    // If collision, skip rename and use existing name
                    $newPath = $file->getPathname();
                }
            }

            $relativeUrl = '/storage/products/' . basename($newPath);
            $normalizedPaths[] = $relativeUrl;
            $counter++;
        }

        // Ensure every product has at least one image pointing to a normalized local file
        $products = Product::select('id')->get();
        $totalImages = count($normalizedPaths);
        if ($totalImages === 0) {
            echo "No normalized images available to link.\n";
            return;
        }

        $i = 0;
        foreach ($products as $product) {
            $imageUrl = $normalizedPaths[$i % $totalImages];
            ProductImage::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'image_url' => $imageUrl,
                ],
                [
                    'is_primary' => true,
                ]
            );

            // Demote any other images for this product to non-primary
            ProductImage::where('product_id', $product->id)
                ->where('image_url', '!=', $imageUrl)
                ->update(['is_primary' => false]);

            $i++;
        }

        echo sprintf("Linked %d products to local images under /storage/products.\n", $products->count());
    }
}

