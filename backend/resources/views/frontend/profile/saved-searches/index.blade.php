@extends('frontend.layouts.app')

@section('title', __('frontend.saved_searches.title'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')
            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-2xl font-bold text-gray-800 mb-4">{{ __('frontend.saved_searches.title') }}</h1>
                    @if($savedSearches->count() > 0)
                        <div class="space-y-3">
                            @foreach($savedSearches as $item)
                                <div class="border border-gray-200 rounded-lg p-4 flex items-center justify-between gap-3">
                                    <a href="{{ route('profile.saved-searches.show', $item->id) }}" class="flex-1 min-w-0">
                                        <p class="font-semibold text-gray-800 truncate">
                                            {{ $item->name ?: ($item->filters['search'] ?? __('frontend.saved_searches.untitled')) }}
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">{{ $item->created_at?->diffForHumans() }}</p>
                                    </a>
                                    <form action="{{ route('profile.saved-searches.destroy', $item->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600 hover:underline text-sm">{{ __('frontend.saved_searches.delete') }}</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-6">{{ $savedSearches->links() }}</div>
                    @else
                        <p class="text-gray-500">{{ __('frontend.saved_searches.empty') }}</p>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

