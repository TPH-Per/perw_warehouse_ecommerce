<?php

/**
 * Master test runner to execute all test cases in the project
 * This script will run all tests in the TestCases and CheckTests directories
 */

require_once __DIR__ . '/../vendor/autoload.php';

echo "=========================================\n";
echo "PERW Project - Complete Test Suite Runner\n";
echo "=========================================\n\n";

// Define test directories
$testDirectories = [
    'TestCases',
    'CheckTests'
];

$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

foreach ($testDirectories as $directory) {
    $dirPath = __DIR__ . '/' . $directory;

    if (!is_dir($dirPath)) {
        echo "Warning: Directory {$dirPath} does not exist.\n\n";
        continue;
    }

    echo "Running tests in {$directory}...\n";
    echo str_repeat("-", 40) . "\n";

    // Get all PHP files in the directory
    $files = glob($dirPath . '/*.php');

    foreach ($files as $file) {
        $fileName = basename($file);

        // Skip the runner itself
        if ($fileName === 'RunAllTests.php') {
            continue;
        }

        $totalTests++;
        echo "Executing: {$fileName}\n";

        try {
            // For each test file, we'll execute it in a separate process to avoid conflicts
            $output = [];
            $returnCode = 0;
            exec("php " . escapeshellarg($file), $output, $returnCode);

            if ($returnCode === 0) {
                echo "Result: PASSED\n";
                echo "Output:\n" . implode("\n", $output) . "\n";
                $passedTests++;
            } else {
                echo "Result: FAILED\n";
                echo "Error Output:\n" . implode("\n", $output) . "\n\n";
                $failedTests++;
            }
        } catch (Exception $e) {
            echo "Result: FAILED\n";
            echo "Error: " . $e->getMessage() . "\n\n";
            $failedTests++;
        } catch (Error $e) {
            echo "Result: FAILED\n";
            echo "Error: " . $e->getMessage() . "\n\n";
            $failedTests++;
        }

        echo str_repeat("-", 40) . "\n";
    }

    echo "\n";
}

// Display summary
echo "=========================================\n";
echo "TEST SUITE SUMMARY\n";
echo "=========================================\n";
echo "Total Tests:  {$totalTests}\n";
echo "Passed:       {$passedTests}\n";
echo "Failed:       {$failedTests}\n";
echo "Success Rate: " . ($totalTests > 0 ? round(($passedTests / $totalTests) * 100, 2) : 0) . "%\n";
echo "=========================================\n";

if ($failedTests > 0) {
    exit(1);
} else {
    echo "All tests completed successfully!\n";
    exit(0);
}
