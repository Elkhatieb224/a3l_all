@extends('frontend.layouts.app')

@section('title', __('frontend.messages.my_messages'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <!-- Main Content -->
            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <!-- Header -->
                    <div class="mb-6 pb-6 border-b border-gray-200">
                        <h1 class="text-2xl font-bold text-primary mb-2">{{ __('frontend.messages.my_messages') }}</h1>
                        <p class="text-gray-600 text-sm">{{ __('frontend.messages.manage_conversations') }}</p>
                    </div>

                    <!-- Conversations List -->
                    @if($conversations->count() > 0)
                        <div class="space-y-3">
                            @foreach($conversations as $conversation)
                                <a href="{{ route('messages.show', $conversation->id) }}" 
                                   class="block bg-gray-50 hover:bg-gray-100 rounded-lg p-4 transition">
                                    <div class="flex items-center gap-4">
                                        <!-- Ad Image -->
                                        <div class="w-16 h-16 flex-shrink-0">
                                            @if($conversation->ad)
                                                @php
                                                    $images = is_array($conversation->ad->images) ? $conversation->ad->images : (is_string($conversation->ad->images) ? json_decode($conversation->ad->images, true) : []);
                                                    $images = $images ?? [];
                                                    $firstImage = !empty($images) && is_array($images) ? $images[0] : null;
                                                    $firstImagePath = is_string($firstImage) ? $firstImage : (is_array($firstImage) ? ($firstImage['path'] ?? $firstImage) : '');
                                                @endphp
                                                @if($firstImagePath)
                                                    <img src="{{ asset('storage/' . $firstImagePath) }}"
                                                         alt="{{ $conversation->ad->title }}"
                                                         class="w-full h-full object-cover rounded-lg"
                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <div class="w-full h-full hidden bg-gray-200 rounded-lg items-center justify-center">
                                                        <i class="fas fa-image text-gray-400"></i>
                                                    </div>
                                                @else
                                                    <div class="w-full h-full bg-gray-200 rounded-lg flex items-center justify-center">
                                                        <i class="fas fa-image text-gray-400"></i>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="w-full h-full bg-blue-50 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-comments text-primary"></i>
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Conversation Info -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-1">
                                                <h3 class="font-semibold text-gray-800 truncate">
                                                    {{ $conversation->ad?->title ?? __('frontend.messages.chat_with_seller') }}
                                                </h3>
                                                @if($conversation->latestMessage && !$conversation->latestMessage->is_read && $conversation->latestMessage->sender_id !== Auth::id())
                                                    <span class="w-2 h-2 bg-primary rounded-full flex-shrink-0"></span>
                                                @endif
                                            </div>
                                            <p class="text-sm text-gray-600 truncate mb-1">
                                                {{ $conversation->sender_id === Auth::id() ? $conversation->receiver->name : $conversation->sender->name }}
                                            </p>
                                            @if($conversation->latestMessage)
                                                <p class="text-xs text-gray-500 truncate">
                                                    {{ $conversation->latestMessage->message }}
                                                </p>
                                            @endif
                                        </div>

                                        <!-- Time -->
                                        <div class="text-xs text-gray-500 flex-shrink-0">
                                            @if($conversation->last_message_at)
                                                {{ $conversation->last_message_at->diffForHumans() }}
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $conversations->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-comments text-gray-300 text-6xl mb-4"></i>
                            <p class="text-gray-500 text-lg mb-4">{{ __('frontend.messages.no_conversations') }}</p>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

