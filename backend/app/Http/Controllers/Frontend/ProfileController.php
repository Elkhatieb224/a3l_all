<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAdImagesJob;
use App\Models\Ad;
use App\Models\BlockedUser;
use App\Models\ActivityLog;
use App\Models\UserActivityLog;
use App\Models\SellerRating;
use App\Models\EmailVerificationCode;
use App\Mail\EmailVerificationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    private function normalizeOptionalUrl(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (!preg_match('/^https?:\/\//i', $value)) {
            $value = 'https://' . $value;
        }

        return $value;
    }

    public function __construct()
    {
        $this->middleware('auth')->except(['showSeller']);
    }

    public function index()
    {
        $user = Auth::user();
        return view('frontend.profile.index', compact('user'));
    }

    public function personalInfo()
    {
        $user = Auth::user();
        return view('frontend.profile.personal-info', compact('user'));
    }

    public function updatePersonalInfo(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'location_country' => 'nullable|in:SY,TR',
            'location_city' => 'nullable|string|max:255',
            'location_district' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:4096|dimensions:min_width=480,min_height=480',
        ], [
            'name.required' => __('frontend.profile.name_required'),
            'bio.max' => __('frontend.profile.bio_max'),
            'location_country.in' => __('frontend.profile.country_invalid'),
            'location_city.max' => __('frontend.profile.city_max'),
            'avatar.image' => __('frontend.profile.avatar_must_be_image'),
            'avatar.max' => __('frontend.profile.avatar_max_size'),
            'avatar.dimensions' => __('frontend.profile.avatar_min_dimensions'),
        ]);

        $data = [
            'name' => $request->name,
            'bio' => $request->bio,
            'location_country' => $request->location_country,
            'location_city' => $request->location_city,
            'location_district' => $request->location_district,
        ];

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $avatarPath = store_image_as_webp($request->file('avatar'), 'avatars');
            $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($avatarPath);
            if (file_exists($fullPath)) {
                chmod($fullPath, 0644);
            }
            $data['avatar'] = $avatarPath;
        }

        // Update slug if name changed
        if ($request->name !== $user->name) {
            $baseSlug = \Illuminate\Support\Str::slug($request->name);
            $slug = $baseSlug;
            $counter = 1;
            while (\App\Models\User::where('slug', $slug)->where('id', '!=', $user->id)->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }
            $data['slug'] = $slug;
        }

        $user->update($data);

        return redirect()->route('profile.personal-info')
                       ->with('success', __('frontend.profile.updated_successfully'));
    }

    public function email()
    {
        $user = Auth::user();

        // Get latest verification code if exists
        $latestCode = EmailVerificationCode::where('user_id', $user->id)
            ->where('email', $user->email)
            ->where('is_used', false)
            ->latest()
            ->first();

        $canResend = true;
        if ($latestCode && $latestCode->created_at->diffInSeconds(now()) < 60) {
            $canResend = false;
        }

        return view('frontend.profile.email', compact('user', 'latestCode', 'canResend'));
    }

    public function updateEmail(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'required',
        ], [
            'email.required' => __('frontend.profile.email_required'),
            'email.email' => __('frontend.profile.email_invalid'),
            'email.unique' => __('frontend.profile.email_exists'),
            'password.required' => __('frontend.profile.password_required'),
        ]);

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => __('frontend.profile.invalid_password')]);
        }

        $user->update([
            'email' => $request->email,
            'email_verified_at' => null, // Reset verification
        ]);

        // بعد تغيير البريد: إرسال رمز تحقق تلقائياً إلى البريد الجديد
        $user->refresh();
        $verificationCode = EmailVerificationCode::generateCode($user->id, $user->email);

        try {
            if (config('mail.mailers.smtp.host') === 'localhost' || config('mail.mailers.smtp.host') === '127.0.0.1') {
                $to = $user->email;
                $subject = __('frontend.profile.email_verification_subject');
                $message = view('emails.email-verification', [
                    'code' => $verificationCode->code,
                    'userName' => $user->name
                ])->render();
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= 'From: ' . config('mail.from.name') . ' <' . config('mail.from.address') . '>' . "\r\n";

                mail($to, $subject, $message, $headers);
            } else {
                Mail::to($user->email)->send(new EmailVerificationMail($verificationCode->code, $user->name));
            }
        } catch (\Throwable $e) {
            \Log::error('Email verification send after email update failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => $e,
            ]);
        }

        return redirect()->route('profile.email')
            ->with('success', __('frontend.profile.email_updated'));
    }

    // Send Email Verification Code
    public function sendEmailVerificationCode(Request $request)
    {
        $user = Auth::user();

        // Check if email is already verified
        if ($user->email_verified_at) {
            return back()->withErrors(['error' => __('frontend.profile.email_already_verified')]);
        }

        // Check rate limiting (1 minute between requests)
        $latestCode = EmailVerificationCode::where('user_id', $user->id)
            ->where('email', $user->email)
            ->latest()
            ->first();

        if ($latestCode && $latestCode->created_at->diffInSeconds(now()) < 60) {
            $remainingSeconds = 60 - $latestCode->created_at->diffInSeconds(now());
            return back()->withErrors(['error' => __('frontend.profile.email_verification_rate_limit', ['seconds' => $remainingSeconds])]);
        }

        // Generate and save code
        $verificationCode = EmailVerificationCode::generateCode($user->id, $user->email);

        try {
            // For localhost, use mail() function directly to avoid SSL issues
            if (config('mail.mailers.smtp.host') === 'localhost' || config('mail.mailers.smtp.host') === '127.0.0.1') {
                // Use PHP mail() function directly
                $to = $user->email;
                $subject = __('frontend.profile.email_verification_subject');
                $message = view('emails.email-verification', [
                    'code' => $verificationCode->code,
                    'userName' => $user->name
                ])->render();
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= 'From: ' . config('mail.from.name') . ' <' . config('mail.from.address') . '>' . "\r\n";

                if (mail($to, $subject, $message, $headers)) {
                    return back()->with('success', __('frontend.profile.email_verification_code_sent'));
                } else {
                    throw new \Exception('mail() function failed');
                }
            }

            // Send email using Laravel Mail for other hosts
            Mail::to($user->email)->send(new EmailVerificationMail($verificationCode->code, $user->name));

            return back()->with('success', __('frontend.profile.email_verification_code_sent'));
        } catch (\Exception $e) {
            // Delete the code if email sending failed
            $verificationCode->delete();

            // Log the error for debugging
            \Log::error('Email verification send failed: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'email' => $user->email,
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);

            // Show more detailed error in debug mode
            $errorMessage = config('app.debug')
                ? __('frontend.profile.email_verification_send_failed') . ': ' . $e->getMessage()
                : __('frontend.profile.email_verification_send_failed');

            return back()->withErrors(['error' => $errorMessage]);
        }
    }

    // Verify Email Code
    public function verifyEmailCode(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'code' => 'required|string|size:6',
        ], [
            'code.required' => __('frontend.profile.verification_code_required'),
            'code.size' => __('frontend.profile.verification_code_size'),
        ]);

        // Find valid code
        $verificationCode = EmailVerificationCode::where('user_id', $user->id)
            ->where('email', $user->email)
            ->where('code', $request->code)
            ->valid()
            ->first();

        if (!$verificationCode) {
            return back()->withErrors(['code' => __('frontend.profile.verification_code_invalid')]);
        }

        // Mark code as used
        $verificationCode->update(['is_used' => true]);

        // Verify user email
        $user->update([
            'email_verified_at' => now(),
        ]);

        // Log activity
        UserActivityLog::log(
            'email_verified',
            __('frontend.profile.activity.email_verified_description'),
            $user,
            ['email' => $user->email]
        );

        return redirect()->route('profile.email')
            ->with('verified', true)
            ->with('success', __('frontend.profile.email_verified_successfully'));
    }

    public function phone()
    {
        $user = Auth::user();
        $countryCodes = \App\Models\Setting::get('country_codes', []);
        if (is_string($countryCodes)) {
            $countryCodes = json_decode($countryCodes, true) ?? [];
        }
        return view('frontend.profile.phone', compact('user', 'countryCodes'));
    }

    public function updatePhone(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'country_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
        ], [
            'phone.max' => __('frontend.profile.phone_max'),
        ]);

        $user->update([
            'country_code' => $request->country_code ?? null,
            'phone' => $request->phone,
            'phone_verified_at' => null, // Reset verification
        ]);

        return redirect()->route('profile.phone')
                       ->with('success', __('frontend.profile.phone_updated'));
    }

    public function password()
    {
        return view('frontend.profile.password');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => __('frontend.profile.current_password_required'),
            'password.required' => __('frontend.profile.password_required'),
            'password.confirmed' => __('frontend.profile.password_confirmation'),
            'password.min' => __('frontend.profile.password_min'),
        ]);

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => __('frontend.profile.invalid_current_password')]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.password')
                       ->with('success', __('frontend.profile.password_updated'));
    }

    public function accountVerification()
    {
        $user = Auth::user();
        $verificationRequirements = \App\Models\Setting::get('verification_requirements_' . app()->getLocale(), '');

        // Get latest verification request (any status)
        $latestRequest = \App\Models\VerificationRequest::where('user_id', $user->id)
            ->latest()
            ->first();

        // Get pending request
        $pendingRequest = $latestRequest && $latestRequest->status === 'pending' ? $latestRequest : null;

        // Get rejected request
        $rejectedRequest = $latestRequest && $latestRequest->status === 'rejected' ? $latestRequest : null;


        $approvedRequest = ($user->is_verified && $latestRequest && $latestRequest->status === 'approved')
            ? $latestRequest
            : null;

        return view('frontend.profile.verification', compact('user', 'verificationRequirements', 'pendingRequest', 'rejectedRequest', 'approvedRequest'));
    }

    public function submitVerificationRequest(Request $request)
    {
        $user = Auth::user();

        // Check if user is already verified
        if ($user->is_verified) {
            return back()->withErrors(['error' => __('frontend.profile.account_already_verified')]);
        }

        // Check if user already has a pending request
        $pendingRequest = \App\Models\VerificationRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($pendingRequest) {
            return back()->withErrors(['error' => __('frontend.profile.verification_request_pending')]);
        }

        $request->validate([
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|string|max:255',
            'responsible_person' => 'required|string|max:255',
            'business_address' => 'required|string|max:255',
            'business_phone' => 'required|string|max:50',
            'instagram_url' => 'nullable|string|max:255',
            'facebook_url' => 'nullable|string|max:255',
            'website_url' => 'nullable|string|max:255',
            'primary_document_type' => 'required|in:id,cr',
            'primary_document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'storefront_image' => 'nullable|file|mimes:jpg,jpeg,png|max:10240',
        ], [
            'primary_document.required' => __('frontend.profile.documents_required'),
            'primary_document.mimes' => __('frontend.profile.documents_invalid_format'),
            'primary_document.max' => __('frontend.profile.documents_max_size'),
            'storefront_image.mimes' => __('frontend.profile.documents_invalid_format'),
            'storefront_image.max' => __('frontend.profile.documents_max_size'),
        ]);

        $documents = [];
        $primaryDocumentPath = $request->file('primary_document')->store('verification_documents', 'public');
        $documents[] = $primaryDocumentPath;

        $storefrontPath = null;
        if ($request->hasFile('storefront_image')) {
            $storefrontPath = store_image_as_webp($request->file('storefront_image'), 'verification_documents');
            $documents[] = $storefrontPath;
        }

        \App\Models\VerificationRequest::create([
            'user_id' => $user->id,
            'message' => $request->message,
            'documents' => $documents,
            'business_name' => $request->business_name,
            'business_type' => $request->business_type,
            'responsible_person' => $request->responsible_person,
            'business_address' => $request->business_address,
            'business_phone' => $request->business_phone,
            'instagram_url' => $this->normalizeOptionalUrl($request->instagram_url),
            'facebook_url' => $this->normalizeOptionalUrl($request->facebook_url),
            'website_url' => $this->normalizeOptionalUrl($request->website_url),
            'primary_document_type' => $request->primary_document_type,
            'primary_document_path' => $primaryDocumentPath,
            'storefront_image_path' => $storefrontPath,
            'status' => 'pending',
        ]);

        return redirect()->route('profile.verification')
            ->with('success', __('frontend.profile.verification_request_submitted'));
    }

    public function revokeVerification(Request $request)
    {
        $user = Auth::user();

        // Check if user is verified
        if (!$user->is_verified) {
            return back()->withErrors(['error' => __('frontend.profile.account_not_verified')]);
        }

        // Revoke verification
        $user->update(['is_verified' => false]);

        return redirect()->route('profile.verification')
            ->with('success', __('frontend.profile.verification_revoked_successfully'));
    }


    public function businessProfile()
    {
        $user = Auth::user();
        if (!$user->is_verified) {
            return redirect()->route('profile.verification')
                ->withErrors(['error' => __('frontend.profile.account_not_verified')]);
        }

        return view('frontend.profile.business-profile', compact('user'));
    }

    public function updateBusinessProfile(Request $request)
    {
        $user = Auth::user();
        if (!$user->is_verified) {
            return redirect()->route('profile.verification')
                ->withErrors(['error' => __('frontend.profile.account_not_verified')]);
        }

        $request->validate([
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|string|max:255',
            'business_owner' => 'required|string|max:255',
            'business_address' => 'required|string|max:2000',
            'business_phone' => 'required|string|max:50',
            'instagram_url' => 'nullable|string|max:500',
            'facebook_url' => 'nullable|string|max:500',
            'website_url' => 'nullable|string|max:500',
            'storefront_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:10240',
        ], [
            'storefront_image.image' => __('frontend.profile.documents_invalid_format'),
            'storefront_image.mimes' => __('frontend.profile.documents_invalid_format'),
            'storefront_image.max' => __('frontend.profile.documents_max_size'),
        ]);

        $data = [
            'business_name' => $request->business_name,
            'business_type' => $request->business_type,
            'business_owner' => $request->business_owner,
            'business_address' => $request->business_address,
            'business_phone' => $request->business_phone,
            'instagram_url' => $request->filled('instagram_url') ? $request->instagram_url : null,
            'facebook_url' => $request->filled('facebook_url') ? $request->facebook_url : null,
            'website_url' => $request->filled('website_url') ? $request->website_url : null,
        ];

        if ($request->hasFile('storefront_image')) {
            if ($user->storefront_image_path && Storage::disk('public')->exists($user->storefront_image_path)) {
                Storage::disk('public')->delete($user->storefront_image_path);
            }
            $path = store_image_as_webp($request->file('storefront_image'), 'verification_documents');
            $fullPath = Storage::disk('public')->path($path);
            if (file_exists($fullPath)) {
                chmod($fullPath, 0644);
            }
            $data['storefront_image_path'] = $path;
        }

        $user->update($data);

        return redirect()->route('profile.business-profile')
            ->with('success', __('frontend.profile.business_profile_updated'));
    }

    public function security()
    {
        $user = Auth::user();
        return view('frontend.profile.security', compact('user'));
    }

    public function savedCards()
    {
        return view('frontend.profile.saved-cards');
    }

    public function activities()
    {
        $user = Auth::user();
        $activities = UserActivityLog::where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        return view('frontend.profile.activities', compact('user', 'activities'));
    }

    public function blockedUsers()
    {
        $user = Auth::user();
        $blockedUsers = $user->blockedUsers()->with('blockedUser')->latest()->get();

        return view('frontend.profile.blocked-users', compact('blockedUsers'));
    }

    public function blockUser($id)
    {
        $user = Auth::user();

        // Check if trying to block yourself
        if ($id == $user->id) {
            return back()->with('error', __('frontend.profile.cannot_block_yourself'));
        }

        // Check if already blocked
        if ($user->hasBlocked($id)) {
            return back()->with('error', __('frontend.profile.user_already_blocked'));
        }

        BlockedUser::create([
            'user_id' => $user->id,
            'blocked_user_id' => $id,
        ]);
        $user->forgetBlockedUserIdsCache();

        return back()->with('success', __('frontend.profile.user_blocked'));
    }

    public function unblockUser($id)
    {
        $user = Auth::user();
        $blockedUser = $user->blockedUsers()->where('blocked_user_id', $id)->firstOrFail();
        $blockedUser->delete();
        $user->forgetBlockedUserIdsCache();

        return back()->with('success', __('frontend.profile.user_unblocked'));
    }

    // Cancel Account
    public function cancelAccount()
    {
        $user = Auth::user();
        return view('frontend.profile.cancel-account', compact('user'));
    }

    public function submitCancelAccount(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'password' => 'required|string',
            'confirm' => 'required|accepted',
        ], [
            'password.required' => __('frontend.profile.password_required_for_cancellation'),
            'confirm.required' => __('frontend.profile.confirm_account_cancellation'),
            'confirm.accepted' => __('frontend.profile.confirm_account_cancellation'),
        ]);

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => __('frontend.profile.invalid_password')]);
        }

        // Check if account is already scheduled for deletion
        if ($user->account_status === 'pending_deletion') {
            return back()->withErrors(['error' => __('frontend.profile.account_already_scheduled')]);
        }

        // Schedule account deletion in 14 days
        $user->update([
            'scheduled_deletion_at' => now()->addDays(14),
            'account_status' => 'pending_deletion',
        ]);

        // Log activity
        ActivityLog::log('account_cancellation_scheduled', $user, [
            'scheduled_deletion_at' => $user->scheduled_deletion_at,
        ]);

        Auth::logout();

        return redirect()->route('login')
            ->with('success', __('frontend.profile.account_cancellation_scheduled'));
    }

    // Public Seller Profile
    public function showSeller($slug)
    {
        $seller = \App\Models\User::where('slug', $slug)
            ->where('is_active', true)
            ->withCount('ads')
            ->firstOrFail();

        // Get seller's active ads
        $ads = $seller->ads()
            ->where('status', 'active')
            ->with(['category', 'subcategory'])
            ->latest('published_at')
            ->paginate(12);

        // Calculate average rating
        $averageRating = $seller->average_rating;
        $ratingsCount = $seller->ratings_count;

        // Check if authenticated user has already rated this seller
        $userRating = null;
        if (Auth::check()) {
            $userRating = $seller->ratingsAsSeller()
                ->where('user_id', Auth::id())
                ->first();
        }

        return view('frontend.seller.show', compact('seller', 'ads', 'averageRating', 'ratingsCount', 'userRating'));
    }

    // Store Rating
    public function storeRating(Request $request, $slug)
    {
        $seller = \App\Models\User::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Check if user is authenticated
        if (!Auth::check()) {
            return back()->withErrors(['error' => __('frontend.seller.login_to_rate')]);
        }

        // Check if user is trying to rate themselves
        if ($seller->id === Auth::id()) {
            return back()->withErrors(['error' => __('frontend.seller.cannot_rate_yourself')]);
        }

        // Check if user has already rated this seller
        $existingRating = SellerRating::where('seller_id', $seller->id)
            ->where('user_id', Auth::id())
            ->first();

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ], [
            'rating.required' => __('frontend.seller.rating_required'),
            'rating.min' => __('frontend.seller.rating_min'),
            'rating.max' => __('frontend.seller.rating_max'),
            'comment.max' => __('frontend.seller.comment_max'),
        ]);

        if ($existingRating) {
            // Update existing rating
            $existingRating->update([
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);
            $message = __('frontend.seller.rating_updated');

            // Log activity
            UserActivityLog::log(
                'rating_updated',
                __('frontend.profile.activity.rating_updated_description', ['seller' => $seller->business_name ?? $seller->name, 'rating' => $request->rating]),
                $existingRating,
                ['seller_id' => $seller->id, 'seller_name' => $seller->business_name ?? $seller->name, 'rating' => $request->rating]
            );
        } else {
            // Create new rating
            $rating = SellerRating::create([
                'seller_id' => $seller->id,
                'user_id' => Auth::id(),
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);
            $message = __('frontend.seller.rating_submitted');

            // Log activity
            UserActivityLog::log(
                'rating_submitted',
                __('frontend.profile.activity.rating_submitted_description', ['seller' => $seller->business_name ?? $seller->name, 'rating' => $request->rating]),
                $rating,
                ['seller_id' => $seller->id, 'seller_name' => $seller->business_name ?? $seller->name, 'rating' => $request->rating]
            );
        }

        return redirect()->route('seller.show', $seller->slug)
            ->with('success', $message);
    }

    // User Ads Management
    public function adsIndex(Request $request)
    {
        $user = Auth::user();

        $query = $user->ads()->with(['category', 'subcategory'])->latest();

        // Filter by status or promo (featured/urgent)
        if ($request->filled('status') && $request->status !== 'all') {
            if ($request->status === 'featured') {
                $query->where('is_featured', true);
            } elseif ($request->status === 'urgent') {
                $query->where('is_urgent', true);
            } else {
                $query->where('status', $request->status);
            }
        }

        $ads = $query->paginate(12);

        $statusCounts = [
            'all' => $user->ads()->count(),
            'active' => $user->ads()->where('status', 'active')->count(),
            'pending' => $user->ads()->where('status', 'pending')->count(),
            'rejected' => $user->ads()->where('status', 'rejected')->count(),
            'expired' => $user->ads()->where('status', 'expired')->count(),
            'suspended' => $user->ads()->where('status', 'suspended')->count(),
            'featured' => $user->ads()->where('is_featured', true)->count(),
            'urgent' => $user->ads()->where('is_urgent', true)->count(),
        ];

        return view('frontend.profile.ads.index', compact('ads', 'statusCounts'));
    }

    public function adsSuspend($uid)
    {
        $user = Auth::user();
        $ad = $user->ads()->where('uid', $uid)->firstOrFail();
        if ($ad->status === 'suspended') {
            return redirect()->route('profile.ads.show', $ad->uid)->with('info', __('frontend.profile.ads.ad_already_suspended'));
        }
        $ad->update(['status' => 'suspended']);
        return redirect()->route('profile.ads.show', $ad->uid)->with('success', __('frontend.profile.ads.ad_suspended'));
    }

    public function adsUnsuspend($uid)
    {
        $user = Auth::user();
        $ad = $user->ads()->where('uid', $uid)->firstOrFail();
        if ($ad->status !== 'suspended') {
            return redirect()->route('profile.ads.show', $ad->uid)->with('info', __('frontend.profile.ads.ad_not_suspended'));
        }
        if (!$user->canUnsuspendAd()) {
            return redirect()->route('packages.index')->with('error', __('frontend.profile.ads.unsuspend_limit_reached'));
        }
        $ad->update(['status' => 'active', 'published_at' => now()]);
        return redirect()->route('profile.ads.show', $ad->uid)->with('success', __('frontend.profile.ads.ad_unsuspended'));
    }

    public function adsSetFeatured($uid)
    {
        $user = Auth::user();
        $ad = $user->ads()->where('uid', $uid)->firstOrFail();

        if ($ad->status !== 'active') {
            return redirect()->back()->with('error', __('frontend.profile.ads.only_active_can_promote'));
        }

        if ($ad->is_featured) {
            $ad->update(['is_featured' => false]);
            $user->releaseFeaturedQuota();
            return redirect()->back()->with('success', __('frontend.profile.ads.featured_removed'));
        }

        if (!$user->canCreateFeaturedAd()) {
            return redirect()->back()->with('error', __('frontend.ads.featured_limit_reached'));
        }

        $ad->update(['is_featured' => true]);
        $user->consumeFeaturedQuota();
        return redirect()->back()->with('success', __('frontend.profile.ads.featured_added'));
    }

    public function adsSetUrgent($uid)
    {
        $user = Auth::user();
        $ad = $user->ads()->where('uid', $uid)->firstOrFail();

        if ($ad->status !== 'active') {
            return redirect()->back()->with('error', __('frontend.profile.ads.only_active_can_promote'));
        }

        if ($ad->is_urgent) {
            $ad->update(['is_urgent' => false]);
            $user->releaseUrgentQuota();
            return redirect()->back()->with('success', __('frontend.profile.ads.urgent_removed'));
        }

        if (!$user->canCreateUrgentAd()) {
            return redirect()->back()->with('error', __('frontend.ads.urgent_limit_reached'));
        }

        $ad->update(['is_urgent' => true]);
        $user->consumeUrgentQuota();
        return redirect()->back()->with('success', __('frontend.profile.ads.urgent_added'));
    }

    public function adsDestroy($uid)
    {
        $user = Auth::user();
        $ad = $user->ads()->where('uid', $uid)->firstOrFail();
        foreach ($ad->images ?? [] as $image) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($image);
        }
        $ad->delete();
        return redirect()->route('profile.ads.index')->with('success', __('frontend.profile.ads.ad_deleted'));
    }

    public function adsShow($uid)
    {
        $user = Auth::user();
        $ad = $user->ads()->where('uid', $uid)->with(['category', 'subcategory' => function ($q) {
            $q->with('parent.parent.parent.parent');
        }])->firstOrFail();

        $canAddFeatured = $ad->status === 'active' && $user->canCreateFeaturedAd() && !$ad->is_featured;
        $canRemoveFeatured = $ad->status === 'active' && $ad->is_featured;
        $canAddUrgent = $ad->status === 'active' && $user->canCreateUrgentAd() && !$ad->is_urgent;
        $canRemoveUrgent = $ad->status === 'active' && $ad->is_urgent;
        $remainingFeatured = $user->getRemainingFeaturedAds();
        $remainingUrgent = $user->getRemainingUrgentAds();

        return view('frontend.profile.ads.show', compact(
            'ad',
            'canAddFeatured',
            'canRemoveFeatured',
            'canAddUrgent',
            'canRemoveUrgent',
            'remainingFeatured',
            'remainingUrgent'
        ));
    }

    public function adsEdit($uid)
    {
        $user = Auth::user();
        $ad = $user->ads()->where('uid', $uid)->with(['category', 'subcategory'])->firstOrFail();

        // Allow editing for all statuses - changes will need admin approval
        $category = $ad->category;
        $customFields = \App\Support\CustomFieldsResolver::resolveActiveFields($category, $ad->subcategory);

        return view('frontend.profile.ads.edit', compact('ad', 'customFields'));
    }

    public function adsUpdate(Request $request, $uid)
    {
        $user = Auth::user();
        $ad = $user->ads()->where('uid', $uid)->firstOrFail();

        // Allow editing for all statuses - changes will reset status to pending for admin approval
        $category = $ad->category;
        $subcategory = $ad->subcategory;
        $maxImages = \App\Support\AdImagesConfig::DEFAULT_USER_UPLOAD_MAX_IMAGES;
        if ($category && $subcategory) {
            $maxImages = \App\Support\AdImagesConfig::resolveMaxImages($category, $subcategory);
        }

        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|in:SYP,TRY,USD,EUR',
            'images' => 'nullable|array|max:'.$maxImages,
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'video' => 'nullable|file|mimes:mp4,mov,webm|max:'.\App\Support\AdVideoUpload::maxSizeKbForValidator(),
        ];

        // Add validation for custom fields (resolved from subcategory inheritance)
        $customFieldsStructure = [];
        foreach (\App\Support\CustomFieldsResolver::resolveActiveFields($category, $subcategory) as $field) {
            $fieldId = $field['id'];
            $fieldLabel = $field['label'][app()->getLocale()] ?? $field['id'];
            $fieldRule = '';

            if (($field['required'] ?? false) && !($field['type'] === 'checkbox' && !$request->has($fieldId))) {
                if ($field['type'] === 'location') {
                    $fieldRule .= 'required|';
                } else {
                    $fieldRule .= 'required|';
                }
            }

            if ($field['type'] === 'number' && !empty($field['show_currency'])) {
                $allowTbd = !empty($field['allow_tbd']);
                $rules[$fieldId . '.value'] = ($field['required'] ?? false) && !$allowTbd
                    ? 'required|numeric'
                    : ($allowTbd ? 'required_without:' . $fieldId . '.tbd|nullable|numeric' : 'nullable|numeric');
                if (isset($field['min'])) $rules[$fieldId . '.value'] .= '|min:' . $field['min'];
                if (isset($field['max'])) $rules[$fieldId . '.value'] .= '|max:' . $field['max'];
                $rules[$fieldId . '.currency'] = 'nullable|in:SYP,TRY,USD,EUR';
            } elseif ($field['type'] === 'number' && empty($field['show_currency'])) {
                $fieldRule .= 'numeric|';
                if (isset($field['min'])) $fieldRule .= 'min:' . $field['min'] . '|';
                if (isset($field['max'])) $fieldRule .= 'max:' . $field['max'] . '|';
            } elseif ($field['type'] === 'text' || $field['type'] === 'textarea') {
                $fieldRule .= 'string|max:255|';
            } elseif ($field['type'] === 'select' && isset($field['options'])) {
                $allowedOptions = collect($field['options'])->pluck(app()->getLocale())->filter()->implode(',');
                $fieldRule .= 'in:' . $allowedOptions . '|';
            }

            if (!empty($fieldRule)) {
                $rules[$fieldId] = rtrim($fieldRule, '|');
            }

            // Store field structure for later processing
            $customFieldsStructure[$fieldId] = $field;
        }

        $validated = $request->validate($rules, [
            'title.required' => __('frontend.ads.title_required'),
            'description.required' => __('frontend.ads.description_required'),
            'images.max' => __('frontend.ads.images_max_count', ['max' => $maxImages]),
            'images.*.image' => __('frontend.ads.images_must_be_image'),
            'images.*.mimes' => __('frontend.ads.images_invalid_format'),
            'images.*.max' => __('frontend.ads.images_max_size'),
        ]);

        // Handle images (تحويل WebP بعد الاستجابة)
        $newImages = null;
        if ($request->hasFile('images')) {
            $newImages = [];
            foreach ($request->file('images') as $image) {
                $newImages[] = store_ad_image_raw($image);
            }
        }

        if ($request->hasFile('video')) {
            $vErrs = \App\Support\AdVideoUpload::validate($request->file('video'));
            if ($vErrs !== []) {
                return back()->withErrors(['video' => $vErrs[0]])->withInput();
            }
        }

        // Process custom fields from validated data and compare with current values
        $customFieldValues = [];
        $currentCustomFields = $ad->custom_fields ?? [];

        foreach ($customFieldsStructure as $fieldId => $field) {
            // Get value from request (validated or not)
            $fieldValue = $validated[$fieldId] ?? $request->input($fieldId);
            if ($field['type'] === 'number' && !empty($field['show_currency'])) {
                if ($request->has($fieldId . '.tbd') && $request->boolean($fieldId . '.tbd')) {
                    $fieldValue = ['tbd' => true];
                } else {
                    $fieldValue = [
                        'value' => $validated[$fieldId . '.value'] ?? $request->input($fieldId . '.value'),
                        'currency' => $validated[$fieldId . '.currency'] ?? $request->input($fieldId . '.currency') ?: \App\Models\Setting::get('default_currency', 'SYP'),
                    ];
                }
            }
            $currentValue = $currentCustomFields[$fieldId] ?? null;

            // Handle different field types to get normalized values for comparison
            $normalizedNewValue = null;
            if (isset($fieldValue)) {
                if ($field['type'] === 'location' && is_array($fieldValue)) {
                    $normalizedNewValue = [
                        'latitude' => $fieldValue['latitude'] ?? null,
                        'longitude' => $fieldValue['longitude'] ?? null,
                        'address' => $fieldValue['address'] ?? null,
                    ];
                } elseif ($field['type'] === 'number' && !empty($field['show_currency']) && is_array($fieldValue)) {
                    if (!empty($fieldValue['tbd'])) {
                        $normalizedNewValue = ['tbd' => true];
                    } else {
                        $normalizedNewValue = [
                            'value' => $fieldValue['value'] ?? null,
                            'currency' => !empty($fieldValue['currency']) ? $fieldValue['currency'] : \App\Models\Setting::get('default_currency', 'SYP'),
                        ];
                    }
                } elseif ($field['type'] === 'checkbox') {
                    $normalizedNewValue = (bool) $fieldValue;
                } else {
                    $normalizedNewValue = $fieldValue;
                }
            }

            // Helper: value is effectively empty (including array with empty 'value')
            $isEffectivelyEmpty = function ($v) {
                if ($v === null || $v === '') return true;
                if (is_array($v) && array_key_exists('value', $v)) {
                    $x = $v['value'];
                    return $x === null || $x === '';
                }
                return empty($v) && $v !== 0 && $v !== '0';
            };
            $newEmpty = $isEffectivelyEmpty($normalizedNewValue);
            $currentFilled = !$isEffectivelyEmpty($currentValue);

            // Compare values - only add if changed
            // When request sent empty but ad has a value, preserve current (do not record as "deleted")
            if ($field['type'] === 'location' && is_array($normalizedNewValue) && is_array($currentValue)) {
                if (($normalizedNewValue['latitude'] ?? null) != ($currentValue['latitude'] ?? null) ||
                    ($normalizedNewValue['longitude'] ?? null) != ($currentValue['longitude'] ?? null) ||
                    ($normalizedNewValue['address'] ?? null) != ($currentValue['address'] ?? null)) {
                    $customFieldValues[$fieldId] = $normalizedNewValue;
                }
            } elseif ($normalizedNewValue != $currentValue) {
                if ($newEmpty && $currentFilled) {
                    continue; // preserve current, don't record as deleted
                }
                if ($newEmpty && !$currentFilled) {
                    continue; // both empty, no change
                }
                $customFieldValues[$fieldId] = $normalizedNewValue;
            }
        }

        if (! $user->is_verified) {
            $schema = array_values($customFieldsStructure);
            $merged = array_merge($currentCustomFields, $customFieldValues);
            $merged = \App\Support\SellerTypeField::applyLockedOwner($merged, $schema, $user);
            $locked = $merged[\App\Support\SellerTypeField::FIELD_ID] ?? null;
            if ($locked !== null) {
                $currentSeller = $currentCustomFields[\App\Support\SellerTypeField::FIELD_ID] ?? null;
                if ($currentSeller != $locked) {
                    $customFieldValues[\App\Support\SellerTypeField::FIELD_ID] = $locked;
                } else {
                    unset($customFieldValues[\App\Support\SellerTypeField::FIELD_ID]);
                }
            }
        }

        // Prepare pending changes - only include changed values
        $pendingChanges = [];

        // Compare title
        if ($validated['title'] != $ad->title) {
            $pendingChanges['title'] = $validated['title'];
        }

        // Compare description
        if ($validated['description'] != $ad->description) {
            $pendingChanges['description'] = $validated['description'];
        }

        // Compare price/currency only if they were sent (e.g. when form includes them; edit form may show only custom fields)
        if ($request->has('price') || $request->has('currency')) {
            $newPrice = $validated['price'] ?? null;
            $currentPrice = $ad->price;
            if ($newPrice != $currentPrice) {
                $pendingChanges['price'] = $newPrice;
                $pendingChanges['currency'] = $validated['currency'] ?? $ad->currency ?? 'SYP';
            } elseif ($request->has('currency') && ($validated['currency'] ?? $ad->currency ?? 'SYP') != ($ad->currency ?? 'SYP')) {
                $pendingChanges['price'] = $ad->price;
                $pendingChanges['currency'] = $validated['currency'] ?? $ad->currency ?? 'SYP';
            }
        }

        // Compare custom fields - only include if there are changes
        if (!empty($customFieldValues)) {
            $pendingChanges['custom_fields'] = $customFieldValues;
        }

        // Compare images - only include if new images were uploaded
        if ($newImages !== null && $newImages != $ad->images) {
            $pendingChanges['images'] = $newImages;
        }

        if ($request->hasFile('video')) {
            $oldPendingVideo = is_array($ad->pending_changes) ? ($ad->pending_changes['video'] ?? null) : null;
            $newVideoPath = store_ad_video_raw($request->file('video'));
            $currentVideo = trim((string) ($ad->video ?? ''));
            if ($newVideoPath !== $currentVideo) {
                $pendingChanges['video'] = $newVideoPath;
            }
            if (is_string($oldPendingVideo) && $oldPendingVideo !== '' && $oldPendingVideo !== ($pendingChanges['video'] ?? null)) {
                Storage::disk('public')->delete($oldPendingVideo);
            }
        }

        // Only save pending changes if there are actual changes
        if (!empty($pendingChanges)) {
            // Save pending changes instead of updating the ad directly
            $ad->update([
                'pending_changes' => $pendingChanges,
            ]);

            if ($newImages !== null && $newImages !== []) {
                ProcessAdImagesJob::dispatch($ad->id, $newImages)->afterResponse();
            }

            return redirect()->route('profile.ads.show', $ad->uid)
                ->with('success', __('frontend.profile.ads.changes_pending_review'));
        } else {
            // No changes detected, clear any existing pending changes
            if ($ad->pending_changes) {
                $ad->update([
                    'pending_changes' => null,
                ]);
            }

            return redirect()->route('profile.ads.show', $ad->uid)
                ->with('info', __('frontend.profile.ads.no_changes_detected'));
        }
    }

    public function adsStats($uid)
    {
        $user = Auth::user();
        $ad = $user->ads()->where('uid', $uid)->with(['category', 'subcategory'])->firstOrFail();

        // Get statistics
        $stats = [
            'views' => $ad->views_count,
            'created_at' => $ad->created_at,
            'published_at' => $ad->published_at,
            'expires_at' => $ad->expires_at ? $ad->expires_at : null,
            'status' => $ad->status,
            'is_featured' => $ad->is_featured,
            'is_urgent' => $ad->is_urgent,
        ];

        return view('frontend.profile.ads.stats', compact('ad', 'stats'));
    }

    // My Ratings
    public function ratings()
    {
        $user = Auth::user();

        // Get ratings received by the user (as a seller)
        $ratings = $user->ratingsAsSeller()
            ->with('user:id,name,avatar')
            ->latest()
            ->paginate(15);

        // Calculate average rating
        $averageRating = $user->average_rating;
        $ratingsCount = $user->ratings_count;

        return view('frontend.profile.ratings', compact('ratings', 'averageRating', 'ratingsCount'));
    }
}
