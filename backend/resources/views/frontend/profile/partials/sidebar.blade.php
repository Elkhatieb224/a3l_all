<aside class="w-full lg:w-64 flex-shrink-0">
    <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 sticky top-20">
        <h2 class="text-lg sm:text-xl font-bold text-primary mb-4 sm:mb-6">
            {{ __('frontend.profile.account_info') }}
        </h2>
        
        <nav class="space-y-1">
            <!-- Personal Info -->
            <a href="{{ route('profile.personal-info') }}" 
               class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.personal-info') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                <span class="font-semibold">{{ __('frontend.profile.personal_info') }}</span>
                <i class="fas fa-chevron-left text-xs"></i>
            </a>

            <!-- Email -->
            <a href="{{ route('profile.email') }}" 
               class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.email') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                <span>{{ __('frontend.profile.email') }}</span>
                <i class="fas fa-chevron-left text-xs"></i>
            </a>

            <!-- Phone -->
            <a href="{{ route('profile.phone') }}" 
               class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.phone') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                <span>{{ __('frontend.profile.phone') }}</span>
                <i class="fas fa-chevron-left text-xs"></i>
            </a>

            <!-- Account Verification -->
            <a href="{{ route('profile.verification') }}" 
               class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.verification') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                <span>{{ __('frontend.profile.account_verification') }}</span>
                <i class="fas fa-chevron-left text-xs"></i>
            </a>

            @if(auth()->user()->is_verified)
            <a href="{{ route('profile.business-profile') }}"
               class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.business-profile') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                <span>{{ __('frontend.profile.business_profile_title') }}</span>
                <i class="fas fa-chevron-left text-xs"></i>
            </a>
            @endif

            <!-- Password -->
            <a href="{{ route('profile.password') }}" 
               class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.password') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                <span>{{ __('frontend.profile.set_password') }}</span>
                <i class="fas fa-chevron-left text-xs"></i>
            </a>

            <!-- Cancel Account -->
            <a href="{{ route('profile.cancel-account') }}" 
               class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.cancel-account') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                <span>{{ __('frontend.profile.cancel_account') }}</span>
                <i class="fas fa-chevron-left text-xs"></i>
            </a>

            <!-- Security -->
            <div class="border-t border-gray-200 mt-2 pt-2">
                <a href="{{ route('profile.security') }}" 
                   class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.security') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                    <span>{{ __('frontend.profile.security') }}</span>
                    <i class="fas fa-chevron-down text-xs"></i>
                </a>
            </div>

            <!-- Saved Cards -->
            <a href="{{ route('profile.saved-cards') }}" 
               class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.saved-cards') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                <span>{{ __('frontend.profile.saved_cards') }}</span>
                <i class="fas fa-chevron-left text-xs"></i>
            </a>

            <!-- Account Activities -->
            <div class="border-t border-gray-200 mt-2 pt-2">
                <a href="{{ route('profile.activities') }}" 
                   class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.activities') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition mb-2">
                    <span>{{ __('frontend.profile.account_activities') }}</span>
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>
                
                <!-- Activity Log Section -->
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <h3 class="text-sm font-semibold text-gray-700 mb-2 px-2">
                        {{ __('frontend.profile.recent_activities') }}
                    </h3>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @forelse($recentActivities ?? [] as $activity)
                            <div class="px-2 py-2 rounded-lg hover:bg-gray-50 transition">
                                <div class="flex items-start gap-2">
                                    <i class="fas fa-circle text-xs mt-1.5 {{ $activity->action === 'login' ? 'text-green-500' : ($activity->action === 'logout' ? 'text-red-500' : 'text-blue-500') }}"></i>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs text-gray-700 truncate">
                                            {{ $activity->description ?? __('frontend.profile.activity.' . $activity->action) }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ $activity->created_at->diffForHumans() }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-2 py-2 text-center">
                                <p class="text-xs text-gray-400">{{ __('frontend.profile.no_activities') }}</p>
                            </div>
                        @endforelse
                    </div>
                    @if(isset($recentActivities) && $recentActivities->count() > 0)
                        <a href="{{ route('profile.activities') }}" class="block mt-2 text-xs text-primary hover:underline text-center px-2">
                            {{ __('frontend.profile.view_all_activities') }}
                        </a>
                    @endif
                </div>
            </div>

            <!-- Blocked Users -->
            <a href="{{ route('profile.blocked-users') }}" 
               class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.blocked-users') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                <span>{{ __('frontend.profile.blocked_users') }}</span>
                <i class="fas fa-chevron-left text-xs"></i>
            </a>

            <!-- My Ads -->
            <div class="border-t border-gray-200 mt-2 pt-2">
                <a href="{{ route('profile.ads.index') }}" 
                   class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.ads.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                    <span>{{ __('frontend.profile.my_ads') }}</span>
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>
            </div>

            <!-- My Ratings -->
            <div class="border-t border-gray-200 mt-2 pt-2">
                <a href="{{ route('profile.ratings') }}" 
                   class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.ratings') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                    <span>
                        <i class="fas fa-star ml-2"></i>
                        {{ __('frontend.profile.my_ratings') }}
                    </span>
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>
            </div>

            <!-- Wallet & Hawala -->
            <div class="border-t border-gray-200 mt-2 pt-2">
                <a href="{{ route('profile.hawala.index') }}" 
                   class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.hawala.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                    <span>
                        <i class="fas fa-wallet ml-2"></i>
                        {{ __('frontend.hawala.wallet') }}
                    </span>
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>
            </div>

            <!-- Packages -->
            <div class="border-t border-gray-200 mt-2 pt-2">
                <a href="{{ route('packages.index') }}" 
                   class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('packages.index') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                    <span>
                        <i class="fas fa-box ml-2"></i>
                        {{ __('frontend.packages.title') }}
                    </span>
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>
                <a href="{{ route('profile.package-requests.index') }}" 
                   class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.package-requests.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                    <span>
                        <i class="fas fa-clipboard-list ml-2"></i>
                        {{ __('frontend.packages.my_requests') }}
                    </span>
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>
            </div>

            <!-- Favorites -->
            <a href="{{ route('favorites.index') }}" 
               class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('favorites.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                <span>{{ __('frontend.favorites.my_favorites') }}</span>
                <i class="fas fa-chevron-left text-xs"></i>
            </a>

            <!-- Messages -->
            <a href="{{ route('messages.index') }}" 
               class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('messages.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                <span>{{ __('frontend.messages.my_messages') }}</span>
                <i class="fas fa-chevron-left text-xs"></i>
            </a>

            <!-- Saved Searches -->
            <a href="{{ route('profile.saved-searches.index') }}"
               class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.saved-searches.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                <span>{{ __('frontend.saved_searches.title') }}</span>
                <i class="fas fa-chevron-left text-xs"></i>
            </a>

            <!-- Reports -->
            <div class="border-t border-gray-200 mt-2 pt-2">
                <a href="{{ route('profile.reports.index') }}" 
                   class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.reports.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                    <span>{{ __('frontend.profile.reports') }}</span>
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>
            </div>

            <!-- Support Messages -->
            <div class="border-t border-gray-200 mt-2 pt-2">
                <a href="{{ route('profile.support-messages.index') }}" 
                   class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('profile.support-messages.*') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                    <span>{{ __('frontend.help.my_support_messages') }}</span>
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>
            </div>

            <!-- Negotiations -->
            <div class="border-t border-gray-200 mt-2 pt-2">
                <a href="{{ route('negotiations.sent') }}" 
                   class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('negotiations.sent') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                    <span>{{ __('frontend.negotiations.sent_requests') }}</span>
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>
                <a href="{{ route('negotiations.received') }}" 
                   class="flex items-center justify-between p-3 rounded-lg {{ request()->routeIs('negotiations.received') ? 'bg-primary text-white' : 'text-gray-700 hover:bg-gray-50' }} transition">
                    <span>{{ __('frontend.negotiations.received_requests') }}</span>
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>
            </div>
        </nav>
    </div>
</aside>

