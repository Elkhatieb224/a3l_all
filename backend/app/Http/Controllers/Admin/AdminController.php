<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Get permissions for a specific role
     */
    public static function getRolePermissions($role)
    {
        $permissions = [
            'super_admin' => [
                'manage_admins',
                'view_logs',
                'manage_categories',
                'manage_users',
                'manage_subcategories',
                'manage_ads',
                'manage_packages',
                'manage_payments',
                'manage_settings',
                'manage_translations',
                'view_reporting',
                'view_categories',
                'view_ads',
                'view_reports',
                'process_reports',
                'manage_support',
                'manage_faqs',
                'send_notifications',
            ],
            'admin' => [
                'manage_users',
                'manage_subcategories',
                'manage_ads',
                'manage_packages',
                'manage_payments',
                'manage_settings',
                'manage_translations',
                'view_reporting',
                'view_categories',
                'view_ads',
                'view_reports',
                'send_notifications',
            ],
            'moderator' => [
                'view_categories',
                'view_ads',
                'view_reports',
            ],
            'support_agent' => [
                'view_reports',
                'process_reports',
                'manage_support',
                'manage_faqs',
            ],
        ];

        return $permissions[$role] ?? [];
    }

    public function index()
    {
        $admins = Admin::latest()->paginate(20);
        return view('admin.admins.index', compact('admins'));
    }

    public function create()
    {
        return view('admin.admins.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:super_admin,admin,moderator,support_agent',
        ]);

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        ActivityLog::log('admin_created', $admin);

        return redirect()->route('admin.admins.index')
            ->with('success', 'تم إضافة المدير بنجاح');
    }

    public function edit($id)
    {
        $admin = Admin::findOrFail($id);
        return view('admin.admins.edit', compact('admin'));
    }

    public function update(Request $request, $id)
    {
        $admin = Admin::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email,' . $id,
            'password' => 'nullable|min:6|confirmed',
            'role' => 'required|in:super_admin,admin,moderator,support_agent',
        ]);

        $data = $request->only(['name', 'email', 'role']);
        
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);

        ActivityLog::log('admin_updated', $admin);

        return redirect()->route('admin.admins.index')
            ->with('success', 'تم تحديث بيانات المدير بنجاح');
    }

    public function destroy($id)
    {
        $admin = Admin::findOrFail($id);
        
        // Prevent deleting yourself
        if ($admin->id === auth('admin')->id()) {
            return back()->with('error', 'لا يمكنك حذف حسابك الخاص');
        }

        ActivityLog::log('admin_deleted', $admin);
        
        $admin->delete();

        return back()->with('success', 'تم حذف المدير بنجاح');
    }

    public function toggleStatus($id)
    {
        $admin = Admin::findOrFail($id);
        
        // Prevent deactivating yourself
        if ($admin->id === auth('admin')->id()) {
            return back()->with('error', 'لا يمكنك تعطيل حسابك الخاص');
        }

        $admin->update(['is_active' => !$admin->is_active]);

        ActivityLog::log('admin_status_toggle', $admin);

        return back()->with('success', 'تم تغيير حالة المدير بنجاح');
    }
}

