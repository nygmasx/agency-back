<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\NotificationPreference;
use App\Models\PortalNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PortalNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $collaborator = $request->collaborator;

        $notifications = PortalNotification::forCollaborator($collaborator)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $unreadCount = PortalNotification::forCollaborator($collaborator)
            ->unread()
            ->count();

        return response()->json([
            'notifications' => $notifications->map(fn ($notification) => $this->formatNotification($notification)),
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead(Request $request, PortalNotification $notification): JsonResponse
    {
        $collaborator = $request->collaborator;

        if ($notification->notifiable_id !== $collaborator->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->markAsRead();

        return response()->json([
            'notification' => $this->formatNotification($notification),
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $collaborator = $request->collaborator;

        PortalNotification::forCollaborator($collaborator)
            ->unread()
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'All notifications marked as read.',
        ]);
    }

    public function preferences(Request $request): JsonResponse
    {
        $collaborator = $request->collaborator;

        $preferences = NotificationPreference::getOrCreateForCollaborator($collaborator);

        return response()->json([
            'preferences' => $this->formatPreferences($preferences),
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $collaborator = $request->collaborator;

        $validated = $request->validate([
            'email_enabled' => ['sometimes', 'boolean'],
            'email_task_assigned' => ['sometimes', 'boolean'],
            'email_task_completed' => ['sometimes', 'boolean'],
            'email_task_comment' => ['sometimes', 'boolean'],
            'email_task_due_soon' => ['sometimes', 'boolean'],
            'email_daily_digest' => ['sometimes', 'boolean'],
            'push_enabled' => ['sometimes', 'boolean'],
        ]);

        $preferences = NotificationPreference::getOrCreateForCollaborator($collaborator);
        $preferences->update($validated);

        return response()->json([
            'preferences' => $this->formatPreferences($preferences),
        ]);
    }

    protected function formatNotification(PortalNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'message' => $notification->message,
            'link' => $notification->link,
            'data' => $notification->data,
            'is_read' => $notification->isRead(),
            'read_at' => $notification->read_at,
            'created_at' => $notification->created_at,
        ];
    }

    protected function formatPreferences(NotificationPreference $preferences): array
    {
        return [
            'email_enabled' => $preferences->email_enabled,
            'email_task_assigned' => $preferences->email_task_assigned,
            'email_task_completed' => $preferences->email_task_completed,
            'email_task_comment' => $preferences->email_task_comment,
            'email_task_due_soon' => $preferences->email_task_due_soon,
            'email_daily_digest' => $preferences->email_daily_digest,
            'push_enabled' => $preferences->push_enabled,
        ];
    }
}
