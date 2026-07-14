<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminTwoFactorMail;
use App\Models\ActivityLog;
use App\Models\AdminTwoFactorChallenge;
use App\Models\AdminTwoFactorIpTrust;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    /**
     * Show profile page
     */
    public function index()
    {
        $admin = auth('admin')->user();
        return view('admin.profile.index', compact('admin'));
    }

    /**
     * Update profile
     */
    public function update(Request $request)
    {
        $admin = auth('admin')->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email,' . $admin->id,
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = $request->only(['name', 'email', 'phone']);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar
            if ($admin->avatar) {
                Storage::disk('public')->delete($admin->avatar);
            }

            $path = store_image_as_webp($request->file('avatar'), 'avatars');
            // Set correct permissions
            $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
            if (file_exists($fullPath)) {
                chmod($fullPath, 0644);
            }
            $data['avatar'] = $path;
        }

        $admin->update($data);

        ActivityLog::log('profile_updated', $admin);

        return back()->with('success', __('admin.profile_updated_successfully'));
    }

    /**
     * Show change password form
     */
    public function showChangePasswordForm()
    {
        return view('admin.profile.change-password');
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $admin = auth('admin')->user();

        // Check current password
        if (!Hash::check($request->current_password, $admin->password)) {
            return back()->with('error', __('admin.current_password_incorrect'));
        }

        $admin->update([
            'password' => Hash::make($request->password),
        ]);

        ActivityLog::log('password_changed', $admin);

        return back()->with('success', __('admin.password_changed_successfully'));
    }

    public function twoFactorStart(Request $request)
    {
        $admin = auth('admin')->user();

        if ($admin->two_factor_enabled) {
            return back()->with('error', __('admin.two_factor.already_enabled'));
        }

        $request->validate([
            'two_factor_email' => 'required|email|max:255',
        ]);

        $plain = AdminTwoFactorChallenge::createChallenge(
            $admin->id,
            AdminTwoFactorChallenge::TYPE_SETUP,
            $request->two_factor_email
        );

        Mail::to($request->two_factor_email)->send(
            new AdminTwoFactorMail($plain, $admin->name, AdminTwoFactorChallenge::TYPE_SETUP)
        );

        $request->session()->put('profile_2fa_setup_email', $request->two_factor_email);

        return back()->with('success', __('admin.two_factor.code_sent'));
    }

    public function twoFactorConfirm(Request $request)
    {
        $admin = auth('admin')->user();

        if ($admin->two_factor_enabled) {
            return back()->with('error', __('admin.two_factor.already_enabled'));
        }

        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $challenge = AdminTwoFactorChallenge::verify(
            $admin->id,
            AdminTwoFactorChallenge::TYPE_SETUP,
            $request->code
        );

        if (! $challenge) {
            return back()->withErrors(['code' => __('admin.two_factor.invalid_code')]);
        }

        $admin->update([
            'two_factor_enabled' => true,
            'two_factor_email' => $challenge->email,
        ]);

        $challenge->delete();
        $request->session()->forget('profile_2fa_setup_email');

        ActivityLog::log('admin_two_factor_enabled', $admin);

        return back()->with('success', __('admin.two_factor.enabled_success'));
    }

    public function twoFactorDisable(Request $request)
    {
        $admin = auth('admin')->user();

        if (! $admin->two_factor_enabled) {
            return back();
        }

        $request->validate([
            'two_factor_disable_password' => 'required|string',
        ]);

        if (! Hash::check($request->two_factor_disable_password, $admin->password)) {
            return back()->withErrors([
                'two_factor_disable_password' => __('admin.current_password_incorrect'),
            ]);
        }

        AdminTwoFactorChallenge::where('admin_id', $admin->id)->delete();
        AdminTwoFactorIpTrust::forgetForAdmin($admin->id);

        $admin->update([
            'two_factor_enabled' => false,
            'two_factor_email' => null,
        ]);

        $request->session()->forget('profile_2fa_setup_email');

        ActivityLog::log('admin_two_factor_disabled', $admin);

        return back()->with('success', __('admin.two_factor.disabled_success'));
    }
}

