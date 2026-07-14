<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Support\LoginAttemptLimiter;
use App\Mail\PasswordResetMail;
use App\Models\FcmToken;
use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:users,name',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'country_code' => 'nullable|string|in:SY,TR',
            'fcm_token' => 'nullable|string|max:500',
            'device_type' => 'nullable|string|in:android,ios,web',
            'device_id' => 'nullable|string|max:255',
        ], [
            'name.unique' => __('frontend.auth.username_exists'),
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json([
                'success' => false,
                'message' => $errors->first(),
                'errors' => $errors,
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'country_code' => $request->country_code,
            'is_active' => true,
        ]);

        if ($request->filled('fcm_token')) {
            $this->saveFcmToken($user, $request->fcm_token, $request->device_type, $request->device_id);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'fcm_token' => 'nullable|string|max:500',
            'device_type' => 'nullable|string|in:android,ios,web',
            'device_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            return response()->json([
                'success' => false,
                // إظهار أول رسالة خطأ بدلاً من النص العام "Validation error"
                'message' => $errors->first(),
                'errors' => $errors,
            ], 422);
        }

        if (LoginAttemptLimiter::tooManyAttempts(LoginAttemptLimiter::CHANNEL_API, $request)) {
            $retryAfter = LoginAttemptLimiter::availableSeconds(LoginAttemptLimiter::CHANNEL_API, $request);
            $minutes = LoginAttemptLimiter::lockoutMinutes(LoginAttemptLimiter::CHANNEL_API, $request);

            return response()->json([
                'success' => false,
                'message' => __('frontend.auth.login_locked', ['minutes' => $minutes]),
                'retry_after_seconds' => $retryAfter,
            ], 429)->withHeaders([
                'Retry-After' => (string) max(1, $retryAfter),
            ]);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            LoginAttemptLimiter::recordFailedAttempt(LoginAttemptLimiter::CHANNEL_API, $request, (string) $request->email);

            return response()->json([
                'success' => false,
                'message' => __('frontend.auth.invalid_credentials'),
            ], 401);
        }

        LoginAttemptLimiter::clear(LoginAttemptLimiter::CHANNEL_API, $request);

        $user = User::where('email', $request->email)->firstOrFail();

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.auth.account_inactive')
            ], 403);
        }

        $user->update(['last_login_at' => now()]);

        if ($request->filled('fcm_token')) {
            $this->saveFcmToken($user, $request->fcm_token, $request->device_type, $request->device_id);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    /**
     * حفظ أو تحديث FCM token للمستخدم (يُستخدم عند التسجيل وتسجيل الدخول).
     */
    private function saveFcmToken(User $user, string $fcmToken, ?string $deviceType = null, ?string $deviceId = null): void
    {
        $tokenHash = hash('sha256', $fcmToken);

        FcmToken::updateOrCreate(
            [
                'user_id' => $user->id,
                'token_hash' => $tokenHash,
            ],
            [
                'token' => $fcmToken,
                'device_type' => $deviceType,
                'device_id' => $deviceId,
                'last_used_at' => now(),
            ]
        );
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user())
        ]);
    }

    /**
     * إرسال رمز استعادة كلمة المرور إلى البريد الإلكتروني
     */
    public function sendPasswordResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.auth.email_not_found')
            ], 404);
        }

        $latestCode = PasswordResetCode::where('email', $request->email)->latest()->first();
        if ($latestCode && $latestCode->created_at->diffInSeconds(now()) < 60) {
            $remainingSeconds = 60 - $latestCode->created_at->diffInSeconds(now());
            return response()->json([
                'success' => false,
                'message' => __('frontend.auth.password_reset_rate_limit', ['seconds' => $remainingSeconds])
            ], 429);
        }

        $resetCode = PasswordResetCode::generateCode($request->email);

        try {
            $sent = false;
            if (in_array(config('mail.mailers.smtp.host'), ['localhost', '127.0.0.1'])) {
                $sent = $this->sendPasswordResetViaMail($request->email, $resetCode->code, $user->name);
            } else {
                try {
                    Mail::to($request->email)->send(new PasswordResetMail($resetCode->code, $user->name));
                    $sent = true;
                } catch (\Exception $mailEx) {
                    $sent = $this->sendPasswordResetViaMail($request->email, $resetCode->code, $user->name);
                }
            }
            if (!$sent) {
                throw new \Exception('mail() function failed');
            }
        } catch (\Exception $e) {
            $resetCode->delete();
            \Log::error('Password reset send failed: ' . $e->getMessage(), [
                'email' => $request->email,
                'exception' => $e,
            ]);
            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? __('frontend.auth.password_reset_send_failed') . ': ' . $e->getMessage()
                    : __('frontend.auth.password_reset_send_failed')
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => __('frontend.auth.password_reset_send_success')
        ]);
    }

    /**
     * استعادة كلمة المرور باستخدام الرمز
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $resetCode = PasswordResetCode::where('email', $request->email)
            ->where('code', $request->code)
            ->valid()
            ->first();

        if (!$resetCode) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.auth.code_invalid')
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.auth.email_not_found')
            ], 404);
        }

        $user->update(['password' => Hash::make($request->password)]);
        $resetCode->update(['is_used' => true]);

        return response()->json([
            'success' => true,
            'message' => __('frontend.auth.password_reset_success')
        ]);
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
