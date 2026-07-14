<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->only(['requestPackage']);
    }

    public function index()
    {
        $packages = Package::active()
            ->ordered()
            ->get();

        $user = Auth::user();
        $remainingFreeAds = $user ? $user->getRemainingFreeAds() : 0;
        $freeAdsLimit = (int) \App\Models\Setting::get('free_ads_limit', 3);

        $activeSubscription = null;
        $activeSubscriptions = collect();
        $walletBalances = [];
        $packagePurchaseMeta = [];
        $remainingAds = null;
        $adsLimit = null;
        $currentPlanFeatures = [];
        $currentPlanName = null;
        $planExpiresAt = null;
        $remainingFeatured = 0;
        $remainingUrgent = 0;
        $featuredLimit = 0;
        $urgentLimit = 0;

        if ($user) {
            $walletBalances = WalletTransaction::where('user_id', $user->id)
                ->selectRaw('currency, SUM(amount) as total')
                ->groupBy('currency')
                ->pluck('total', 'currency')
                ->map(fn ($v) => (float) $v)
                ->toArray();

            $activeSubscriptions = $user->activeSubscriptions()->with('package')->orderBy('expires_at')->get();
            $activeSubscription = $activeSubscriptions->last();

            if ($activeSubscriptions->isNotEmpty()) {
                $pkg = $activeSubscription?->package;
                $activeAdsCount = $user->getActiveAdsCount();
                $adsLimit = (int) $user->getAdsLimit();
                $remainingAds = max(0, $adsLimit - $activeAdsCount);
                $currentPlanName = $activeSubscriptions->count() > 1
                    ? __('frontend.packages.title')
                    : ($pkg?->getName(app()->getLocale()) ?? __('frontend.packages.title'));
                $planExpiresAt = $user->getLatestActiveSubscriptionExpiry();
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

                $currentPlanFeatures = [];
                $currentPlanFeatures[] = 'feature_ads_limit';
                if ($remainingFeatured > 0) {
                    $currentPlanFeatures[] = $remainingFeatured >= 999
                        ? 'feature_featured_ads'
                        : ['feature_featured_ads_limit', $remainingFeatured];
                }
                if ($remainingUrgent > 0) {
                    $currentPlanFeatures[] = $remainingUrgent >= 999
                        ? 'feature_urgent_ads'
                        : ['feature_urgent_ads_limit', $remainingUrgent];
                }
                if ($activeSubscriptions->contains(fn ($s) => (bool) ($s->package->priority_support ?? false))) {
                    $currentPlanFeatures[] = 'feature_priority_support';
                }
                if ($activeSubscriptions->contains(fn ($s) => (bool) ($s->package->homepage_display ?? false))) {
                    $currentPlanFeatures[] = 'feature_homepage_display';
                }
                foreach ($activeSubscriptions as $sub) {
                    $customFeatures = $sub->package->features ?? [];
                    foreach ($customFeatures as $f) {
                        if (!empty(trim((string) $f))) {
                            $currentPlanFeatures[] = $f;
                        }
                    }
                }
                $currentPlanFeatures = array_values(array_unique($currentPlanFeatures, SORT_REGULAR));
            } else {
                $remainingAds = $remainingFreeAds;
                $adsLimit = $freeAdsLimit;
                $currentPlanFeatures = ['free_feature_regular_ad'];
                $currentPlanName = __('frontend.packages.free_plan');
                $planExpiresAt = null;
            }

            foreach ($packages as $pkg) {
                $currency = (string) ($pkg->currency ?? 'SYP');
                $balance = (float) ($walletBalances[$currency] ?? 0);
                $required = (float) $pkg->price;
                $packagePurchaseMeta[(int) $pkg->id] = [
                    'wallet_balance' => $balance,
                    'required_amount' => $required,
                    'missing_amount' => max(0, $required - $balance),
                    'can_activate_now' => $balance >= $required,
                ];
            }
        }

        return view('frontend.packages.index', compact(
            'packages',
            'remainingFreeAds',
            'freeAdsLimit',
            'activeSubscription',
            'remainingAds',
            'adsLimit',
            'currentPlanFeatures',
            'currentPlanName',
            'planExpiresAt',
            'remainingFeatured',
            'remainingUrgent',
            'featuredLimit',
            'urgentLimit',
            'activeSubscriptions',
            'packagePurchaseMeta'
        ));
    }

    /**
     * تفعيل الباقة مباشرة عند كفاية الرصيد، وإرجاع رسالة شحن رصيد عند عدم الكفاية.
     */
    public function requestPackage($id)
    {
        $user = Auth::user();
        $package = Package::active()->findOrFail($id);
        $currency = (string) ($package->currency ?? 'SYP');
        $price = (float) $package->price;
        $balance = (float) WalletTransaction::where('user_id', $user->id)
            ->where('currency', $currency)
            ->sum('amount');
        if ($balance < $price) {
            return redirect()->route('packages.index')
                ->with('error', __('frontend.packages.insufficient_balance'));
        }

        DB::transaction(function () use ($user, $package, $currency, $price) {
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
                    'source' => 'web_auto_activate',
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
        });

        return redirect()->route('packages.index')
            ->with('success', __('frontend.packages.package_activated_success'));
    }
}
