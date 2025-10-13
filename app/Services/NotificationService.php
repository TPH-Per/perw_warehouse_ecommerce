<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\PurchaseOrder;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    /**
     * Get user notifications with pagination
     */
    public function getUserNotifications(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Notification::where('user_id', $userId)
                          ->orderBy('created_at', 'desc')
                          ->paginate($perPage);
    }

    /**
     * Get unread notifications
     */
    public function getUnreadNotifications(int $userId, int $limit = 10)
    {
        return Notification::where('user_id', $userId)
                          ->unread()
                          ->latest()
                          ->limit($limit)
                          ->get();
    }

    /**
     * Get unread count
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::getUnreadCount($userId);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
                                   ->where('user_id', $userId)
                                   ->first();

        if ($notification) {
            return $notification->markAsRead();
        }

        return false;
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::markAllAsRead($userId);
    }

    /**
     * Delete notification
     */
    public function deleteNotification(int $notificationId, int $userId): bool
    {
        return Notification::where('id', $notificationId)
                          ->where('user_id', $userId)
                          ->delete() > 0;
    }

    /**
     * Create order notification
     */
    public function notifyOrderCreated(PurchaseOrder $order): Notification
    {
        return Notification::createNotification(
            $order->user_id,
            Notification::TYPE_ORDER_CREATED,
            'Order Created',
            "Your order #{$order->order_code} has been created successfully.",
            ['order_id' => $order->id, 'order_code' => $order->order_code],
            "/orders/{$order->id}"
        );
    }

    /**
     * Create order updated notification
     */
    public function notifyOrderUpdated(PurchaseOrder $order, string $oldStatus): Notification
    {
        $message = "Your order #{$order->order_code} status has been updated from {$oldStatus} to {$order->status}.";

        return Notification::createNotification(
            $order->user_id,
            Notification::TYPE_ORDER_UPDATED,
            'Order Updated',
            $message,
            [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'old_status' => $oldStatus,
                'new_status' => $order->status,
            ],
            "/orders/{$order->id}"
        );
    }

    /**
     * Create order shipped notification
     */
    public function notifyOrderShipped(PurchaseOrder $order, ?string $trackingCode = null): Notification
    {
        $message = "Your order #{$order->order_code} has been shipped.";
        
        if ($trackingCode) {
            $message .= " Tracking code: {$trackingCode}";
        }

        return Notification::createNotification(
            $order->user_id,
            Notification::TYPE_ORDER_SHIPPED,
            'Order Shipped',
            $message,
            [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'tracking_code' => $trackingCode,
            ],
            "/orders/{$order->id}"
        );
    }

    /**
     * Create order delivered notification
     */
    public function notifyOrderDelivered(PurchaseOrder $order): Notification
    {
        return Notification::createNotification(
            $order->user_id,
            Notification::TYPE_ORDER_DELIVERED,
            'Order Delivered',
            "Your order #{$order->order_code} has been delivered successfully.",
            ['order_id' => $order->id, 'order_code' => $order->order_code],
            "/orders/{$order->id}"
        );
    }

    /**
     * Create payment received notification
     */
    public function notifyPaymentReceived(PurchaseOrder $order, float $amount): Notification
    {
        return Notification::createNotification(
            $order->user_id,
            Notification::TYPE_PAYMENT_RECEIVED,
            'Payment Received',
            "Payment of " . number_format($amount, 0, ',', '.') . " VND has been received for order #{$order->order_code}.",
            [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'amount' => $amount,
            ],
            "/orders/{$order->id}"
        );
    }

    /**
     * Create low stock notification for admins
     */
    public function notifyLowStock(int $productId, string $productName, int $currentStock): void
    {
        $admins = User::where('role_id', 1)->get();

        foreach ($admins as $admin) {
            Notification::createNotification(
                $admin->id,
                Notification::TYPE_LOW_STOCK,
                'Low Stock Alert',
                "Product '{$productName}' is running low on stock. Current stock: {$currentStock}",
                [
                    'product_id' => $productId,
                    'product_name' => $productName,
                    'current_stock' => $currentStock,
                ],
                "/admin/products/{$productId}"
            );
        }
    }

    /**
     * Create back in stock notification
     */
    public function notifyBackInStock(int $userId, int $productId, string $productName): Notification
    {
        return Notification::createNotification(
            $userId,
            Notification::TYPE_PRODUCT_BACK_IN_STOCK,
            'Product Back in Stock',
            "Good news! '{$productName}' is back in stock.",
            [
                'product_id' => $productId,
                'product_name' => $productName,
            ],
            "/products/{$productId}"
        );
    }

    /**
     * Create review posted notification
     */
    public function notifyReviewPosted(int $userId, int $productId, string $productName): Notification
    {
        return Notification::createNotification(
            $userId,
            Notification::TYPE_REVIEW_POSTED,
            'Review Posted',
            "Your review for '{$productName}' has been posted successfully.",
            [
                'product_id' => $productId,
                'product_name' => $productName,
            ],
            "/products/{$productId}"
        );
    }

    /**
     * Create system notification
     */
    public function notifySystem(int $userId, string $title, string $message, ?array $data = null): Notification
    {
        return Notification::createNotification(
            $userId,
            Notification::TYPE_SYSTEM,
            $title,
            $message,
            $data
        );
    }

    /**
     * Broadcast notification to all users
     */
    public function broadcastToAllUsers(string $title, string $message, ?array $data = null): int
    {
        $users = User::all();
        $count = 0;

        foreach ($users as $user) {
            Notification::createNotification(
                $user->id,
                Notification::TYPE_SYSTEM,
                $title,
                $message,
                $data
            );
            $count++;
        }

        return $count;
    }

    /**
     * Broadcast notification to specific role
     */
    public function broadcastToRole(int $roleId, string $title, string $message, ?array $data = null): int
    {
        $users = User::where('role_id', $roleId)->get();
        $count = 0;

        foreach ($users as $user) {
            Notification::createNotification(
                $user->id,
                Notification::TYPE_SYSTEM,
                $title,
                $message,
                $data
            );
            $count++;
        }

        return $count;
    }

    /**
     * Clean up old notifications
     */
    public function cleanupOldNotifications(int $days = 90): int
    {
        return Notification::deleteOldNotifications($days);
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats(int $userId): array
    {
        $total = Notification::where('user_id', $userId)->count();
        $unread = Notification::where('user_id', $userId)->unread()->count();
        $read = $total - $unread;

        $byType = Notification::where('user_id', $userId)
                             ->selectRaw('type, COUNT(*) as count')
                             ->groupBy('type')
                             ->get()
                             ->pluck('count', 'type')
                             ->toArray();

        return [
            'total' => $total,
            'unread' => $unread,
            'read' => $read,
            'by_type' => $byType,
        ];
    }
}
