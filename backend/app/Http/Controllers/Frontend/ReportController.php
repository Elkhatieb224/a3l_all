<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\Ad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $reports = Report::where('user_id', $user->id)
            ->with(['ad', 'reportedUser', 'reviewer', 'conversation'])
            ->latest()
            ->paginate(15);

        return view('frontend.profile.reports.index', compact('reports'));
    }

    public function create(Request $request)
    {
        $adId = $request->get('ad_id');
        $userId = $request->get('user_id');
        $conversationId = $request->get('conversation_id');
        
        $ad = $adId ? Ad::find($adId) : null;
        $reportedUser = $userId ? User::find($userId) : null;
        $conversation = $conversationId ? \App\Models\Conversation::with(['ad', 'sender', 'receiver'])->find($conversationId) : null;
        
        // If reporting conversation, get last 10 messages
        $conversationMessages = null;
        if ($conversation) {
            $conversationMessages = \App\Models\Message::where('conversation_id', $conversation->id)
                ->with('sender')
                ->latest()
                ->take(10)
                ->get()
                ->reverse()
                ->values();
        }

        return view('frontend.profile.reports.create', compact('ad', 'reportedUser', 'conversation', 'conversationMessages'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'type' => 'required|in:spam,fraud,inappropriate,duplicate,other',
            'reason' => 'required|string|max:1000',
            'ad_id' => 'nullable|exists:ads,id',
            'reported_user_id' => 'nullable|exists:users,id',
            'conversation_id' => 'nullable|exists:conversations,id',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ], [
            'type.required' => __('frontend.reports.type_required'),
            'type.in' => __('frontend.reports.type_invalid'),
            'reason.required' => __('frontend.reports.reason_required'),
            'reason.max' => __('frontend.reports.reason_max'),
            'ad_id.exists' => __('frontend.reports.ad_not_found'),
            'reported_user_id.exists' => __('frontend.reports.user_not_found'),
            'conversation_id.exists' => __('frontend.reports.conversation_not_found'),
            'images.*.image' => __('frontend.reports.images_must_be_image'),
            'images.*.max' => __('frontend.reports.images_max_size'),
        ]);

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = store_image_as_webp($image, 'reports');
                if ($path) {
                    $imagePaths[] = $path;
                }
            }
        }

        // If reporting conversation, get last 10 messages
        $conversationMessages = null;
        if ($request->conversation_id) {
            $conversationMessages = \App\Models\Message::where('conversation_id', $request->conversation_id)
                ->with('sender')
                ->latest()
                ->take(10)
                ->get()
                ->map(function($message) {
                    return [
                        'id' => $message->id,
                        'sender_id' => $message->sender_id,
                        'sender_name' => $message->sender->name,
                        'message' => $message->message,
                        'attachments' => $message->attachments,
                        'created_at' => $message->created_at->toDateTimeString(),
                    ];
                })
                ->reverse()
                ->values()
                ->toArray();
        }

        Report::create([
            'user_id' => $user->id,
            'ad_id' => $request->ad_id,
            'reported_user_id' => $request->reported_user_id,
            'conversation_id' => $request->conversation_id,
            'conversation_messages' => $conversationMessages,
            'type' => $request->type,
            'reason' => $request->reason,
            'images' => $imagePaths,
            'status' => 'pending',
        ]);

        return redirect()->route('profile.reports.index')
            ->with('success', __('frontend.reports.report_submitted'));
    }

    public function show($id)
    {
        $user = Auth::user();
        $report = Report::where('user_id', $user->id)
            ->with(['ad', 'reportedUser', 'reviewer', 'conversation'])
            ->findOrFail($id);

        return view('frontend.profile.reports.show', compact('report'));
    }
}
