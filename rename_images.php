<?php

$dir = __DIR__ . '/storage/app/public/products';
$files = glob($dir . '/*.{png,jpg,jpeg,gif,webp}', GLOB_BRACE);

if (empty($files)) {
    echo "No image files found in {$dir}\n";
    exit(1);
}

echo "Found " . count($files) . " image files\n";

$counter = 1;
foreach ($files as $file) {
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    $newName = sprintf('product-%03d.%s', $counter, $extension);
    $newPath = $dir . '/' . $newName;

    // Skip if already renamed
    if (basename($file) === $newName) {
        echo "Skipping {$file} (already named correctly)\n";
        $counter++;
        continue;
    }

    // Rename the file
    if (rename($file, $newPath)) {
        echo "Renamed: " . basename($file) . " -> {$newName}\n";
    } else {
        echo "Failed to rename: " . basename($file) . "\n";
    }

    $counter++;
}

echo "Renamed " . ($counter - 1) . " files\n";
