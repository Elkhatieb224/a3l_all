<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageRequest;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class PackageController extends Controller
{
    private function walletBalanceForCurrency(int $userId, string $currency): float
    {
        return (float) WalletTransaction::where('user_id', $userId)
            ->where('currency', $currency)
            ->sum('amount');
    }

    public function index(Request $request)
    {
        $token = $request->bearerToken() ?: $request->header('X-Authorization');
        if ($token && str_starts_with($token, 'Bearer ')) {
            $token = substr($token, 7);
        }
        if ($token) {
            $accessToken = PersonalAccessToken::findToken(trim($token));
            if ($accessToken && $accessToken->tokenable
                && (! $accessToken->expires_at || ! $accessToken->expires_at->isPast())) {
                Auth::setUser($accessToken->tokenable);
            }
        }

        $packages = Package::active()
            ->ordered()
            ->get();

        if ($packages->isEmpty()) {
            $packages = Package::ordered()->get();
        }

        $user = Auth::user();
        $remainingFreeAds = $user ? $user->getRemainingFreeAds() : 0;
        $freeAdsLimit = \App\Models\Setting::get('free_ads_limit', 3);

        $locale = app()->getLocale();
        $packagesArray = $packages->map(function ($package) use ($locale, $user) {
            $features = $package->features;
            if (!is_array($features)) {
                $features = is_string($features) ? [$features] : [];
            }
            $features = array_values(array_map('strval', $features));
            $canActivateNow = false;
            $walletBalance = null;
            $requiredAmount = (float) $package->price;
            $missingAmount = null;
            if ($user) {
                $walletBalance = $this->walletBalanceForCurrency($user->id, (string) $package->currency);
                $canActivateNow = $walletBalance >= $requiredAmount;
                $missingAmount = max(0, $requiredAmount - $walletBalance);
            }

            return [
                'id' => (int) $package->id,
                'name' => (string) ($package->getName($locale) ?? $package->name_ar ?? ''),
                'name_ar' => $package->name_ar,
                'name_en' => $package->name_en,
                'name_tr' => $package->name_tr,
                'description' => $package->getDescription($locale),
                'price' => (float) $package->price,
                'currency' => $package->currency,
                'formatted_price' => format_price($package->price, 2, $package->currency),
                'duration_days' => (int) $package->duration_days,
                'ads_limit' => (int) $package->ads_limit,
                'featured_ads' => (bool) $package->featured_ads,
                'urgent_ads' => (bool) $package->urgent_ads,
                'priority_support' => (bool) $package->priority_support,
                'homepage_display' => (bool) $package->homepage_display,
                'features' => $features,
                'wallet_balance' => $walletBalance,
                'required_amount' => $requiredAmount,
                'missing_amount' => $missingAmount,
                'can_activate_now' => $canActivateNow,
            ];
        })->values()->toArray();

        $activeSubscriptions = $user ? $user->activeSubscriptions()->with('package')->orderBy('expires_at')->get() : collect();
        $activeSubscription = $activeSubscriptions->last();
        $activeSubscriptionData = null;
        $activeSubscriptionsData = [];
        if ($activeSubscription && $activeSubscription->package) {
            $pkg = $activeSubscription->package;
            $activeSubscriptionData = [
                'package_id' => (int) $activeSubscription->package_id,
                'package_name' => (string) ($pkg->getName($locale) ?? $pkg->name_ar ?? $pkg->name),
                'expires_at' => $activeSubscription->expires_at->toIso8601String(),
                'ads_used' => (int) $activeSubscription->ads_used,
                'ads_limit' => (int) $user->getAdsLimit(),
                'active_packages_count' => (int) $activeSubscriptions->count(),
            ];
        }
        if ($activeSubscriptions->isNotEmpty()) {
            $activeSubscriptionsData = $activeSubscriptions->map(function ($sub) use ($locale) {
                $pkg = $sub->package;
                return [
                    'subscription_id' => (int) $sub->id,
                    'package_id' => (int) $sub->package_id,
                    'package_name' => (string) ($pkg?->getName($locale) ?? $pkg?->name_ar ?? ''),
                    'expires_at' => $sub->expires_at?->toIso8601String(),
                ];
            })->values()->toArray();
        }

        $currentPlan = null;
        if ($user) {
            $adsLimit = (int) $freeAdsLimit;
            $remainingAds = (int) $remainingFreeAds;
            $planName = __('frontend.packages.free_plan');
            $planExpiresAt = null;
            $remainingFeatured = 0;
            $featuredLimit = 0;
            $remainingUrgent = 0;
            $urgentLimit = 0;
            $featureLabels = [__('frontend.packages.free_feature_regular_ad')];

            if ($activeSubscriptions->isNotEmpty()) {
                $pkg = $activeSubscription?->package;
                $activeAdsCount = $user->getActiveAdsCount();
                $adsLimit = (int) $user->getAdsLimit();
                $remainingAds = max(0, $adsLimit - $activeAdsCount);
                $planName = $activeSubscriptions->count() > 1
                    ? __('frontend.packages.title')
                    : (string) ($pkg?->getName($locale) ?? $pkg?->name_ar ?? '');
                $latestExpiry = $user->getLatestActiveSubscriptionExpiry();
                $planExpiresAt = $latestExpiry ? $latestExpiry->toIso8601String() : null;
                $remainingFeatured = $user->getRemainingFeaturedAds();
                $remainingUrgent = $user->getRemainingUrgentAds();
                $featuredLimit = 0;
                $urgentLimit = 0;
                foreach ($activeSubscriptions as $sub) {
                    if (($sub->package->featured_ads ?? false) === true) {
                        $f = (int) ($sub->package->featured_ads_limit ?? 0);
                        if ($f <= 0) {
                            $featuredLimit = 0;
                        } elseif ($featuredLimit > 0) {
                            $featuredLimit += $f;
                        } else {
                            $featuredLimit = $f;
                        }
                    }
                    if (($sub->package->urgent_ads ?? false) === true) {
                        $u = (int) ($sub->package->urgent_ads_limit ?? 0);
                        if ($u <= 0) {
                            $urgentLimit = 0;
                        } elseif ($urgentLimit > 0) {
                            $urgentLimit += $u;
                        } else {
                            $urgentLimit = $u;
                        }
                    }
                }

                $featureLabels = [];
                $featureLabels[] = __('frontend.packages.feature_ads_limit', ['count' => $adsLimit]);
                if ($remainingFeatured > 0) {
                    $featureLabels[] = $remainingFeatured >= 999
                        ? __('frontend.packages.feature_featured_ads')
                        : __('frontend.packages.feature_featured_ads_limit', ['count' => $remainingFeatured]);
                }
                if ($remainingUrgent > 0) {
                    $featureLabels[] = $remainingUrgent >= 999
                        ? __('frontend.packages.feature_urgent_ads')
                        : __('frontend.packages.feature_urgent_ads_limit', ['count' => $remainingUrgent]);
                }
                if ($activeSubscriptions->contains(fn ($s) => (bool) ($s->package->priority_support ?? false))) {
                    $featureLabels[] = __('frontend.packages.priority_support');
                }
                if ($activeSubscriptions->contains(fn ($s) => (bool) ($s->package->homepage_display ?? false))) {
                    $featureLabels[] = __('frontend.packages.homepage_display');
                }
                foreach ($activeSubscriptions as $sub) {
                    $customFeatures = $sub->package->features ?? [];
                    foreach ($customFeatures as $f) {
                        if (!empty(trim((string) $f))) {
                            $featureLabels[] = $f;
                        }
                    }
                }
                $featureLabels = array_values(array_unique($featureLabels));
            }

            $currentPlan = [
                'plan_name' => $planName,
                'remaining_ads' => $remainingAds,
                'ads_limit' => $adsLimit,
                'remaining_featured' => $remainingFeatured,
                'featured_limit' => $featuredLimit,
                'remaining_urgent' => $remainingUrgent,
                'urgent_limit' => $urgentLimit,
                'expires_at' => $planExpiresAt,
                'features' => array_values($featureLabels),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'packages' => $packagesArray,
                'user_stats' => [
                    'remaining_free_ads' => (int) $remainingFreeAds,
                    'free_ads_limit' => (int) $freeAdsLimit,
                    'has_active_subscription' => $activeSubscriptions->isNotEmpty(),
                    'active_subscription' => $activeSubscriptionData,
                    'active_subscriptions' => $activeSubscriptionsData,
                ],
                'current_plan' => $currentPlan,
            ]
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * تفعيل الباقة مباشرة عند كفاية الرصيد، وإرجاع حالة نقص الرصيد عند عدم الكفاية.
     */
    public function request(Request $request, $id)
    {
        $user = Auth::user();
        $package = Package::active()->findOrFail($id);
        $currency = (string) ($package->currency ?? 'SYP');
        $price = (float) $package->price;
        $balance = $this->walletBalanceForCurrency($user->id, $currency);
        if ($balance < $price) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.packages.insufficient_balance'),
                'action' => 'add_balance',
                'required_amount' => $price,
                'wallet_balance' => $balance,
                'missing_amount' => max(0, $price - $balance),
            ], 422);
        }

        $subscription = DB::transaction(function () use ($user, $package, $currency, $price) {
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'starts_at' => now(),
                'expires_at' => now()->addDays($package->duration_days),
                'status' => 'active',
                'ads_used' => 0,
                'featured_ads_used' => 0,
                'urgent_ads_used' => 0,
            ]);

            Payment::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'subscription_id' => $subscription->id,
                'transaction_id' => 'PKG-' . strtoupper(Str::random(12)),
                'amount' => $price,
                'currency' => $currency,
                'payment_method' => 'wallet',
                'status' => 'completed',
                'payment_details' => [
                    'source' => 'api_auto_activate',
                ],
                'paid_at' => now(),
            ]);

            WalletTransaction::create([
                'user_id' => $user->id,
                'amount' => -$price,
                'currency' => $currency,
                'type' => WalletTransaction::TYPE_PACKAGE_PURCHASE,
                'reference_type' => 'subscription',
                'reference_id' => $subscription->id,
                'description' => __('admin.hawala.package_purchase_description', [
                    'package' => $package->name_ar ?? $package->name_en ?? $package->name,
                ]),
            ]);

            return $subscription;
        });

        return response()->json([
            'success' => true,
            'message' => __('frontend.packages.package_activated_success'),
            'data' => ['subscription_id' => $subscription->id],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function myRequests(Request $request)
    {
        $user = Auth::user();
        $requests = PackageRequest::with('package')
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        $items = $requests->getCollection()->map(function ($req) {
            $pkg = $req->package;
            return [
                'id' => $req->id,
                'package_id' => $req->package_id,
                'package_name' => $pkg ? ($pkg->name_ar ?? $pkg->name_en ?? $pkg->name) : null,
                'status' => $req->status,
                'admin_response' => $req->admin_response,
                'responded_at' => $req->responded_at?->toIso8601String(),
                'created_at' => $req->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'requests' => $items,
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    public function showRequest($id)
    {
        $req = PackageRequest::with('package')
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $pkg = $req->package;
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $req->id,
                'package_id' => $req->package_id,
                'package_name' => $pkg ? ($pkg->name_ar ?? $pkg->name_en ?? $pkg->name) : null,
                'status' => $req->status,
                'admin_response' => $req->admin_response,
                'responded_at' => $req->responded_at?->toIso8601String(),
                'created_at' => $req->created_at->toIso8601String(),
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
