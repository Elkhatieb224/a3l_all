<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\ActivityLog;
use App\Notifications\ReportActionNotification;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Report::with(['user', 'ad', 'reportedUser']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $reports = $query->latest()->paginate(20);

        $statusCounts = [
            'all' => Report::count(),
            'pending' => Report::where('status', 'pending')->count(),
            'reviewed' => Report::where('status', 'reviewed')->count(),
            'resolved' => Report::where('status', 'resolved')->count(),
            'rejected' => Report::where('status', 'rejected')->count(),
        ];

        return view('admin.reports.index', compact('reports', 'statusCounts'));
    }

    public function show($id)
    {
        $report = Report::with([
            'user',
            'ad.user',
            'reportedUser',
            'reviewer',
            'conversation' => fn ($q) => $q->with(['sender', 'receiver', 'ad']),
        ])->findOrFail($id);

        return view('admin.reports.show', compact('report'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,reviewed,resolved,rejected',
            'admin_notes' => 'nullable|string',
            'admin_attachments.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp,mp4,webm,avi,mov,pdf|max:20480',
        ], [], [
            'admin_attachments.*' => __('admin.reports.show.admin_attachment_file'),
        ]);

        $report = Report::findOrFail($id);

        $adminAttachments = $report->admin_attachments ?? [];
        if ($request->hasFile('admin_attachments')) {
            foreach ($request->file('admin_attachments') as $file) {
                if ($file->isValid()) {
                    $path = $file->store('report-admin-attachments', 'public');
                    $adminAttachments[] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType(),
                    ];
                }
            }
        }

        $report->update([
            'status' => $request->status,
            'admin_notes' => $request->admin_notes,
            'admin_attachments' => $adminAttachments,
            'reviewed_by' => auth('admin')->id(),
            'reviewed_at' => now(),
        ]);

        ActivityLog::log('report_reviewed', $report, [
            'status' => $request->status
        ]);

        // إرسال إشعار فوري: Push + بريد الإلكتروني المسجّل عند أي تحديث على البلاغ
        $report->refresh();
        $report->load('user');
        if ($report->user) {
            $user = $report->user;
            try {
                $user->notify(new ReportActionNotification($report));
                Log::info('Report action notification sent', [
                    'report_id' => $report->id,
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'status' => $report->status,
                ]);
            } catch (\Throwable $e) {
                Log::error('Report action notification failed', [
                    'report_id' => $report->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                // محاولة إرسال البريد والـ Push بشكل منفصل لضمان وصولهما
                $notification = new ReportActionNotification($report);
                try {
                    $mailChannel = app(\Illuminate\Notifications\Channels\MailChannel::class);
                    $mailChannel->send($user, $notification);
                    Log::info('Report notification: mail sent as fallback', ['user_id' => $user->id]);
                } catch (\Throwable $mailEx) {
                    Log::warning('Report notification: fallback mail failed', ['error' => $mailEx->getMessage()]);
                }
                try {
                    $fcmPayload = $notification->toFcm($user);
                    $firebase = app(FirebaseService::class);
                    $firebase->sendToUser($user, $fcmPayload['title'] ?? '', $fcmPayload['body'] ?? '', $fcmPayload['data'] ?? []);
                    Log::info('Report notification: FCM sent as fallback', ['user_id' => $user->id]);
                } catch (\Throwable $fcmEx) {
                    Log::warning('Report notification: fallback FCM failed', ['error' => $fcmEx->getMessage()]);
                }
            }
        }

        return back()->with('success', __('admin.reports.show.update_success'));
    }
}

