@extends('frontend.layouts.app')

@section('title', __('frontend.negotiations.sent_requests'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.negotiations.sent_requests') }}
                    </h1>

                    @if($negotiations->count() > 0)
                        <div class="space-y-4">
                            @foreach($negotiations as $negotiation)
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition">
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                                        <div class="flex-1">
                                            <div class="flex items-start gap-4 mb-3">
                                                @php
                                                    $images = is_array($negotiation->ad->images) ? $negotiation->ad->images : (is_string($negotiation->ad->images) ? json_decode($negotiation->ad->images, true) : []);
                                                    $images = $images ?? [];
                                                    $firstImage = !empty($images) && is_array($images) ? $images[0] : null;
                                                    $firstImagePath = is_string($firstImage) ? $firstImage : (is_array($firstImage) ? ($firstImage['path'] ?? $firstImage) : '');
                                                @endphp
                                                @if($firstImagePath)
                                                    <img src="{{ asset('storage/' . $firstImagePath) }}" 
                                                         alt="{{ $negotiation->ad->title }}"
                                                         class="w-20 h-20 object-cover rounded-lg"
                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div class="w-20 h-20 hidden bg-gray-200 rounded-lg items-center justify-center">
                                                        <i class="fas fa-image text-gray-400"></i>
                                                    </div>
                                                @else
                                                    <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center">
                                                        <i class="fas fa-image text-gray-400"></i>
                                                    </div>
                                                @endif
                                                <div class="flex-1">
                                                    <a href="{{ route('ads.show', $negotiation->ad->uid) }}" 
                                                       class="font-bold text-gray-800 hover:text-primary transition mb-1 block">
                                                        {{ $negotiation->ad->title }}
                                                    </a>
                                                    <p class="text-sm text-gray-600 mb-2">
                                                        {{ __('frontend.negotiations.offered_price') }}: 
                                                        <span class="font-semibold text-primary">
                                                            {{ format_price($negotiation->offered_price, 2, $negotiation->currency) }}
                                                        </span>
                                                    </p>
                                                    @if($negotiation->message)
                                                        <p class="text-sm text-gray-700 mb-2">
                                                            <i class="fas fa-comment ml-1"></i>
                                                            {{ Str::limit($negotiation->message, 100) }}
                                                        </p>
                                                    @endif
                                                    <p class="text-xs text-gray-500">
                                                        {{ __('frontend.negotiations.sent_to') }}: {{ $negotiation->seller->name }}
                                                    </p>
                                                    <p class="text-xs text-gray-500">
                                                        {{ __('frontend.negotiations.sent_at') }}: {{ $negotiation->created_at->format('Y-m-d H:i') }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end gap-2">
                                            @if($negotiation->status === 'pending')
                                                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">
                                                    {{ __('frontend.negotiations.pending') }}
                                                </span>
                                            @elseif($negotiation->status === 'accepted')
                                                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">
                                                    {{ __('frontend.negotiations.accepted') }}
                                                </span>
                                                @if($negotiation->conversation_id)
                                                    <a href="{{ route('messages.show', $negotiation->conversation_id) }}" 
                                                       class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-primary-dark transition text-sm">
                                                        <i class="fas fa-comments ml-1"></i>
                                                        {{ __('frontend.negotiations.view_conversation') }}
                                                    </a>
                                                @endif
                                            @elseif($negotiation->status === 'rejected')
                                                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">
                                                    {{ __('frontend.negotiations.rejected') }}
                                                </span>
                                                @if($negotiation->rejection_reason)
                                                    <div class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700 max-w-xs">
                                                        <strong>{{ __('frontend.negotiations.rejection_reason') }}:</strong>
                                                        <p class="mt-1">{{ $negotiation->rejection_reason }}</p>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $negotiations->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-handshake text-gray-300 text-6xl mb-4"></i>
                            <p class="text-gray-500 text-lg mb-4">{{ __('frontend.negotiations.no_sent_requests') }}</p>
                            <a href="{{ route('ads.index') }}" class="btn-primary inline-block">
                                <i class="fas fa-search ml-2"></i>
                                {{ __('frontend.ads.browse_ads') }}
                            </a>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

