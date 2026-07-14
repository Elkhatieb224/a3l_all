@extends('frontend.layouts.app')

@section('title', __('frontend.help.message_details'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">
                            {{ __('frontend.help.message_details') }}
                        </h1>
                    </div>
                    <a href="{{ route('profile.support-messages.index') }}" class="text-gray-600 hover:text-primary transition">
                        <i class="fas fa-arrow-right ml-2"></i>
                        {{ __('frontend.back') }}
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Message Header -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-xl font-bold text-gray-800">{{ $message->subject }}</h2>
                    </div>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full
                        @if($message->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($message->status === 'in_progress') bg-blue-100 text-blue-800
                        @elseif($message->status === 'resolved') bg-green-100 text-green-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ __('frontend.help.status_' . $message->status) }}
                    </span>
                </div>
                <div class="text-sm text-gray-600 mb-4">
                    {{ __('frontend.help.date') }}: {{ $message->created_at->format('Y-m-d H:i') }}
                </div>
            </div>

            <!-- Conversation Thread -->
            <div class="space-y-4 mb-6">
                <!-- Original Message -->
                <div class="bg-gray-50 border border-gray-200 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                            <i class="fas fa-user"></i>
                            {{ __('frontend.help.you') }}
                        </h4>
                        <span class="text-sm text-gray-600">
                            {{ $message->created_at->format('Y-m-d H:i') }}
                        </span>
                    </div>
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <p class="text-gray-900 whitespace-pre-wrap">{{ $message->message }}</p>
                    </div>
                </div>

                <!-- Original Admin Response (if exists) -->
                @if($message->admin_response)
                    <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-lg font-bold text-green-800 flex items-center gap-2">
                                <i class="fas fa-user-shield"></i>
                                {{ __('frontend.help.admin_response') }}
                            </h4>
                            <span class="text-sm text-gray-600">
                                @if($message->responded_at)
                                    {{ $message->responded_at->format('Y-m-d H:i') }}
                                @endif
                            </span>
                        </div>
                        <div class="bg-white border border-green-200 rounded-lg p-4">
                            <p class="text-gray-900 whitespace-pre-wrap">{{ $message->admin_response }}</p>
                        </div>
                        @if($message->admin)
                            <div class="text-sm text-gray-600 mt-2">
                                <p>{{ __('frontend.help.responded_by') }}: {{ $message->admin->name }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                <!-- Replies Thread -->
                @if($message->replies->count() > 0)
                    @foreach($message->replies as $reply)
                        <div class="bg-{{ $reply->isFromAdmin() ? 'blue' : 'gray' }}-50 border border-{{ $reply->isFromAdmin() ? 'blue' : 'gray' }}-200 rounded-xl p-6">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-lg font-bold text-{{ $reply->isFromAdmin() ? 'blue' : 'gray' }}-800 flex items-center gap-2">
                                    <i class="fas fa-{{ $reply->isFromAdmin() ? 'user-shield' : 'user' }}"></i>
                                    {{ $reply->isFromAdmin() ? __('frontend.help.admin') : __('frontend.help.you') }}
                                </h4>
                                <span class="text-sm text-gray-600">
                                    {{ $reply->created_at->format('Y-m-d H:i') }}
                                </span>
                            </div>
                            <div class="bg-white border border-{{ $reply->isFromAdmin() ? 'blue' : 'gray' }}-200 rounded-lg p-4">
                                <p class="text-gray-900 whitespace-pre-wrap">{{ $reply->message }}</p>
                            </div>
                            @if($reply->isFromAdmin() && $reply->admin)
                                <div class="text-sm text-gray-600 mt-2">
                                    <p>{{ $reply->admin->name }}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                @endif

                <!-- Pending Status -->
                @if(!$message->admin_response && $message->replies->count() == 0)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                            <div>
                                <h4 class="text-lg font-bold text-yellow-800 mb-1">{{ __('frontend.help.pending_response') }}</h4>
                                <p class="text-yellow-700 text-sm">{{ __('frontend.help.pending_response_description') }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Reply Form -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h4 class="text-lg font-bold text-primary mb-4">{{ __('frontend.help.send_reply') }}</h4>
                <form action="{{ route('profile.support-messages.reply', $message->id) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('frontend.help.message') }} <span class="text-red-500">*</span>
                        </label>
                        <textarea name="message" rows="6" required
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                                  placeholder="{{ __('frontend.help.reply_placeholder') }}">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center gap-4">
                        <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-bold">
                            <i class="fas fa-paper-plane ml-2"></i>
                            {{ __('frontend.help.send_reply') }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-4">
                <a href="{{ route('profile.support-messages.index') }}" class="btn-secondary px-6 py-3 rounded-lg font-bold">
                    <i class="fas fa-arrow-right ml-2"></i>
                    {{ __('frontend.back') }}
                </a>
                <a href="{{ route('help.contact') }}" class="btn-primary px-6 py-3 rounded-lg font-bold">
                    <i class="fas fa-plus ml-2"></i>
                    {{ __('frontend.help.send_new_message') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

