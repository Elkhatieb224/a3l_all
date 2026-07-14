<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ad;
use App\Models\Payment;
use App\Models\Report;
use App\Models\Package;
use App\Models\Subscription;
use App\Models\Category;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportingController extends Controller
{
    /**
     * Reports Dashboard
     */
    public function index()
    {
        return view('admin.reporting.index');
    }

    /**
     * Users Report
     */
    public function usersReport(Request $request)
    {
        $query = User::query();

        // Date filter
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === 'active');
        }

        // Verified filter
        if ($request->has('verified') && $request->verified !== '') {
            $query->where('is_verified', $request->verified === 'yes');
        }

        $users = $query->withCount('ads')->latest()->paginate(50);

        // Statistics
        $stats = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'verified' => User::where('is_verified', true)->count(),
            'with_ads' => User::has('ads')->count(),
            'new_this_month' => User::whereMonth('created_at', now()->month)->count(),
        ];

        // Chart data - Last 30 days
        $chartData = User::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.reporting.users', compact('users', 'stats', 'chartData'));
    }

    /**
     * Ads Report
     */
    public function adsReport(Request $request)
    {
        $query = Ad::with(['user', 'category']);

        // Date filter
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Category filter
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $ads = $query->latest()->paginate(50);

        // Statistics
        $stats = [
            'total' => Ad::count(),
            'pending' => Ad::where('status', 'pending')->count(),
            'active' => Ad::where('status', 'active')->count(),
            'rejected' => Ad::where('status', 'rejected')->count(),
            'expired' => Ad::where('status', 'expired')->count(),
            'featured' => Ad::where('is_featured', true)->count(),
            'urgent' => Ad::where('is_urgent', true)->count(),
            'total_views' => Ad::sum('views_count'),
        ];

        // Category breakdown
        $categoryBreakdown = Category::withCount('ads')->get();

        // Chart data
        $chartData = Ad::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $categories = Category::orderBy('name_ar')->get();

        return view('admin.reporting.ads', compact('ads', 'stats', 'categoryBreakdown', 'chartData', 'categories'));
    }

    /**
     * Financial Report
     */
    public function financialReport(Request $request)
    {
        $query = Payment::with(['user', 'package']);

        // Date filter
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $payments = $query->latest()->paginate(50);

        // Statistics
        $stats = [
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
            'pending_amount' => Payment::where('status', 'pending')->sum('amount'),
            'completed_count' => Payment::where('status', 'completed')->count(),
            'pending_count' => Payment::where('status', 'pending')->count(),
            'failed_count' => Payment::where('status', 'failed')->count(),
            'refunded_amount' => Payment::where('status', 'refunded')->sum('amount'),
            'this_month' => Payment::where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->sum('amount'),
            'today' => Payment::where('status', 'completed')
                ->whereDate('created_at', now())
                ->sum('amount'),
        ];

        // Revenue by package
        $packageRevenue = Package::withSum(['subscriptions as revenue' => function($q) {
            $q->join('payments', 'subscriptions.id', '=', 'payments.subscription_id')
              ->where('payments.status', 'completed');
        }], 'payments.amount')->get();

        // Chart data - Last 30 days
        $chartData = Payment::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.reporting.financial', compact('payments', 'stats', 'packageRevenue', 'chartData'));
    }

    /**
     * Reports Report (البلاغات)
     */
    public function reportsReport(Request $request)
    {
        $query = Report::with(['user', 'ad']);

        // Date filter
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Type filter
        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        $reports = $query->latest()->paginate(50);

        // Statistics
        $stats = [
            'total' => Report::count(),
            'pending' => Report::where('status', 'pending')->count(),
            'reviewed' => Report::where('status', 'reviewed')->count(),
            'resolved' => Report::where('status', 'resolved')->count(),
            'rejected' => Report::where('status', 'rejected')->count(),
        ];

        // Type breakdown
        $typeBreakdown = Report::selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->get();

        return view('admin.reporting.reports', compact('reports', 'stats', 'typeBreakdown'));
    }

    /**
     * Activity Report
     */
    public function activityReport(Request $request)
    {
        $query = ActivityLog::with('admin');

        // Date filter
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Admin filter
        if ($request->admin_id) {
            $query->where('admin_id', $request->admin_id);
        }

        // Action filter
        if ($request->action) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        $logs = $query->latest()->paginate(100);

        // Statistics
        $stats = [
            'total_actions' => ActivityLog::count(),
            'today' => ActivityLog::whereDate('created_at', now())->count(),
            'this_week' => ActivityLog::where('created_at', '>=', now()->subWeek())->count(),
            'this_month' => ActivityLog::whereMonth('created_at', now()->month)->count(),
        ];

        // Most active admins
        $topAdmins = ActivityLog::select('admin_id', DB::raw('COUNT(*) as count'))
            ->with('admin')
            ->groupBy('admin_id')
            ->orderByDesc('count')
            ->take(5)
            ->get();

        // Most common actions
        $topActions = ActivityLog::selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        $admins = \App\Models\Admin::orderBy('name')->get();

        return view('admin.reporting.activity', compact('logs', 'stats', 'topAdmins', 'topActions', 'admins'));
    }

    /**
     * Packages Report
     */
    public function packagesReport()
    {
        $packages = Package::withCount(['subscriptions' => function($q) {
            $q->where('status', 'active');
        }])->get();

        // Statistics per package
        $packageStats = $packages->map(function($package) {
            return [
                'package' => $package,
                'total_subscriptions' => Subscription::where('package_id', $package->id)->count(),
                'active_subscriptions' => Subscription::where('package_id', $package->id)
                    ->where('status', 'active')->count(),
                'expired_subscriptions' => Subscription::where('package_id', $package->id)
                    ->where('status', 'expired')->count(),
                'total_revenue' => Payment::where('package_id', $package->id)
                    ->where('status', 'completed')->sum('amount'),
                'avg_revenue' => Payment::where('package_id', $package->id)
                    ->where('status', 'completed')->avg('amount'),
            ];
        });

        // Overall statistics
        $overallStats = [
            'total_packages' => Package::count(),
            'active_packages' => Package::where('is_active', true)->count(),
            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'total_revenue' => Payment::where('status', 'completed')->sum('amount'),
        ];

        return view('admin.reporting.packages', compact('packageStats', 'overallStats'));
    }

    /**
     * Export Users Report
     */
    public function exportUsers(Request $request)
    {
        $query = User::query();

        // Apply same filters as usersReport
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->has('status') && $request->status !== '') {
            $query->where('is_active', $request->status === 'active');
        }
        if ($request->has('verified') && $request->verified !== '') {
            $query->where('is_verified', $request->verified === 'yes');
        }

        $users = $query->withCount('ads')->latest()->get();

        $filename = 'users_report_' . now()->format('Y-m-d_H-i-s') . '.csv';
        return $this->exportToCsv($users->map(function($user) {
            return [
                'ID' => $user->id,
                'الاسم' => $user->name,
                'البريد الإلكتروني' => $user->email,
                'رقم الهاتف' => $user->phone ?? '',
                'البلد' => $user->location_country ?? '',
                'المدينة' => $user->location_city ?? '',
                'الاسم التجاري' => $user->business_name ?? '',
                'موثق' => $user->is_verified ? 'نعم' : 'لا',
                'نشط' => $user->is_active ? 'نشط' : 'غير نشط',
                'عدد الإعلانات' => $user->ads_count ?? 0,
                'تاريخ الإنشاء' => $user->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray(), $filename);
    }

    /**
     * Export Ads Report
     */
    public function exportAds(Request $request)
    {
        $query = Ad::with(['user', 'category']);

        // Apply same filters as adsReport
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $ads = $query->latest()->get();

        $filename = 'ads_report_' . now()->format('Y-m-d_H-i-s') . '.csv';
        return $this->exportToCsv($ads->map(function($ad) {
            return [
                'ID' => $ad->id,
                'UID' => $ad->uid,
                'العنوان' => $ad->title,
                'المستخدم' => $ad->user->name ?? '',
                'القسم' => $ad->category->name_ar ?? '',
                'القسم الفرعي' => $ad->subcategory->name_ar ?? '',
                'السعر' => $ad->price ?? 0,
                'العملة' => get_currency_symbol_for_code($ad->currency ?? 'SYP'),
                'الحالة' => $ad->status,
                'مميز' => $ad->is_featured ? 'نعم' : 'لا',
                'عاجل' => $ad->is_urgent ? 'نعم' : 'لا',
                'المشاهدات' => $ad->views_count ?? 0,
                'تاريخ الإنشاء' => $ad->created_at->format('Y-m-d H:i:s'),
                'تاريخ النشر' => $ad->published_at ? $ad->published_at->format('Y-m-d H:i:s') : '',
            ];
        })->toArray(), $filename);
    }

    /**
     * Export Financial Report
     */
    public function exportFinancial(Request $request)
    {
        $query = Payment::with(['user', 'package']);

        // Apply same filters as financialReport
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $payments = $query->latest()->get();

        $filename = 'financial_report_' . now()->format('Y-m-d_H-i-s') . '.csv';
        return $this->exportToCsv($payments->map(function($payment) {
            return [
                'رقم العملية' => $payment->transaction_id ?? '',
                'المستخدم' => $payment->user->name ?? '',
                'الباقة' => $payment->package->name_ar ?? '',
                'المبلغ' => $payment->amount ?? 0,
                'العملة' => get_currency_symbol_for_code($payment->currency ?? 'SYP'),
                'طريقة الدفع' => $payment->payment_method ?? '',
                'الحالة' => $payment->status,
                'تاريخ العملية' => $payment->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray(), $filename);
    }

    /**
     * Export Reports Report
     */
    public function exportReports(Request $request)
    {
        $query = Report::with(['user', 'ad']);

        // Apply same filters as reportsReport
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }
        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        $reports = $query->latest()->get();

        $filename = 'reports_report_' . now()->format('Y-m-d_H-i-s') . '.csv';
        return $this->exportToCsv($reports->map(function($report) {
            return [
                'ID' => $report->id,
                'النوع' => $report->type,
                'المبلغ' => $report->user->name ?? '',
                'الإعلان' => $report->ad->title ?? '',
                'السبب' => $report->reason ?? '',
                'الحالة' => $report->status,
                'تاريخ البلاغ' => $report->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray(), $filename);
    }

    /**
     * Export Activity Report
     */
    public function exportActivity(Request $request)
    {
        $query = ActivityLog::with('admin');

        // Apply same filters as activityReport
        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }
        if ($request->admin_id) {
            $query->where('admin_id', $request->admin_id);
        }
        if ($request->action) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        $logs = $query->latest()->get();

        $filename = 'activity_report_' . now()->format('Y-m-d_H-i-s') . '.csv';
        return $this->exportToCsv($logs->map(function($log) {
            return [
                'ID' => $log->id,
                'المدير' => $log->admin->name ?? 'System',
                'الإجراء' => $log->action,
                'النموذج' => $log->model_type ? class_basename($log->model_type) : '',
                'معرف النموذج' => $log->model_id ?? '',
                'عنوان IP' => $log->ip_address ?? '',
                'التاريخ والوقت' => $log->created_at->format('Y-m-d H:i:s'),
            ];
        })->toArray(), $filename);
    }

    /**
     * Export Packages Report
     */
    public function exportPackages()
    {
        $packages = Package::withCount(['subscriptions' => function($q) {
            $q->where('status', 'active');
        }])->get();

        $filename = 'packages_report_' . now()->format('Y-m-d_H-i-s') . '.csv';
        return $this->exportToCsv($packages->map(function($package) {
            $totalSubscriptions = Subscription::where('package_id', $package->id)->count();
            $activeSubscriptions = Subscription::where('package_id', $package->id)->where('status', 'active')->count();
            $totalRevenue = Payment::where('package_id', $package->id)->where('status', 'completed')->sum('amount');
            
            return [
                'ID' => $package->id,
                'الاسم (عربي)' => $package->name_ar,
                'الاسم (إنجليزي)' => $package->name_en,
                'الاسم (تركي)' => $package->name_tr,
                'السعر' => $package->price,
                'العملة' => get_currency_symbol_for_code($package->currency),
                'مدة الصلاحية (يوم)' => $package->duration_days,
                'حد الإعلانات' => $package->ads_limit,
                'إعلانات مميزة' => $package->featured_ads ? 'نعم' : 'لا',
                'إعلانات عاجلة' => $package->urgent_ads ? 'نعم' : 'لا',
                'دعم متميز' => $package->priority_support ? 'نعم' : 'لا',
                'عرض في الصفحة الرئيسية' => $package->homepage_display ? 'نعم' : 'لا',
                'نشط' => $package->is_active ? 'نعم' : 'لا',
                'إجمالي الاشتراكات' => $totalSubscriptions,
                'الاشتراكات النشطة' => $activeSubscriptions,
                'إجمالي الإيرادات' => $totalRevenue,
            ];
        })->toArray(), $filename);
    }

    /**
     * Helper method to export data to CSV
     */
    private function exportToCsv(array $data, string $filename)
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Headers
            if (!empty($data)) {
                fputcsv($file, array_keys($data[0]), ',');
                
                // Data rows
                foreach ($data as $row) {
                    fputcsv($file, $row, ',');
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

