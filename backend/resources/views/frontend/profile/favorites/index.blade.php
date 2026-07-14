@extends('frontend.layouts.app')

@section('title', __('frontend.favorites.my_favorites'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <!-- Main Content -->
            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <!-- Header -->
                    <div class="mb-6 pb-6 border-b border-gray-200">
                        <h1 class="text-2xl font-bold text-primary mb-2">{{ __('frontend.favorites.my_favorites') }}</h1>
                        <p class="text-gray-600 text-sm">{{ __('frontend.favorites.manage_favorites') }}</p>
                    </div>

                    <!-- Favorites Grid -->
                    @if($favorites->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($favorites as $favorite)
                                <div class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-lg transition">
                                    <!-- Image -->
                                    <div class="relative h-40 bg-gray-200">
                                        <a href="{{ route('ads.show', $favorite->ad->uid) }}">
                                            @php
                                                $images = is_array($favorite->ad->images) ? $favorite->ad->images : (is_string($favorite->ad->images) ? json_decode($favorite->ad->images, true) : []);
                                                $images = $images ?? [];
                                                $firstImage = !empty($images) && is_array($images) ? $images[0] : null;
                                                $firstImagePath = is_string($firstImage) ? $firstImage : (is_array($firstImage) ? ($firstImage['path'] ?? $firstImage) : '');
                                            @endphp
                                            @if($firstImagePath)
                                                <img src="{{ asset('storage/' . $firstImagePath) }}" 
                                                     alt="{{ $favorite->ad->title }}"
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
                                        
                                        <!-- Remove Favorite Button -->
                                        <form action="{{ route('favorites.destroy', $favorite->ad->uid) }}" 
                                              method="POST" 
                                              class="absolute top-2 {{ app()->getLocale() === 'ar' ? 'right-2' : 'left-2' }}"
                                              onsubmit="return confirm('{{ __('frontend.favorites.confirm_remove') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="p-2 bg-white rounded-full shadow-md hover:bg-red-50 text-red-600 transition">
                                                <i class="fas fa-heart"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Content -->
                                    <div class="p-4">
                                        <a href="{{ route('ads.show', $favorite->ad->uid) }}">
                                            <h3 class="font-semibold text-gray-800 mb-2 line-clamp-2 hover:text-primary transition">{{ $favorite->ad->title }}</h3>
                                        </a>
                                        
                                        @if($favorite->ad->display_price)
                                            <div class="text-lg font-bold text-primary mb-2">
                                                {{ $favorite->ad->display_price }}
                                            </div>
                                        @endif

                                        <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                                            <span><i class="fas fa-eye ml-1"></i> {{ $favorite->ad->views_count }}</span>
                                            <span>{{ $favorite->ad->created_at->diffForHumans() }}</span>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('ads.show', $favorite->ad->uid) }}" 
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
                            {{ $favorites->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-heart text-gray-300 text-6xl mb-4"></i>
                            <p class="text-gray-500 text-lg mb-4">{{ __('frontend.favorites.no_favorites') }}</p>
                            <a href="{{ route('ads.index') }}" class="btn-primary inline-block">
                                <i class="fas fa-search ml-2"></i>
                                {{ __('frontend.ads.browse_ads') }}
                            </a>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

