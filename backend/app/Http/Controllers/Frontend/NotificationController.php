<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->paginate(20);
        
        return view('frontend.notifications.index', compact('notifications'));
    }

    public function markAsRead($id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('profile.notifications.index')->with('success', __('frontend.notifications.mark_as_read'));
    }

    public function markAllAsRead()
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('profile.notifications.index')->with('success', __('frontend.notifications.mark_all_read'));
    }

    public function unreadCount()
    {
        $user = Auth::user();
        $count = $user->unreadNotifications->count();
        
        return response()->json(['count' => $count]);
    }

    public function latest()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->latest()->take(10)->get();
        
        return response()->json([
            'notifications' => $notifications->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'data' => $notification->data,
                    'read_at' => $notification->read_at,
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            }),
            'unread_count' => $user->unreadNotifications->count(),
        ]);
    }
}
