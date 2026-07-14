<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create($uid)
    {
        $user = Auth::user();
        $ad = Ad::where('uid', $uid)->where('status', 'active')->firstOrFail();
        
        // Check if user is trying to message themselves
        if ($ad->user_id === $user->id) {
            return redirect()->route('ads.show', $ad->uid)
                ->withErrors(['error' => __('frontend.messages.cannot_message_yourself')]);
        }
        
        // Find or create conversation
        $conversation = Conversation::where('ad_id', $ad->id)
            ->where(function($query) use ($user, $ad) {
                $query->where(function($q) use ($user, $ad) {
                    $q->where('sender_id', $user->id)
                      ->where('receiver_id', $ad->user_id);
                })->orWhere(function($q) use ($user, $ad) {
                    $q->where('sender_id', $ad->user_id)
                      ->where('receiver_id', $user->id);
                });
            })
            ->first();
        
        if (!$conversation) {
            $conversation = Conversation::create([
                'ad_id' => $ad->id,
                'sender_id' => $user->id,
                'receiver_id' => $ad->user_id,
                'last_message_at' => now(),
            ]);
        }
        
        return redirect()->route('messages.show', $conversation->id);
    }

    public function createWithSeller(string $slug)
    {
        $user = Auth::user();
        $seller = User::where('slug', $slug)->firstOrFail();

        if ($seller->id === $user->id) {
            return back()->withErrors(['error' => __('frontend.messages.cannot_message_yourself')]);
        }

        if ($seller->isBlockedBy($user->id)) {
            return back()->withErrors(['error' => __('frontend.messages.you_are_blocked')]);
        }

        if ($user->hasBlocked($seller->id)) {
            return back()->withErrors(['error' => 'You have blocked this user']);
        }

        $conversation = Conversation::whereNull('ad_id')
            ->where(function ($query) use ($user, $seller) {
                $query->where(function ($q) use ($user, $seller) {
                    $q->where('sender_id', $user->id)
                      ->where('receiver_id', $seller->id);
                })->orWhere(function ($q) use ($user, $seller) {
                    $q->where('sender_id', $seller->id)
                      ->where('receiver_id', $user->id);
                });
            })
            ->first();

        if (!$conversation) {
            $conversation = Conversation::create([
                'ad_id' => null,
                'sender_id' => $user->id,
                'receiver_id' => $seller->id,
                'last_message_at' => now(),
            ]);
        }

        return redirect()->route('messages.show', $conversation->id);
    }

    public function index()
    {
        $user = Auth::user();
        $conversations = Conversation::where(function($query) use ($user) {
                $query->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
            })
            ->with(['ad', 'sender', 'receiver', 'latestMessage'])
            ->latest('last_message_at')
            ->paginate(20);
        
        return view('frontend.messages.index', compact('conversations'));
    }

    public function show($id, Request $request)
    {
        $user = Auth::user();
        $conversation = Conversation::where('id', $id)
            ->where(function($query) use ($user) {
                $query->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
            })
            ->with(['ad.subcategory.parent', 'sender', 'receiver'])
            ->firstOrFail();
        
        // Mark messages as read
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
        
        $query = Message::where('conversation_id', $conversation->id)
            ->with('sender')
            ->orderBy('created_at', 'asc');
        
        // If AJAX request with last_id, return only new messages
        if ($request->expectsJson() && $request->has('last_id')) {
            $newMessages = $query->where('id', '>', $request->last_id)->get();
            
            return response()->json([
                'success' => true,
                'messages' => $newMessages->map(function($message) {
                    return [
                        'id' => $message->id,
                        'sender_id' => $message->sender_id,
                        'html' => view('frontend.messages.partials.message', ['message' => $message])->render(),
                    ];
                }),
            ]);
        }
        
        $messages = $query->get();
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'messages' => $messages,
            ]);
        }
        
        return view('frontend.messages.show', compact('conversation', 'messages'));
    }

    public function store(Request $request, $id)
    {
        $user = Auth::user();
        $conversation = Conversation::where('id', $id)
            ->where(function($query) use ($user) {
                $query->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
            })
            ->with('ad')
            ->firstOrFail();
        
        // Check if user is blocked
        $otherUserId = $conversation->sender_id === $user->id ? $conversation->receiver_id : $conversation->sender_id;
        if ($user->isBlockedBy($otherUserId)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('frontend.messages.you_are_blocked'),
                ], 403);
            }
            return back()->withErrors(['error' => __('frontend.messages.you_are_blocked')]);
        }
        
        $request->validate([
            'message' => 'nullable|string|max:5000',
            'attachments.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp,pdf,doc,docx,txt|max:10240',
        ], [
            'message.max' => __('frontend.messages.message_max'),
            'attachments.*.file' => __('frontend.messages.invalid_file'),
            'attachments.*.mimes' => __('frontend.messages.invalid_file_type'),
            'attachments.*.max' => __('frontend.messages.file_too_large'),
        ]);
        
        // At least message or attachment must be provided
        if (empty($request->message) && !$request->hasFile('attachments')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('frontend.messages.message_or_attachment_required'),
                ], 422);
            }
            return back()->withErrors(['message' => __('frontend.messages.message_or_attachment_required')]);
        }
        
        // Check if this is the first message in conversation
        $isFirstMessage = $conversation->messages()->count() === 0;
        
        $messageText = $request->message ?? '';
        
        // Handle file uploads (تحويل الصور لـ WebP)
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = store_image_as_webp($file, 'messages/attachments');
                $attachments[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => str_ends_with(strtolower($path), '.webp') ? 'image/webp' : $file->getMimeType(),
                ];
            }
        }
        
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => $messageText,
            'attachments' => !empty($attachments) ? $attachments : null,
        ]);
        
        // Update conversation last message time
        $conversation->update(['last_message_at' => now()]);

        // إرسال إشعار فوري (Push + Database) للمستلم - الضغط يوجّه للمحادثة
        $receiver = $conversation->sender_id === $user->id ? $conversation->receiver : $conversation->sender;
        if ($receiver) {
            $receiver->notify(new NewMessageNotification($message, $user, $conversation));
        }
        
        // Load sender relationship for response
        $message->load('sender');
        
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'html' => view('frontend.messages.partials.message', ['message' => $message])->render(),
            ]);
        }
        
        return redirect()->route('messages.show', $conversation->id)
            ->with('success', __('frontend.messages.sent'));
    }
}
