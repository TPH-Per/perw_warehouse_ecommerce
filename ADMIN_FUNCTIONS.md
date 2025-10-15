# Admin Functions Documentation

This document describes all the admin functionality implemented for the PerW E-commerce & Warehouse Management System.

## Overview

The admin panel provides comprehensive management tools for:
- Products & Inventory
- Orders & Shipments
- Users & Roles
- Dashboard Statistics

## Table of Contents

1. [Architecture](#architecture)
2. [Controllers](#controllers)
3. [Routes](#routes)
4. [Middleware](#middleware)
5. [Usage Examples](#usage-examples)

---

## Architecture

### Base Controller

**Location:** `app/Http/Controllers/Admin/AdminController.php`

Provides helper methods for all admin controllers:

- `successResponse()` - Returns JSON success response
- `errorResponse()` - Returns JSON error response
- `successRedirect()` - Redirects with success message
- `errorRedirect()` - Redirects back with error message
- `isAdmin()` - Checks if current user is admin

---

## Controllers

### 1. DashboardController

**Location:** `app/Http/Controllers/Admin/DashboardController.php`

#### Methods:

**`index(Request $request)`**
- Displays admin dashboard with comprehensive statistics
- Returns: View with stats, recent orders, top products, sales data, etc.
- Route: `GET /admin/dashboard`

**`getStatistics(Request $request)`**
- Returns dashboard statistics as JSON
- Parameters: `date_from`, `date_to` (optional)
- Returns: JSON with users, products, orders, revenue, and inventory stats
- Route: `GET /admin/dashboard/statistics`

#### Statistics Provided:
- Total users, new users this month
- Total products, active/inactive products
- Order counts by status
- Revenue (total and this month)
- Low stock and out-of-stock items
- Pending reviews
- Recent orders, top selling products
- Sales chart data (last 7 days)

---

### 2. ProductAdminController

**Location:** `app/Http/Controllers/Admin/ProductAdminController.php`

#### Methods:

**`index(Request $request)`**
- Lists all products with filtering and search
- Filters: `search`, `category_id`, `supplier_id`, `status`
- Returns: Paginated product list
- Route: `GET /admin/products`

**`create()`**
- Shows product creation form
- Route: `GET /admin/products/create`

**`store(Request $request)`**
- Creates new product with variants
- Required: `name`, `category_id`, `supplier_id`, `status`, `variants[]`
- Handles image uploads
- Route: `POST /admin/products`

**`show(Product $product)`**
- Displays product details
- Route: `GET /admin/products/{product}`

**`edit(Product $product)`**
- Shows product edit form
- Route: `GET /admin/products/{product}/edit`

**`update(Request $request, Product $product)`**
- Updates product information
- Route: `PUT /admin/products/{product}`

**`destroy(Product $product)`**
- Deletes product and associated data
- Route: `DELETE /admin/products/{product}`

**`addVariant(Request $request, Product $product)`**
- Adds new variant to existing product
- Route: `POST /admin/products/{product}/variants`

**`uploadImages(Request $request, Product $product)`**
- Uploads product images
- Supports multiple images
- Route: `POST /admin/products/{product}/images`

**`setPrimaryImage(Product $product, ProductImage $image)`**
- Sets primary product image
- Route: `PUT /admin/products/{product}/images/{image}/primary`

**`deleteImage(Product $product, ProductImage $image)`**
- Deletes product image
- Route: `DELETE /admin/products/{product}/images/{image}`

**`bulkUpdateStatus(Request $request)`**
- Updates status for multiple products
- Route: `POST /admin/products/bulk/status`

---

### 3. OrderAdminController

**Location:** `app/Http/Controllers/Admin/OrderAdminController.php`

#### Methods:

**`index(Request $request)`**
- Lists all orders with filtering
- Filters: `search`, `status`, `date_from`, `date_to`
- Returns: Paginated order list
- Route: `GET /admin/orders`

**`show(PurchaseOrder $order)`**
- Displays order details with items, payment, and shipment
- Route: `GET /admin/orders/{order}`

**`updateStatus(Request $request, PurchaseOrder $order)`**
- Updates order status
- Statuses: `pending`, `processing`, `shipped`, `delivered`, `cancelled`
- Auto-refunds on cancellation
- Route: `PUT /admin/orders/{order}/status`

**`processPayment(Request $request, PurchaseOrder $order)`**
- Processes payment for order
- Required: `payment_method_id`, `transaction_id`, `amount`
- Route: `POST /admin/orders/{order}/payment`

**`createShipment(Request $request, PurchaseOrder $order)`**
- Creates shipment for order
- Required: `shipping_method_id`, `tracking_number`
- Optional: `carrier`, `estimated_delivery`
- Route: `POST /admin/orders/{order}/shipment`

**`updateShipmentStatus(Request $request, Shipment $shipment)`**
- Updates shipment status
- Statuses: `pending`, `in_transit`, `delivered`, `failed`
- Route: `PUT /admin/orders/shipments/{shipment}/status`

**`cancel(Request $request, PurchaseOrder $order)`**
- Cancels order with reason
- Required: `cancellation_reason`
- Route: `POST /admin/orders/{order}/cancel`

**`statistics(Request $request)`**
- Returns order statistics
- Route: `GET /admin/orders/statistics`

**`export(Request $request)`**
- Exports orders to CSV
- Route: `GET /admin/orders/export`

**`bulkUpdateStatus(Request $request)`**
- Updates status for multiple orders
- Route: `POST /admin/orders/bulk/status`

---

### 4. UserAdminController

**Location:** `app/Http/Controllers/Admin/UserAdminController.php`

#### Methods:

**`index(Request $request)`**
- Lists all users with filtering
- Filters: `search`, `role_id`, `status`
- Returns: Paginated user list
- Route: `GET /admin/users`

**`create()`**
- Shows user creation form
- Route: `GET /admin/users/create`

**`store(Request $request)`**
- Creates new user
- Required: `role_id`, `full_name`, `email`, `password`, `status`
- Route: `POST /admin/users`

**`show(User $user)`**
- Displays user details with statistics
- Shows: orders, spending, reviews
- Route: `GET /admin/users/{user}`

**`edit(User $user)`**
- Shows user edit form
- Route: `GET /admin/users/{user}/edit`

**`update(Request $request, User $user)`**
- Updates user information
- Route: `PUT /admin/users/{user}`

**`updatePassword(Request $request, User $user)`**
- Updates user password
- Route: `PUT /admin/users/{user}/password`

**`suspend(User $user)`**
- Suspends user account
- Route: `POST /admin/users/{user}/suspend`

**`activate(User $user)`**
- Activates user account
- Route: `POST /admin/users/{user}/activate`

**`destroy(User $user)`**
- Deletes user (only if no orders)
- Route: `DELETE /admin/users/{user}`

**`statistics(Request $request)`**
- Returns user statistics
- Route: `GET /admin/users/statistics`

**`export(Request $request)`**
- Exports users to CSV
- Route: `GET /admin/users/export`

**`bulkUpdateStatus(Request $request)`**
- Updates status for multiple users
- Route: `POST /admin/users/bulk/status`

---

### 5. InventoryAdminController

**Location:** `app/Http/Controllers/Admin/InventoryAdminController.php`

#### Methods:

**`index(Request $request)`**
- Lists all inventory with filtering
- Filters: `search`, `warehouse_id`, `low_stock`, `out_of_stock`
- Route: `GET /admin/inventory`

**`show(Inventory $inventory)`**
- Displays inventory details with transactions
- Route: `GET /admin/inventory/{inventory}`

**`store(Request $request)`**
- Creates inventory for product variant
- Required: `warehouse_id`, `product_variant_id`, `quantity_on_hand`, `reorder_level`
- Route: `POST /admin/inventory`

**`adjust(Request $request, Inventory $inventory)`**
- Adjusts inventory quantity
- Required: `quantity`, `transaction_type` (inbound/outbound/adjustment)
- Optional: `reference_number`, `notes`
- Route: `POST /admin/inventory/{inventory}/adjust`

**`transfer(Request $request)`**
- Transfers inventory between warehouses
- Required: `from_warehouse_id`, `to_warehouse_id`, `product_variant_id`, `quantity`
- Route: `POST /admin/inventory/transfer`

**`setReorderLevel(Request $request, Inventory $inventory)`**
- Sets reorder level for inventory
- Required: `reorder_level`
- Route: `PUT /admin/inventory/{inventory}/reorder-level`

**`lowStock(Request $request)`**
- Returns low stock items
- Route: `GET /admin/inventory/low-stock`

**`statistics(Request $request)`**
- Returns inventory statistics
- Route: `GET /admin/inventory/statistics`

**`transactions(Request $request)`**
- Lists inventory transactions with filtering
- Filters: `warehouse_id`, `transaction_type`, `date_from`, `date_to`
- Route: `GET /admin/inventory/transactions`

**`export(Request $request)`**
- Exports inventory to CSV
- Route: `GET /admin/inventory/export`

---

## Routes

All admin routes are prefixed with `/admin` and require authentication with admin role.

### Route Structure:

```
/admin
├── /dashboard                    # Dashboard
│   └── /statistics              # Dashboard statistics API
├── /products                     # Products CRUD
│   ├── /{product}/variants      # Add variant
│   ├── /{product}/images        # Upload images
│   └── /bulk/status             # Bulk status update
├── /orders                       # Orders management
│   ├── /{order}/status          # Update status
│   ├── /{order}/payment         # Process payment
│   ├── /{order}/shipment        # Create shipment
│   ├── /{order}/cancel          # Cancel order
│   ├── /statistics              # Order statistics
│   └── /export                  # Export orders
├── /users                        # Users management
│   ├── /{user}/password         # Update password
│   ├── /{user}/suspend          # Suspend user
│   ├── /{user}/activate         # Activate user
│   ├── /statistics              # User statistics
│   └── /export                  # Export users
└── /inventory                    # Inventory management
    ├── /{inventory}/adjust      # Adjust quantity
    ├── /transfer                # Transfer between warehouses
    ├── /{inventory}/reorder-level # Set reorder level
    ├── /low-stock               # Low stock alerts
    ├── /statistics              # Inventory statistics
    ├── /transactions            # Transaction history
    └── /export                  # Export inventory
```

---

## Middleware

### IsAdmin Middleware

**Location:** `app/Http/Middleware/IsAdmin.php`

**Alias:** `admin`

**Checks:**
1. User is authenticated
2. User has 'Admin' role
3. User account status is 'active'

**Responses:**
- JSON response for API requests
- Redirect to login for web requests
- 403 error for unauthorized access

**Registration:** `bootstrap/app.php`

**Usage:** All admin routes are protected with `middleware(['admin'])`

---

## Usage Examples

### 1. Accessing Admin Dashboard

```
GET /admin/dashboard
```

Protected by admin middleware. Shows comprehensive dashboard with statistics.

### 2. Creating a Product

```
POST /admin/products
Content-Type: application/json

{
  "name": "Naruto Figure",
  "description": "Limited edition Naruto figure",
  "category_id": 1,
  "supplier_id": 1,
  "status": "active",
  "variants": [
    {
      "sku": "NAR-001",
      "variant_name": "Standard",
      "price": 29.99,
      "weight": 0.5,
      "dimensions": "10x10x15"
    }
  ]
}
```

### 3. Updating Order Status

```
PUT /admin/orders/1/status
Content-Type: application/json

{
  "status": "shipped"
}
```

### 4. Adjusting Inventory

```
POST /admin/inventory/1/adjust
Content-Type: application/json

{
  "quantity": 50,
  "transaction_type": "inbound",
  "reference_number": "PO-12345",
  "notes": "New stock arrival"
}
```

### 5. Transferring Inventory

```
POST /admin/inventory/transfer
Content-Type: application/json

{
  "from_warehouse_id": 1,
  "to_warehouse_id": 2,
  "product_variant_id": 1,
  "quantity": 20,
  "notes": "Stock rebalancing"
}
```

### 6. Creating Shipment

```
POST /admin/orders/1/shipment
Content-Type: application/json

{
  "shipping_method_id": 1,
  "tracking_number": "TRACK123456",
  "carrier": "FedEx",
  "estimated_delivery": "2025-10-20"
}
```

### 7. Bulk Update Product Status

```
POST /admin/products/bulk/status
Content-Type: application/json

{
  "product_ids": [1, 2, 3, 4],
  "status": "inactive"
}
```

### 8. Export Orders to CSV

```
GET /admin/orders/export?date_from=2025-01-01&date_to=2025-12-31&status=delivered
```

### 9. Getting Dashboard Statistics (API)

```
GET /admin/dashboard/statistics?date_from=2025-01-01&date_to=2025-12-31
```

Returns JSON with comprehensive statistics.

### 10. Suspending User Account

```
POST /admin/users/1/suspend
```

---

## Features Summary

### Product Management
- ✅ Full CRUD operations
- ✅ Product variants management
- ✅ Image upload and management
- ✅ Bulk status updates
- ✅ Search and filtering

### Order Management
- ✅ Order listing and details
- ✅ Status updates
- ✅ Payment processing
- ✅ Shipment tracking
- ✅ Order cancellation with refunds
- ✅ Statistics and reporting
- ✅ CSV export

### User Management
- ✅ Full CRUD operations
- ✅ Role-based access
- ✅ Account suspension/activation
- ✅ Password management
- ✅ User statistics
- ✅ CSV export
- ✅ Bulk operations

### Inventory Management
- ✅ Multi-warehouse support
- ✅ Inventory adjustments
- ✅ Warehouse transfers
- ✅ Low stock alerts
- ✅ Reorder level management
- ✅ Transaction history
- ✅ Statistics and reporting
- ✅ CSV export

### Dashboard & Analytics
- ✅ Comprehensive statistics
- ✅ Recent orders view
- ✅ Top selling products
- ✅ Sales charts
- ✅ Low stock alerts
- ✅ Recent user registrations
- ✅ Order status distribution

---

## Security

- All admin routes protected by `IsAdmin` middleware
- Authentication required
- Role-based authorization (Admin role only)
- Active account status check
- CSRF protection on all POST/PUT/DELETE requests
- Input validation on all endpoints
- SQL injection prevention via Eloquent ORM

---

## Next Steps for Development

1. **Create Blade Views**: Develop frontend views for all admin pages
2. **Implement Authentication**: Set up Laravel Sanctum or Passport
3. **Add Vue.js Components**: Create interactive UI components
4. **Implement API Documentation**: Add Swagger/OpenAPI documentation
5. **Add Unit Tests**: Create tests for all controller methods
6. **Implement Logging**: Add activity logging for admin actions
7. **Add Email Notifications**: Send notifications for order status changes
8. **Create Reports Module**: Add advanced reporting and analytics
9. **Implement File Upload**: Complete image upload functionality with validation
10. **Add Caching**: Implement Redis caching for dashboard statistics

---

## File Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Admin/
│   │       ├── AdminController.php
│   │       ├── DashboardController.php
│   │       ├── ProductAdminController.php
│   │       ├── OrderAdminController.php
│   │       ├── UserAdminController.php
│   │       └── InventoryAdminController.php
│   └── Middleware/
│       └── IsAdmin.php
├── Models/
│   ├── User.php
│   ├── Role.php
│   ├── Product.php
│   ├── ProductVariant.php
│   ├── ProductImage.php
│   ├── PurchaseOrder.php
│   ├── Payment.php
│   ├── Shipment.php
│   ├── Inventory.php
│   ├── InventoryTransaction.php
│   └── ...
bootstrap/
└── app.php (middleware registration)
routes/
└── web.php (admin routes)
```

---

## Support

For issues or questions about the admin functionality, please refer to:
- Project README: `README_PERW.md`
- Database Structure: Check migration files in `database/migrations/`
- Models Documentation: Check individual model files in `app/Models/`
