# Testing PerW System

## Prerequisites
1. PHP 8.2 or higher
2. MySQL or compatible database server
3. Composer installed
4. Node.js and NPM (for frontend development)

## Database Setup

1. Create a MySQL database named `perw`:
   ```sql
   CREATE DATABASE perw CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Update your `.env` file with database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=perw
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

## Running Migrations and Seeders

1. Run migrations to create database tables:
   ```bash
   php artisan migrate
   ```

2. Seed the database with basic data:
   ```bash
   php artisan db:seed --class=BasicPerWSeeder
   ```

## Testing Models

You can test the models using Laravel Tinker:

1. Start Tinker:
   ```bash
   php artisan tinker
   ```

2. Test creating a category:
   ```php
   $category = new App\Models\Category();
   $category->name = 'Test Category';
   $category->slug = 'test-category';
   $category->save();
   ```

3. Test creating a role:
   ```php
   $role = new App\Models\Role();
   $role->name = 'Test Role';
   $role->description = 'A test role for verification';
   $role->save();
   ```

4. Exit Tinker:
   ```php
   exit
   ```

## Testing API Endpoints

1. Start the development server:
   ```bash
   php artisan serve
   ```

2. Visit the products API endpoint:
   ```
   http://localhost:8000/api/products
   ```

   You should receive an empty JSON array `[]` since no products have been created yet.

## Testing Admin Interface

1. Start the development server:
   ```bash
   php artisan serve
   ```

2. Visit the admin dashboard:
   ```
   http://localhost:8000/admin/dashboard
   ```

   You should see the basic admin dashboard template.

## Running Tests

To run the built-in Laravel tests:
```bash
php artisan test
```

## Development Workflow

1. Create new features in separate branches
2. Write tests for new functionality
3. Follow PSR-12 coding standards
4. Use Laravel's built-in tools for code quality:
   ```bash
   ./vendor/bin/pint
   ```

## Common Issues and Solutions

### Database Connection Error
- Verify database credentials in `.env` file
- Ensure MySQL service is running
- Check if the database `perw` exists

### Migration Error
- Run `php artisan config:clear` to clear configuration cache
- Ensure all migration files are properly formatted

### Class Not Found Error
- Run `composer dump-autoload` to refresh the autoloader
- Check if the class name matches the file name

## Next Steps

1. Implement full CRUD operations for all entities
2. Add authentication and authorization
3. Develop Vue.js frontend
4. Implement business logic
5. Add comprehensive testing
