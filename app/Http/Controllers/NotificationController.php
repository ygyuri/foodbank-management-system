<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // 🟢 Fetch All Notifications for Admin
    public function getAllNotificationsForAdmin()
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {  // Use string-based role check
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notifications = Notification::with('user') // Assuming a relationship with User
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }


    // 🟢 Fetch Notifications for Authenticated User
    public function getUserNotifications()
    {
        $user = Auth::user();

        $notifications = Notification::where('notifiable_id', $user->id)
            ->latest()
            ->paginate(20);

        return response()->json($notifications);
    }

    // 🟢 Mark Notification as Read
    public function markAsRead($id)
    {
        $user = Auth::user();
        $notification = Notification::where('notifiable_id', $user->id)
            ->find($id);

        if (!$notification) {
            return response()->json(['message' => 'Notification not found'], 404);
        }

        $notification->update(['read_at' => now()]);

        return response()->json(['message' => 'Notification marked as read']);
    }
}
