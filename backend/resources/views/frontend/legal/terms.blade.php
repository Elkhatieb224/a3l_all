@extends('frontend.layouts.app')

@section('title', __('frontend.legal.terms_title'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="bg-gradient-to-r from-primary to-blue-900 text-white py-8 sm:py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-3xl sm:text-4xl font-bold mb-4">{{ __('frontend.legal.terms_title') }}</h1>
                <p class="text-blue-100 text-lg">{{ __('frontend.legal.terms_subtitle') }}</p>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8">
                @if($content)
                    <div class="prose prose-lg max-w-none legal-content" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                        {!! $content !!}
                    </div>
                @else
                    <div class="text-center py-12">
                        <i class="fas fa-file-contract text-gray-300 text-6xl mb-4"></i>
                        <p class="text-gray-500 text-lg">{{ __('frontend.legal.no_content') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.legal-content {
    line-height: 1.8;
}

.legal-content h1, .legal-content h2, .legal-content h3 {
    color: #002C60;
    margin-top: 2rem;
    margin-bottom: 1rem;
    font-weight: 700;
}

.legal-content p {
    margin-bottom: 1rem;
    color: #374151;
}

.legal-content ul, .legal-content ol {
    margin-bottom: 1rem;
    padding-right: 2rem;
    padding-left: 2rem;
}

.legal-content li {
    margin-bottom: 0.5rem;
}

.legal-content strong {
    color: #002C60;
    font-weight: 600;
}

.legal-content a {
    color: #002C60;
    text-decoration: underline;
}

.legal-content a:hover {
    color: #FFD600;
}
</style>
@endpush
@endsection

