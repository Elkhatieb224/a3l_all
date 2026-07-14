<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ad;
use App\Models\Payment;
use App\Models\Package;
use App\Models\Report;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'verified_users' => User::where('is_verified', true)->count(),
            'total_ads' => Ad::count(),
            'pending_ads' => Ad::where('status', 'pending')->count(),
            'active_ads' => Ad::where('status', 'active')->count(),
            'rejected_ads' => Ad::where('status', 'rejected')->count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'pending_payments' => Payment::where('status', 'pending')->count(),
            'total_packages' => Package::where('is_active', true)->count(),
            'pending_reports' => Report::where('status', 'pending')->count(),
        ];

        // Recent activities
        $recentActivities = ActivityLog::with('admin')
            ->latest()
            ->take(10)
            ->get();

        // Recent ads
        $recentAds = Ad::with(['user', 'category'])
            ->latest()
            ->take(5)
            ->get();

        // Recent users
        $recentUsers = User::latest()
            ->take(5)
            ->get();

        // Chart data - Last 30 days
        $last30Days = now()->subDays(30);
        $adsChart = Ad::where('created_at', '>=', $last30Days)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $usersChart = User::where('created_at', '>=', $last30Days)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentActivities',
            'recentAds',
            'recentUsers',
            'adsChart',
            'usersChart'
        ));
    }
}

