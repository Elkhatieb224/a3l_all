<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::withCount('subscriptions')
            ->orderBy('order')
            ->get();

        return view('admin.packages.index', compact('packages'));
    }

    public function create()
    {
        return view('admin.packages.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'name_tr' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'duration_days' => 'required|integer|min:1',
            'ads_limit' => 'required|integer|min:1',
        ]);

        $data = $request->all();
        if (!$request->has('featured_ads')) {
            $data['featured_ads'] = false;
            $data['featured_ads_limit'] = 0;
        }
        if (!$request->has('urgent_ads')) {
            $data['urgent_ads'] = false;
            $data['urgent_ads_limit'] = 0;
        }
        $package = Package::create($data);

        ActivityLog::log('package_created', $package);

        return redirect()->route('admin.packages.index')
            ->with('success', 'تم إضافة الباقة بنجاح');
    }

    public function edit($id)
    {
        $package = Package::findOrFail($id);
        return view('admin.packages.edit', compact('package'));
    }

    public function update(Request $request, $id)
    {
        $package = Package::findOrFail($id);

        $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'name_tr' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|max:3',
            'duration_days' => 'required|integer|min:1',
            'ads_limit' => 'required|integer|min:1',
        ]);

        $data = $request->all();
        if (!$request->has('featured_ads')) {
            $data['featured_ads'] = false;
            $data['featured_ads_limit'] = 0;
        }
        if (!$request->has('urgent_ads')) {
            $data['urgent_ads'] = false;
            $data['urgent_ads_limit'] = 0;
        }
        $oldData = $package->toArray();
        $package->update($data);

        ActivityLog::log('package_updated', $package, [
            'old' => $oldData,
            'new' => $package->toArray()
        ]);

        return redirect()->route('admin.packages.index')
            ->with('success', 'تم تحديث الباقة بنجاح');
    }

    public function destroy($id)
    {
        $package = Package::findOrFail($id);
        
        ActivityLog::log('package_deleted', $package);
        
        $package->delete();

        return back()->with('success', 'تم حذف الباقة بنجاح');
    }

    public function toggleStatus($id)
    {
        $package = Package::findOrFail($id);
        $package->update(['is_active' => !$package->is_active]);

        ActivityLog::log('package_status_toggle', $package);

        return back()->with('success', 'تم تغيير حالة الباقة بنجاح');
    }
}

