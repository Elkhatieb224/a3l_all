@extends('frontend.layouts.app')

@section('title', __('frontend.errors.not_found_title'))

@section('content')
<div class="min-h-[60vh] flex items-center justify-center bg-gray-50 py-16 px-4">
    <div class="max-w-lg w-full text-center bg-white rounded-2xl shadow-lg p-10">
        <div class="text-primary text-6xl font-extrabold mb-4">404</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-3">{{ __('frontend.errors.not_found_heading') }}</h1>
        <p class="text-gray-600 mb-8">{{ __('frontend.errors.not_found_message') }}</p>
        <a href="{{ route('home') }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg shadow hover:bg-primary-dark transition">
            <i class="fas fa-home"></i>
            {{ __('frontend.errors.back_home') }}
        </a>
    </div>
</div>
@endsection

