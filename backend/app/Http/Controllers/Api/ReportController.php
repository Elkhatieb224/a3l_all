<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $user = Auth::user();
        $reports = Report::where('user_id', $user->id)
            ->with(['ad', 'reportedUser', 'reviewer'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $reports->map(function($report) {
                return [
                    'id' => $report->id,
                    'type' => $report->type,
                    'reason' => $report->reason,
                    'status' => $report->status,
                    'admin_response' => $report->admin_notes,
                    'ad' => $report->ad ? [
                        'id' => $report->ad->id,
                        'uid' => $report->ad->uid,
                        'title' => $report->ad->title,
                    ] : null,
                    'reported_user' => $report->reportedUser ? [
                        'id' => $report->reportedUser->id,
                        'name' => $report->reportedUser->name,
                    ] : null,
                    'created_at' => $report->created_at,
                    'updated_at' => $report->updated_at,
                ];
            }),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:spam,fraud,inappropriate,duplicate,other',
            'reason' => 'required|string|max:1000',
            'ad_id' => 'nullable|exists:ads,id',
            'reported_user_id' => 'nullable|exists:users,id',
            'conversation_id' => 'nullable|exists:conversations,id',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Handle report images (تحويل تلقائي لـ WebP)
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
            $conversationMessages = Message::where('conversation_id', $request->conversation_id)
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

        $report = Report::create([
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

        return response()->json([
            'success' => true,
            'message' => 'Report submitted successfully',
            'data' => $report
        ], 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        $report = Report::where('user_id', $user->id)
            ->with(['ad', 'reportedUser', 'reviewer'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $report->id,
                'type' => $report->type,
                'reason' => $report->reason,
                'status' => $report->status,
                'admin_response' => $report->admin_notes,
                'ad' => $report->ad ? [
                    'id' => $report->ad->id,
                    'uid' => $report->ad->uid,
                    'title' => $report->ad->title,
                ] : null,
                'reported_user' => $report->reportedUser ? [
                    'id' => $report->reportedUser->id,
                    'name' => $report->reportedUser->name,
                ] : null,
                'conversation_messages' => $report->conversation_messages,
                'created_at' => $report->created_at,
                'updated_at' => $report->updated_at,
            ]
        ]);
    }
}
