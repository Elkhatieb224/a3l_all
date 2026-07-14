@extends('admin.layouts.app')

@section('title', __('admin.support.message_details'))
@section('page-title', __('admin.support.message_details'))

@section('content')
<div class="space-y-6">
    <!-- Message Details -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-primary">{{ __('admin.support.message_details') }}</h3>
            <form action="{{ route('admin.support.update-status', $message->id) }}" method="POST" class="inline">
                @csrf
                @method('PUT')
                <select name="status" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    <option value="pending" {{ $message->status === 'pending' ? 'selected' : '' }}>{{ __('admin.support.status_pending') }}</option>
                    <option value="in_progress" {{ $message->status === 'in_progress' ? 'selected' : '' }}>{{ __('admin.support.status_in_progress') }}</option>
                    <option value="resolved" {{ $message->status === 'resolved' ? 'selected' : '' }}>{{ __('admin.support.status_resolved') }}</option>
                    <option value="closed" {{ $message->status === 'closed' ? 'selected' : '' }}>{{ __('admin.support.status_closed') }}</option>
                </select>
            </form>
        </div>

        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('admin.support.id') }}</label>
                    <p class="text-gray-900">#{{ $message->id }}</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('admin.support.status') }}</label>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full
                        @if($message->status === 'pending') bg-yellow-100 text-yellow-800
                        @elseif($message->status === 'in_progress') bg-blue-100 text-blue-800
                        @elseif($message->status === 'resolved') bg-green-100 text-green-800
                        @else bg-gray-100 text-gray-800
                        @endif">
                        {{ __('admin.support.status_' . $message->status) }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('admin.support.user') }}</label>
                    @if($message->user)
                        <a href="{{ route('admin.users.show', $message->user_id) }}" class="text-primary hover:underline">
                            {{ $message->user->name }}
                        </a>
                        <p class="text-sm text-gray-500">{{ $message->user->email }}</p>
                    @else
                        <p class="text-gray-900">{{ $message->name }}</p>
                        <p class="text-sm text-gray-500">{{ $message->email }}</p>
                    @endif
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('admin.support.date') }}</label>
                    <p class="text-gray-900">{{ $message->created_at->format('Y-m-d H:i:s') }}</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('admin.support.subject') }}</label>
                <p class="text-gray-900">{{ $message->subject }}</p>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('admin.support.message') }}</label>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <p class="text-gray-900 whitespace-pre-wrap">{{ $message->message }}</p>
                </div>
            </div>

            @if($message->attachments && count($message->attachments) > 0)
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.support.attachments') }}</label>
                <div class="flex flex-wrap gap-4">
                    @foreach($message->attachments as $path)
                        @php
                            $url = str_starts_with(trim($path), 'http') ? $path : asset('storage/' . ltrim($path, '/'));
                        @endphp
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="block border border-gray-200 rounded-lg overflow-hidden hover:border-primary transition" title="{{ __('admin.support.view_attachment') }}">
                            <img src="{{ $url }}" alt="{{ __('admin.support.attachment') }}" class="w-48 h-36 object-cover" loading="lazy">
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Response Section -->
    @if($message->admin_response)
        <div class="bg-green-50 border border-green-200 rounded-xl p-6">
            <h4 class="text-lg font-bold text-green-800 mb-4 flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                {{ __('admin.support.response_sent') }}
            </h4>
            <div class="bg-white border border-green-200 rounded-lg p-4 mb-4">
                <p class="text-gray-900 whitespace-pre-wrap">{{ $message->admin_response }}</p>
            </div>
            <div class="text-sm text-gray-600">
                <p>{{ __('admin.support.responded_by') }}: {{ $message->admin->name ?? '-' }}</p>
                <p>{{ __('admin.support.responded_at') }}: {{ $message->responded_at ? $message->responded_at->format('Y-m-d H:i:s') : '-' }}</p>
            </div>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-md p-6">
            <h4 class="text-lg font-bold text-primary mb-4">{{ __('admin.support.send_response') }}</h4>
            <form action="{{ route('admin.support.respond', $message->id) }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.support.response') }} <span class="text-red-500">*</span>
                    </label>
                    <textarea name="response" rows="6" required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('response') }}</textarea>
                    @error('response')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-bold">
                    <i class="fas fa-paper-plane ml-2"></i>
                    {{ __('admin.support.send_response') }}
                </button>
            </form>
        </div>
    @endif

    <div class="flex items-center gap-4">
        <a href="{{ route('admin.support.index') }}" class="text-gray-600 hover:text-primary transition">
            <i class="fas fa-arrow-right ml-2"></i>
            {{ __('admin.support.back_to_list') }}
        </a>
    </div>
</div>
@endsection

