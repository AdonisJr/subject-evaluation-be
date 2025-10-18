<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Get all notifications for the currently authenticated user
     */
    public function index()
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($user->notifications);
    }

    /**
     * Get only unread notifications
     */
    public function unread()
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return response()->json($user->unreadNotifications);
    }

    /**
     * Mark one notification as read
     */
    public function markAsRead($id, Request $request)
    {
        $user = auth('sanctum')->user();

        if (!$user || !method_exists($user, 'notifications')) {
            return response()->json([
                'message' => 'Unauthorized or invalid user model',
            ], 401);
        }

        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }


    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $user->unreadNotifications->markAsRead();

        return response()->json(['message' => 'All notifications marked as read']);
    }
}
