# PerW - Anime Merchandise E-commerce & Warehouse Management System

PerW is a comprehensive Laravel-based e-commerce platform specifically designed for selling anime merchandise with integrated warehouse management capabilities. The system provides robust functionality for managing products, inventory across multiple warehouses, orders, customers, and administrative operations.

## Table of Contents

- [Features](#features)
- [Technology Stack](#technology-stack)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Structure](#database-structure)
- [Available Commands](#available-commands)
- [API Documentation](#api-documentation)
- [Admin Panel](#admin-panel)
- [Manager Panel](#manager-panel)
- [Development](#development)
- [Testing](#testing)
- [Deployment](#deployment)
- [Project Structure](#project-structure)
- [Contributing](#contributing)
- [License](#license)

## Features

### E-commerce Functionality (Customer API)
- Product catalog with category filtering, search, and price range filter
- Product variants (SKU, pricing, weight, dimensions)
- Shopping cart management (add, update, remove, clear)
- Cart organized by warehouse buckets (Hanoi, HCMC, Binh Dinh)
- Order creation with automatic inventory deduction
- Order cancellation with inventory restoration
- Public order tracking by order code
- Order history and status tracking
- User profile management (name, email, phone)
- Multiple address management with default address
- Product reviews (only approved reviews shown publicly)

### Payment Processing
- VNPAY payment gateway integration (QR code and card)
- Cash on Delivery (COD) payment method
- Payment status tracking (pending / completed / failed / refunded)
- Local test QR payment mode for development

### Warehouse Management
- Three warehouses: Hanoi (North), Ho Chi Minh City (South), Binh Dinh (Central)
- Automatic warehouse assignment based on customer's province
- Multi-warehouse inventory tracking per product variant
- Inbound receipt recording (single and batch)
- Outbound stock deduction on order creation
- Inventory reservations tracking
- Stock adjustments and inter-warehouse transfers
- Low stock alerts and reorder level management
- Full inventory transaction history (inbound / outbound)

### Admin Panel
- Comprehensive dashboard with key metrics and charts
- Full product management (CRUD, variants, images, categories, suppliers)
- Bulk product status updates
- Order management (processing, shipment creation, status updates, cancellation)
- CSV export for orders and users
- Customer/user management (activate, suspend, password reset)
- Inventory management (inbound, adjust, transfer, statistics, export)
- User statistics and analytics

### Manager Panel (Warehouse-scoped)
- Warehouse-specific dashboard
- Inventory management limited to assigned warehouse
- Direct sales / walk-in customer orders (no shipping required)
- Shipping order management (view and update status + tracking)
- Product inventory visibility limited to assigned warehouse

### Security & Authentication
- Role-based access control: `admin`, `manager` (inventory), `endUser` (customer)
- Laravel Sanctum token authentication for API
- Web session authentication for admin/manager panels
- Account activation and suspension
- CSRF protection and input validation

## Technology Stack

- **Backend**: Laravel 12 with PHP 8.2+
- **Frontend**: Blade templates for admin, Vue.js planned for client interface
- **Database**: MySQL
- **API**: RESTful API with Laravel Sanctum for authentication
- **Styling**: Bootstrap 5 for admin panel, Tailwind CSS for frontend
- **Build Tools**: Vite
- **Testing**: PHPUnit with Faker for test data
- **Development Tools**: Laravel Pint, Sail, Tinker, Pail

## System Requirements

- PHP 8.2 or higher
- MySQL 5.7 or higher
- Composer
- Node.js and npm
- BCMath PHP Extension
- Ctype PHP Extension
- cURL PHP Extension
- DOM PHP Extension
- Fileinfo PHP Extension
- JSON PHP Extension
- Mbstring PHP Extension
- OpenSSL PHP Extension
- PCRE PHP Extension
- PDO PHP Extension
- Tokenizer PHP Extension
- XML PHP Extension

## Installation

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd perw-project
   ```

2. Install PHP dependencies:
   ```bash
   composer install
   ```

3. Install Node.js dependencies:
   ```bash
   npm install
   ```

4. Copy and configure the environment file:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. Configure your database in the `.env` file:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=perw
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

6. Run database migrations:
   ```bash
   php artisan migrate
   ```

7. Seed the database with basic data:
   ```bash
   php artisan db:seed --class=BasicPerWSeeder
   ```

8. For development with sample data:
   ```bash
   php artisan db:seed --class=PerWDatabaseSeeder
   ```

## Configuration

### Environment Variables

Key environment variables to configure:

- `APP_URL`: Your application URL
- `DB_*`: Database connection settings
- `VNPAY_*`: VNPAY payment gateway configuration
- `MAIL_*`: Email configuration (for notifications)

### Localization

The project is fully localized to Vietnamese. The locale is set to 'vi' in both `config/app.php` and `.env` files.

### Payment Gateway

VNPAY integration is configured through:
- `VNPAY_TMN_CODE`: Merchant code
- `VNPAY_HASH_SECRET`: Security hash
- `VNPAY_URL`: Payment endpoint
- `VNPAY_RETURN_URL`: Return URL after payment
- `VNPAY_IPN_URL`: Instant Payment Notification URL

## Database Structure

The system includes a comprehensive database schema with the following main entities:

### Core Entities
- **Roles**: User roles (Admin, Inventory Manager, Customer)
- **Users**: Customer and admin accounts
- **Categories**: Product categorization hierarchy
- **Suppliers**: Product suppliers
- **Products**: Main product information
- **ProductVariants**: SKU-level product details
- **ProductImages**: Product imagery

### Inventory Management
- **Warehouses**: Storage locations
- **Inventories**: Stock levels per product variant per warehouse
- **InventoryTransactions**: Stock movement history

### Order Processing
- **Addresses**: Customer addresses
- **Carts**: Shopping carts
- **CartDetails**: Items in carts
- **PurchaseOrders**: Customer orders
- **PurchaseOrderDetails**: Items in orders
- **Payments**: Payment records
- **Shipments**: Shipping information
- **ShippingMethods**: Available shipping options
- **PaymentMethods**: Available payment options

### User Engagement
- **ProductReviews**: Customer product reviews

## Available Commands

### Setup Commands
```bash
# Complete setup (install dependencies, generate key, migrate, seed, build assets)
composer run setup

# Development server with all services
composer run dev

# Run tests
composer run test
```

### Custom Artisan Commands
- `php artisan check:admin-user`: Verify admin user exists
- `php artisan reset:admin-password`: Reset admin password
- `php artisan test:inventory`: Test inventory functionality

## API Documentation

The system provides a RESTful API for client applications:

### Public Endpoints
- `POST /api/auth/register` — Customer registration
- `POST /api/auth/login` — Customer login
- `GET /api/products` — List products (search, category, price range filter)
- `GET /api/products/{id}` — Get product details with variants, images, reviews
- `GET /api/products/slug/{slug}` — Get product by slug
- `GET /api/products/featured` — Get 8 featured products
- `GET /api/products/search?q=` — Search products by name/description
- `GET /api/categories` — List categories with product counts
- `GET /api/categories/{id}/products` — List products in category
- `GET /api/orders/track/{orderCode}` — Track order by code
- `GET /api/provinces` — List provinces grouped by warehouse cluster
- `GET /api/provinces/list` — Flat list of all provinces

### Protected Endpoints (Bearer token required)
- `GET /api/auth/user` — Get authenticated user profile
- `POST /api/auth/logout` — Logout (revoke token)
- `GET /api/cart` — Get cart with warehouse buckets and totals
- `POST /api/cart/items` — Add item to cart (validates stock)
- `PUT /api/cart/items/{item}` — Update cart item quantity
- `DELETE /api/cart/items/{item}` — Remove item from cart
- `DELETE /api/cart` — Clear entire cart
- `GET /api/orders` — List user orders (paginated, filterable by status)
- `GET /api/orders/{id}` — Get single order details
- `POST /api/orders` — Create order from cart
- `POST /api/orders/{id}/cancel` — Cancel order (restores inventory)
- `GET /api/provinces/warehouse/{province}` — Get warehouse ID for a province

## Admin Panel

The admin panel is accessible at `/admin` (requires `admin` role) and provides:

### Dashboard
- System overview: total users, products, orders, revenue
- Recent orders and top selling products (last 30 days)
- Sales charts (last 7 days)
- Order status distribution
- Low stock inventory alerts
- Recent user registrations

### Management Modules
1. **Product Management**: Full CRUD for products, variants, images; bulk status updates; create categories and suppliers inline
2. **Order Management**: View/update order status, create shipments, process payments (VNPAY / COD), cancel orders, export CSV
3. **User Management**: Create/edit/suspend/activate users, assign roles and warehouse, change passwords, export CSV
4. **Inventory Control**: Inbound receipts, adjustments, inter-warehouse transfers, statistics, export, reorder levels
5. **Authentication**: Protected by `IsAdmin` middleware; session-based login

## Manager Panel

The manager panel is accessible at `/manager` (requires `manager` role and assigned warehouse) and provides:

### Dashboard
- Warehouse-specific inventory overview
- Low stock alerts for assigned warehouse

### Management Modules
1. **Inventory Management**: Record inbound, view/adjust inventory — scoped to assigned warehouse only
2. **Direct Sales**: Create walk-in/counter orders with immediate inventory deduction (no shipping)
3. **Order Management**: View and update shipping orders assigned to the warehouse
4. **Product Viewing**: Read-only product catalog with warehouse inventory levels

## Development

### Code Standards
- PSR-12 coding standards
- Laravel conventions
- Bootstrap 5 for admin UI
- Tailwind CSS for frontend

### Development Server
Start the development server with all required services:
```bash
composer run dev
```

This command concurrently runs:
- Laravel development server
- Queue worker
- Log viewer
- Vite development server

### Building Assets
```bash
# Development build
npm run dev

# Production build
npm run build
```

## Testing

Run the test suite:
```bash
composer run test
```

The project includes:
- Unit tests
- Feature tests
- Custom test cases
- Test factories for data generation

## Deployment

### Production Setup
1. Configure environment variables for production
2. Run migrations:
   ```bash
   php artisan migrate --force
   ```
3. Seed essential data:
   ```bash
   php artisan db:seed --class=BasicPerWSeeder
   ```
4. Optimize the application:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
5. Build frontend assets:
   ```bash
   npm run build
   ```

### Queue Workers
For processing background jobs:
```bash
php artisan queue:work --tries=3
```

## Project Structure

```
app/
├── Console/Commands        # Custom Artisan commands
├── Events                  # Domain events
├── Http/
│   ├── Controllers/        # Web and API controllers
│   └── Middleware/         # Request middleware
├── Models/                 # Eloquent models
├── Providers/              # Service providers
└── Services/               # Business logic services
config/                     # Configuration files
database/
├── factories/              # Model factories
├── migrations/             # Database migrations
└── seeders/                # Database seeders
public/                     # Public assets
resources/
├── css/                    # Stylesheets
├── js/                     # JavaScript files
├── lang/                   # Language files
└── views/                  # Blade templates
routes/                     # Route definitions
storage/                    # File storage
tests/                      # Test suite
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a pull request

## License

This project is proprietary and intended for educational purposes. All rights reserved.
