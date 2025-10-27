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
- [Development](#development)
- [Testing](#testing)
- [Deployment](#deployment)
- [Project Structure](#project-structure)
- [Contributing](#contributing)
- [License](#license)

## Features

### E-commerce Functionality
- Product catalog with categories and suppliers
- Product variants (SKU, pricing, specifications)
- Shopping cart system
- Order processing workflow
- Payment integration (VNPAY sandbox)
- Shipping management
- Customer account management
- Address book
- Order history
- Product review system with admin approval

### Warehouse Management
- Multi-warehouse inventory tracking
- Real-time stock level monitoring
- Inventory reservations
- Inbound and outbound transaction logging
- Stock adjustments and transfers between warehouses
- Low stock alerts
- Reorder level management

### Admin Panel
- Comprehensive dashboard with analytics
- Product management (CRUD operations)
- Order management and fulfillment
- Customer management
- Inventory control
- User role management
- Reporting and statistics

### Security & Authentication
- Role-based access control (Admin, Inventory Manager, Customer)
- Secure authentication system
- Password encryption
- CSRF protection
- Input validation

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
- `POST /api/auth/register`: Customer registration
- `POST /api/auth/login`: Customer login
- `GET /api/products`: List products
- `GET /api/products/{id}`: Get product details
- `GET /api/products/slug/{slug}`: Get product by slug
- `GET /api/products/featured`: Get featured products
- `GET /api/products/search`: Search products

### Protected Endpoints
- `GET /api/auth/user`: Get authenticated user
- `POST /api/auth/logout`: Logout
- `GET /api/cart`: Get cart
- `POST /api/cart`: Add to cart
- `PUT /api/cart/{id}`: Update cart item
- `DELETE /api/cart/{id}`: Remove from cart
- `POST /api/orders`: Create order
- `GET /api/orders`: List user orders
- `GET /api/orders/{id}`: Get order details

## Admin Panel

The admin panel is accessible at `/admin` and provides:

### Dashboard
- System overview with key metrics
- Recent orders
- Top selling products
- Sales charts
- Inventory alerts

### Management Modules
1. **Product Management**: Full CRUD for products and variants
2. **Order Management**: Process orders, update status, manage shipments
3. **Customer Management**: View and manage customer accounts
4. **Inventory Control**: Monitor stock levels, adjust inventory, transfer between warehouses
5. **User Management**: Admin user management with role assignments

### Authentication
- Protected by `IsAdmin` middleware
- Role-based access control
- Session management

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
