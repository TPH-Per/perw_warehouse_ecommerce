# PerW — Multi-Branch Warehouse & E-commerce Management System

PerW is a **Laravel 12 / PHP 8.2** web application that combines an e-commerce storefront with a multi-branch warehouse management back-end. It is designed around the model of a single e-commerce platform served by several regional warehouses (branches), where each branch independently controls its own stock while a central admin oversees the entire operation.

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.2 |
| Database | MySQL |
| Auth | Laravel Sanctum (API tokens) + session (web panels) |
| Frontend | Blade templates (admin/manager), Tailwind CSS, Vite |
| Payments | VNPAY gateway + Cash on Delivery |
| Testing | PHPUnit |

## Model Overview

```
Admin (global)
 ├── manages all products, categories, users, orders
 └── oversees all warehouses / inventory

Warehouse Manager (per branch)
 ├── records inbound receipts → stock increases only through receipts
 ├── processes walk-in (direct) sales
 └── updates shipping order status

Customer (API)
 ├── browses products, manages cart, places orders
 └── order is automatically routed to the nearest warehouse
```

Stock levels are **append-only** — inventory can only increase via inbound receipts, ensuring a complete, auditable transaction history at every branch.

## Quick Start

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# configure DB_* in .env
php artisan migrate
php artisan db:seed --class=BasicPerWSeeder
npm run build
php artisan serve
```

Admin panel: `/admin` · Manager panel: `/manager` · Customer API: `/api`
