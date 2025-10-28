# PERW Project - Run All Tests

## Running All Tests

To run all test cases at once, use the master test runner:

```bash
php tests/RunAllTests.php
```

This command will execute all test files in both:
- `tests/TestCases/` - Functional tests
- `tests/CheckTests/` - Data verification tests

All 22 tests have been successfully organized and are now running correctly with a 100% success rate.

## Test Results

When you run the test suite, you'll see output similar to:

```
=========================================
PERW Project - Complete Test Suite Runner
=========================================

Running tests in TestCases...
----------------------------------------
Executing: test_admin_user_form.php
Result: PASSED
Output:
Admin role found: ID=1, Name=Admin
Form submission processed successfully!
User created successfully via form!
User ID: 6
User Name: Test Admin via Form
User Role ID: 1
User Role Name: Admin
----------------------------------------
...

=========================================
TEST SUITE SUMMARY
=========================================
Total Tests:  22
Passed:       22
Failed:       0
Success Rate: 100%
=========================================
All tests completed successfully!
```

## Test Categories

### TestCases (`tests/TestCases/`)
Functional tests that verify various aspects of the application:
- Authentication tests
- Route generation
- Controller logic
- Form submissions
- Payment processing
- Pagination
- Data validation
- User filtering
- And more...

### CheckTests (`tests/CheckTests/`)
Data verification tests that check:
- Database structure and record counts
- Table relationships
- Data integrity
- Role assignments
- Product and inventory data
- User data consistency

## Individual Test Execution

You can also run individual test files directly:

```bash
php tests/TestCases/test_auth.php
php tests/CheckTests/check_data.php
```

## Windows Batch Script

For easier execution on Windows, you can also use the batch script:

```bash
run-tests.bat
```

This will run the same test suite and pause at the end to show you the results.

## Troubleshooting

If you encounter any issues:
1. Ensure all dependencies are installed (`composer install`)
2. Check that the database is accessible
3. Verify environment variables in `.env` file
4. Make sure the application can boot properly

## Adding New Tests

To add new tests:
1. Place test files in the appropriate directory (`TestCases` or `CheckTests`)
2. Ensure they follow the existing pattern with proper PHP opening tags
3. The master runner will automatically include them
