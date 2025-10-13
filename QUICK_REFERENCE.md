# Quick Reference Cheat Sheet

## 🚀 Common Usage Patterns

### Models - Query Scopes

```php
// Products
Product::active()->get();
Product::featured()->get();
Product::search('keyword')->get();
Product::byCategory($id)->get();

// Orders
PurchaseOrder::pending()->get();
PurchaseOrder::paid()->get();
PurchaseOrder::recent(30)->get();
PurchaseOrder::byUser($userId)->get();

// Categories
Category::active()->rootCategories()->get();
Category::withProductCount()->get();
```

### Models - Accessors

```php
// Product
$product->average_rating; // 4.5
$product->review_count; // 120
$product->price_range; // "100,000 - 150,000"
$product->total_stock; // 50
$product->primary_image; // URL

// ProductVariant
$variant->available_stock; // 25
$variant->discount_percentage; // 20.5
$variant->full_name; // "Product Name - Variant"
$variant->profit_margin; // 35.2

// PurchaseOrder
$order->item_count; // 5
$order->total_paid; // 450000
$order->remaining_amount; // 50000
$order->formatted_total; // "500,000 VND"
```

### Models - Helper Methods

```php
// Product
$product->isActive(); // true/false
$product->hasStock(); // true/false
$product->isLowStock(10); // true/false
$product->getStatusBadgeClass(); // "bg-green-100 text-green-800"

// Order
$order->canBeCancelled(); // true/false
$order->getProgressPercentage(); // 50
$order->getAvailableStatusTransitions(); // ['shipped', 'cancelled']

// Category
$category->isRoot(); // true/false
$category->hasChildren(); // true/false
$category->getAllCategoryIds(); // [1, 2, 3]
```

### Services - Analytics

```php
use App\Services\AnalyticsService;

$analytics = new AnalyticsService();

// Dashboard stats
$stats = $analytics->getDashboardStats([
    'from_date' => now()->startOfMonth(),
    'to_date' => now()
]);

// Access data
$stats['revenue']['total_revenue'];
$stats['orders']['total_orders'];
$stats['products']['active_products'];
$stats['inventory']['low_stock_count'];
```

### Services - Wishlist

```php
use App\Services\WishlistService;

$wishlist = new WishlistService();

// Get user wishlist
$items = $wishlist->getUserWishlist($userId);

// Add to wishlist
$wishlist->addToWishlist($userId, $productId);

// Toggle wishlist
$result = $wishlist->toggleWishlist($userId, $productId);

// Move to cart
$wishlist->moveToCart($userId, [$productId1, $productId2]);

// Clear wishlist
$wishlist->clearWishlist($userId);
```

### Services - Notifications

```php
use App\Services\NotificationService;

$notify = new NotificationService();

// Order notifications
$notify->notifyOrderCreated($order);
$notify->notifyOrderShipped($order, $trackingCode);
$notify->notifyOrderDelivered($order);

// Get notifications
$notifications = $notify->getUserNotifications($userId, 20);
$unreadCount = $notify->getUnreadCount($userId);

// Mark as read
$notify->markAsRead($notificationId, $userId);
$notify->markAllAsRead($userId);

// System notification
$notify->notifySystem($userId, 'Title', 'Message', ['key' => 'value']);
```

### Enums - Usage

```php
use App\Enums\OrderStatus;
use App\Enums\ProductStatus;

// Order Status
$status = OrderStatus::PAID;
$label = $status->label(); // "Paid"
$class = $status->badgeClass(); // "bg-green-100 text-green-800"

// Check transitions
if ($currentStatus->canTransitionTo($newStatus)) {
    $order->status = $newStatus->value;
}

// Get all values
$allStatuses = OrderStatus::values();
$options = OrderStatus::options();

// Product Status
ProductStatus::ACTIVE->isAvailableForPurchase(); // true
```

### Frontend - UIHelpers

```javascript
import { UIHelpers } from './utils/helpers.js';

// Toast notifications
UIHelpers.showToast('Success!', 'success');
UIHelpers.showToast('Error occurred', 'error');

// Date formatting
UIHelpers.formatDate(date, 'short'); // "Jan 15, 2024"
UIHelpers.formatDate(date, 'long'); // "January 15, 2024, 10:30 AM"
UIHelpers.getRelativeTime(date); // "2 hours ago"

// Currency
UIHelpers.formatCurrency(150000, 'VND'); // "150,000 VND"

// Utilities
await UIHelpers.copyToClipboard('ORD123456');
UIHelpers.scrollToElement('#section', 100);
UIHelpers.truncate(text, 100); // "Long text..."

// LocalStorage
UIHelpers.localStorage.set('cart', cartData);
const cart = UIHelpers.localStorage.get('cart', []);

// Debounce & Throttle
const search = UIHelpers.debounce(searchFunction, 300);
const scroll = UIHelpers.throttle(scrollFunction, 100);
```

### Frontend - API

```javascript
import api from './utils/api.js';

// GET request
const products = await api.get('/products', { page: 1, limit: 10 });

// POST request
const result = await api.post('/wishlist', { product_id: 1 });

// PUT request
await api.put('/orders/1', { status: 'shipped' });

// DELETE request
await api.delete('/wishlist/1');

// File upload with progress
await api.upload('/upload', file, (progress) => {
    console.log(`${progress}% uploaded`);
});

// Batch requests
const results = await api.batch([
    { method: 'GET', endpoint: '/products' },
    { method: 'GET', endpoint: '/categories' }
]);

// Download file
await api.download('/export/orders', 'orders.xlsx');
```

### Frontend - Notification Module

```javascript
import { NotificationModule } from './modules/notification.js';

const notifications = new NotificationModule();

// Automatically polls for new notifications
// Updates badge counter
// Shows dropdown on click
// Handles mark as read/unread
```

### Frontend - Wishlist Module

```javascript
import { WishlistModule } from './modules/wishlist.js';

const wishlist = new WishlistModule();

// Get count
const count = wishlist.getWishlistCount();

// Check if in wishlist
const inWishlist = wishlist.isInWishlist(productId);
```

## 📋 Common Patterns

### Check Product Availability

```php
if ($product->isActive() && $product->hasStock()) {
    // Product can be purchased
    $variant = $product->variants->first();
    
    if ($variant->canPurchase($quantity)) {
        // Add to cart
    }
}
```

### Get Product with Full Details

```php
$product = Product::with([
    'category',
    'images',
    'variants.inventories',
    'approvedReviews' => function($q) {
        $q->latest()->limit(10);
    }
])->findOrFail($id);

// Access computed properties
$product->average_rating;
$product->total_stock;
$product->price_range;
```

### Process Order Lifecycle

```php
// Create order
$order = PurchaseOrder::create([...]);
$notificationService->notifyOrderCreated($order);

// Update status
$order->update(['status' => OrderStatus::PAID->value]);
$notificationService->notifyPaymentReceived($order, $amount);

// Ship order
if ($order->canBeShipped()) {
    $order->update([
        'status' => OrderStatus::SHIPPED->value,
        'shipped_at' => now()
    ]);
    $notificationService->notifyOrderShipped($order, $trackingCode);
}
```

### Analytics Dashboard

```php
$analytics = new AnalyticsService();
$stats = $analytics->getDashboardStats();

return view('dashboard', [
    'revenue' => $stats['revenue'],
    'orders' => $stats['orders'],
    'products' => $stats['products'],
    'inventory' => $stats['inventory'],
    'trends' => $stats['trends']
]);
```

### Handle Low Stock

```php
// In observer or service
if ($inventory->quantity_on_hand <= 10) {
    $notificationService->notifyLowStock(
        $product->id,
        $product->name,
        $inventory->quantity_on_hand
    );
}
```

## 🎯 Performance Tips

### Eager Loading

```php
// Good ✅
$products = Product::with(['category', 'images', 'variants'])
    ->active()
    ->get();

// Bad ❌ (N+1 problem)
$products = Product::active()->get();
foreach ($products as $product) {
    echo $product->category->name;
}
```

### Use Scopes

```php
// Good ✅
Product::active()->featured()->byCategory($id)->get();

// Okay, but less reusable ❌
Product::where('status', 'active')
    ->where('is_featured', true)
    ->where('category_id', $id)
    ->get();
```

### Cache Frequently Used Data

```php
// Cache categories
$categories = Cache::remember('categories', 3600, function () {
    return Category::active()->with('children')->get();
});

// Cache dashboard stats
$stats = Cache::remember('dashboard_stats', 600, function () {
    return (new AnalyticsService())->getDashboardStats();
});
```

## 🔒 Security Checklist

- [ ] Always validate input in controllers
- [ ] Use `escapeHtml()` for user-generated content
- [ ] Implement authorization checks
- [ ] Use parameterized queries (Eloquent handles this)
- [ ] Validate file uploads
- [ ] Implement rate limiting
- [ ] Use HTTPS in production
- [ ] Keep dependencies updated

## 📞 Quick Commands

```bash
# Create migration
php artisan make:migration create_table_name

# Run migrations
php artisan migrate

# Create controller
php artisan make:controller ControllerName

# Create model
php artisan make:model ModelName

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run tests
php artisan test

# Check syntax
php -l filename.php
```

---

**Keep this cheat sheet handy for quick reference!**
