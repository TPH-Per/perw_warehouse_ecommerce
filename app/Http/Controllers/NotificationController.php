<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controller;

class NotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth:sanctum');
        $this->notificationService = $notificationService;
    }

    /**
     * Get user's notifications with pagination
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $limit = $request->input('limit', null);
        
        if ($limit) {
            $notifications = $this->notificationService->getUnreadNotifications(Auth::id(), $limit);
            return response()->json([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => $this->notificationService->getUnreadCount(Auth::id())
            ]);
        }
        
        $notifications = $this->notificationService->getUserNotifications(Auth::id(), $perPage);
        
        return response()->json([
            'success' => true,
            'notifications' => $notifications->items(),
            'pagination' => [
                'total' => $notifications->total(),
                'per_page' => $notifications->perPage(),
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage()
            ]
        ]);
    }

    /**
     * Get unread notifications
     */
    public function unread()
    {
        $limit = request('limit', 10);
        $notifications = $this->notificationService->getUnreadNotifications(Auth::id(), $limit);
        
        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $this->notificationService->getUnreadCount(Auth::id())
        ]);
    }

    /**
     * Get unread count
     */
    public function unreadCount()
    {
        $count = $this->notificationService->getUnreadCount(Auth::id());
        
        return response()->json([
            'success' => true,
            'unread_count' => $count
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $result = $this->notificationService->markAsRead($id, Auth::id());
        
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Notification marked as read' : 'Notification not found'
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $count = $this->notificationService->markAllAsRead(Auth::id());
        
        return response()->json([
            'success' => true,
            'message' => "Marked {$count} notifications as read",
            'count' => $count
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy($id)
    {
        $result = $this->notificationService->deleteNotification($id, Auth::id());
        
        return response()->json([
            'success' => $result,
            'message' => $result ? 'Notification deleted' : 'Notification not found'
        ]);
    }

    /**
     * Get notification statistics
     */
    public function stats()
    {
        $stats = $this->notificationService->getNotificationStats(Auth::id());
        
        return response()->json([
            'success' => true,
            'stats' => $stats
        ]);
    }
}
