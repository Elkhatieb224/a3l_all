<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Negotiation;
use App\Models\Conversation;
use App\Models\Message;
use App\Notifications\NegotiationRespondedNotification;
use App\Notifications\NewNegotiationRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NegotiationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function create($uid)
    {
        $ad = Ad::where('uid', $uid)->where('status', 'active')->firstOrFail();
        $ad->loadMissing('category:id,enable_negotiation');
        
        // Check if user is trying to negotiate on their own ad
        if ($ad->user_id === Auth::id()) {
            return redirect()->route('ads.show', $ad->uid)
                ->withErrors(['error' => __('frontend.negotiations.cannot_negotiate_own_ad')]);
        }

        if (! (bool) ($ad->category?->enable_negotiation ?? true)) {
            return redirect()->route('ads.show', $ad->uid)
                ->withErrors(['error' => __('frontend.negotiations.disabled_for_category')]);
        }

        return view('frontend.negotiations.create', compact('ad'));
    }

    public function store(Request $request, $uid)
    {
        $ad = Ad::where('uid', $uid)->where('status', 'active')->firstOrFail();
        $ad->loadMissing('category:id,enable_negotiation');
        
        // Check if user is trying to negotiate on their own ad
        if ($ad->user_id === Auth::id()) {
            return redirect()->route('ads.show', $ad->uid)
                ->withErrors(['error' => __('frontend.negotiations.cannot_negotiate_own_ad')]);
        }

        if (! (bool) ($ad->category?->enable_negotiation ?? true)) {
            return redirect()->route('ads.show', $ad->uid)
                ->withErrors(['error' => __('frontend.negotiations.disabled_for_category')]);
        }

        $request->validate([
            'offered_price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'message' => 'nullable|string|max:1000',
        ], [
            'offered_price.required' => __('frontend.negotiations.price_required'),
            'offered_price.numeric' => __('frontend.negotiations.price_numeric'),
            'offered_price.min' => __('frontend.negotiations.price_min'),
            'currency.required' => __('frontend.negotiations.currency_required'),
            'message.max' => __('frontend.negotiations.message_max'),
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
        }

        return redirect()->route('negotiations.sent')
            ->with('success', __('frontend.negotiations.request_sent'));
    }

    public function sent()
    {
        $user = Auth::user();
        $negotiations = Negotiation::where('buyer_id', $user->id)
            ->whereHas('ad')
            ->with(['ad', 'seller'])
            ->latest()
            ->paginate(15);

        return view('frontend.negotiations.sent', compact('negotiations'));
    }

    public function received()
    {
        $user = Auth::user();
        $negotiations = Negotiation::where('seller_id', $user->id)
            ->whereHas('ad')
            ->with(['ad', 'buyer'])
            ->latest()
            ->paginate(15);

        return view('frontend.negotiations.received', compact('negotiations'));
    }

    public function accept($id)
    {
        $user = Auth::user();
        $negotiation = Negotiation::where('id', $id)
            ->where('seller_id', $user->id)
            ->where('status', 'pending')
            ->whereHas('ad')
            ->with(['ad', 'buyer', 'seller'])
            ->firstOrFail();

        // Find or create conversation
        $conversation = Conversation::where('ad_id', $negotiation->ad_id)
            ->where(function($query) use ($negotiation) {
                $query->where(function($q) use ($negotiation) {
                    $q->where('sender_id', $negotiation->buyer_id)
                      ->where('receiver_id', $negotiation->seller_id);
                })->orWhere(function($q) use ($negotiation) {
                    $q->where('sender_id', $negotiation->seller_id)
                      ->where('receiver_id', $negotiation->buyer_id);
                });
            })
            ->first();
        
        if (!$conversation) {
            $conversation = Conversation::create([
                'ad_id' => $negotiation->ad_id,
                'sender_id' => $negotiation->buyer_id,
                'receiver_id' => $negotiation->seller_id,
                'last_message_at' => now(),
            ]);
        }

        // Create first message with ad link and negotiation price
        $adLink = route('ads.show', $negotiation->ad->uid);
        $messageText = __('frontend.negotiations.accepted_message', [
            'ad_link' => $adLink,
            'ad_title' => $negotiation->ad->title,
            'offered_price' => format_price($negotiation->offered_price, 2, $negotiation->currency),
        ]);

        if ($negotiation->message) {
            $messageText = $negotiation->message . "\n\n" . $messageText;
        }

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $negotiation->seller_id,
            'message' => $messageText,
        ]);

        // Update negotiation
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

        return redirect()->route('messages.show', $conversation->id)
            ->with('success', __('frontend.negotiations.accepted_success'));
    }

    public function reject(Request $request, $id)
    {
        $user = Auth::user();
        $negotiation = Negotiation::where('id', $id)
            ->where('seller_id', $user->id)
            ->where('status', 'pending')
            ->whereHas('ad')
            ->with(['ad', 'buyer', 'seller'])
            ->firstOrFail();

        $request->validate([
            'rejection_reason' => 'nullable|string|max:500',
        ], [
            'rejection_reason.max' => __('frontend.negotiations.rejection_reason_max'),
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

        return back()->with('success', __('frontend.negotiations.rejected_success'));
    }
}
