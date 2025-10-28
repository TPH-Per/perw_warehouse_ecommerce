<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Run all test cases in the project
     *
     * @return void
     */
    public function runAllTests()
    {
        // This method would call our RunAllTests.php script
        echo "Use 'php tests/RunAllTests.php' to run all tests\n";
    }
}
