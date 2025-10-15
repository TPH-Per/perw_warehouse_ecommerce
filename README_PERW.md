# PerW E-commerce & Warehouse Management System

This is a Laravel-based system for managing an anime merchandise e-commerce platform with warehouse management capabilities.

## System Components

### 1. Database Migrations
All database tables have been created with proper relationships:
- **Core Data Tables**: Roles, Users, Addresses, Categories, Suppliers
- **Product & Inventory Tables**: Products, ProductVariants, ProductImages, Warehouses, Inventories, InventoryTransactions
- **Business Transaction Tables**: Carts, CartDetails, PurchaseOrders, PurchaseOrderDetails, Payments, Shipments
- **Configuration Tables**: ShippingMethods, PaymentMethods
- **Review System**: ProductReviews

### 2. Eloquent Models
All models have been created with proper relationships defined:
- Role, User, Address
- Category, Supplier
- Product, ProductVariant, ProductImage
- Warehouse, Inventory, InventoryTransaction
- Cart, CartDetail
- PurchaseOrder, PurchaseOrderDetail
- Payment, Shipment
- ShippingMethod, PaymentMethod
- ProductReview

### 3. Factories & Seeders
- Factories for generating test data
- Sample seeder with basic data structure

## Setup Instructions

1. **Database Configuration**:
   - Configure your database connection in `.env` file
   - For MySQL:
     ```
     DB_CONNECTION=mysql
     DB_HOST=127.0.0.1
     DB_PORT=3306
     DB_DATABASE=perw
     DB_USERNAME=your_username
     DB_PASSWORD=your_password
     ```
   - Create the database in your MySQL server

2. **Run Migrations**:
   ```bash
   php artisan migrate
   ```

3. **Seed Basic Data**:
   ```bash
   php artisan db:seed --class=BasicPerWSeeder
   ```

4. **For Development with Factories**:
   ```bash
   php artisan db:seed --class=PerWDatabaseSeeder
   ```

## System Architecture

### User Roles
- **Admin**: Full system access
- **End User**: Customer functionality

### Core Modules

1. **Product Management**:
   - Categories with hierarchical structure
   - Products with multiple variants (SKU, pricing)
   - Product images
   - Supplier management

2. **Inventory Management**:
   - Multi-warehouse support
   - Stock tracking (on hand vs reserved)
   - Inventory transactions (inbound/outbound)

3. **Order Management**:
   - Shopping cart functionality
   - Order processing
   - Payment integration
   - Shipping management

4. **User Management**:
   - User profiles
   - Address book
   - Order history

5. **Review System**:
   - Product reviews with ratings
   - Admin approval workflow

## API Structure

The system is designed to support both:
1. **Admin Interface**: MVC with Blade templates
2. **Client Interface**: Vue.js with RESTful API

## Next Steps

1. Implement controllers for each module
2. Create API endpoints for Vue.js frontend
3. Develop admin dashboard with Blade templates
4. Implement authentication and authorization
5. Add business logic for inventory management
6. Create order processing workflows
