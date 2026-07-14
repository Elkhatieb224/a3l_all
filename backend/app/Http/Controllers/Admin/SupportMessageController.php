<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportMessageReply;
use App\Notifications\SupportActionNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupportMessageController extends Controller
{
    public function index()
    {
        $messages = SupportMessage::with(['user', 'admin'])
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => SupportMessage::count(),
            'pending' => SupportMessage::pending()->count(),
            'resolved' => SupportMessage::resolved()->count(),
        ];

        return view('admin.support.index', compact('messages', 'stats'));
    }

    public function show($id)
    {
        $message = SupportMessage::with(['user', 'admin', 'replies.user', 'replies.admin'])->findOrFail($id);
        return view('admin.support.show', compact('message'));
    }

    public function respond(Request $request, $id)
    {
        $request->validate([
            'response' => 'required|string',
        ]);

        $message = SupportMessage::findOrFail($id);
        
        // Create reply
        SupportMessageReply::create([
            'support_message_id' => $message->id,
            'sender_type' => 'admin',
            'admin_id' => Auth::guard('admin')->id(),
            'message' => $request->response,
        ]);

        // Update original admin_response if it doesn't exist (for backward compatibility)
        if (!$message->admin_response) {
            $message->markAsResponded(Auth::guard('admin')->id(), $request->response);
        } else {
            // Update status if it was pending
            if ($message->status === 'pending') {
                $message->update(['status' => 'in_progress']);
            }
        }

        // إرسال إشعار فوري (Push + بريد إلكتروني) للمستخدم عند الرد على رسالة الدعم
        if ($message->user) {
            $message->user->notify(new SupportActionNotification($message, 'response'));
        }

        return back()->with('success', __('admin.support.response_sent'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,in_progress,resolved,closed',
        ]);

        $message = SupportMessage::findOrFail($id);
        $message->update(['status' => $request->status]);

        // إرسال إشعار فوري (Push + بريد إلكتروني) للمستخدم عند تحديث حالة رسالة الدعم
        if ($message->user) {
            $message->user->notify(new SupportActionNotification($message, 'status'));
        }

        return back()->with('success', __('admin.support.status_updated'));
    }
}
