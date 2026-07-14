@extends('frontend.layouts.app')

@section('title', $seller->business_name ?? $seller->name)

@section('content')
<div class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-2 sm:px-4">
        @php
            $displayName = $seller->business_name ?? $seller->name;
            $displayType = $seller->business_type ?? null;
            $displayPhone = $seller->business_phone ?? $seller->phone;
            $whatsAppNumber = $displayPhone ? preg_replace('/[^0-9+]/', '', $displayPhone) : null;
        @endphp

        @if($seller->storefront_image_path)
            <div class="mb-4 rounded-2xl overflow-hidden shadow">
                <img src="{{ asset('storage/' . $seller->storefront_image_path) }}" alt="{{ $displayName }}" class="w-full h-48 sm:h-64 object-cover">
            </div>
        @endif

        <!-- Seller Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-6">
                <!-- Avatar -->
                <div class="flex-shrink-0">
                    @if($seller->avatar)
                        <img src="{{ asset('storage/' . $seller->avatar) }}" 
                             alt="{{ $displayName }}"
                             class="w-24 h-24 sm:w-32 sm:h-32 rounded-full object-cover border-4 border-primary">
                    @else
                        <div class="w-24 h-24 sm:w-32 sm:h-32 rounded-full bg-primary flex items-center justify-center border-4 border-primary">
                            <span class="text-white text-3xl sm:text-4xl font-bold">
                                {{ strtoupper(substr($displayName, 0, 1)) }}
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Seller Info -->
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-1">
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">{{ $displayName }}</h1>
                        @if($seller->is_verified)
                            <span class="px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-sm font-semibold flex items-center gap-1">
                                <i class="fas fa-check-circle"></i>
                                {{ __('frontend.profile.verified') }}
                            </span>
                        @endif
                    </div>
                    @if($displayType)
                        <p class="text-gray-600 text-sm sm:text-base mb-3">{{ $displayType }}</p>
                    @endif

                    @if($seller->bio)
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-gray-700 mb-2">{{ __('frontend.profile.bio') }}:</h3>
                            <p class="text-gray-600 leading-relaxed">{{ $seller->bio }}</p>
                        </div>
                    @endif

                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600">
                        @if($seller->business_address ?? $seller->location_city)
                            <div class="flex items-center gap-1">
                                <i class="fas fa-map-marker-alt text-primary"></i>
                                <span>
                                    {{ $seller->business_address ?? $seller->location_city }}
                                    @if(!$seller->business_address && $seller->location_country)
                                        {{ ', ' . ($seller->location_country === 'SY' ? __('frontend.profile.syria') : __('frontend.profile.turkey')) }}
                                    @endif
                                </span>
                            </div>
                        @endif
                        <div class="flex items-center gap-1">
                            <i class="fas fa-bullhorn text-primary"></i>
                            <span>{{ $seller->ads_count }} {{ __('frontend.ads.total_ads') }}</span>
                        </div>
                        @if($seller->created_at)
                            <div class="flex items-center gap-1">
                                <i class="fas fa-calendar text-primary"></i>
                                <span>{{ __('frontend.seller.member_since') }} {{ $seller->created_at->format('Y') }}</span>
                            </div>
                        @endif
                        <!-- Rating Display -->
                        @if($ratingsCount > 0)
                            <div class="flex items-center gap-1">
                                <i class="fas fa-star text-yellow-400"></i>
                                <span class="font-semibold">{{ number_format($averageRating, 1) }}</span>
                                <span class="text-gray-500">({{ $ratingsCount }} {{ __('frontend.seller.ratings') }})</span>
                            </div>
                        @endif
                    </div>

                    <!-- Contact Actions -->
                    <div class="flex flex-wrap gap-3 mt-4">
                        @if($displayPhone)
                            <a href="tel:{{ $displayPhone }}" 
                               class="text-blue-600 hover:text-blue-700 px-4 py-2 rounded-lg text-sm flex items-center justify-center transition" 
                               title="{{ $displayPhone }}">
                                <i class="fas fa-phone text-lg"></i>
                            </a>
                            @if($whatsAppNumber)
                                <a href="https://wa.me/{{ ltrim($whatsAppNumber, '+') }}" target="_blank" class="bg-green-500 text-white px-4 py-2 rounded-lg text-sm flex items-center gap-2 hover:bg-green-600 transition">
                                    <i class="fab fa-whatsapp"></i>
                                    واتساب
                                </a>
                            @endif
                        @endif

                        @auth
                            @if($seller->id !== Auth::id())
                                <form action="{{ route('messages.create.seller', $seller->slug) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="btn-primary px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                                        <i class="fas fa-comments"></i>
                                        {{ __('frontend.messages.chat_with_seller') }}
                                    </button>
                                </form>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn-primary px-4 py-2 rounded-lg text-sm flex items-center gap-2">
                                <i class="fas fa-comments"></i>
                                {{ __('frontend.messages.chat_with_seller') }}
                            </a>
                        @endauth
                    </div>

                    <!-- Social Links -->
                    @if($seller->instagram_url || $seller->facebook_url || $seller->website_url)
                        <div class="flex items-center gap-3 mt-4 text-primary">
                            @if($seller->instagram_url)
                                <a href="{{ $seller->instagram_url }}" target="_blank" class="text-xl hover:text-secondary">
                                    <i class="fab fa-instagram"></i>
                                </a>
                            @endif
                            @if($seller->facebook_url)
                                <a href="{{ $seller->facebook_url }}" target="_blank" class="text-xl hover:text-secondary">
                                    <i class="fab fa-facebook"></i>
                                </a>
                            @endif
                            @if($seller->website_url)
                                <a href="{{ $seller->website_url }}" target="_blank" class="text-xl hover:text-secondary">
                                    <i class="fas fa-globe"></i>
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Rating Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-6">
                {{ __('frontend.seller.ratings') }}
                @if($ratingsCount > 0)
                    <span class="text-lg font-normal text-gray-600">({{ number_format($averageRating, 1) }}/5)</span>
                @endif
            </h2>

            <!-- Rating Stars Display -->
            @if($ratingsCount > 0)
                <div class="mb-6 flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= floor($averageRating))
                                <i class="fas fa-star text-yellow-400 text-2xl"></i>
                            @elseif($i - 0.5 <= $averageRating)
                                <i class="fas fa-star-half-alt text-yellow-400 text-2xl"></i>
                            @else
                                <i class="far fa-star text-gray-300 text-2xl"></i>
                            @endif
                        @endfor
                    </div>
                    <div class="text-gray-600">
                        <span class="font-semibold text-lg">{{ number_format($averageRating, 1) }}</span>
                        <span class="text-sm">/ 5.0</span>
                        <span class="text-sm">({{ $ratingsCount }} {{ __('frontend.seller.ratings_count') }})</span>
                    </div>
                </div>
            @else
                <div class="mb-6 text-center py-8 text-gray-500">
                    <i class="fas fa-star text-gray-300 text-4xl mb-2"></i>
                    <p>{{ __('frontend.seller.no_ratings_yet') }}</p>
                </div>
            @endif

            <!-- Rate Seller Form (for authenticated users) -->
            @auth
                @if($seller->id !== Auth::id())
                    <div class="border-t pt-6 mb-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            @if($userRating)
                                {{ __('frontend.seller.update_rating') }}
                            @else
                                {{ __('frontend.seller.rate_seller') }}
                            @endif
                        </h3>
                        <form action="{{ route('seller.rate', $seller->slug) }}" method="POST" class="space-y-4">
                            @csrf
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('frontend.seller.your_rating') }}
                                </label>
                                <div class="flex items-center gap-2" id="rating-stars">
                                    @for($i = 1; $i <= 5; $i++)
                                        <button type="button" 
                                                class="star-rating-btn text-2xl focus:outline-none transition"
                                                data-rating="{{ $i }}">
                                            <i class="far fa-star text-gray-300 hover:text-yellow-400"></i>
                                        </button>
                                    @endfor
                                </div>
                                <input type="hidden" name="rating" id="rating-input" value="{{ $userRating ? $userRating->rating : '' }}" required>
                                @error('rating')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="comment" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ __('frontend.seller.comment') }} ({{ __('frontend.seller.optional') }})
                                </label>
                                <textarea name="comment" 
                                          id="comment" 
                                          rows="3" 
                                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                          placeholder="{{ __('frontend.seller.comment_placeholder') }}">{{ $userRating ? $userRating->comment : '' }}</textarea>
                                @error('comment')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                                {{ $userRating ? __('frontend.seller.update_rating') : __('frontend.seller.submit_rating') }}
                            </button>
                        </form>
                    </div>
                @endif
            @else
                <div class="border-t pt-6 text-center">
                    <p class="text-gray-600 mb-4">{{ __('frontend.seller.login_to_rate') }}</p>
                    <a href="{{ route('login') }}" class="btn-primary inline-block px-6 py-2 rounded-lg">
                        {{ __('frontend.nav.login') }}
                    </a>
                </div>
            @endauth
        </div>

        <!-- Seller's Ads -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-6">
                {{ __('frontend.seller.seller_ads') }} ({{ $ads->total() }})
            </h2>

            @if($ads->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-6">
                    @foreach($ads as $ad)
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition">
                            <!-- Image -->
                            <div class="relative h-40 bg-gray-200">
                                <a href="{{ route('ads.show', $ad->uid) }}">
                                    @php
                                        $images = is_array($ad->images) ? $ad->images : (is_string($ad->images) ? json_decode($ad->images, true) : []);
                                        $images = $images ?? [];
                                        $firstImage = !empty($images) && is_array($images) ? $images[0] : null;
                                        $firstImagePath = is_string($firstImage) ? $firstImage : (is_array($firstImage) ? ($firstImage['path'] ?? $firstImage) : '');
                                    @endphp
                                    @if($firstImagePath)
                                        <img src="{{ asset('storage/' . $firstImagePath) }}"
                                             alt="{{ $ad->title }}"
                                             class="w-full h-full object-cover"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="w-full h-full hidden items-center justify-center bg-gray-100">
                                            <i class="fas fa-image text-gray-400 text-4xl"></i>
                                        </div>
                                    @else
                                        <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                            <i class="fas fa-image text-gray-400 text-4xl"></i>
                                        </div>
                                    @endif
                                </a>
                                @if($ad->is_featured)
                                    <span class="absolute top-2 {{ app()->getLocale() === 'ar' ? 'right-2' : 'left-2' }} bg-gradient-to-r from-yellow-400 to-amber-500 text-white px-2 py-1 rounded text-xs font-bold shadow-sm ring-1 ring-amber-300/70 inline-flex items-center gap-1">
                                        <img src="{{ asset('assets/badges/featured_badge.svg') }}" alt="{{ __('frontend.ads.featured') }}" class="w-4 h-4">
                                        {{ __('frontend.ads.featured') }}
                                    </span>
                                @endif
                            </div>

                            <!-- Content -->
                            <div class="p-4">
                                <a href="{{ route('ads.show', $ad->uid) }}">
                                    <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2 hover:text-primary transition">{{ $ad->title }}</h3>
                                </a>

                                @if($ad->display_price)
                                    <div class="text-lg font-bold text-primary mb-2">
                                        {{ $ad->display_price }}
                                    </div>
                                @endif

                                <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                                    <span><i class="fas fa-eye ml-1"></i> {{ $ad->views_count }}</span>
                                    <span>{{ $ad->created_at->diffForHumans() }}</span>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('ads.show', $ad->uid) }}"
                                       class="flex-1 text-center bg-blue-50 text-blue-600 hover:bg-blue-100 py-2 rounded-lg text-sm transition">
                                        <i class="fas fa-eye"></i> {{ __('frontend.view') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $ads->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-bullhorn text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg">{{ __('frontend.seller.no_ads') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ratingStars = document.querySelectorAll('.star-rating-btn');
    const ratingInput = document.getElementById('rating-input');
    
    if (ratingStars.length > 0 && ratingInput) {
        let currentRating = parseInt(ratingInput.value) || 0;
        
        // Set initial rating if exists
        if (currentRating > 0) {
            updateStarsDisplay(currentRating);
        }
        
        ratingStars.forEach((star, index) => {
            const rating = index + 1;
            
            star.addEventListener('click', function() {
                currentRating = rating;
                ratingInput.value = rating;
                updateStarsDisplay(rating);
            });
            
            star.addEventListener('mouseenter', function() {
                if (!star.classList.contains('active')) {
                    updateStarsDisplay(rating, true);
                }
            });
        });
        
        // Reset on mouse leave
        const ratingContainer = document.getElementById('rating-stars');
        if (ratingContainer) {
            ratingContainer.addEventListener('mouseleave', function() {
                updateStarsDisplay(currentRating);
            });
        }
        
        function updateStarsDisplay(rating, hover = false) {
            ratingStars.forEach((star, index) => {
                const starIcon = star.querySelector('i');
                if (index < rating) {
                    starIcon.classList.remove('far', 'fa-star');
                    starIcon.classList.add('fas', 'fa-star', 'text-yellow-400');
                    if (!hover) {
                        star.classList.add('active');
                    }
                } else {
                    starIcon.classList.remove('fas', 'fa-star', 'text-yellow-400');
                    starIcon.classList.add('far', 'fa-star', 'text-gray-300');
                    star.classList.remove('active');
                }
            });
        }
    }
});
</script>
@endpush
@endsection

