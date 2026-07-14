<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\MessageResource;
use App\Models\Ad;
use App\Models\Conversation;
use App\Models\Setting;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    private const CONVERSATION_AD_COLUMNS = [
        'id',
        'uid',
        'title',
        'price',
        'currency',
        'custom_fields',
        'images',
        'location_state',
        'location_city',
        'location_district',
        'views_count',
        'published_at',
        'user_id',
        'category_id',
        'subcategory_id',
    ];

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $blockedLookup = array_flip($user->blockedUsers()->pluck('blocked_user_id')->all());
        $request->attributes->set('blocked_user_ids_lookup', $blockedLookup);

        $conversations = Conversation::where(function ($query) use ($user) {
            $query->where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id);
        })
            ->withCount([
                'messages as api_unread_messages_count' => function ($q) use ($user) {
                    $q->where('sender_id', '!=', $user->id)->where('is_read', false);
                },
            ])
            ->with([
                'ad' => fn ($q) => $q->select(self::CONVERSATION_AD_COLUMNS)
                    ->with([
                        'category:id,name_ar,name_en,name_tr',
                        'subcategory:id,name_ar,name_en,name_tr',
                    ]),
                'sender' => fn ($q) => $q->select('id', 'name', 'slug', 'avatar', 'is_verified'),
                'receiver' => fn ($q) => $q->select('id', 'name', 'slug', 'avatar', 'is_verified'),
                'latestMessage',
            ])
            ->latest('last_message_at')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => ConversationResource::collection($conversations),
            'meta' => [
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
            ]
        ]);
    }

    public function show(Request $request, $id)
    {
        $user = Auth::user();

        $blockedLookup = array_flip($user->blockedUsers()->pluck('blocked_user_id')->all());
        $request->attributes->set('blocked_user_ids_lookup', $blockedLookup);

        $conversation = Conversation::where('id', $id)
            ->where(function ($query) use ($user) {
                $query->where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            })
            ->withCount([
                'messages as api_unread_messages_count' => function ($q) use ($user) {
                    $q->where('sender_id', '!=', $user->id)->where('is_read', false);
                },
            ])
            ->with([
                'ad' => fn ($q) => $q->select(self::CONVERSATION_AD_COLUMNS)
                    ->withCount(['conversations', 'favorites'])
                    ->with([
                        'category:id,name_ar,name_en,name_tr',
                        'subcategory:id,name_ar,name_en,name_tr',
                    ]),
                'sender' => fn ($q) => $q->select('id', 'name', 'slug', 'avatar', 'is_verified'),
                'receiver' => fn ($q) => $q->select('id', 'name', 'slug', 'avatar', 'is_verified'),
            ])
            ->firstOrFail();

        // Mark messages as read
        Message::where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        $conversation->setAttribute('api_unread_messages_count', 0);

        $messages = Message::where('conversation_id', $conversation->id)
            ->with(['sender' => fn ($q) => $q->select('id', 'name', 'avatar', 'is_verified')])
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        $locale = app()->getLocale();
        $messagingRules = Setting::get('messaging_rules_' . $locale, '');

        return response()->json([
            'success' => true,
            'data' => [
                'conversation' => new ConversationResource($conversation),
                'messages' => MessageResource::collection($messages),
                'messaging_rules' => $messagingRules,
                'messaging_rules_title' => __('frontend.messages.important_alert'),
            ],
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ]
        ]);
    }

    public function create($uid)
    {
        $user = Auth::user();
        $ad = Ad::where('uid', $uid)->where('status', 'active')->firstOrFail();

        // Check if user is trying to message themselves
        if ($ad->user_id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot message yourself'
            ], 403);
        }

        // Check if the ad owner blocked the current user
        if ($ad->user->isBlockedBy($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are blocked by this user'
            ], 403);
        }

        // Check if the current user has blocked the ad owner (cannot open chat)
        if ($user->hasBlocked($ad->user_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You have blocked this user'
            ], 403);
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

        return response()->json([
            'success' => true,
            'data' => new ConversationResource($conversation->load([
                'ad' => fn ($q) => $q->select(self::CONVERSATION_AD_COLUMNS)
                    ->with([
                        'category:id,name_ar,name_en,name_tr',
                        'subcategory:id,name_ar,name_en,name_tr',
                    ]),
                'sender' => fn ($q) => $q->select('id', 'name', 'slug', 'avatar', 'is_verified'),
                'receiver' => fn ($q) => $q->select('id', 'name', 'slug', 'avatar', 'is_verified'),
            ])),
        ]);
    }

    public function createWithSeller(string $slug)
    {
        $user = Auth::user();
        $seller = User::where('slug', $slug)->firstOrFail();

        if ($seller->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot message yourself'
            ], 403);
        }

        if ($seller->isBlockedBy($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You are blocked by this user'
            ], 403);
        }

        if ($user->hasBlocked($seller->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You have blocked this user'
            ], 403);
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

        return response()->json([
            'success' => true,
            'data' => new ConversationResource($conversation->load([
                'ad' => fn ($q) => $q->select(self::CONVERSATION_AD_COLUMNS)
                    ->with([
                        'category:id,name_ar,name_en,name_tr',
                        'subcategory:id,name_ar,name_en,name_tr',
                    ]),
                'sender' => fn ($q) => $q->select('id', 'name', 'slug', 'avatar', 'is_verified'),
                'receiver' => fn ($q) => $q->select('id', 'name', 'slug', 'avatar', 'is_verified'),
            ])),
        ]);
    }

    public function store(Request $request, $id)
    {
        $user = Auth::user();

        $conversation = Conversation::where('id', $id)
            ->where(function($query) use ($user) {
                $query->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
            })
            ->firstOrFail();

        $otherUserId = $conversation->sender_id === $user->id ? $conversation->receiver_id : $conversation->sender_id;
        if ($user->isBlockedBy($otherUserId)) {
            return response()->json([
                'success' => false,
                'message' => 'You are blocked by this user'
            ], 403);
        }
        if ($user->hasBlocked($otherUserId)) {
            return response()->json([
                'success' => false,
                'message' => 'You have blocked this user'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'message' => 'required_without:attachments|string|max:5000',
            'attachments.*' => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf,doc,docx|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle attachments (تحويل الصور لـ WebP)
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = store_image_as_webp($file, 'messages/attachments');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'type' => str_ends_with(strtolower($path), '.webp') ? 'image/webp' : $file->getMimeType(),
                    'size' => $file->getSize(),
                ];
            }
        }

        // If first message and no message text, add ad link
        $messageText = $request->message ?? '';
        if (
            empty($messageText)
            && $conversation->messages()->count() === 0
            && $conversation->ad !== null
        ) {
            $adLink = route('ads.show', $conversation->ad->uid);
            $messageText = __('frontend.messages.ad_link_message', ['link' => $adLink]);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'message' => $messageText,
            'attachments' => $attachments,
            'is_read' => false,
        ]);

        // Update conversation last message time
        $conversation->update(['last_message_at' => now()]);

        // إرسال إشعار فوري (Push + Database) للمستلم - الضغط يوجّه للمحادثة
        $receiver = $conversation->sender_id === $user->id ? $conversation->receiver : $conversation->sender;
        if ($receiver) {
            $receiver->notify(new NewMessageNotification($message, $user, $conversation));
        }

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully',
            'data' => new MessageResource($message->load([
                'sender' => fn ($q) => $q->select('id', 'name', 'avatar', 'is_verified'),
            ])),
        ], 201);
    }

    public function markAsRead($id)
    {
        $user = Auth::user();

        $message = Message::where('id', $id)
            ->whereHas('conversation', function($query) use ($user) {
                $query->where(function($q) use ($user) {
                    $q->where('sender_id', $user->id)
                      ->orWhere('receiver_id', $user->id);
                });
            })
            ->firstOrFail();

        $message->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read'
        ]);
    }
}
