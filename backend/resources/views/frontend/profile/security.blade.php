@extends('frontend.layouts.app')

@section('title', __('frontend.profile.security'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.profile.security') }}
                    </h1>

                    <div class="space-y-4">
                        <p class="text-gray-600">{{ __('frontend.profile.security_info') }}</p>
                        <!-- TODO: Add security settings -->
                    </div>
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

