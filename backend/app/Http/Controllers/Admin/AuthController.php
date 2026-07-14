<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminTwoFactorMail;
use App\Models\ActivityLog;
use App\Models\AdminTwoFactorChallenge;
use App\Models\AdminTwoFactorIpTrust;
use App\Support\LoginAttemptLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (LoginAttemptLimiter::tooManyAttempts(LoginAttemptLimiter::CHANNEL_ADMIN, $request)) {
            // نفس رسمة الخطأ العادية دون كشف الحظر (حتى مع بيانات صحيحة لا يُسمح بالدخول حتى انتهاء المهلة)
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('admin.invalid_login_credentials')]);
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::guard('admin')->attempt($credentials, $remember)) {
            LoginAttemptLimiter::clear(LoginAttemptLimiter::CHANNEL_ADMIN, $request);
            $admin = Auth::guard('admin')->user();

            if ($admin->two_factor_enabled
                && $admin->two_factor_email
                && ! AdminTwoFactorIpTrust::isTrusted($admin->id, $request->ip())) {
                Auth::guard('admin')->logout();

                $plain = AdminTwoFactorChallenge::createChallenge(
                    $admin->id,
                    AdminTwoFactorChallenge::TYPE_LOGIN,
                    $admin->two_factor_email
                );

                Mail::to($admin->two_factor_email)->send(
                    new AdminTwoFactorMail($plain, $admin->name, AdminTwoFactorChallenge::TYPE_LOGIN)
                );

                $request->session()->put('admin_2fa_pending_id', $admin->id);
                $request->session()->put('admin_2fa_remember', $remember);
                $request->session()->save();

                return redirect()->route('admin.two-factor.show');
            }

            $admin->update(['last_login_at' => now()]);
            ActivityLog::log('admin_login', $admin);
            $request->session()->save();

            return redirect()->intended(route('admin.dashboard'))
                ->with('success', __('admin.welcome'));
        }

        LoginAttemptLimiter::recordFailedAttempt(LoginAttemptLimiter::CHANNEL_ADMIN, $request, (string) $request->input('email'));

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __('admin.invalid_login_credentials')]);
    }

    public function logout(Request $request)
    {
        ActivityLog::log('admin_logout');
        
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}

