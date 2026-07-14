@extends('frontend.layouts.app')

@section('title', __('frontend.negotiations.received_requests'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.negotiations.received_requests') }}
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
                                                        {{ __('frontend.negotiations.current_price') }}: 
                                                        <span class="font-semibold">{{ $negotiation->ad->display_price ?? '-' }}</span>
                                                    </p>
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
                                                        {{ __('frontend.negotiations.from') }}: {{ $negotiation->buyer->name }}
                                                    </p>
                                                    <p class="text-xs text-gray-500">
                                                        {{ __('frontend.negotiations.received_at') }}: {{ $negotiation->created_at->format('Y-m-d H:i') }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex flex-col items-end gap-2">
                                            @if($negotiation->status === 'pending')
                                                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold mb-2">
                                                    {{ __('frontend.negotiations.pending') }}
                                                </span>
                                                <div class="flex gap-2">
                                                    <form action="{{ route('negotiations.accept', $negotiation->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" 
                                                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                                                            <i class="fas fa-check ml-1"></i>
                                                            {{ __('frontend.negotiations.accept') }}
                                                        </button>
                                                    </form>
                                                    <button type="button" 
                                                            onclick="showRejectModal({{ $negotiation->id }})"
                                                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
                                                        <i class="fas fa-times ml-1"></i>
                                                        {{ __('frontend.negotiations.reject') }}
                                                    </button>
                                                </div>
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
                            <p class="text-gray-500 text-lg mb-4">{{ __('frontend.negotiations.no_received_requests') }}</p>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold text-gray-800 mb-4">{{ __('frontend.negotiations.reject_negotiation') }}</h3>
            <form id="rejectForm" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="rejection_reason" class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('frontend.negotiations.rejection_reason') }} <span class="text-gray-500 text-xs">({{ __('frontend.optional') }})</span>
                    </label>
                    <textarea name="rejection_reason" 
                              id="rejection_reason" 
                              rows="4" 
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                              placeholder="{{ __('frontend.negotiations.rejection_reason_placeholder') }}"></textarea>
                </div>
                <div class="flex items-center justify-end gap-4">
                    <button type="button" 
                            onclick="hideRejectModal()"
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-semibold">
                        {{ __('frontend.cancel') }}
                    </button>
                    <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold">
                        {{ __('frontend.negotiations.reject') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showRejectModal(negotiationId) {
            document.getElementById('rejectForm').action = '{{ route("negotiations.reject", ":id") }}'.replace(':id', negotiationId);
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function hideRejectModal() {
            document.getElementById('rejectModal').classList.add('hidden');
            document.getElementById('rejection_reason').value = '';
        }
    </script>
</div>
@endsection

