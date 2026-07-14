@extends('frontend.layouts.app')

@section('title', __('frontend.categories.title'))

@section('content')
<div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
    <div class="mb-4 sm:mb-8">
        <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-gray-800 mb-2">
            <i class="fas fa-th-large text-secondary ml-2"></i>
            {{ __('frontend.categories.all_categories') }}
        </h1>
        <p class="text-sm sm:text-base text-gray-600">{{ __('frontend.tagline') }}</p>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-5 gap-3 sm:gap-4 lg:gap-6">
        @foreach($categories as $category)
            <a href="{{ route('categories.show', $category->slug) }}" 
               class="bg-white rounded-lg shadow-md p-4 sm:p-6 hover:shadow-xl transition group">
                <div class="text-center">
                    @if($category->icon)
                        <img src="{{ asset('storage/' . $category->icon) }}" 
                             alt="{{ $category->getName(app()->getLocale()) }}"
                             class="w-12 h-12 sm:w-16 sm:h-20 lg:w-20 lg:h-20 mx-auto mb-3 sm:mb-4 object-contain">
                    @else
                        <div class="w-12 h-12 sm:w-16 sm:h-16 lg:w-20 lg:h-20 mx-auto mb-3 sm:mb-4 bg-primary rounded-lg flex items-center justify-center">
                            <i class="fas fa-folder text-secondary text-xl sm:text-2xl lg:text-3xl"></i>
                        </div>
                    @endif
                    <h3 class="font-bold text-gray-800 group-hover:text-primary transition mb-1 sm:mb-2 text-sm sm:text-base lg:text-lg">
                        {{ $category->getName(app()->getLocale()) }}
                    </h3>
                    @if($category->subcategories_count > 0)
                        <p class="text-xs sm:text-sm text-gray-500 mb-1">
                            <i class="fas fa-list text-primary ml-1"></i>
                            {{ $category->subcategories_count }} {{ __('frontend.categories.subcategories') }}
                        </p>
                    @endif
                    <p class="text-xs sm:text-sm text-primary font-semibold">
                        <i class="fas fa-bullhorn ml-1"></i>
                        {{ $category->ads_count }} {{ __('frontend.categories.ads_count') }}
                    </p>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endsection

