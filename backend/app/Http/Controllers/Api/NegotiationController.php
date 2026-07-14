<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Negotiation;
use App\Notifications\NegotiationRespondedNotification;
use App\Notifications\NewNegotiationRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NegotiationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function create(string $uid)
    {
        $ad = Ad::where('uid', $uid)->where('status', 'active')->firstOrFail();
        $ad->loadMissing('category:id,enable_negotiation');

        if ($ad->user_id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.negotiations.cannot_negotiate_own_ad'),
            ], 403);
        }

        if (! (bool) ($ad->category?->enable_negotiation ?? true)) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.negotiations.disabled_for_category'),
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'ad' => [
                    'uid' => $ad->uid,
                    'title' => $ad->title,
                    'price' => $ad->price,
                    'currency' => $ad->currency,
                    'formatted_price' => $ad->display_price,
                ],
            ],
        ]);
    }

    public function store(Request $request, string $uid)
    {
        $ad = Ad::where('uid', $uid)->where('status', 'active')->firstOrFail();
        $ad->loadMissing('category:id,enable_negotiation');

        if ($ad->user_id === Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.negotiations.cannot_negotiate_own_ad'),
            ], 403);
        }

        if (! (bool) ($ad->category?->enable_negotiation ?? true)) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.negotiations.disabled_for_category'),
            ], 403);
        }

        $request->validate([
            'offered_price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'message' => 'nullable|string|max:1000',
        ]);

        $negotiation = Negotiation::create([
            'ad_id' => $ad->id,
            'buyer_id' => Auth::id(),
            'seller_id' => $ad->user_id,
            'offered_price' => $request->offered_price,
            'currency' => $request->currency,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        // Notify seller: new negotiation request (database + FCM + mail)
        try {
            $negotiation->loadMissing(['ad:id,uid,title,images,price,currency', 'buyer:id,name', 'seller:id,name,email']);
            if ($negotiation->seller) {
                $negotiation->seller->notify(new NewNegotiationRequestNotification($negotiation));
            }
        } catch (\Throwable $e) {
            // Never fail the API response due to notification problems.
        }

        return response()->json([
            'success' => true,
            'message' => __('frontend.negotiations.request_sent'),
            'data' => [
                'id' => $negotiation->id,
                'status' => $negotiation->status,
            ],
        ], 201);
    }

    public function sent(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 50);
        $negotiations = Negotiation::where('buyer_id', Auth::id())
            ->whereHas('ad')
            ->with(['ad:id,uid,title,images', 'seller:id,name,slug,avatar'])
            ->latest()
            ->paginate($perPage);

        $items = collect($negotiations->items())
            ->map(fn (Negotiation $n) => $this->negotiationPayload($n, 'buyer'))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $negotiations->currentPage(),
                'last_page' => $negotiations->lastPage(),
                'per_page' => $negotiations->perPage(),
                'total' => $negotiations->total(),
            ],
        ]);
    }

    public function received(Request $request)
    {
        $perPage = min((int) $request->get('per_page', 15), 50);
        $negotiations = Negotiation::where('seller_id', Auth::id())
            ->whereHas('ad')
            ->with(['ad:id,uid,title,images', 'buyer:id,name,slug,avatar'])
            ->latest()
            ->paginate($perPage);

        $items = collect($negotiations->items())
            ->map(fn (Negotiation $n) => $this->negotiationPayload($n, 'seller'))
            ->values();

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $negotiations->currentPage(),
                'last_page' => $negotiations->lastPage(),
                'per_page' => $negotiations->perPage(),
                'total' => $negotiations->total(),
            ],
        ]);
    }

    public function accept(string $id)
    {
        $negotiation = Negotiation::where('id', $id)
            ->where('seller_id', Auth::id())
            ->where('status', 'pending')
            ->whereHas('ad')
            ->with(['ad', 'buyer', 'seller'])
            ->firstOrFail();

        $conversation = Conversation::where('ad_id', $negotiation->ad_id)
            ->where(function ($query) use ($negotiation) {
                $query->where(function ($q) use ($negotiation) {
                    $q->where('sender_id', $negotiation->buyer_id)
                        ->where('receiver_id', $negotiation->seller_id);
                })->orWhere(function ($q) use ($negotiation) {
                    $q->where('sender_id', $negotiation->seller_id)
                        ->where('receiver_id', $negotiation->buyer_id);
                });
            })
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'ad_id' => $negotiation->ad_id,
                'sender_id' => $negotiation->buyer_id,
                'receiver_id' => $negotiation->seller_id,
                'last_message_at' => now(),
            ]);
        }

        $adLink = url('/ads/'.$negotiation->ad->uid);
        $messageText = __('frontend.negotiations.accepted_message', [
            'ad_link' => $adLink,
            'ad_title' => $negotiation->ad->title,
            'offered_price' => format_price($negotiation->offered_price, 2, $negotiation->currency),
        ]);

        if ($negotiation->message) {
            $messageText = $negotiation->message."\n\n".$messageText;
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $negotiation->seller_id,
            'message' => $messageText,
        ]);

        $negotiation->update([
            'status' => 'accepted',
            'conversation_id' => $conversation->id,
            'responded_at' => now(),
        ]);

        // Notify buyer: accepted (database + FCM + mail)
        try {
            $negotiation->loadMissing(['ad:id,uid,title', 'buyer:id,name', 'seller:id,name']);
            if ($negotiation->buyer) {
                $negotiation->buyer->notify(new NegotiationRespondedNotification($negotiation));
            }
        } catch (\Throwable $e) {
        }

        return response()->json([
            'success' => true,
            'message' => __('frontend.negotiations.accepted_success'),
            'data' => [
                'conversation_id' => $conversation->id,
                'negotiation_id' => $negotiation->id,
            ],
        ]);
    }

    public function reject(Request $request, string $id)
    {
        $negotiation = Negotiation::where('id', $id)
            ->where('seller_id', Auth::id())
            ->where('status', 'pending')
            ->whereHas('ad')
            ->with(['ad', 'buyer', 'seller'])
            ->firstOrFail();

        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ]);

        $negotiation->update([
            'status' => 'rejected',
            'rejection_reason' => $request->rejection_reason,
            'responded_at' => now(),
        ]);

        // Notify buyer: rejected (database + FCM + mail)
        try {
            $negotiation->loadMissing(['ad:id,uid,title', 'buyer:id,name', 'seller:id,name']);
            if ($negotiation->buyer) {
                $negotiation->buyer->notify(new NegotiationRespondedNotification($negotiation));
            }
        } catch (\Throwable $e) {
        }

        return response()->json([
            'success' => true,
            'message' => __('frontend.negotiations.rejected_success'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function negotiationPayload(Negotiation $n, string $role): array
    {
        $adImages = collect($n->ad?->images ?? [])
            ->map(fn ($img) => AdResource::imageToFullUrl($img, request()))
            ->filter()
            ->values()
            ->toArray();
        $firstImage = $adImages[0] ?? null;

        $base = [
            'id' => $n->id,
            'status' => $n->status,
            'offered_price' => (float) $n->offered_price,
            'currency' => $n->currency,
            'message' => $n->message,
            'rejection_reason' => $n->rejection_reason,
            'conversation_id' => $n->conversation_id,
            'responded_at' => $n->responded_at?->toIso8601String(),
            'created_at' => $n->created_at->toIso8601String(),
            'ad' => $n->ad ? [
                'uid' => $n->ad->uid,
                'title' => $n->ad->title,
                'images' => $adImages,
                'first_image' => $firstImage,
                'image' => $firstImage,
            ] : null,
        ];

        if ($role === 'buyer' && $n->relationLoaded('seller') && $n->seller) {
            $base['seller'] = [
                'id' => $n->seller->id,
                'name' => $n->seller->name,
                'slug' => $n->seller->slug,
                'avatar' => $n->seller->avatar ? asset('storage/'.$n->seller->avatar) : null,
            ];
        }
        if ($role === 'seller' && $n->relationLoaded('buyer') && $n->buyer) {
            $base['buyer'] = [
                'id' => $n->buyer->id,
                'name' => $n->buyer->name,
                'slug' => $n->buyer->slug,
                'avatar' => $n->buyer->avatar ? asset('storage/'.$n->buyer->avatar) : null,
            ];
        }

        return $base;
    }
}
