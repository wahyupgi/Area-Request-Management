<?php

namespace App\Features\Notification\Services;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;

class NotificationService
{
    public function getForUser(int $userId, int $limit = 50): Collection
    {
        return Notification::forUser($userId)
            ->with(['memo:id,title,code'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function markAsRead(Notification $notification, int $userId): bool
    {
        if ($notification->user_id !== $userId) {
            abort(403);
        }

        return (bool) $notification->update(['is_read' => true]);
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::forUser($userId)
            ->unread()
            ->update(['is_read' => true]);
    }

    public function unreadCount(int $userId): int
    {
        return Notification::forUser($userId)
            ->unread()
            ->count();
    }
}
