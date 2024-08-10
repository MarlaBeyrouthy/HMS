<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function markAsRead($notificationId)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($notificationId);
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marked as read']);
    }

    public function index()
    {
        $user = Auth::user();
        $unreadNotifications = $user->unreadNotifications;

        return response()->json($unreadNotifications);
    }


}
