<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Category;

try {
    // Test soft delete functionality
    echo "Testing soft delete functionality...\n";

    // Create a test category
    $category = Category::create([
        'name' => 'Test Category for Soft Delete',
        'slug' => 'test-category-soft-delete'
    ]);

    echo "Created category with ID: {$category->id}\n";

    // Verify category exists
    $foundCategory = Category::find($category->id);
    if ($foundCategory) {
        echo "Category found before deletion\n";
    } else {
        echo "ERROR: Category not found before deletion\n";
        exit(1);
    }

    // Soft delete the category
    $category->delete();
    echo "Category soft deleted\n";

    // Verify category is not found with normal query
    $foundCategory = Category::find($category->id);
    if ($foundCategory) {
        echo "ERROR: Category still found after soft delete\n";
        exit(1);
    } else {
        echo "Category correctly hidden after soft delete\n";
    }

    // Verify category can be found with withTrashed
    $foundCategory = Category::withTrashed()->find($category->id);
    if ($foundCategory) {
        echo "Category found with withTrashed() - soft delete working correctly\n";
    } else {
        echo "ERROR: Category not found even with withTrashed()\n";
        exit(1);
    }

    // Test restore functionality
    $foundCategory->restore();
    echo "Category restored\n";

    // Verify category is found again
    $foundCategory = Category::find($category->id);
    if ($foundCategory) {
        echo "Category correctly found after restore\n";
    } else {
        echo "ERROR: Category not found after restore\n";
        exit(1);
    }

    // Clean up - permanently delete the test category
    $foundCategory->forceDelete();
    echo "Test category cleaned up\n";

    echo "All tests passed! Soft delete functionality is working correctly.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
