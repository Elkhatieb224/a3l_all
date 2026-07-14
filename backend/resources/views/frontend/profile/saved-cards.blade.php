@extends('frontend.layouts.app')

@section('title', __('frontend.profile.saved_cards'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.profile.saved_cards') }}
                    </h1>

                    <div class="text-center py-12">
                        <i class="fas fa-credit-card text-gray-300 text-6xl mb-4"></i>
                        <p class="text-gray-500">{{ __('frontend.profile.no_saved_cards') }}</p>
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

