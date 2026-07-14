@extends('frontend.layouts.app')

@section('title', __('frontend.profile.my_ratings'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.profile.my_ratings') }}
                    </h1>

                    <!-- Rating Summary -->
                    @if($ratingsCount > 0)
                        <div class="bg-gradient-to-r from-primary to-secondary rounded-lg p-6 mb-6 text-white">
                            <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
                                <div class="text-center sm:text-right">
                                    <div class="text-4xl sm:text-5xl font-bold mb-2">{{ number_format($averageRating, 1) }}</div>
                                    <div class="flex items-center justify-center gap-1 mb-2">
                                        @for($i = 1; $i <= 5; $i++)
                                            @if($i <= floor($averageRating))
                                                <i class="fas fa-star text-yellow-300 text-xl"></i>
                                            @elseif($i - 0.5 <= $averageRating)
                                                <i class="fas fa-star-half-alt text-yellow-300 text-xl"></i>
                                            @else
                                                <i class="far fa-star text-yellow-200 text-xl"></i>
                                            @endif
                                        @endfor
                                    </div>
                                    <div class="text-sm opacity-90">
                                        {{ $ratingsCount }} {{ __('frontend.profile.ratings_count') }}
                                    </div>
                                </div>
                                <div class="flex-1 text-center sm:text-right">
                                    <h2 class="text-lg sm:text-xl font-semibold mb-2">{{ __('frontend.profile.ratings_summary') }}</h2>
                                    <p class="text-sm opacity-90">{{ __('frontend.profile.ratings_description') }}</p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="bg-gray-100 rounded-lg p-6 mb-6 text-center">
                            <i class="fas fa-star text-gray-300 text-5xl mb-3"></i>
                            <p class="text-gray-600">{{ __('frontend.profile.no_ratings_received') }}</p>
                        </div>
                    @endif

                    <!-- Ratings List -->
                    @if($ratings->count() > 0)
                        <div class="space-y-4">
                            @foreach($ratings as $rating)
                                <div class="border border-gray-200 rounded-lg p-4 sm:p-6 hover:bg-gray-50 transition">
                                    <div class="flex flex-col sm:flex-row items-start gap-4">
                                        <!-- User Avatar -->
                                        <div class="flex-shrink-0">
                                            @if($rating->user->avatar)
                                                <img src="{{ asset('storage/' . $rating->user->avatar) }}" 
                                                     alt="{{ $rating->user->name }}"
                                                     class="w-12 h-12 sm:w-16 sm:h-16 rounded-full object-cover border-2 border-primary">
                                            @else
                                                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded-full bg-primary flex items-center justify-center border-2 border-primary">
                                                    <span class="text-white text-lg sm:text-xl font-bold">
                                                        {{ strtoupper(substr($rating->user->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Rating Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                                                <div>
                                                    <h3 class="font-semibold text-gray-800 text-lg">{{ $rating->user->name }}</h3>
                                                    <div class="flex items-center gap-2 mt-1">
                                                        <div class="flex items-center gap-1">
                                                            @for($i = 1; $i <= 5; $i++)
                                                                @if($i <= $rating->rating)
                                                                    <i class="fas fa-star text-yellow-400"></i>
                                                                @else
                                                                    <i class="far fa-star text-gray-300"></i>
                                                                @endif
                                                            @endfor
                                                        </div>
                                                        <span class="text-sm text-gray-600">({{ $rating->rating }}/5)</span>
                                                    </div>
                                                </div>
                                                <div class="text-xs sm:text-sm text-gray-500">
                                                    <i class="fas fa-clock ml-1"></i>
                                                    {{ $rating->created_at->format('Y-m-d') }}
                                                    <span class="mr-2">•</span>
                                                    {{ $rating->created_at->diffForHumans() }}
                                                </div>
                                            </div>

                                            @if($rating->comment)
                                                <div class="mt-3 p-3 bg-gray-50 rounded-lg border-r-4 border-primary">
                                                    <p class="text-gray-700 leading-relaxed">{{ $rating->comment }}</p>
                                                </div>
                                            @else
                                                <p class="text-sm text-gray-500 mt-2">{{ __('frontend.profile.no_comment') }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $ratings->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-star text-gray-300 text-6xl mb-4"></i>
                            <p class="text-gray-500 text-lg">{{ __('frontend.profile.no_ratings_received') }}</p>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

