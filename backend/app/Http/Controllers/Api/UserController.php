<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\EmailVerificationCode;
use App\Models\UserActivityLog;
use App\Mail\EmailVerificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function show(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => new UserResource($request->user())
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $rules = [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:30',
            'country_code' => 'nullable|string|in:SY,TR',
            'business_name' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'location_country' => 'nullable|string|in:SY,TR',
            'location_city' => 'nullable|string|max:255',
            'location_district' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ];

        if ($user->is_verified) {
            $rules['business_type'] = 'nullable|string|max:255';
            $rules['business_owner'] = 'nullable|string|max:255';
            $rules['business_address'] = 'nullable|string|max:2000';
            $rules['business_phone'] = 'nullable|string|max:30';
            $rules['instagram_url'] = 'nullable|string|max:500';
            $rules['facebook_url'] = 'nullable|string|max:500';
            $rules['website_url'] = 'nullable|string|max:500';
            $rules['storefront_image'] = 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'email', 'phone', 'country_code', 'business_name', 'bio', 'location_country', 'location_city', 'location_district']);

        if ($user->is_verified) {
            foreach (['business_type', 'business_owner', 'business_address', 'business_phone', 'instagram_url', 'facebook_url', 'website_url'] as $key) {
                if ($request->has($key)) {
                    $val = $request->input($key);
                    $data[$key] = $val === '' ? null : $val;
                }
            }
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = store_image_as_webp($request->file('avatar'), 'avatars');
            // Set correct permissions
            $fullPath = Storage::disk('public')->path($data['avatar']);
            if (file_exists($fullPath)) {
                chmod($fullPath, 0644);
            }
        }

        if ($user->is_verified && $request->hasFile('storefront_image')) {
            if ($user->storefront_image_path) {
                Storage::disk('public')->delete($user->storefront_image_path);
            }
            $data['storefront_image_path'] = store_image_as_webp($request->file('storefront_image'), 'verification/documents');
            $fullPath = Storage::disk('public')->path($data['storefront_image_path']);
            if (file_exists($fullPath)) {
                chmod($fullPath, 0644);
            }
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => new UserResource($user->fresh())
        ]);
    }

    public function updateFcmToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $request->user()->update(['fcm_token' => $request->fcm_token]);

        return response()->json([
            'success' => true,
            'message' => 'FCM token saved',
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully'
        ]);
    }

    public function updateEmail(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is incorrect'
            ], 422);
        }

        $user->update([
            'email' => $request->email,
            'email_verified_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Email updated successfully',
            'data' => new UserResource($user->fresh())
        ]);
    }

    public function sendEmailVerificationCode(Request $request)
    {
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email is already verified'
            ], 400);
        }

        $latestCode = EmailVerificationCode::where('user_id', $user->id)
            ->where('email', $user->email)
            ->latest()
            ->first();

        if ($latestCode && $latestCode->created_at->diffInSeconds(now()) < 60) {
            $remainingSeconds = 60 - $latestCode->created_at->diffInSeconds(now());
            return response()->json([
                'success' => false,
                'message' => "Please wait {$remainingSeconds} seconds before requesting a new code"
            ], 429);
        }

        $verificationCode = EmailVerificationCode::generateCode($user->id, $user->email);

        try {
            if (config('mail.mailers.smtp.host') === 'localhost' || config('mail.mailers.smtp.host') === '127.0.0.1') {
                $to = $user->email;
                $subject = 'Email Verification Code';
                $message = view('emails.email-verification', [
                    'code' => $verificationCode->code,
                    'userName' => $user->name
                ])->render();
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= 'From: ' . config('mail.from.name') . ' <' . config('mail.from.address') . '>' . "\r\n";
                
                if (!mail($to, $subject, $message, $headers)) {
                    throw new \Exception('mail() function failed');
                }
            } else {
                Mail::to($user->email)->send(new EmailVerificationMail($verificationCode->code, $user->name));
            }

            return response()->json([
                'success' => true,
                'message' => 'Verification code sent successfully'
            ]);
        } catch (\Exception $e) {
            $verificationCode->delete();
            
            \Log::error('Email verification send failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code. Please try again later.'
            ], 500);
        }
    }

    public function verifyEmailCode(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $verificationCode = EmailVerificationCode::where('user_id', $user->id)
            ->where('email', $user->email)
            ->where('code', $request->code)
            ->valid()
            ->first();

        if (!$verificationCode) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code'
            ], 422);
        }

        $verificationCode->update(['is_used' => true]);

        $user->update([
            'email_verified_at' => now(),
        ]);

        UserActivityLog::log(
            'email_verified',
            'Email verified successfully',
            $user,
            ['email' => $user->email]
        );

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully',
            'data' => new UserResource($user->fresh())
        ]);
    }

    /**
     * Request to change email: send verification code to the new email.
     * Email is only updated after verifying the code via verifyEmailChangeCode.
     */
    public function requestEmailChange(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'new_email' => 'required|email|max:255|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $newEmail = $request->new_email;

        $latestCode = EmailVerificationCode::where('user_id', $user->id)
            ->where('email', $newEmail)
            ->latest()
            ->first();

        if ($latestCode && $latestCode->created_at->diffInSeconds(now()) < 60) {
            $remainingSeconds = 60 - $latestCode->created_at->diffInSeconds(now());
            return response()->json([
                'success' => false,
                'message' => "Please wait {$remainingSeconds} seconds before requesting a new code"
            ], 429);
        }

        $verificationCode = EmailVerificationCode::generateCode($user->id, $newEmail);

        try {
            if (config('mail.mailers.smtp.host') === 'localhost' || config('mail.mailers.smtp.host') === '127.0.0.1') {
                $subject = 'Email Change Verification Code';
                $message = view('emails.email-verification', [
                    'code' => $verificationCode->code,
                    'userName' => $user->name
                ])->render();
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= 'From: ' . config('mail.from.name') . ' <' . config('mail.from.address') . '>' . "\r\n";
                if (!mail($newEmail, $subject, $message, $headers)) {
                    throw new \Exception('mail() function failed');
                }
            } else {
                Mail::to($newEmail)->send(new EmailVerificationMail($verificationCode->code, $user->name));
            }

            return response()->json([
                'success' => true,
                'message' => 'Verification code sent to the new email'
            ]);
        } catch (\Exception $e) {
            $verificationCode->delete();
            \Log::error('Email change verification send failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification code. Please try again later.'
            ], 500);
        }
    }

    /**
     * Verify the code sent to the new email and update user email.
     */
    public function verifyEmailChangeCode(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'code' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $verificationCode = EmailVerificationCode::where('user_id', $user->id)
            ->where('code', $request->code)
            ->valid()
            ->first();

        if (!$verificationCode) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code'
            ], 422);
        }

        if ($verificationCode->email === $user->email) {
            return response()->json([
                'success' => false,
                'message' => 'Use the standard verify endpoint for current email'
            ], 422);
        }

        $newEmail = $verificationCode->email;
        $verificationCode->update(['is_used' => true]);

        $user->update([
            'email' => $newEmail,
            'email_verified_at' => now(),
        ]);

        UserActivityLog::log(
            'email_changed',
            'Email changed successfully',
            $user,
            ['email' => $newEmail]
        );

        return response()->json([
            'success' => true,
            'message' => 'Email updated successfully',
            'data' => new UserResource($user->fresh())
        ]);
    }

    public function updatePhone(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'country_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update([
            'country_code' => $request->country_code ?? null,
            'phone' => $request->phone,
            'phone_verified_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Phone updated successfully',
            'data' => new UserResource($user->fresh())
        ]);
    }

    public function activities(Request $request)
    {
        $user = $request->user();
        $activities = UserActivityLog::where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    public function cancelAccount(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
            'confirm' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is incorrect'
            ], 422);
        }

        if ($user->account_status === 'pending_deletion') {
            return response()->json([
                'success' => false,
                'message' => 'Account is already scheduled for deletion'
            ], 400);
        }

        $user->update([
            'scheduled_deletion_at' => now()->addDays(14),
            'account_status' => 'pending_deletion',
        ]);

        UserActivityLog::log(
            'account_cancellation_scheduled',
            'Account cancellation scheduled',
            $user,
            ['scheduled_deletion_at' => $user->scheduled_deletion_at]
        );

        return response()->json([
            'success' => true,
            'message' => 'Account cancellation scheduled. Your account will be deleted in 14 days.'
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully'
        ]);
    }
}
