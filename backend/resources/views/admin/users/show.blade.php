@extends('admin.layouts.app')

@section('title', __('admin.users.details_title'))
@section('page-title', __('admin.users.details_title'))

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">{{ session('error') }}</div>
    @endif
    <!-- User Info -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-start justify-between mb-6">
            <div class="flex items-center gap-4">
                <img src="{{ $user->avatar ? asset('storage/' . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}"
                     alt="{{ $user->name }}"
                     class="w-20 h-20 rounded-full border-4 border-secondary">
                <div>
                    <h2 class="text-2xl font-bold text-primary">{{ $user->name }}</h2>
                    <p class="text-gray-600">{{ $user->email }}</p>
                    <div class="flex items-center gap-2 mt-2">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $user->is_active ? __('admin.active') : __('admin.inactive') }}
                        </span>
                        @if($user->is_verified)
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
                                <i class="fas fa-check-circle"></i> {{ __('admin.verified') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-yellow-50 text-yellow-600 hover:bg-yellow-100 px-4 py-2 rounded-lg transition">
                        <i class="fas fa-power-off"></i>
                        {{ $user->is_active ? __('admin.disable') : __('admin.enable') }}
                    </button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">{{ __('admin.users.stats.total_ads') }}</p>
                <p class="text-2xl font-bold text-primary">{{ $user->ads_count }}</p>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">{{ __('admin.users.stats.subscriptions') }}</p>
                <p class="text-2xl font-bold text-primary">{{ $user->subscriptions_count }}</p>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
                <p class="text-sm text-gray-600 mb-1">{{ __('admin.users.stats.payments') }}</p>
                <p class="text-2xl font-bold text-primary">{{ $user->payments_count }}</p>
            </div>
        </div>

        @if($user->is_verified)
        <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h3 class="text-lg font-bold text-primary mb-4 flex items-center gap-2">
                <i class="fas fa-briefcase"></i> {{ __('admin.users.verified_business_data') }}
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-800">
                <div>
                    <p class="font-semibold text-gray-700">{{ __('admin.verification.business.name') }}</p>
                    <p>{{ $user->business_name ?? '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-700">{{ __('admin.verification.business.type') }}</p>
                    <p>{{ $user->business_type ?? '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-700">{{ __('admin.users.business_owner') }}</p>
                    <p>{{ $user->business_owner ?? '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-700">{{ __('admin.verification.business.phone') }}</p>
                    <p>{{ $user->business_phone ?? '-' }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="font-semibold text-gray-700">{{ __('admin.verification.business.address') }}</p>
                    <p>{{ $user->business_address ?? '-' }}</p>
                </div>
                <div>
                    <p class="font-semibold text-gray-700">{{ __('admin.users.contact_links') }}</p>
                    <div class="flex items-center gap-3 mt-1 text-primary">
                        @if($user->instagram_url)
                            <a href="{{ $user->instagram_url }}" target="_blank" class="text-xl hover:text-secondary"><i class="fab fa-instagram"></i></a>
                        @endif
                        @if($user->facebook_url)
                            <a href="{{ $user->facebook_url }}" target="_blank" class="text-xl hover:text-secondary"><i class="fab fa-facebook"></i></a>
                        @endif
                        @if($user->website_url)
                            <a href="{{ $user->website_url }}" target="_blank" class="text-xl hover:text-secondary"><i class="fas fa-globe"></i></a>
                        @endif
                        @if(!$user->instagram_url && !$user->facebook_url && !$user->website_url)
                            <span class="text-gray-500 text-sm">{{ __('admin.verification.contact.none') }}</span>
                        @endif
                    </div>
                </div>
                <div>
                    <p class="font-semibold text-gray-700">{{ __('admin.users.storefront_image') }}</p>
                    @if($user->storefront_image_path)
                        <a href="{{ asset('storage/' . $user->storefront_image_path) }}" target="_blank" class="block mt-2">
                            <img src="{{ asset('storage/' . $user->storefront_image_path) }}" alt="{{ __('admin.users.storefront_image') }}" class="w-full max-w-xs rounded-lg border">
                        </a>
                    @else
                        <span class="text-gray-500 text-sm">{{ __('admin.users.no_storefront') }}</span>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Current Subscription -->
    @php $activeSub = $user->activeSubscription; @endphp
    @if($activeSub)
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-4">
                <i class="fas fa-crown ml-2"></i> {{ __('admin.users.current_subscription') }}
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-blue-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">{{ __('admin.users.subscription_type') }}</p>
                    <p class="font-bold text-primary">{{ $activeSub->package->name_ar ?? $activeSub->package->name_en ?? $activeSub->package->name ?? '—' }}</p>
                </div>
                @php $activeAdsCount = $user->getActiveAdsCount(); $adsLimit = (int)($activeSub->package->ads_limit ?? 0); @endphp
                <div class="bg-green-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">{{ __('admin.users.ads_used_remaining') }}</p>
                    <p class="font-bold text-primary">{{ $activeAdsCount }} / {{ $activeSub->package->ads_limit ?? '—' }} — {{ __('admin.users.ads_remaining') }}: {{ max(0, $adsLimit - $activeAdsCount) }}</p>
                </div>
                @if($activeSub->package->featured_ads && (int)($activeSub->package->featured_ads_limit ?? 0) > 0)
                <div class="bg-yellow-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">{{ __('admin.packages.featured_ads') }} {{ __('admin.users.ads_remaining') }}</p>
                    <p class="font-bold text-yellow-700">{{ max(0, (int)($activeSub->package->featured_ads_limit ?? 0) - (int)($activeSub->featured_ads_used ?? 0)) }} / {{ $activeSub->package->featured_ads_limit }}</p>
                </div>
                @endif
                @if($activeSub->package->urgent_ads && (int)($activeSub->package->urgent_ads_limit ?? 0) > 0)
                <div class="bg-red-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">{{ __('admin.packages.urgent_ads') }} {{ __('admin.users.ads_remaining') }}</p>
                    <p class="font-bold text-red-700">{{ max(0, (int)($activeSub->package->urgent_ads_limit ?? 0) - (int)($activeSub->urgent_ads_used ?? 0)) }} / {{ $activeSub->package->urgent_ads_limit }}</p>
                </div>
                @endif
                <div class="bg-yellow-50 rounded-lg p-4">
                    <p class="text-sm text-gray-600 mb-1">{{ __('admin.users.subscription_expires_at') }}</p>
                    <p class="font-bold text-primary">{{ $activeSub->expires_at->format('Y-m-d') }}</p>
                </div>
                <div class="rounded-lg p-4 {{ $activeSub->isAdminGranted() ? 'bg-orange-50' : 'bg-purple-50' }}">
                    <p class="text-sm text-gray-600 mb-1">{{ __('admin.users.subscription_source_label') }}</p>
                    <p class="font-bold {{ $activeSub->isAdminGranted() ? 'text-orange-700' : 'text-purple-700' }}">
                        {{ $activeSub->isAdminGranted() ? __('admin.users.subscription_source_admin') : __('admin.users.subscription_source_paid') }}
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-4">
                <i class="fas fa-crown ml-2"></i> {{ __('admin.users.current_subscription') }}
            </h3>
            <p class="text-gray-500">{{ __('admin.users.current_subscription_none') }}</p>
        </div>
    @endif

    <!-- Wallet & Actions -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-xl font-bold text-primary mb-4">{{ __('admin.users.wallet_balance') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                @if(!empty($walletBalances))
                    <div class="flex flex-wrap gap-3 mb-4">
                        @foreach($walletBalances as $currency => $total)
                            <span class="inline-flex items-center px-4 py-2 rounded-lg bg-green-50 text-green-800 font-semibold">
                                {{ number_format($total, 2) }} {{ $currency }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 mb-4">{{ __('admin.users.no_wallet_balance') }}</p>
                @endif
                <form action="{{ route('admin.users.add-balance', $user->id) }}" method="POST" class="space-y-3 max-w-sm" id="form-add-balance">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.users.wallet_amount') }}</label>
                        <input type="number" name="amount" step="0.01" min="0.01" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary"
                               placeholder="0.00">
                        @error('amount')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.users.wallet_currency') }}</label>
                        <select name="currency" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                            @foreach($availableCurrencies ?? ['SYP', 'TRY', 'USD', 'EUR'] as $code)
                                <option value="{{ $code }}" {{ old('currency', array_key_first($walletBalances ?? []) ?? \App\Models\Setting::get('default_currency', 'SYP')) === $code ? 'selected' : '' }}>
                                    {{ get_currency_symbol_for_code($code) }} ({{ $code }})
                                </option>
                            @endforeach
                        </select>
                        @error('currency')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.users.wallet_note_optional') }}</label>
                        <input type="text" name="note" maxlength="500"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary"
                               placeholder="{{ __('admin.users.wallet_note_placeholder') }}">
                    </div>
                    <button type="submit" id="btn-add-balance" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition disabled:opacity-70 disabled:cursor-not-allowed" data-loading-text="{{ __('admin.users.adding_balance') }}">
                        <i class="fas fa-plus ml-1"></i> {{ __('admin.users.add_balance') }}
                    </button>
                </form>
            </div>
            <div>
                <h4 class="font-semibold text-gray-800 mb-2">{{ __('admin.users.activate_package') }}</h4>
                <p class="text-sm text-gray-500 mb-3">{{ __('admin.users.activate_package_hint') }}</p>
                <form action="{{ route('admin.users.activate-package', $user->id) }}" method="POST" class="space-y-3" id="form-activate-package">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('admin.users.select_package') }}</label>
                        <select name="package_id" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                            <option value="">{{ __('admin.users.choose_package') }}</option>
                            @foreach($packages as $pkg)
                                <option value="{{ $pkg->id }}" {{ old('package_id') == $pkg->id ? 'selected' : '' }}>
                                    {{ $pkg->name_ar ?? $pkg->name_en ?? $pkg->name ?? $pkg->id }} — {{ $pkg->price }} {{ $pkg->currency }}
                                </option>
                            @endforeach
                        </select>
                        @error('package_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" id="btn-activate-package" class="bg-primary hover:bg-primary/90 text-white px-4 py-2 rounded-lg transition disabled:opacity-70 disabled:cursor-not-allowed" data-loading-text="{{ __('admin.users.activating_package') }}">
                        <i class="fas fa-check ml-1"></i> {{ __('admin.users.activate_package_btn') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Subscriptions & Payments -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-primary">{{ __('admin.users.all_subscriptions_history') }}</h3>
                @if($user->subscriptions_count > 5)
                    @if($showAllSubscriptions ?? false)
                        <a href="{{ route('admin.users.show', $user->id) }}" class="text-sm text-primary hover:text-primary/80 font-medium flex items-center gap-1">
                            {{ __('admin.users.show_last_5_subscriptions') }}
                            <i class="fas fa-compress-alt text-xs"></i>
                        </a>
                    @else
                        <a href="{{ route('admin.users.show', $user->id) }}?subscriptions=all" class="text-sm text-primary hover:text-primary/80 font-medium flex items-center gap-1">
                            {{ __('admin.users.view_all_subscriptions') }}
                            <i class="fas fa-external-link-alt text-xs"></i>
                        </a>
                    @endif
                @endif
            </div>
            @if($user->subscriptions && $user->subscriptions->count() > 0)
                <div class="space-y-3 overflow-x-auto">
                    @foreach($user->subscriptions as $sub)
                        <div class="border border-gray-200 rounded-lg p-4 {{ $sub->status === 'active' && $sub->expires_at > now() ? 'bg-green-50 border-green-200' : 'bg-gray-50' }}">
                            <div class="flex justify-between items-start flex-wrap gap-2">
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $sub->package->name_ar ?? $sub->package->name_en ?? $sub->package->name ?? '—' }}</p>
                                    <p class="text-sm text-gray-500">
                                        {{ __('admin.users.starts_at') }}: {{ $sub->starts_at->format('Y-m-d') }}
                                        · {{ __('admin.users.expires_at') }}: {{ $sub->expires_at->format('Y-m-d') }}
                                    </p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $sub->ads_used }} / {{ $sub->package->ads_limit ?? '—' }} {{ __('admin.users.ads_used') }}</p>
                                    <span class="inline-block mt-2 px-2 py-0.5 rounded text-xs font-medium {{ $sub->isAdminGranted() ? 'bg-orange-100 text-orange-700' : 'bg-purple-100 text-purple-700' }}">
                                        {{ $sub->isAdminGranted() ? __('admin.users.subscription_source_admin') : __('admin.users.subscription_source_paid') }}
                                    </span>
                                </div>
                                <span class="px-2 py-1 rounded text-xs font-semibold
                                    {{ $sub->status === 'active' ? 'bg-green-100 text-green-700' :
                                       ($sub->status === 'cancelled' ? 'bg-red-100 text-red-700' : 'bg-gray-200 text-gray-700') }}">
                                    {{ $sub->status }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
                @if(($user->subscriptions_count > 5) && !($showAllSubscriptions ?? false))
                    <p class="text-xs text-gray-500 mt-3">{{ __('admin.users.last_5_subscriptions_only') }}</p>
                @endif
            @else
                <p class="text-gray-500 py-4">{{ __('admin.users.no_subscriptions') }}</p>
            @endif
        </div>
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-xl font-bold text-primary">{{ __('admin.users.payment_history') }}</h3>
                @if($user->payments_count > 5)
                    <a href="{{ route('admin.payments.index', ['user_id' => $user->id]) }}" class="text-sm text-primary hover:text-primary/80 font-medium flex items-center gap-1">
                        {{ __('admin.users.view_all_payments') }}
                        <i class="fas fa-external-link-alt text-xs"></i>
                    </a>
                @endif
            </div>
            @if($user->payments && $user->payments->count() > 0)
                <div class="space-y-2 overflow-x-auto">
                    @foreach($user->payments as $pay)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-0">
                            <div>
                                <p class="font-medium text-gray-800">{{ $pay->package->name_ar ?? $pay->package->name_en ?? $pay->package->name ?? '—' }}</p>
                                <p class="text-xs text-gray-500">{{ $pay->paid_at?->format('Y-m-d H:i') }} · {{ $pay->transaction_id }}</p>
                            </div>
                            <span class="font-semibold text-primary">{{ number_format($pay->amount, 0) }} {{ $pay->currency }}</span>
                        </div>
                    @endforeach
                </div>
                @if($user->payments_count > 5)
                    <p class="text-xs text-gray-500 mt-3">{{ __('admin.users.last_5_payments_only') }}</p>
                @endif
            @else
                <p class="text-gray-500 py-4">{{ __('admin.users.no_payments') }}</p>
            @endif
        </div>
    </div>

    <!-- Recent Ads -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-xl font-bold text-primary mb-6">{{ __('admin.user_ads') }}</h3>

        @if($user->ads->count() > 0)
            <div class="space-y-3">
                @foreach($user->ads as $ad)
                    <a href="{{ route('admin.ads.show', $ad->uid) }}" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-800">{{ $ad->title }}</h4>
                            <p class="text-xs text-gray-500">
                                {{ $ad->category->getName(app()->getLocale()) }} • {{ $ad->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            {{ $ad->status === 'active' ? 'bg-green-100 text-green-700' :
                               ($ad->status === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                            {{ __('admin.' . $ad->status) }}
                        </span>
                    </a>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-center py-8">{{ __('admin.no_ads') }}</p>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var formAddBalance = document.getElementById('form-add-balance');
    var formActivatePackage = document.getElementById('form-activate-package');

    if (formAddBalance) {
        formAddBalance.addEventListener('submit', function() {
            var btn = document.getElementById('btn-add-balance');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                var text = btn.getAttribute('data-loading-text') || '...';
                btn.innerHTML = '<i class="fas fa-spinner fa-spin ml-1"></i> ' + text;
            }
        });
    }

    if (formActivatePackage) {
        formActivatePackage.addEventListener('submit', function() {
            var btn = document.getElementById('btn-activate-package');
            if (btn && !btn.disabled) {
                btn.disabled = true;
                var text = btn.getAttribute('data-loading-text') || '...';
                btn.innerHTML = '<i class="fas fa-spinner fa-spin ml-1"></i> ' + text;
            }
        });
    }
});
</script>
@endsection

