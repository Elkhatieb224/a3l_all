@extends('frontend.layouts.app')

@section('title', __('frontend.ads.choose_subcategory'))

@section('content')
<div class="bg-gray-50 min-h-screen py-8">
    <div class="container mx-auto px-4">
        <div class="max-w-7xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">{{ __('frontend.ads.choose_category_step_by_step') }}</h1>
            </div>

            <!-- Breadcrumbs -->
            <div class="bg-white rounded-lg shadow-md p-4 mb-6">
                <div class="flex items-center gap-2 flex-wrap text-sm">
                    @if(!empty($selectedSubcategories))
                        @foreach($selectedSubcategories as $index => $sub)
                            <span class="text-gray-700">{{ $sub['name'] }}</span>
                            @if($index < count($selectedSubcategories) - 1)
                                <span class="text-gray-400"> < </span>
                            @endif
                        @endforeach
                        <span class="text-gray-400"> < </span>
                    @endif
                    <span class="text-primary font-semibold">{{ $category->getName(app()->getLocale()) }}</span>
                </div>
            </div>

            <!-- Subcategories Selection -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Column 1: Main Category -->
                <div class="bg-white rounded-lg shadow-md p-4">
                    <h3 class="text-sm font-semibold text-gray-500 mb-3">{{ __('frontend.ads.main_category') }}</h3>
                    <div class="space-y-2">
                        <div class="p-3 bg-gray-100 rounded-lg flex items-center justify-between">
                            <span class="font-semibold text-gray-800">{{ $category->getName(app()->getLocale()) }}</span>
                            <i class="fas fa-arrow-left text-primary"></i>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Levels - Show all selected levels and next available level -->
                @for($levelIndex = 0; $levelIndex <= count($selectedSubcategories); $levelIndex++)
                    @if(isset($levels[$levelIndex]) && $levels[$levelIndex]->count() > 0)
                        <div class="bg-white rounded-lg shadow-md p-4">
                            <h3 class="text-sm font-semibold text-gray-500 mb-3">
                                @if($levelIndex === 0)
                                    {{ __('frontend.ads.subcategory_level_1') }}
                                @elseif($levelIndex === 1)
                                    {{ __('frontend.ads.subcategory_level_2') }}
                                @elseif($levelIndex === 2)
                                    {{ __('frontend.ads.subcategory_level_3') }}
                                @else
                                    {{ __('frontend.ads.subcategory_level') }} {{ $levelIndex + 1 }}
                                @endif
                            </h3>
                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                @foreach($levels[$levelIndex] as $sub)
                                    @php
                                        $isSelected = isset($selectedSubcategories[$levelIndex]) && $selectedSubcategories[$levelIndex]['id'] == $sub->id;
                                    @endphp
                                    <form action="{{ route('ads.create.subcategory.process') }}" method="POST" class="mb-2">
                                        @csrf
                                        <input type="hidden" name="subcategory_id" value="{{ $sub->id }}">
                                        <button type="submit" 
                                                class="w-full p-3 rounded-lg flex items-center justify-between transition {{ $isSelected ? 'bg-primary text-white' : 'bg-gray-50 hover:bg-gray-100 text-gray-800' }}">
                                            <span class="font-semibold">{{ $sub->getName(app()->getLocale()) }}</span>
                                            <i class="fas fa-arrow-left text-xs"></i>
                                        </button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endfor

                <!-- Completion Card - Show if no more levels available -->
                @if(!empty($selectedSubcategories))
                    @php
                        $lastSubcategory = \App\Models\Subcategory::find(end($selectedSubcategories)['id']);
                        $hasMoreLevels = \App\Models\Subcategory::where('parent_subcategory_id', $lastSubcategory->id)
                            ->active()
                            ->exists();
                        $nextLevelIndex = count($selectedSubcategories);
                    @endphp
                    
                    @if(!$hasMoreLevels)
                        <!-- Show completion card -->
                        <div class="bg-white border border-gray-200 rounded-lg p-6 text-center">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-check text-green-600 text-2xl"></i>
                            </div>
                            <p class="text-gray-700 font-semibold mb-4">
                                {{ __('frontend.ads.category_selection_completed') }}
                            </p>
                            <a href="{{ route('ads.create.details') }}" 
                               class="btn-primary px-6 py-3 rounded-lg font-bold inline-block">
                                {{ __('frontend.ads.continue') }}
                            </a>
                        </div>
                    @endif
                @endif
            </div>

            <!-- Back Button -->
            <div class="mt-6 text-center">
                <a href="{{ route('ads.create') }}" 
                   class="text-gray-600 hover:text-gray-800 inline-flex items-center gap-2">
                    <i class="fas fa-arrow-right"></i>
                    {{ __('frontend.ads.back_to_categories') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
