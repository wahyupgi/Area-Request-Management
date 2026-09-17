<?php

namespace App\Features\Notification\Controllers;

use App\Features\Notification\Services\NotificationService;
use App\Http\Controllers\Controller;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function __construct(protected NotificationService $notificationService) {}

    /**
     * Get all notifications for the authenticated user.
     */
    public function index()
    {
        $notifications = $this->notificationService->getForUser(auth()->id());

        return response()->json($notifications);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Notification $notification)
    {
        $this->notificationService->markAsRead($notification, auth()->id());

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        $this->notificationService->markAllAsRead(auth()->id());

        return response()->json(['success' => true]);
    }

    /**
     * Get unread notification count.
     */
    public function unreadCount()
    {
        $count = $this->notificationService->unreadCount(auth()->id());

        return response()->json(['count' => $count]);
    }
}
