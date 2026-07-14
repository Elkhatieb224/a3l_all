<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetMail;
use App\Support\LoginAttemptLimiter;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLoginForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        // Allow email links to pass an intended redirect target:
        // /login?redirect=/profile/ads/{uid}
        $redirect = (string) $request->query('redirect', '');
        if ($redirect !== '') {
            $decoded = urldecode($redirect);
            if (Str::startsWith($decoded, '/')) {
                $request->session()->put('url.intended', $decoded);
            } else {
                $appUrl = rtrim((string) config('app.url', ''), '/');
                if ($appUrl !== '' && Str::startsWith($decoded, $appUrl)) {
                    $request->session()->put('url.intended', $decoded);
                }
            }
        }
        
        return view('frontend.auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => __('frontend.auth.email_required'),
            'email.email' => __('frontend.auth.email_invalid'),
            'password.required' => __('frontend.auth.password_required'),
        ]);

        if (LoginAttemptLimiter::tooManyAttempts(LoginAttemptLimiter::CHANNEL_WEB, $request)) {
            $minutes = LoginAttemptLimiter::lockoutMinutes(LoginAttemptLimiter::CHANNEL_WEB, $request);

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => __('frontend.auth.login_locked', ['minutes' => $minutes]),
                ])
                ->withHeaders([
                    'Retry-After' => (string) max(1, LoginAttemptLimiter::availableSeconds(LoginAttemptLimiter::CHANNEL_WEB, $request)),
                ]);
        }

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        if (Auth::attempt($credentials, $remember)) {
            LoginAttemptLimiter::clear(LoginAttemptLimiter::CHANNEL_WEB, $request);
            $user = Auth::user();
            
            // Check if account is deleted
            if ($user->account_status === 'deleted') {
                Auth::logout();
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => __('frontend.auth.account_deleted')]);
            }
            
            // Check if user is active
            if (!$user->is_active) {
                Auth::logout();
                return back()
                    ->withInput($request->only('email'))
                    ->withErrors(['email' => __('frontend.auth.account_inactive')]);
            }
            
            // Cancel scheduled deletion if user logs in before deletion date
            if ($user->account_status === 'pending_deletion' && $user->scheduled_deletion_at && $user->scheduled_deletion_at->isFuture()) {
                $user->update([
                    'account_status' => 'active',
                    'scheduled_deletion_at' => null,
                ]);
            }
            
            // Update last login
            $user->update(['last_login_at' => now()]);
            
            $request->session()->regenerate();

            return redirect()->intended(route('home'))
                           ->with('success', __('frontend.auth.login_success'));
        }

        LoginAttemptLimiter::recordFailedAttempt(LoginAttemptLimiter::CHANNEL_WEB, $request, (string) $request->email);

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __('frontend.auth.invalid_credentials')]);
    }

    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        
        return view('frontend.auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:users,name',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
            'agreement' => 'required|accepted',
        ], [
            'name.required' => __('frontend.auth.name_required'),
            'name.unique' => __('frontend.auth.username_exists'),
            'email.required' => __('frontend.auth.email_required'),
            'email.email' => __('frontend.auth.email_invalid'),
            'email.unique' => __('frontend.auth.email_exists'),
            'password.required' => __('frontend.auth.password_required'),
            'password.confirmed' => __('frontend.auth.password_confirmation'),
            'password.min' => __('frontend.auth.password_min'),
            'agreement.required' => __('frontend.auth.agreement_required'),
            'agreement.accepted' => __('frontend.auth.agreement_required'),
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
            'is_verified' => false,
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('home')
                       ->with('success', __('frontend.auth.register_success'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('frontend.auth.logout_success'),
                'redirect' => route('home')
            ]);
        }

        return redirect()->route('home')
                       ->with('success', __('frontend.auth.logout_success'));
    }

    public function showForgotPasswordForm()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('frontend.auth.forgot-password');
    }

    public function sendPasswordResetCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => __('frontend.auth.email_required'),
            'email.email' => __('frontend.auth.email_invalid'),
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('frontend.auth.email_not_found')]);
        }

        $latestCode = PasswordResetCode::where('email', $request->email)->latest()->first();
        if ($latestCode && $latestCode->created_at->diffInSeconds(now()) < 60) {
            $remainingSeconds = 60 - $latestCode->created_at->diffInSeconds(now());
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('frontend.auth.password_reset_rate_limit', ['seconds' => $remainingSeconds])]);
        }

        $resetCode = PasswordResetCode::generateCode($request->email);

        try {
            $sent = false;
            // نحاول دائماً استخدام Laravel Mailer أولاً (نفس سلوك بقية الإشعارات)
            try {
                Mail::to($request->email)->send(new PasswordResetMail($resetCode->code, $user->name));
                $sent = true;
            } catch (\Throwable $mailEx) {
                // في حال فشل Mailer (أي سبب)، نحاول fall-back إلى دالة mail() العادية
                $sent = $this->sendPasswordResetViaMail($request->email, $resetCode->code, $user->name);
                if (!$sent) {
                    throw $mailEx;
                }
            }
        } catch (\Throwable $e) {
            $resetCode->delete();
            \Log::error('Password reset send failed: ' . $e->getMessage(), [
                'email' => $request->email,
                'exception' => $e,
            ]);
            $errorMessage = config('app.debug')
                ? __('frontend.auth.password_reset_send_failed') . ': ' . $e->getMessage()
                : __('frontend.auth.password_reset_send_failed');
            return back()->withInput($request->only('email'))->withErrors(['email' => $errorMessage]);
        }

        session(['email' => $request->email]);
        return redirect()->route('password.verify-code');
    }

    public function showVerifyCodeForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        $email = session('email');
        if (!$email) {
            return redirect()->route('password.forgot')->withErrors(['email' => __('frontend.auth.email_required')]);
        }
        return view('frontend.auth.verify-reset-code', compact('email'));
    }

    public function verifyPasswordResetCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ], [
            'code.required' => __('frontend.auth.code_required'),
            'code.size' => __('frontend.auth.code_size'),
        ]);

        $email = session('email');
        if (!$email) {
            return redirect()->route('password.forgot');
        }

        $resetCode = PasswordResetCode::where('email', $email)
            ->where('code', $request->code)
            ->valid()
            ->first();

        if (!$resetCode) {
            return back()->withErrors(['code' => __('frontend.auth.code_invalid')]);
        }

        $resetCode->update(['is_used' => true]);
        session(['password_reset_verified' => true, 'reset_email' => $email]);

        return redirect()->route('password.reset-form');
    }

    public function showResetPasswordForm(Request $request)
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        if (!session('password_reset_verified') || !session('reset_email')) {
            return redirect()->route('password.forgot');
        }
        return view('frontend.auth.reset-password');
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.required' => __('frontend.auth.password_required'),
            'password.confirmed' => __('frontend.profile.password_confirmation'),
            'password.min' => __('frontend.auth.password_min'),
        ]);

        $email = session('reset_email');
        if (!$email) {
            return redirect()->route('password.forgot');
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return redirect()->route('password.forgot');
        }

        $user->update(['password' => Hash::make($request->password)]);
        $request->session()->forget(['password_reset_verified', 'reset_email', 'email']);

        return redirect()->route('login')->with('success', __('frontend.auth.password_reset_success'));
    }

    private function sendPasswordResetViaMail(string $to, string $code, ?string $userName): bool
    {
        $subject = __('frontend.auth.password_reset_subject');
        $message = view('emails.password-reset', [
            'code' => $code,
            'userName' => $userName
        ])->render();
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: ' . config('mail.from.name') . ' <' . config('mail.from.address') . '>' . "\r\n";
        return (bool) mail($to, $subject, $message, $headers);
    }
}
