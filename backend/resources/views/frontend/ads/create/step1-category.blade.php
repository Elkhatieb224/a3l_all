@extends('frontend.layouts.app')

@section('title', __('frontend.ads.create_ad'))

@section('content')
<div class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ __('frontend.ads.choose_category_step_by_step') }}</h1>
                <p class="text-gray-600">{{ __('frontend.ads.select_main_category') }}</p>
            </div>

            <!-- Categories Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($categories as $category)
                    <form action="{{ route('ads.create.category') }}" method="POST" class="h-full">
                        @csrf
                        <input type="hidden" name="category_id" value="{{ $category->id }}">
                        <button type="submit" 
                                class="w-full bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition-all duration-300 text-right h-full flex flex-col items-start hover:border-2 hover:border-primary group">
                            @if($category->icon)
                                <div class="mb-4 w-16 h-16 flex items-center justify-center bg-gray-50 rounded-lg group-hover:bg-primary/10 transition">
                                    <img src="{{ asset('storage/' . $category->icon) }}" 
                                         alt="{{ $category->getName(app()->getLocale()) }}" 
                                         class="w-12 h-12 object-contain">
                                </div>
                            @else
                                <div class="mb-4 w-16 h-16 flex items-center justify-center bg-gray-50 rounded-lg group-hover:bg-primary/10 transition">
                                    <i class="fas fa-folder text-3xl text-gray-400 group-hover:text-primary transition"></i>
                                </div>
                            @endif
                            <h3 class="text-lg font-bold text-gray-800 mb-2 group-hover:text-primary transition">
                                {{ $category->getName(app()->getLocale()) }}
                            </h3>
                            @if($category->getDescription(app()->getLocale()))
                                <p class="text-sm text-gray-600 line-clamp-2">
                                    {{ Str::limit($category->getDescription(app()->getLocale()), 60) }}
                                </p>
                            @endif
                            <div class="mt-auto pt-4 w-full">
                                <span class="text-xs text-primary font-semibold flex items-center gap-1">
                                    {{ __('frontend.ads.continue') }}
                                    <i class="fas fa-arrow-left"></i>
                                </span>
                            </div>
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

