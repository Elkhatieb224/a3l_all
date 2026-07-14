<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $perPage = min((int) $request->get('per_page', 20), 50);
        $notifications = $user->notifications()->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications->map(fn ($n) => [
                'id' => $n->id,
                'type' => $n->type,
                'data' => $n->data,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function markAsRead(string $id)
    {
        $user = Auth::user();
        $notification = $user->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['success' => true]);
    }

    public function markAllAsRead(Request $request)
    {
        $user = Auth::user();
        $user->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }

    public function unreadCount()
    {
        $user = Auth::user();
        $count = $user->unreadNotifications->count();

        return response()->json(['success' => true, 'count' => $count]);
    }
}
