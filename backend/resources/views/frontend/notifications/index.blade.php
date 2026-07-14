@extends('frontend.layouts.app')

@section('title', __('frontend.notifications.title'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <p class="text-sm text-green-700">
                        <i class="fas fa-check-circle ml-1"></i> {{ session('success') }}
                    </p>
                </div>
            @endif
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800">
                    {{ __('frontend.notifications.all_notifications') }}
                </h1>
                @if($notifications->whereNull('read_at')->count() > 0)
                    <form action="{{ route('profile.notifications.read-all') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="text-sm text-primary hover:underline">
                            {{ __('frontend.notifications.mark_all_read') }}
                        </button>
                    </form>
                @endif
            </div>

            @if($notifications->count() > 0)
                <div class="space-y-3">
                    @foreach($notifications as $notification)
                        @php
                            $notificationData = $notification->data ?? [];
                            $type = $notificationData['type'] ?? null;

                            $adUrl = $notificationData['ad_url'] ?? null;
                            $packageRequestId = $notificationData['package_request_id'] ?? null;
                            $hawalaTransferId = $notificationData['hawala_transfer_id'] ?? null;
                            $verificationRequestId = $notificationData['verification_request_id'] ?? null;
                            $conversationId = $notificationData['conversation_id'] ?? null;
                            $reportId = $notificationData['report_id'] ?? null;
                            $supportMessageId = $notificationData['support_message_id'] ?? null;

                            // حدد رابط مناسب للإشعار بناءً على نوعه وبياناته
                            $clickUrl = null;

                            if ($adUrl) {
                                $clickUrl = $adUrl;
                            } elseif ($type === 'package_request_responded' && $packageRequestId) {
                                $clickUrl = route('profile.package-requests.show', $packageRequestId);
                            } elseif (in_array($type, ['hawala_approved', 'hawala_rejected']) && $hawalaTransferId) {
                                $clickUrl = route('profile.hawala.index');
                            } elseif (in_array($type, ['verification_approved', 'verification_rejected']) && $verificationRequestId) {
                                $clickUrl = route('profile.verification');
                            } elseif ($type === 'package_activated') {
                                $clickUrl = route('profile.index');
                            } elseif ($type === 'new_message' && $conversationId) {
                                $clickUrl = route('messages.show', $conversationId);
                            } elseif ($type === 'report_action' && $reportId) {
                                $clickUrl = route('profile.reports.show', $reportId);
                            } elseif ($type === 'support_action' && $supportMessageId) {
                                $clickUrl = route('profile.support-messages.show', $supportMessageId);
                            } elseif ($type === 'new_negotiation_request') {
                                $clickUrl = $notificationData['negotiations_url'] ?? route('negotiations.received');
                            } elseif ($type === 'negotiation_responded') {
                                $clickUrl = $notificationData['click_url'] ?? (($notificationData['conversation_id'] ?? null) ? route('messages.show', $notificationData['conversation_id']) : route('negotiations.sent'));
                            } elseif ($type === 'saved_search_match') {
                                $savedSearchId = $notificationData['saved_search_id'] ?? null;
                                $clickUrl = $savedSearchId ? route('profile.saved-searches.show', $savedSearchId) : route('profile.saved-searches.index');
                            }

                            $isClickable = !empty($clickUrl);
                        @endphp
                        
                        @if($isClickable)
                            <a href="{{ $clickUrl }}" 
                               onclick="event.preventDefault(); markNotificationAsRead('{{ $notification->id }}'); window.location.href='{{ $clickUrl }}';"
                               class="block border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition {{ $notification->read_at ? '' : 'bg-blue-50' }} cursor-pointer">
                        @else
                            <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition {{ $notification->read_at ? '' : 'bg-blue-50' }}">
                        @endif
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 mt-1">
                                    <i class="fas fa-circle text-xs {{ $notification->read_at ? 'text-gray-300' : 'text-blue-500' }}"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1">
                                            <p class="text-sm font-medium text-gray-800">
                                                {{ $notificationData['title'] ?? __('frontend.notifications.notification') }}
                                            </p>
                                            <p class="text-sm text-gray-600 mt-1">
                                                {{ $notificationData['message'] ?? '' }}
                                            </p>
                                            <div class="flex items-center gap-4 mt-2 text-xs text-gray-500">
                                                <span>
                                                    <i class="fas fa-clock ml-1"></i>
                                                    {{ $notification->created_at->format('Y-m-d H:i') }}
                                                </span>
                                                <span>
                                                    <i class="fas fa-history ml-1"></i>
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </span>
                                                @if(!$notification->read_at)
                                                    <span class="text-blue-600">
                                                        <i class="fas fa-circle ml-1"></i>
                                                        {{ __('frontend.notifications.unread') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        @if(!$notification->read_at && !$isClickable)
                                            <form action="{{ route('profile.notifications.read', $notification->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="text-xs text-primary hover:underline">
                                                    {{ __('frontend.notifications.mark_as_read') }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @if($isClickable)
                            </a>
                        @else
                            </div>
                        @endif
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $notifications->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-bell-slash text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500">{{ __('frontend.notifications.no_notifications') }}</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    function markNotificationAsRead(notificationId) {
        fetch(`/profile/notifications/${notificationId}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
    }
</script>
@endpush
@endsection

