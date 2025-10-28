<?php

require_once 'vendor/autoload.php';

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Pagination\LengthAwarePaginator;

try {
    // Create a simple paginator for testing
    $items = collect([1, 2, 3, 4, 5, 6, 7, 8, 9, 10]);
    $paginator = new LengthAwarePaginator($items->forPage(1, 5), $items->count(), 5, 1);

    // Render the pagination links
    $links = (string) $paginator->links();
    echo "Pagination links rendered successfully\n";

    // Check if Bootstrap classes are present
    if (strpos($links, 'pagination') !== false && strpos($links, 'page-item') !== false) {
        echo "Bootstrap pagination is being used\n";
    } else {
        echo "Pagination might still be using Tailwind CSS\n";
    }

    // Show a snippet of the rendered HTML
    echo "HTML snippet:\n";
    echo substr($links, 0, 200) . "...\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
