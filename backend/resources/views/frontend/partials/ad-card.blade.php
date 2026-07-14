<a href="{{ route('ads.show', $ad->uid) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md hover:border-secondary transition group">
    <!-- Image -->
    <div class="relative h-40 bg-gray-200 overflow-hidden">
        @php
            $images = is_array($ad->images) ? $ad->images : (is_string($ad->images) ? json_decode($ad->images, true) : []);
            $images = $images ?? [];
            $firstImage = !empty($images) && is_array($images) ? $images[0] : null;
            $firstImagePath = is_string($firstImage) ? $firstImage : (is_array($firstImage) ? ($firstImage['path'] ?? $firstImage) : '');
        @endphp
        @if($firstImagePath)
            <img src="{{ asset('storage/' . $firstImagePath) }}"
                 alt="{{ $ad->title }}"
                 class="w-full h-full object-contain group-hover:scale-110 transition duration-300"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="w-full h-full hidden items-center justify-center bg-gray-100">
                <i class="fas fa-image text-gray-400 text-4xl"></i>
            </div>
        @else
            <div class="w-full h-full flex items-center justify-center bg-gray-100">
                <i class="fas fa-image text-gray-400 text-4xl"></i>
            </div>
        @endif

        <!-- Badges -->
        <div class="absolute top-2 {{ app()->getLocale() === 'ar' ? 'right-2' : 'left-2' }} flex flex-col gap-2">
            @if($ad->is_featured)
                <span class="bg-gradient-to-r from-yellow-400 to-amber-500 text-white px-2 py-1 rounded text-xs font-bold shadow-sm ring-1 ring-amber-300/70 inline-flex items-center gap-1">
                    <img src="{{ asset('assets/badges/featured_badge.svg') }}" alt="{{ __('frontend.ads.featured') }}" class="w-4 h-4">
                    {{ __('frontend.ads.featured') }}
                </span>
            @endif
            @if($ad->is_urgent)
                <span class="bg-red-500 text-white px-2 py-1 rounded text-xs font-bold inline-flex items-center gap-1">
                    <img src="{{ asset('assets/badges/urgent_badge.svg') }}" alt="{{ __('frontend.ads.urgent') }}" class="w-4 h-4">
                    {{ __('frontend.ads.urgent') }}
                </span>
            @endif
        </div>
    </div>

    <!-- Content -->
    <div class="p-3">
        <h3 class="font-semibold text-gray-800 mb-1 line-clamp-2 group-hover:text-primary transition text-sm leading-tight">
            {{ $ad->title }}
        </h3>

        @if($ad->display_price)
            <div class="text-lg font-bold text-primary mb-1">
                {{ $ad->display_price }}
            </div>
        @endif

        @php
            $cardLat = null;
            $cardLng = null;
            if ($ad->latitude !== null && $ad->longitude !== null) {
                $cla = (float) $ad->latitude;
                $cln = (float) $ad->longitude;
                if (is_finite($cla) && is_finite($cln)) {
                    $cardLat = $cla;
                    $cardLng = $cln;
                }
            }
            if ($cardLat === null && is_array($ad->custom_fields ?? null)) {
                foreach ($ad->custom_fields as $cv) {
                    if (!is_array($cv)) {
                        continue;
                    }
                    $clat = $cv['lat'] ?? $cv['latitude'] ?? null;
                    $clng = $cv['lng'] ?? $cv['longitude'] ?? null;
                    if ($clat === null || $clng === null) {
                        continue;
                    }
                    $cla = (float) $clat;
                    $cln = (float) $clng;
                    if (is_finite($cla) && is_finite($cln)) {
                        $cardLat = $cla;
                        $cardLng = $cln;
                        break;
                    }
                }
            }
            $cardShowLoc = (bool) ($ad->show_location ?? true);
            $staticLocLabel = '';
            if ($cardShowLoc) {
                $parts = array_values(array_filter([
                    $ad->location_state ?? null,
                    $ad->location_city ?? null,
                    $ad->location_district ?? null,
                ], fn ($s) => is_string($s) && trim($s) !== ''));
                $staticLocLabel = $parts !== [] ? implode(' · ', $parts) : '';
            }
        @endphp
        @if($cardShowLoc && $cardLat !== null && $cardLng !== null)
            <div class="flex items-center gap-1 text-xs text-gray-600 mb-1 js-ad-card-loc"
                 data-lat="{{ $cardLat }}"
                 data-lng="{{ $cardLng }}"
                 data-fallback="{{ e(__('frontend.ads.location_on_map_short')) }}">
                <i class="fas fa-map-marker-alt text-gray-400 shrink-0"></i>
                <span class="truncate js-ad-card-loc-text">{{ __('frontend.ads.resolving_location') }}</span>
            </div>
        @elseif($cardShowLoc && $staticLocLabel !== '')
            <div class="flex items-center gap-1 text-xs text-gray-600 mb-1">
                <i class="fas fa-map-marker-alt text-gray-400"></i>
                <span class="truncate">{{ $staticLocLabel }}</span>
            </div>
        @endif

        <!-- Meta -->
        <div class="flex items-center justify-between text-xs text-gray-500 pt-2 border-t border-gray-100">
            <span class="flex items-center gap-1">
                <i class="fas fa-eye text-gray-400"></i>
                {{ $ad->views_count }}
            </span>
            @if($ad->published_at)
                <span class="text-gray-400">
                    {{ $ad->published_at->diffForHumans() }}
                </span>
            @endif
        </div>
    </div>
</a>

@include('frontend.partials.nominatim-reverse-geocode')

