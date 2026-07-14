<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminTwoFactorMail;
use App\Models\ActivityLog;
use App\Models\Admin;
use App\Models\AdminTwoFactorChallenge;
use App\Models\AdminTwoFactorIpTrust;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AdminTwoFactorController extends Controller
{
    public function showChallenge(Request $request)
    {
        return view('admin.auth.two-factor-challenge');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required|digits:6',
        ]);

        $failKey = 'admin_2fa_failed';
        if ((int) $request->session()->get($failKey, 0) >= 5) {
            $request->session()->forget(['admin_2fa_pending_id', 'admin_2fa_remember', $failKey]);

            return redirect()->route('admin.login')
                ->withErrors(['email' => __('admin.two_factor.too_many_otp_attempts')]);
        }

        $adminId = (int) $request->session()->get('admin_2fa_pending_id');
        if (! $adminId) {
            return redirect()->route('admin.login');
        }

        $challenge = AdminTwoFactorChallenge::verify($adminId, AdminTwoFactorChallenge::TYPE_LOGIN, $request->code);
        if (! $challenge) {
            $request->session()->put($failKey, (int) $request->session()->get($failKey, 0) + 1);

            return back()->withErrors(['code' => __('admin.two_factor.invalid_code')]);
        }

        $remember = (bool) $request->session()->get('admin_2fa_remember', false);
        $challenge->delete();
        $request->session()->forget(['admin_2fa_pending_id', 'admin_2fa_remember', $failKey]);

        Auth::guard('admin')->loginUsingId($adminId, $remember);

        $admin = Auth::guard('admin')->user();
        if (! $admin || ! $admin->is_active) {
            Auth::guard('admin')->logout();

            return redirect()->route('admin.login')
                ->withErrors(['email' => __('admin.invalid_login_credentials')]);
        }

        $admin->update(['last_login_at' => now()]);
        AdminTwoFactorIpTrust::trust($admin->id, $request->ip());
        ActivityLog::log('admin_login', $admin);

        $request->session()->save();

        return redirect()->intended(route('admin.dashboard'))
            ->with('success', __('admin.welcome'));
    }

    public function resend(Request $request)
    {
        $adminId = (int) $request->session()->get('admin_2fa_pending_id');
        if (! $adminId) {
            return redirect()->route('admin.login');
        }

        $admin = Admin::where('id', $adminId)->where('two_factor_enabled', true)->first();
        if (! $admin || ! $admin->two_factor_email) {
            $request->session()->forget(['admin_2fa_pending_id', 'admin_2fa_remember']);

            return redirect()->route('admin.login');
        }

        $plain = AdminTwoFactorChallenge::createChallenge(
            $admin->id,
            AdminTwoFactorChallenge::TYPE_LOGIN,
            $admin->two_factor_email
        );

        Mail::to($admin->two_factor_email)->send(
            new AdminTwoFactorMail($plain, $admin->name, AdminTwoFactorChallenge::TYPE_LOGIN)
        );

        return back()->with('success', __('admin.two_factor.code_resent'));
    }
}
