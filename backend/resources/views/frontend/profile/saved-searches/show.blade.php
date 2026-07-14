@extends('frontend.layouts.app')

@section('title', __('frontend.saved_searches.results_title'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')
            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h1 class="text-2xl font-bold text-gray-800">
                            {{ $savedSearch->name ?: ($savedSearch->filters['search'] ?? __('frontend.saved_searches.untitled')) }}
                        </h1>
                        <a href="{{ route('profile.saved-searches.index') }}" class="text-primary hover:underline text-sm">
                            {{ __('frontend.saved_searches.back_to_list') }}
                        </a>
                    </div>

                    @if($ads->count() > 0)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 mb-4 sm:mb-6">
                            @foreach($ads as $ad)
                                @include('frontend.partials.ad-card', ['ad' => $ad])
                            @endforeach
                        </div>
                        <div class="mt-6">{{ $ads->links() }}</div>
                    @else
                        <p class="text-gray-500">{{ __('frontend.saved_searches.no_results_now') }}</p>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

