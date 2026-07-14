@extends('frontend.layouts.app')

@section('title', $ad->title)

@section('content')
@php
    $adMapLat = null;
    $adMapLng = null;
    if ($ad->latitude !== null && $ad->longitude !== null) {
        $la = (float) $ad->latitude;
        $ln = (float) $ad->longitude;
        if (is_finite($la) && is_finite($ln)) {
            $adMapLat = $la;
            $adMapLng = $ln;
        }
    }
    if ($adMapLat === null && is_array($ad->custom_fields ?? null)) {
        foreach ($ad->custom_fields as $val) {
            if (!is_array($val)) {
                continue;
            }
            $lat = $val['lat'] ?? $val['latitude'] ?? null;
            $lng = $val['lng'] ?? $val['longitude'] ?? null;
            if ($lat === null || $lng === null) {
                continue;
            }
            $la = (float) $lat;
            $ln = (float) $lng;
            if (is_finite($la) && is_finite($ln)) {
                $adMapLat = $la;
                $adMapLng = $ln;
                break;
            }
        }
    }
    $adHasMapPin = $adMapLat !== null && $adMapLng !== null;
@endphp
<div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
    <nav class="mb-4 sm:mb-6 text-xs sm:text-sm" aria-label="breadcrumb">
        <div class="flex flex-wrap items-center gap-x-1 gap-y-2 text-gray-600">
            <a href="{{ route('home') }}" class="hover:text-primary shrink-0">{{ __('frontend.nav.home') }}</a>
            <span class="text-gray-400 select-none" aria-hidden="true">›</span>
            @include('frontend.partials.ad-category-path-links', [
                'ad' => $ad,
                'linkClass' => 'hover:text-primary font-medium break-words text-gray-700',
            ])
        </div>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Main Content -->
        <main class="lg:col-span-2">
            <!-- Ad Header -->
            <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg border border-gray-100 p-4 sm:p-6 mb-4 sm:mb-6">
                <div class="flex flex-col sm:flex-row items-start justify-between gap-3 sm:gap-0 mb-4">
                    <div class="flex-1 w-full">
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            @if($ad->is_featured)
                                <span class="bg-gradient-to-r from-yellow-400 to-yellow-500 text-white px-3 py-1.5 rounded-full text-xs sm:text-sm font-bold shadow-md flex items-center gap-1.5">
                                    <img src="{{ asset('assets/badges/featured_badge.svg') }}" alt="{{ __('frontend.ads.featured') }}" class="w-4 h-4">
                                    {{ __('frontend.ads.featured') }}
                                </span>
                            @endif
                            @if($ad->is_urgent)
                                <span class="bg-gradient-to-r from-red-500 to-red-600 text-white px-3 py-1.5 rounded-full text-xs sm:text-sm font-bold shadow-md flex items-center gap-1.5 animate-pulse">
                                    <img src="{{ asset('assets/badges/urgent_badge.svg') }}" alt="{{ __('frontend.ads.urgent') }}" class="w-4 h-4">
                                    {{ __('frontend.ads.urgent') }}
                                </span>
                            @endif
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-900 mb-3 leading-tight">{{ $ad->title }}</h1>
                                @if($ad->display_price)
                                    <div class="flex items-baseline gap-2 mb-2">
                                        <span class="text-3xl sm:text-4xl font-bold text-primary">{{ $ad->display_price }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center gap-1 sm:gap-2 shrink-0">
                                <button type="button"
                                        id="share-ad-btn"
                                        class="p-3 sm:p-4 rounded-xl transition-all duration-300 text-gray-500 hover:text-primary hover:bg-primary/10"
                                        title="{{ __('frontend.ads.share_ad') }}"
                                        aria-label="{{ __('frontend.ads.share_ad') }}">
                                    <i class="fas fa-share-alt text-2xl sm:text-3xl"></i>
                                </button>
                                @auth
                                    @if(!$isOwner)
                                    <button id="favorite-btn-mobile"
                                            data-uid="{{ $ad->uid }}"
                                            class="p-3 sm:p-4 rounded-xl transition-all duration-300 {{ $isFavorite ? 'text-red-600 bg-red-50 shadow-md' : 'text-gray-400 hover:text-red-600 hover:bg-red-50 hover:shadow-md' }}">
                                        <i class="fas fa-heart text-2xl sm:text-3xl {{ $isFavorite ? 'fas' : 'far' }}"></i>
                                    </button>
                                    @endif
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location (إحداثيات: خريطة مصغّرة + ترميز عكسي؛ وإلا الحقول الثابتة) -->
                @php
                    $viewerSeesLocation = (bool) ($ad->show_location ?? true);
                    $hasStaticLocation = $viewerSeesLocation && (bool) ($ad->location_country || $ad->location_state || $ad->location_city || $ad->location_district || $ad->location_address);
                @endphp
                @if($viewerSeesLocation && ($adHasMapPin || $hasStaticLocation))
                    <div class="bg-blue-50 rounded-lg p-4 mb-4 border border-blue-100">
                        <div class="flex items-start gap-3">
                            <div class="bg-primary/10 p-2 rounded-lg shrink-0">
                                <i class="fas fa-map-marker-alt text-primary text-lg"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-gray-800 mb-2 text-sm">{{ __('frontend.ads.location') }}:</div>
                                @if($adHasMapPin)
                                    @php
                                        $bboxDelta = 0.015;
                                        $bboxWest = $adMapLng - $bboxDelta;
                                        $bboxSouth = $adMapLat - $bboxDelta;
                                        $bboxEast = $adMapLng + $bboxDelta;
                                        $bboxNorth = $adMapLat + $bboxDelta;
                                    @endphp
                                    @if(config('services.google_maps.api_key'))
                                        <div class="mb-3 rounded-lg overflow-hidden border border-blue-100 bg-gray-200 relative h-[200px] sm:h-[240px] touch-manipulation isolate">
                                            <div id="adShowInlineMapCanvas" class="absolute inset-0 w-full h-full z-0" role="application" aria-label="{{ __('frontend.ads.location_on_map') }}"></div>
                                            <div class="pointer-events-none absolute inset-x-0 bottom-0 z-[2] flex flex-wrap items-stretch sm:items-center justify-center gap-2 p-3 pt-12 sm:pt-14 bg-gradient-to-t from-black/80 via-black/45 to-transparent">
                                                <a href="https://www.google.com/maps/search/?api=1&amp;query={{ $adMapLat }},{{ $adMapLng }}"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="pointer-events-auto inline-flex flex-1 sm:flex-none min-w-[8rem] justify-center items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-gray-900 font-bold text-sm shadow-lg border border-white/90 hover:bg-gray-50 transition">
                                                    <i class="fas fa-map"></i>
                                                    {{ __('frontend.ads.open_in_google_maps') }}
                                                </a>
                                                <a href="https://www.google.com/maps/dir/?api=1&amp;destination={{ $adMapLat }},{{ $adMapLng }}"
                                                   target="_blank"
                                                   rel="noopener noreferrer"
                                                   class="pointer-events-auto inline-flex flex-1 sm:flex-none min-w-[8rem] justify-center items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white font-bold text-sm shadow-lg hover:opacity-95 transition">
                                                    <i class="fas fa-route"></i>
                                                    {{ __('frontend.ads.open_directions') }}
                                                </a>
                                            </div>
                                        </div>
                                    @else
                                        <div class="mb-3 rounded-lg overflow-hidden border border-blue-100 bg-gray-200 h-[150px]">
                                            <iframe title="{{ __('frontend.ads.location_section') }}"
                                                    class="w-full h-[150px] border-0"
                                                    loading="lazy"
                                                    referrerpolicy="no-referrer-when-downgrade"
                                                    src="https://www.openstreetmap.org/export/embed.html?bbox={{ $bboxWest }}%2C{{ $bboxSouth }}%2C{{ $bboxEast }}%2C{{ $bboxNorth }}&amp;layer=mapnik&amp;marker={{ $adMapLat }}%2C{{ $adMapLng }}"></iframe>
                                        </div>
                                        <div class="flex flex-wrap gap-2 mb-3">
                                            <a href="https://www.google.com/maps/search/?api=1&amp;query={{ $adMapLat }},{{ $adMapLng }}"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border-2 border-primary/50 text-primary font-semibold text-sm hover:bg-primary/5 transition">
                                                <i class="fas fa-map-pin"></i>
                                                {{ __('frontend.ads.open_in_google_maps') }}
                                            </a>
                                            <a href="https://www.google.com/maps/dir/?api=1&amp;destination={{ $adMapLat }},{{ $adMapLng }}"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg bg-primary text-white font-semibold text-sm hover:opacity-95 transition">
                                                <i class="fas fa-route"></i>
                                                {{ __('frontend.ads.open_directions') }}
                                            </a>
                                        </div>
                                    @endif
                                    <p id="ad-show-reverse-geocode-line"
                                       class="text-gray-700 text-sm mb-3 leading-relaxed"
                                       data-lat="{{ $adMapLat }}"
                                       data-lng="{{ $adMapLng }}"
                                       data-resolving="{{ e(__('frontend.ads.resolving_location')) }}"
                                       data-fallback="{{ e(__('frontend.ads.location_on_map_short')) }}">{{ __('frontend.ads.resolving_location') }}</p>
                                @else
                                    <div class="text-gray-700 space-y-1">
                                        @if($ad->location_address)
                                            <div class="font-medium">{{ $ad->location_address }}</div>
                                        @endif
                                        @if($ad->location_country || $ad->location_state || $ad->location_city || $ad->location_district)
                                            <div class="text-sm flex items-center gap-1 flex-wrap">
                                                @if($ad->location_country)
                                                    <span class="bg-white px-2 py-1 rounded font-semibold">
                                                        @if($ad->location_country === 'SY')
                                                            🇸🇾 سوريا
                                                        @elseif($ad->location_country === 'TR')
                                                            🇹🇷 تركيا
                                                        @else
                                                            {{ $ad->location_country }}
                                                        @endif
                                                    </span>
                                                @endif
                                                @if($ad->location_state)
                                                    <span class="bg-white px-2 py-1 rounded">{{ $ad->location_state }}</span>
                                                @endif
                                                @if($ad->location_city)
                                                    <span class="bg-white px-2 py-1 rounded">{{ $ad->location_city }}</span>
                                                @endif
                                                @if($ad->location_district)
                                                    <span class="bg-white px-2 py-1 rounded">{{ $ad->location_district }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Meta Info -->
                <div class="flex flex-wrap items-center gap-4 sm:gap-6 text-sm text-gray-600 pt-4 border-t border-gray-200">
                    <span class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg">
                        <i class="fas fa-eye text-primary"></i>
                        <span class="font-semibold">{{ number_format($ad->views_count) }}</span>
                        <span class="text-gray-500">{{ __('frontend.ads.views') }}</span>
                    </span>
                    @if($ad->published_at)
                        <span class="flex items-center gap-2 bg-gray-50 px-3 py-1.5 rounded-lg">
                            <i class="fas fa-clock text-primary"></i>
                            <span>{{ __('frontend.ads.posted') }}:</span>
                            <span class="font-semibold">{{ $ad->published_at->diffForHumans() }}</span>
                        </span>
                    @endif
                    @if($ad->category)
                        <span class="flex flex-wrap items-center gap-x-1 gap-y-1 bg-gray-50 px-3 py-1.5 rounded-lg max-w-full">
                            <i class="fas fa-tag text-primary shrink-0"></i>
                            <span class="flex flex-wrap items-center gap-x-1 gap-y-1 font-semibold text-gray-800 min-w-0">
                                @include('frontend.partials.ad-category-path-links', [
                                    'ad' => $ad,
                                    'linkClass' => 'text-primary hover:underline font-semibold break-words',
                                ])
                            </span>
                        </span>
                    @endif
                </div>
            </div>

            <!-- Images Gallery with Lightbox -->
            @php
                $images = is_array($ad->images) ? $ad->images : (is_string($ad->images) ? json_decode($ad->images, true) : []);
                $images = $images ?? [];
                $adVideoRel = is_string($ad->video ?? null) ? trim($ad->video) : '';
                $adVideoUrl = $adVideoRel !== '' ? asset('storage/' . $adVideoRel) : null;
            @endphp
            @if(!empty($images) && is_array($images) && count($images) > 0)
                <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6 mb-6 overflow-hidden">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl sm:text-2xl font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-images text-primary"></i>
                            {{ __('frontend.ads.images') }} <span class="text-gray-500 text-lg">({{ count($images) }})</span>
                        </h2>
                    </div>

                    <!-- Main Image Display -->
                    <div class="mb-4">
                        <div class="relative bg-gray-100 rounded-xl overflow-hidden" style="aspect-ratio: 16/9;">
                            <img id="main-image"
                                 src="{{ asset('storage/' . (is_string($images[0]) ? $images[0] : (is_array($images[0]) ? ($images[0]['path'] ?? $images[0]) : ''))) }}"
                                 alt="{{ $ad->title }}"
                                 class="w-full h-full object-contain cursor-zoom-in transition-transform duration-300 hover:scale-[1.02]"
                                 role="button"
                                 tabindex="0"
                                 aria-label="{{ __('frontend.ads.gallery_zoom_aria') }}"
                                 onclick="window.adShowGallery && window.adShowGallery.openLightbox()"
                                 onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.adShowGallery&&window.adShowGallery.openLightbox();}">
                            <div id="ad-main-counter"
                                 class="absolute top-3 left-3 z-[2] rounded-lg bg-black/55 text-white text-xs font-bold px-2.5 py-1.5 shadow"
                                 aria-label="{{ __('frontend.ads.images') }}">
                                <span id="ad-main-counter-current">1</span>/<span id="ad-main-counter-total">{{ count($images) }}</span>
                            </div>
                            @if($adVideoUrl)
                                <button type="button"
                                        id="ad-open-video-btn"
                                        class="absolute bottom-3 right-3 z-[2] inline-flex items-center gap-2 rounded-full bg-primary text-white pl-2 pr-3 py-1.5 shadow-lg hover:opacity-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                                        aria-haspopup="dialog"
                                        aria-controls="ad-video-modal"
                                        aria-label="{{ __('frontend.ads.watch_video') }}">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-white/20">
                                        <i class="fas fa-play text-[11px] ms-0.5" aria-hidden="true"></i>
                                    </span>
                                    <span class="text-xs font-extrabold whitespace-nowrap">{{ __('frontend.ads.watch_video') }}</span>
                                </button>
                            @endif
                            @if(count($images) > 1)
                                <button type="button" onclick="window.adShowGallery && window.adShowGallery.changeMainImage(-1)" class="absolute left-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-3 rounded-full transition z-[1]" aria-label="{{ __('frontend.ads.gallery_lightbox_prev') }}">
                                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                                </button>
                                <button type="button" onclick="window.adShowGallery && window.adShowGallery.changeMainImage(1)" class="absolute right-4 top-1/2 -translate-y-1/2 bg-black/50 hover:bg-black/70 text-white p-3 rounded-full transition z-[1]" aria-label="{{ __('frontend.ads.gallery_lightbox_next') }}">
                                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Thumbnail Gallery -->
                    @if(count($images) > 1)
                        <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 sm:gap-3">
                            @foreach($images as $index => $image)
                                @php
                                    $imagePath = is_string($image) ? $image : (is_array($image) ? ($image['path'] ?? $image) : '');
                                @endphp
                                @if($imagePath)
                                    <div class="thumbnail-item relative group cursor-pointer rounded-lg {{ $index === 0 ? 'ring-2 ring-primary shadow-md' : '' }}" data-thumb-index="{{ $index }}" onclick="window.adShowGallery && window.adShowGallery.changeMainImageTo({{ $index }})" role="button" tabindex="0" aria-label="{{ __('frontend.ads.gallery_lightbox_select') }}" onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.adShowGallery&&window.adShowGallery.changeMainImageTo({{ $index }});}">
                                        <img src="{{ asset('storage/' . $imagePath) }}"
                                             alt="{{ $ad->title }} - {{ $index + 1 }}"
                                             class="thumb-img w-full h-20 sm:h-24 object-contain bg-gray-50 rounded-lg transition-all duration-300"
                                             onerror="this.style.display='none';">
                                        <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 rounded-lg transition"></div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    <!-- Fullscreen viewer: zoom + navigate (available to all visitors) -->
                    <div id="ad-show-image-lightbox"
                         class="fixed inset-0 z-[100] hidden flex-col bg-black/95 text-white"
                         role="dialog"
                         aria-modal="true"
                         aria-label="{{ __('frontend.ads.images') }}">
                        <div class="flex items-center justify-between gap-2 px-3 py-2 sm:px-4 border-b border-white/10 shrink-0">
                            <p id="ad-lb-counter" class="text-sm font-medium text-white/90 truncate flex-1 min-w-0"></p>
                            <div class="flex items-center gap-1 sm:gap-2 shrink-0">
                                <button type="button" id="ad-lb-zoom-out" class="p-2.5 rounded-lg bg-white/10 hover:bg-white/20 transition" title="{{ __('frontend.ads.gallery_zoom_out') }}" aria-label="{{ __('frontend.ads.gallery_zoom_out') }}">
                                    <i class="fas fa-search-minus" aria-hidden="true"></i>
                                </button>
                                <button type="button" id="ad-lb-zoom-reset" class="p-2.5 rounded-lg bg-white/10 hover:bg-white/20 transition text-xs font-bold px-3" title="{{ __('frontend.ads.gallery_zoom_reset') }}" aria-label="{{ __('frontend.ads.gallery_zoom_reset') }}">1:1</button>
                                <button type="button" id="ad-lb-zoom-in" class="p-2.5 rounded-lg bg-white/10 hover:bg-white/20 transition" title="{{ __('frontend.ads.gallery_zoom_in') }}" aria-label="{{ __('frontend.ads.gallery_zoom_in') }}">
                                    <i class="fas fa-search-plus" aria-hidden="true"></i>
                                </button>
                                <button type="button" id="ad-lb-close" class="p-2.5 rounded-lg bg-white/10 hover:bg-white/20 transition mr-1" title="{{ __('frontend.ads.gallery_lightbox_close') }}" aria-label="{{ __('frontend.ads.gallery_lightbox_close') }}">
                                    <i class="fas fa-times text-xl" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <div class="relative flex-1 min-h-0 flex items-center justify-center overflow-hidden touch-none" id="ad-lb-stage">
                            @if(count($images) > 1)
                                <button type="button" id="ad-lb-prev" class="absolute left-1 sm:left-3 z-20 p-3 rounded-full bg-black/50 hover:bg-black/70 text-white transition" aria-label="{{ __('frontend.ads.gallery_lightbox_prev') }}">
                                    <i class="fas fa-chevron-left text-xl" aria-hidden="true"></i>
                                </button>
                                <button type="button" id="ad-lb-next" class="absolute right-1 sm:right-3 z-20 p-3 rounded-full bg-black/50 hover:bg-black/70 text-white transition" aria-label="{{ __('frontend.ads.gallery_lightbox_next') }}">
                                    <i class="fas fa-chevron-right text-xl" aria-hidden="true"></i>
                                </button>
                            @endif
                            <div id="ad-lb-img-wrap" class="max-w-full max-h-full overflow-auto flex items-center justify-center p-2 sm:p-6 cursor-grab active:cursor-grabbing" style="-webkit-overflow-scrolling: touch;">
                                <img id="ad-lb-img" src="" alt="" class="max-h-[calc(100vh-8rem)] w-auto h-auto object-contain select-none transition-transform duration-150 ease-out" style="transform: scale(1); transform-origin: center center;" draggable="false">
                            </div>
                        </div>
                        <p class="text-center text-xs text-white/50 py-2 shrink-0 hidden sm:block">{{ __('frontend.ads.gallery_lightbox_hint') }}</p>
                    </div>
                </div>
            @if(!empty($adVideoUrl))
                <div id="ad-video-modal"
                     class="fixed inset-0 z-[120] hidden flex-col items-center justify-center bg-black/80 p-4"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="ad-video-modal-title">
                    <div class="relative w-full max-w-4xl rounded-2xl bg-black overflow-hidden shadow-2xl border border-white/10">
                        <div class="flex items-center justify-between gap-2 px-3 py-2 border-b border-white/10">
                            <h2 id="ad-video-modal-title" class="text-sm font-semibold text-white truncate">{{ __('frontend.ads.watch_video') }}</h2>
                            <button type="button" id="ad-video-modal-close" class="rounded-lg bg-white/10 px-3 py-2 text-white hover:bg-white/20 text-sm">
                                {{ __('frontend.ads.video_modal_close') }}
                            </button>
                        </div>
                        <video id="ad-video-player" class="w-full max-h-[78vh] bg-black" controls playsinline preload="metadata" src="{{ $adVideoUrl }}"></video>
                    </div>
                </div>
                <script>
                (function () {
                    var modal = document.getElementById('ad-video-modal');
                    var btn = document.getElementById('ad-open-video-btn');
                    var closeBtn = document.getElementById('ad-video-modal-close');
                    var player = document.getElementById('ad-video-player');
                    if (!modal || !btn || !closeBtn || !player) return;
                    function openM() {
                        modal.classList.remove('hidden');
                        document.body.classList.add('overflow-hidden');
                        try { player.play(); } catch (e) {}
                    }
                    function closeM() {
                        modal.classList.add('hidden');
                        document.body.classList.remove('overflow-hidden');
                        try { player.pause(); player.currentTime = 0; } catch (e) {}
                    }
                    btn.addEventListener('click', function (e) { e.preventDefault(); openM(); });
                    closeBtn.addEventListener('click', function (e) { e.preventDefault(); closeM(); });
                    modal.addEventListener('click', function (e) { if (e.target === modal) closeM(); });
                    document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeM();
                    });
                })();
                </script>
            @endif
            <script>
            (function () {
                var paths = @json(array_map(function ($img) {
                    return is_string($img) ? $img : (is_array($img) ? ($img['path'] ?? '') : '');
                }, $images ?? []));
                var storage = @json(rtrim(asset('storage'), '/'));
                var adTitle = @json($ad->title);
                var counterTpl = @json(__('frontend.ads.gallery_counter'));
                var mainCounterCur = document.getElementById('ad-main-counter-current');
                var mainCounterTotal = document.getElementById('ad-main-counter-total');
                var lb = document.getElementById('ad-show-image-lightbox');
                var lbImg = document.getElementById('ad-lb-img');
                var lbWrap = document.getElementById('ad-lb-img-wrap');
                var lbCounter = document.getElementById('ad-lb-counter');
                var evHandlers = [];
                var lbScale = 1;
                var currentImageIndex = 0;

                if (!lb || !paths || !paths.length) {
                    window.adShowGallery = {
                        openLightbox: function () {},
                        closeLightbox: function () {},
                        changeMainImage: function () {},
                        changeMainImageTo: function () {},
                        updateMainImage: function () {},
                        updateThumbnails: function () {},
                        get currentImageIndex() {
                            return 0;
                        },
                    };
                    return;
                }

                for (var s = 0; s < paths.length; s++) {
                    if (paths[s]) {
                        currentImageIndex = s;
                        break;
                    }
                }

                function urlFor(i) {
                    var p = paths[i];
                    return p ? storage + '/' + p : '';
                }

                function validIndices() {
                    var a = [];
                    for (var i = 0; i < paths.length; i++) {
                        if (paths[i]) {
                            a.push(i);
                        }
                    }
                    return a;
                }

                function nextIndex(dir) {
                    var v = validIndices();
                    if (v.length <= 1) {
                        return currentImageIndex;
                    }
                    var pos = v.indexOf(currentImageIndex);
                    if (pos < 0) {
                        pos = 0;
                    }
                    var np = pos + (dir > 0 ? 1 : -1);
                    if (np < 0) {
                        np = v.length - 1;
                    }
                    if (np >= v.length) {
                        np = 0;
                    }
                    return v[np];
                }

                function updateCounter() {
                    if (mainCounterCur) {
                        mainCounterCur.textContent = String(currentImageIndex + 1);
                    }
                    if (mainCounterTotal) {
                        mainCounterTotal.textContent = String(paths.length);
                    }
                    if (lbCounter) {
                        lbCounter.textContent = counterTpl
                            .replace(':current', String(currentImageIndex + 1))
                            .replace(':total', String(paths.length));
                    }
                }

                function resetZoom() {
                    lbScale = 1;
                    if (lbImg) {
                        lbImg.style.transform = 'scale(1)';
                    }
                    if (lbWrap) {
                        lbWrap.scrollLeft = 0;
                        lbWrap.scrollTop = 0;
                    }
                }

                function applyZoom(delta) {
                    lbScale = Math.min(5, Math.max(0.5, lbScale + delta));
                    if (lbImg) {
                        lbImg.style.transform = 'scale(' + lbScale + ')';
                    }
                }

                function updateMainImage() {
                    var mainImg = document.getElementById('main-image');
                    var u = urlFor(currentImageIndex);
                    if (mainImg && u) {
                        mainImg.src = u;
                        mainImg.alt = adTitle;
                    }
                    updateCounter();
                }

                function updateThumbnails() {
                    document.querySelectorAll('.thumbnail-item').forEach(function (el) {
                        var idx = parseInt(el.getAttribute('data-thumb-index'), 10);
                        if (idx === currentImageIndex) {
                            el.classList.add('ring-2', 'ring-primary', 'shadow-md');
                        } else {
                            el.classList.remove('ring-2', 'ring-primary', 'shadow-md');
                        }
                    });
                }

                function syncLightboxImage() {
                    var u = urlFor(currentImageIndex);
                    if (lbImg && u) {
                        lbImg.src = u;
                        lbImg.alt = adTitle;
                    }
                    resetZoom();
                    updateCounter();
                }

                function unbindLightbox() {
                    evHandlers.forEach(function (h) {
                        if (h.el && h.type && h.fn) {
                            h.el.removeEventListener(h.type, h.fn, h.opt);
                        }
                    });
                    evHandlers = [];
                }

                function addEv(el, type, fn, opt) {
                    if (!el) {
                        return;
                    }
                    el.addEventListener(type, fn, opt);
                    evHandlers.push({ el: el, type: type, fn: fn, opt: opt || false });
                }

                function bindLightbox() {
                    unbindLightbox();
                    var btnClose = document.getElementById('ad-lb-close');
                    var btnIn = document.getElementById('ad-lb-zoom-in');
                    var btnOut = document.getElementById('ad-lb-zoom-out');
                    var btnReset = document.getElementById('ad-lb-zoom-reset');
                    var btnPrev = document.getElementById('ad-lb-prev');
                    var btnNext = document.getElementById('ad-lb-next');

                    addEv(btnClose, 'click', function (e) {
                        e.preventDefault();
                        closeLightbox();
                    });
                    addEv(btnIn, 'click', function (e) {
                        e.preventDefault();
                        applyZoom(0.25);
                    });
                    addEv(btnOut, 'click', function (e) {
                        e.preventDefault();
                        applyZoom(-0.25);
                    });
                    addEv(btnReset, 'click', function (e) {
                        e.preventDefault();
                        resetZoom();
                    });

                    function stepLightbox(dir) {
                        currentImageIndex = nextIndex(dir);
                        syncLightboxImage();
                        updateMainImage();
                        updateThumbnails();
                    }

                    if (btnPrev) {
                        addEv(btnPrev, 'click', function (e) {
                            e.preventDefault();
                            stepLightbox(-1);
                        });
                    }
                    if (btnNext) {
                        addEv(btnNext, 'click', function (e) {
                            e.preventDefault();
                            stepLightbox(1);
                        });
                    }

                    if (lbWrap) {
                        addEv(lbWrap, 'wheel', function (e) {
                            if (lb.classList.contains('hidden')) {
                                return;
                            }
                            e.preventDefault();
                            applyZoom(e.deltaY < 0 ? 0.15 : -0.15);
                        }, { passive: false });
                    }

                    addEv(document, 'keydown', function (e) {
                        if (lb.classList.contains('hidden')) {
                            return;
                        }
                        if (e.key === 'Escape') {
                            closeLightbox();
                            return;
                        }
                        if (e.key === 'ArrowLeft') {
                            e.preventDefault();
                            stepLightbox(-1);
                        }
                        if (e.key === 'ArrowRight') {
                            e.preventDefault();
                            stepLightbox(1);
                        }
                    });
                }

                function closeLightbox() {
                    lb.classList.add('hidden');
                    lb.classList.remove('flex');
                    document.body.style.overflow = '';
                    unbindLightbox();
                }

                function openLightbox(index) {
                    if (typeof index === 'number' && index >= 0 && index < paths.length && paths[index]) {
                        currentImageIndex = index;
                    }
                    syncLightboxImage();
                    updateThumbnails();
                    lb.classList.remove('hidden');
                    lb.classList.add('flex');
                    document.body.style.overflow = 'hidden';
                    bindLightbox();
                }

                function changeMainImage(direction) {
                    currentImageIndex = nextIndex(direction > 0 ? 1 : -1);
                    updateMainImage();
                    updateThumbnails();
                }

                function changeMainImageTo(index) {
                    if (index >= 0 && index < paths.length && paths[index]) {
                        currentImageIndex = index;
                        updateMainImage();
                        updateThumbnails();
                    }
                }

                window.adShowGallery = {
                    openLightbox: openLightbox,
                    closeLightbox: closeLightbox,
                    changeMainImage: changeMainImage,
                    changeMainImageTo: changeMainImageTo,
                    updateMainImage: updateMainImage,
                    updateThumbnails: updateThumbnails,
                    get currentImageIndex() {
                        return currentImageIndex;
                    },
                };

                updateMainImage();
            })();
            </script>
            @endif

            <!-- Description -->
            <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-100">
                <div class="flex items-center gap-3 mb-4 pb-3 border-b border-gray-200">
                    <div class="bg-primary/10 p-2 rounded-lg">
                        <i class="fas fa-align-right text-primary"></i>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-800">{{ __('frontend.ads.description') }}</h2>
                </div>
                <div class="text-gray-700 whitespace-pre-line leading-relaxed text-base sm:text-lg">{{ $ad->description }}</div>
            </div>

            <!-- Custom Fields -->
            @if($ad->custom_fields)
                @php
                    $categoryFields = \App\Support\CustomFieldsResolver::resolveActiveFields($ad->category, $ad->subcategory);
                    $adCustomFields = $ad->custom_fields;
                @endphp
                @if($categoryFields && $adCustomFields)
                    <div class="bg-white rounded-xl shadow-lg p-6 mb-6 border border-gray-100">
                        <div class="flex items-center gap-3 mb-4 pb-3 border-b border-gray-200">
                            <div class="bg-primary/10 p-2 rounded-lg">
                                <i class="fas fa-list-ul text-primary"></i>
                            </div>
                            <h2 class="text-xl sm:text-2xl font-bold text-gray-800">{{ __('frontend.ads.details') }}</h2>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @foreach($categoryFields as $field)
                                    @if(isset($adCustomFields[$field['id']]))
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 hover:shadow-md transition">
                                        <div class="text-xs text-gray-500 mb-1 font-medium">{{ $field['label'][app()->getLocale()] ?? $field['label']['ar'] }}</div>
                                        <div class="text-gray-800 font-semibold text-base">
                                            @if($field['type'] === 'select')
                                                @php
                                                    $option = collect($field['options'])->firstWhere('id', $adCustomFields[$field['id']]);
                                                @endphp
                                                {{ $option ? ($option[app()->getLocale()] ?? $option['ar']) : $adCustomFields[$field['id']] }}
                                            @elseif($field['type'] === 'number' && is_array($adCustomFields[$field['id']]))
                                                @php
                                                    $numData = $adCustomFields[$field['id']];
                                                    $numVal = $numData['value'] ?? null;
                                                    $isTbd = !empty($numData['tbd']) || $numVal === null || $numVal === '' || (array_key_exists('value', $numData) && ($numVal === null || $numVal === ''));
                                                @endphp
                                                @if($isTbd)
                                                    {{ __('frontend.ads.price_tbd') }}
                                                @elseif(is_numeric($numVal))
                                                    {{ format_price($numVal, 2, $numData['currency'] ?? null) }}
                                                @else
                                                    {{ __('frontend.ads.price_tbd') }}
                                                @endif
                                            @elseif($field['type'] === 'location' && is_array($adCustomFields[$field['id']]))
                                                @php
                                                    $locData = $adCustomFields[$field['id']];
                                                    $locAddr = $locData['address'] ?? __('frontend.ads.location_on_map');
                                                    $hasLocCoords = !empty($locData['lat']) && !empty($locData['lng']);
                                                @endphp
                                                @if($hasLocCoords)
                                                    <button type="button" onclick="showLocationMap({{ $locData['lat'] }}, {{ $locData['lng'] }}, {{ json_encode($locAddr) }})"
                                                            class="text-left w-full flex items-center gap-2 text-primary hover:underline font-semibold">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <span>{{ $locAddr }}</span>
                                                        <i class="fas fa-external-link-alt text-xs"></i>
                                                    </button>
                                                @else
                                                    <span><i class="fas fa-map-marker-alt text-primary ml-1"></i> {{ $locAddr }}</span>
                                                @endif
                                            @else
                                                {{ is_array($adCustomFields[$field['id']]) ? ($adCustomFields[$field['id']]['address'] ?? $adCustomFields[$field['id']]['value'] ?? '') : $adCustomFields[$field['id']] }}
                                            @endif
                                        </div>
                                    </div>
                                    @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </main>

        <!-- Sidebar -->
        <aside class="lg:col-span-1">
            <!-- Actions -->
            <div class="space-y-6">
                @if($isOwner)
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 space-y-3">
                        @if($canAddFeatured || $canRemoveFeatured || $canAddUrgent || $canRemoveUrgent || (!$ad->is_featured && !$canAddFeatured) || (!$ad->is_urgent && !$canAddUrgent))
                            <div class="pb-3 mb-3 border-b border-gray-200">
                                <h4 class="text-sm font-bold text-gray-700 mb-2">{{ __('frontend.ads.promote_ad') }}</h4>
                                <div class="space-y-2">
                                    @if($canAddFeatured)
                                        <form action="{{ route('profile.ads.set-featured', $ad->uid) }}" method="POST" class="block">
                                            @csrf
                                            <button type="submit" class="w-full px-3 py-2 bg-yellow-50 text-yellow-700 hover:bg-yellow-100 border border-yellow-200 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-2">
                                                <img src="{{ asset('assets/badges/featured_badge.svg') }}" alt="{{ __('frontend.profile.ads.add_featured') }}" class="w-4 h-4 inline-block align-text-bottom"> {{ __('frontend.profile.ads.add_featured') }} ({{ $remainingFeatured }})
                                            </button>
                                        </form>
                                    @elseif(!$ad->is_featured)
                                        <button type="button" disabled class="w-full px-3 py-2 bg-gray-100 text-gray-500 border border-gray-200 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 cursor-not-allowed">
                                            <img src="{{ asset('assets/badges/featured_badge.svg') }}" alt="{{ __('frontend.profile.ads.add_featured') }}" class="w-4 h-4 inline-block align-text-bottom"> {{ __('frontend.profile.ads.add_featured') }} — {{ __('frontend.profile.ads.no_promote_quota') }}
                                        </button>
                                    @endif
                                    @if($canRemoveFeatured)
                                        <form action="{{ route('profile.ads.set-featured', $ad->uid) }}" method="POST" class="block" onsubmit="return confirm('{{ __('frontend.profile.ads.confirm_remove_featured') }}');">
                                            @csrf
                                            <button type="submit" class="w-full px-3 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-2">
                                                <img src="{{ asset('assets/badges/featured_badge.svg') }}" alt="{{ __('frontend.profile.ads.remove_featured') }}" class="w-4 h-4 inline-block align-text-bottom"> {{ __('frontend.profile.ads.remove_featured') }}
                                            </button>
                                        </form>
                                    @endif
                                    @if($canAddUrgent)
                                        <form action="{{ route('profile.ads.set-urgent', $ad->uid) }}" method="POST" class="block">
                                            @csrf
                                            <button type="submit" class="w-full px-3 py-2 bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-2">
                                                <img src="{{ asset('assets/badges/urgent_badge.svg') }}" alt="{{ __('frontend.profile.ads.add_urgent') }}" class="w-4 h-4 inline-block align-text-bottom"> {{ __('frontend.profile.ads.add_urgent') }} ({{ $remainingUrgent }})
                                            </button>
                                        </form>
                                    @elseif(!$ad->is_urgent)
                                        <button type="button" disabled class="w-full px-3 py-2 bg-gray-100 text-gray-500 border border-gray-200 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 cursor-not-allowed">
                                            <img src="{{ asset('assets/badges/urgent_badge.svg') }}" alt="{{ __('frontend.profile.ads.add_urgent') }}" class="w-4 h-4 inline-block align-text-bottom"> {{ __('frontend.profile.ads.add_urgent') }} — {{ __('frontend.profile.ads.no_promote_quota') }}
                                        </button>
                                    @endif
                                    @if($canRemoveUrgent)
                                        <form action="{{ route('profile.ads.set-urgent', $ad->uid) }}" method="POST" class="block" onsubmit="return confirm('{{ __('frontend.profile.ads.confirm_remove_urgent') }}');">
                                            @csrf
                                            <button type="submit" class="w-full px-3 py-2 bg-gray-100 text-gray-700 hover:bg-gray-200 border border-gray-300 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-2">
                                                <img src="{{ asset('assets/badges/urgent_badge.svg') }}" alt="{{ __('frontend.profile.ads.remove_urgent') }}" class="w-4 h-4 inline-block align-text-bottom"> {{ __('frontend.profile.ads.remove_urgent') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <a href="{{ route('profile.ads.edit', $ad->uid) }}"
                           class="w-full block text-center py-3.5 rounded-xl bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-semibold transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                            <i class="fas fa-edit"></i>
                            {{ __('frontend.profile.my_ads_management.edit_ad') }}
                        </a>
                        @if($ad->status === 'suspended')
                            <form action="{{ route('profile.ads.unsuspend', $ad->uid) }}" method="POST" onsubmit="return confirm('{{ __('frontend.profile.ads.confirm_unsuspend') }}');">
                                @csrf
                                <button type="submit" class="w-full py-3 rounded-xl bg-green-50 text-green-600 hover:bg-green-100 border-2 border-green-200 font-semibold transition flex items-center justify-center gap-2">
                                    <i class="fas fa-play"></i>
                                    {{ __('frontend.profile.ads.unsuspend_ad') }}
                                </button>
                            </form>
                        @elseif(in_array($ad->status, ['active', 'pending']))
                            <form action="{{ route('profile.ads.suspend', $ad->uid) }}" method="POST" onsubmit="return confirm('{{ __('frontend.profile.ads.confirm_suspend') }}');">
                                @csrf
                                <button type="submit" class="w-full py-3 rounded-xl bg-orange-50 text-orange-600 hover:bg-orange-100 border-2 border-orange-200 font-semibold transition flex items-center justify-center gap-2">
                                    <i class="fas fa-pause"></i>
                                    {{ __('frontend.profile.ads.suspend_ad') }}
                                </button>
                            </form>
                        @endif
                        <form action="{{ route('profile.ads.destroy', $ad->uid) }}" method="POST" onsubmit="return confirm('{{ __('frontend.profile.ads.confirm_delete') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-full py-3 rounded-xl border-2 border-red-200 text-red-600 hover:bg-red-50 font-semibold transition flex items-center justify-center gap-2">
                                <i class="fas fa-trash"></i>
                                {{ __('frontend.profile.ads.delete_ad') }}
                            </button>
                        </form>
                        <a href="{{ route('profile.ads.index') }}"
                           class="w-full block text-center py-3 rounded-xl border-2 border-gray-300 text-gray-700 hover:bg-gray-50 transition-all duration-300 flex items-center justify-center gap-2 font-semibold">
                            <i class="fas fa-list"></i>
                            {{ __('frontend.profile.my_ads') }}
                        </a>
                    </div>
                @else
                <!-- Contact Seller -->
                <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg p-6 border border-gray-100">
                    <div class="flex items-center gap-3 mb-4 pb-3 border-b border-gray-200">
                        <div class="bg-primary/10 p-2 rounded-lg">
                            <i class="fas fa-user-circle text-primary"></i>
                        </div>
                        <h3 class="font-bold text-gray-800 text-lg">{{ __('frontend.ads.contact_seller') }}</h3>
                    </div>
                    @if($ad->user)
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-500">{{ __('frontend.nav.profile') }}:</span>
                                <a href="{{ route('seller.show', $ad->user->slug) }}" class="font-semibold text-gray-800 hover:text-primary transition flex items-center gap-2">
                                    {{ $ad->user->name }}
                                    @if($ad->user->is_verified)
                                        <span class="px-2 py-1 bg-blue-100 text-blue-600 rounded-full text-xs font-semibold flex items-center gap-1">
                                            <i class="fas fa-check-circle"></i>
                                            {{ __('frontend.profile.verified') }}
                                        </span>
                                    @endif
                                </a>
                            </div>
                            @php
                                $displayPhone = $ad->user->business_phone ?? $ad->user->phone;
                                $whatsAppNumber = $displayPhone ? preg_replace('/[^0-9+]/', '', $displayPhone) : null;
                            @endphp
                            @if($whatsAppNumber)
                                <a href="https://wa.me/{{ ltrim($whatsAppNumber, '+') }}"
                                   target="_blank"
                                   class="bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white w-full block text-center py-3.5 rounded-xl transition-all duration-300 flex items-center justify-center gap-2 font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                                    <i class="fab fa-whatsapp text-xl"></i>
                                    {{ __('frontend.seller.whatsapp_contact') }}
                                </a>
                            @elseif($displayPhone)
                                <a href="tel:{{ $displayPhone }}"
                                   class="bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white w-full block text-center py-3.5 rounded-xl transition-all duration-300 flex items-center justify-center gap-2 font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5"
                                   title="{{ $displayPhone }}">
                                    <i class="fas fa-phone text-xl"></i>
                                    <span>{{ __('frontend.seller.call') }}</span>
                                </a>
                            @endif

                            @auth
                                @if(Auth::id() != $ad->user->id)
                                    @if(Auth::user()->hasBlocked($ad->user->id))
                                        <form action="{{ route('profile.blocked-users.unblock', $ad->user->id) }}" method="POST" class="mt-3">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    onclick="return confirm('{{ __('frontend.profile.confirm_unblock') }}')"
                                                    class="w-full bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg transition flex items-center justify-center gap-2">
                                                <i class="fas fa-unlock"></i>
                                                {{ __('frontend.profile.unblock') }}
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('profile.blocked-users.block', $ad->user->id) }}" method="POST" class="mt-3">
                                            @csrf
                                            <button type="submit"
                                                    onclick="return confirm('{{ __('frontend.profile.confirm_block') }}')"
                                                    class="w-full bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg transition flex items-center justify-center gap-2">
                                                <i class="fas fa-ban"></i>
                                                {{ __('frontend.profile.block_user') }}
                                            </button>
                                        </form>
                                    @endif
                                @endif
                            @endauth
                        </div>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 space-y-3">
                    @auth
                        <button id="favorite-btn"
                                data-uid="{{ $ad->uid }}"
                                class="w-full flex items-center justify-center gap-2 py-3.5 rounded-xl transition-all duration-300 {{ $isFavorite ? 'bg-red-50 text-red-600 hover:bg-red-100 border-2 border-red-200' : 'bg-gray-50 text-gray-600 hover:bg-gray-100 border-2 border-gray-200' }} font-semibold">
                            <i class="fas fa-heart {{ $isFavorite ? 'fas' : 'far' }} text-lg"></i>
                            <span id="favorite-text">{{ $isFavorite ? __('frontend.favorites.remove') : __('frontend.favorites.add') }}</span>
                        </button>
                    @endauth

                    @auth
                        @if($ad->user_id !== Auth::id() && $ad->display_price && ($ad->category?->enable_negotiation ?? true))
                            <a href="{{ route('negotiations.create', $ad->uid) }}"
                               class="w-full block text-center py-3.5 rounded-xl bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white font-semibold transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                <i class="fas fa-handshake"></i>
                                {{ __('frontend.negotiations.negotiate_price') }}
                            </a>
                        @endif
                    @else
                        @if($ad->display_price && ($ad->category?->enable_negotiation ?? true))
                            <a href="{{ route('login') }}"
                               class="w-full block text-center py-3.5 rounded-xl bg-gradient-to-r from-yellow-500 to-yellow-600 hover:from-yellow-600 hover:to-yellow-700 text-white font-semibold transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-0.5 flex items-center justify-center gap-2">
                                <i class="fas fa-handshake"></i>
                                {{ __('frontend.negotiations.negotiate_price') }}
                            </a>
                        @endif
                    @endauth

                    @auth
                        @if($ad->user_id !== Auth::id())
                            <form action="{{ route('messages.create', $ad->uid) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-primary w-full py-3.5 rounded-xl shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-2 font-semibold">
                                    <i class="fas fa-comments"></i>
                                    {{ __('frontend.messages.chat_with_seller') }}
                                </button>
                            </form>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="btn-primary w-full block text-center py-3.5 rounded-xl shadow-md hover:shadow-lg transform hover:-translate-y-0.5 transition-all duration-300 flex items-center justify-center gap-2 font-semibold">
                            <i class="fas fa-comments"></i>
                            {{ __('frontend.messages.chat_with_seller') }}
                        </a>
                    @endauth

                    @auth
                        <a href="{{ route('profile.reports.create', ['ad_id' => $ad->id]) }}"
                           class="w-full block text-center py-3 rounded-xl border-2 border-red-300 text-red-600 hover:bg-red-50 transition-all duration-300 flex items-center justify-center gap-2 font-semibold">
                            <i class="fas fa-flag"></i>
                            {{ __('frontend.ads.report_ad') }}
                        </a>
                    @endauth
                </div>
                @endif
            </div>

            <!-- Related Ads -->
            @if($relatedAds->count() > 0)
                <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
                    <div class="flex items-center gap-3 mb-4 pb-3 border-b border-gray-200">
                        <div class="bg-primary/10 p-2 rounded-lg">
                            <i class="fas fa-th-large text-primary"></i>
                        </div>
                        <h3 class="font-bold text-gray-800 text-lg">{{ __('frontend.ads.related_ads') }}</h3>
                    </div>
                    <div class="space-y-3">
                        @foreach($relatedAds as $relatedAd)
                            <a href="{{ route('ads.show', $relatedAd->uid) }}" class="block group">
                                <div class="flex gap-3 p-2 rounded-lg hover:bg-gray-50 transition-all duration-300 border border-transparent hover:border-primary/20">
                                    @php
                                        $relatedImages = is_array($relatedAd->images) ? $relatedAd->images : (is_string($relatedAd->images) ? json_decode($relatedAd->images, true) : []);
                                        $relatedImages = $relatedImages ?? [];
                                        $firstRelatedImage = !empty($relatedImages) && is_array($relatedImages) ? $relatedImages[0] : null;
                                        $firstRelatedImagePath = is_string($firstRelatedImage) ? $firstRelatedImage : (is_array($firstRelatedImage) ? ($firstRelatedImage['path'] ?? $firstRelatedImage) : '');
                                    @endphp
                                    @if($firstRelatedImagePath)
                                        <img src="{{ asset('storage/' . $firstRelatedImagePath) }}"
                                             alt="{{ $relatedAd->title }}"
                                             class="w-20 h-20 object-cover rounded-lg shadow-md group-hover:shadow-lg transition">
                                    @else
                                        <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                            <i class="fas fa-image text-gray-400"></i>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-sm text-gray-800 line-clamp-2 mb-1 group-hover:text-primary transition">
                                            {{ $relatedAd->title }}
                                        </h4>
                                        @if($relatedAd->display_price)
                                            <p class="text-primary font-bold text-sm">
                                                {{ $relatedAd->display_price }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </aside>
    </div>
</div>

<script>
(function () {
    var btn = document.getElementById('share-ad-btn');
    if (!btn) return;
    var shareData = {
        title: @json($ad->title),
        text: @json($ad->title),
        url: @json(route('ads.show', $ad->uid))
    };
    function fallbackCopy(url) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(function () {
                alert(@json(__('frontend.ads.link_copied')));
            }).catch(function () {
                window.prompt(@json(__('frontend.ads.link_copied')), url);
            });
        } else {
            window.prompt(@json(__('frontend.ads.link_copied')), url);
        }
    }
    btn.addEventListener('click', function () {
        if (navigator.share) {
            navigator.share(shareData).catch(function (e) {
                if (e && e.name === 'AbortError') return;
                fallbackCopy(shareData.url);
            });
        } else {
            fallbackCopy(shareData.url);
        }
    });
})();
</script>

@auth
<script>
document.addEventListener('DOMContentLoaded', function() {
    const favoriteBtn = document.getElementById('favorite-btn');
    const favoriteBtnMobile = document.getElementById('favorite-btn-mobile');
    const favoriteText = document.getElementById('favorite-text');

    function toggleFavorite(btn) {
        const uid = btn.getAttribute('data-uid');

        fetch(`{{ route('favorites.toggle', ':uid') }}`.replace(':uid', uid), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update desktop button
                if (favoriteBtn) {
                    if (data.is_favorite) {
                        favoriteBtn.classList.remove('bg-gray-50', 'text-gray-600', 'hover:bg-gray-100', 'border-gray-200');
                        favoriteBtn.classList.add('bg-red-50', 'text-red-600', 'hover:bg-red-100', 'border-red-200');
                        favoriteBtn.querySelector('i').classList.remove('far');
                        favoriteBtn.querySelector('i').classList.add('fas');
                    } else {
                        favoriteBtn.classList.remove('bg-red-50', 'text-red-600', 'hover:bg-red-100', 'border-red-200');
                        favoriteBtn.classList.add('bg-gray-50', 'text-gray-600', 'hover:bg-gray-100', 'border-gray-200');
                        favoriteBtn.querySelector('i').classList.remove('fas');
                        favoriteBtn.querySelector('i').classList.add('far');
                    }
                    if (favoriteText) {
                        favoriteText.textContent = data.is_favorite ? '{{ __('frontend.favorites.remove') }}' : '{{ __('frontend.favorites.add') }}';
                    }
                }

                // Update mobile button
                if (favoriteBtnMobile) {
                    if (data.is_favorite) {
                        favoriteBtnMobile.classList.remove('text-gray-400', 'hover:text-red-600', 'hover:bg-red-50');
                        favoriteBtnMobile.classList.add('text-red-600', 'bg-red-50', 'shadow-md');
                    } else {
                        favoriteBtnMobile.classList.remove('text-red-600', 'bg-red-50', 'shadow-md');
                        favoriteBtnMobile.classList.add('text-gray-400', 'hover:text-red-600', 'hover:bg-red-50');
                        favoriteBtnMobile.querySelector('i').classList.remove('fas');
                        favoriteBtnMobile.querySelector('i').classList.add('far');
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    if (favoriteBtn) {
        favoriteBtn.addEventListener('click', function() {
            toggleFavorite(this);
        });
    }

    if (favoriteBtnMobile) {
        favoriteBtnMobile.addEventListener('click', function() {
            toggleFavorite(this);
        });
    }
});
</script>
@endauth

@php
    $hasLocationField = $adHasMapPin;
    if (!$hasLocationField && $ad->custom_fields) {
        foreach (\App\Support\CustomFieldsResolver::resolveActiveFields($ad->category, $ad->subcategory) as $field) {
            if (($field['type'] ?? '') === 'location' && isset($ad->custom_fields[$field['id']])) {
                $loc = $ad->custom_fields[$field['id']];
                if (!empty($loc['lat']) && !empty($loc['lng'])) {
                    $hasLocationField = true;
                    break;
                }
            }
        }
    }
@endphp
@if($hasLocationField && config('services.google_maps.api_key'))
<!-- Location Map Modal -->
<div id="locationMapModal" class="fixed inset-0 bg-black/70 z-50 hidden flex items-center justify-center p-4" onclick="if(event.target===this) closeLocationMapModal()">
    <div class="bg-white rounded-xl shadow-2xl max-w-3xl w-full max-h-[92vh] overflow-hidden flex flex-col" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 shrink-0">
            <h3 class="text-lg font-bold text-gray-800">
                <i class="fas fa-map-marker-alt text-primary ml-2"></i>
                {{ __('frontend.ads.location_on_map') }}
            </h3>
            <button type="button" onclick="closeLocationMapModal()" class="text-gray-500 hover:text-gray-700 text-2xl">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="relative w-full flex-1 min-h-[min(70vh,420px)] h-[min(70vh,420px)] touch-manipulation">
            <div id="locationMapContainer" class="absolute inset-0 z-0"></div>
            <div class="pointer-events-none absolute inset-x-0 bottom-0 z-[2] flex flex-wrap items-stretch sm:items-center justify-center gap-2 p-3 pt-12 bg-gradient-to-t from-black/80 via-black/45 to-transparent">
                <a href="#" target="_blank" rel="noopener noreferrer" id="locationMapLink"
                   class="pointer-events-auto inline-flex flex-1 sm:flex-none min-w-[8rem] justify-center items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-gray-900 font-bold text-sm shadow-lg border border-white/90 hover:bg-gray-50 transition">
                    <i class="fas fa-map"></i>
                    {{ __('frontend.ads.open_in_google_maps') }}
                </a>
                <a href="#" target="_blank" rel="noopener noreferrer" id="locationMapDirectionsLink"
                   class="pointer-events-auto inline-flex flex-1 sm:flex-none min-w-[8rem] justify-center items-center gap-2 px-4 py-2.5 rounded-xl bg-primary text-white font-bold text-sm shadow-lg hover:opacity-95 transition">
                    <i class="fas fa-route"></i>
                    {{ __('frontend.ads.open_directions') }}
                </a>
            </div>
        </div>
    </div>
</div>
@php
    $adShowPageMapConfig = [
        'apiKey' => config('services.google_maps.api_key'),
        'locale' => app()->getLocale(),
        'priceLabel' => $ad->display_price,
        'inlineCoords' => $adHasMapPin ? ['lat' => (float) $adMapLat, 'lng' => (float) $adMapLng] : null,
    ];
@endphp
<script>
(function () {
    window.__adShowPageMap = @json($adShowPageMapConfig);

    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        var d = document.createElement('div');
        d.textContent = String(text);
        return d.innerHTML;
    }

    function mapOptions(center, zoom) {
        return {
            center: center,
            zoom: zoom,
            gestureHandling: 'greedy',
            zoomControl: true,
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: true,
            clickableIcons: false,
            keyboardShortcuts: true,
        };
    }

    var PricePinOverlayClass = null;

    /** Pin + optional price chip; scale grows when user zooms in (clamped). Defined after Maps API loads. */
    function ensurePricePinOverlayClass() {
        if (PricePinOverlayClass || !(window.google && google.maps)) return;
        function AdShowPricePinOverlay(latLng, priceText) {
            this.latLng_ = latLng instanceof google.maps.LatLng ? latLng : new google.maps.LatLng(latLng.lat, latLng.lng);
            this.priceText_ = priceText ? String(priceText) : '';
            this.div_ = null;
            this.mapListeners_ = [];
        }
        AdShowPricePinOverlay.prototype = Object.create(google.maps.OverlayView.prototype);
        AdShowPricePinOverlay.prototype.constructor = AdShowPricePinOverlay;
        AdShowPricePinOverlay.prototype.onAdd = function () {
            var div = document.createElement('div');
            div.style.position = 'absolute';
            div.style.transformOrigin = '50% 100%';
            div.style.pointerEvents = 'none';
            div.style.zIndex = '10';
            var chip = this.priceText_
                ? '<div style="display:inline-block;padding:.42em .78em;border-radius:9999px;background:#fff;box-shadow:0 2px 12px rgba(0,0,0,.2);font-weight:800;color:#111827;border:2px solid #f5c400;line-height:1.15;max-width:min(240px,72vw);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(this.priceText_) + '</div>'
                : '';
            var pin = '<div style="width:0;height:0;border-left:9px solid transparent;border-right:9px solid transparent;border-top:11px solid #f5c400;margin:0 auto;"></div>'
                + '<div style="width:13px;height:13px;margin:3px auto 0;border-radius:50%;background:#1f2937;border:2px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.35)"></div>';
            div.innerHTML = chip + pin;
            this.div_ = div;
            var panes = this.getPanes();
            panes.overlayMouseTarget.appendChild(div);
            var map = this.getMap();
            var self = this;
            ['zoom_changed', 'center_changed', 'bounds_changed'].forEach(function (ev) {
                self.mapListeners_.push(google.maps.event.addListener(map, ev, function () {
                    self.draw();
                }));
            });
        };
        AdShowPricePinOverlay.prototype.draw = function () {
            if (!this.div_) return;
            var proj = this.getProjection();
            if (!proj) return;
            var pt = proj.fromLatLngToDivPixel(this.latLng_);
            if (!pt) return;
            var z = this.getMap().getZoom();
            var scale = Math.pow(1.09, z - 14);
            scale = Math.max(0.76, Math.min(2.45, scale));
            this.div_.style.left = pt.x + 'px';
            this.div_.style.top = pt.y + 'px';
            this.div_.style.transform = 'translate(-50%, -100%) scale(' + scale + ')';
        };
        AdShowPricePinOverlay.prototype.onRemove = function () {
            this.mapListeners_.forEach(function (l) {
                google.maps.event.removeListener(l);
            });
            this.mapListeners_ = [];
            if (this.div_ && this.div_.parentNode) {
                this.div_.parentNode.removeChild(this.div_);
            }
            this.div_ = null;
        };
        PricePinOverlayClass = AdShowPricePinOverlay;
    }

    function createPriceOverlay(map, pos, priceLabel) {
        ensurePricePinOverlayClass();
        if (!PricePinOverlayClass) return null;
        var overlay = new PricePinOverlayClass(pos, priceLabel);
        overlay.setMap(map);
        return overlay;
    }

    var locationMapModalInstance = null;
    var inlineMapInstance = null;
    var mapsLoadCallbacks = [];

    window.__initAdShowGoogleMaps = function () {
        ensurePricePinOverlayClass();
        while (mapsLoadCallbacks.length) {
            var fn = mapsLoadCallbacks.shift();
            try { fn(); } catch (e) { console.error(e); }
        }
    };

    function loadGoogleMapsApi(cb) {
        var cfg = window.__adShowPageMap;
        if (!cfg || !cfg.apiKey) {
            return false;
        }
        if (window.google && window.google.maps) {
            cb();
            return true;
        }
        mapsLoadCallbacks.push(cb);
        if (window.__adShowGmapsScriptLoading) {
            return true;
        }
        window.__adShowGmapsScriptLoading = true;
        var s = document.createElement('script');
        s.async = true;
        s.defer = true;
        s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(cfg.apiKey)
            + '&language=' + encodeURIComponent(cfg.locale || 'en')
            + '&callback=__initAdShowGoogleMaps';
        s.onerror = function () {
            window.__adShowGmapsScriptLoading = false;
            mapsLoadCallbacks = [];
        };
        document.head.appendChild(s);
        return true;
    }

    function initAdShowInlineMap() {
        if (!(window.google && window.google.maps)) return;
        var el = document.getElementById('adShowInlineMapCanvas');
        if (!el || el.dataset.gmapInit === '1') return;
        var cfg = window.__adShowPageMap;
        if (!cfg || !cfg.inlineCoords) return;
        el.dataset.gmapInit = '1';
        var pos = cfg.inlineCoords;
        var map = new google.maps.Map(el, mapOptions(pos, 15));
        if (inlineMapInstance && inlineMapInstance.overlay) {
            inlineMapInstance.overlay.setMap(null);
        }
        var overlay = createPriceOverlay(map, pos, cfg.priceLabel);
        inlineMapInstance = { map: map, overlay: overlay };
    }

    window.showLocationMap = function (lat, lng, address) {
        var modal = document.getElementById('locationMapModal');
        var mapDiv = document.getElementById('locationMapContainer');
        var link = document.getElementById('locationMapLink');
        var dirLink = document.getElementById('locationMapDirectionsLink');
        var latNum = parseFloat(lat);
        var lngNum = parseFloat(lng);
        var pos = {
            lat: isFinite(latNum) ? latNum : 33.5138,
            lng: isFinite(lngNum) ? lngNum : 36.2765,
        };
        var searchUrl = 'https://www.google.com/maps/search/?api=1&query=' + pos.lat + ',' + pos.lng;
        var dirUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + pos.lat + ',' + pos.lng;

        if (!modal || !mapDiv) {
            window.open(searchUrl, '_blank');
            return;
        }

        var opened = loadGoogleMapsApi(function () {
            if (link) link.href = searchUrl;
            if (dirLink) dirLink.href = dirUrl;
            modal.classList.remove('hidden');

            var cfg = window.__adShowPageMap || {};
            var priceLabel = cfg.priceLabel || '';

            if (locationMapModalInstance) {
                if (locationMapModalInstance.overlay) {
                    locationMapModalInstance.overlay.setMap(null);
                    locationMapModalInstance.overlay = null;
                }
                locationMapModalInstance.map.setCenter(pos);
                locationMapModalInstance.map.setZoom(16);
                locationMapModalInstance.overlay = createPriceOverlay(locationMapModalInstance.map, pos, priceLabel);
            } else {
                var map = new google.maps.Map(mapDiv, mapOptions(pos, 16));
                var overlay = createPriceOverlay(map, pos, priceLabel);
                locationMapModalInstance = { map: map, overlay: overlay };
            }

            var m = locationMapModalInstance.map;
            google.maps.event.trigger(m, 'resize');
            requestAnimationFrame(function () {
                google.maps.event.trigger(m, 'resize');
                m.setCenter(pos);
            });
        });

        if (!opened) {
            window.open(searchUrl, '_blank');
        }
    };

    window.closeLocationMapModal = function () {
        var modal = document.getElementById('locationMapModal');
        if (modal) modal.classList.add('hidden');
    };

    if (window.__adShowPageMap && window.__adShowPageMap.inlineCoords) {
        loadGoogleMapsApi(initAdShowInlineMap);
    }
})();
</script>
@else
<script>
window.showLocationMap = function (lat, lng) {
    var latNum = parseFloat(lat);
    var lngNum = parseFloat(lng);
    if (!isFinite(latNum) || !isFinite(lngNum)) return;
    window.open('https://www.google.com/maps/search/?api=1&query=' + latNum + ',' + lngNum, '_blank');
};
window.closeLocationMapModal = function () {
    document.getElementById('locationMapModal')?.classList.add('hidden');
};
</script>
@endif

@include('frontend.partials.nominatim-reverse-geocode')
@endsection

