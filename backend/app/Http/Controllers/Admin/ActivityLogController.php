<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('admin');

        // Search by action
        if ($request->filled('action')) {
            $query->where('action', 'like', "%{$request->action}%");
        }

        // Filter by admin
        if ($request->filled('admin_id')) {
            $query->where('admin_id', $request->admin_id);
        }

        // Date filter
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $logs = $query->latest()->paginate(50);

        return view('admin.logs.index', compact('logs'));
    }
}

