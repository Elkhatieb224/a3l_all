@extends('admin.layouts.app')

@section('title', __('admin.support.title'))
@section('page-title', __('admin.support.title'))

@section('content')
<div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm mb-1">{{ __('admin.support.total_messages') }}</p>
                    <p class="text-3xl font-bold text-primary">{{ $stats['total'] }}</p>
                </div>
                <div class="bg-primary bg-opacity-10 p-4 rounded-lg">
                    <i class="fas fa-envelope text-primary text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm mb-1">{{ __('admin.support.pending') }}</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ $stats['pending'] }}</p>
                </div>
                <div class="bg-yellow-100 p-4 rounded-lg">
                    <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm mb-1">{{ __('admin.support.resolved') }}</p>
                    <p class="text-3xl font-bold text-green-600">{{ $stats['resolved'] }}</p>
                </div>
                <div class="bg-green-100 p-4 rounded-lg">
                    <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-primary">{{ __('admin.support.messages_list') }}</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.support.id') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.support.user') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.support.subject') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.support.status') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.support.attachments') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.support.date') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('admin.support.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($messages as $message)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">#{{ $message->id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($message->user)
                                    <a href="{{ route('admin.users.show', $message->user_id) }}" class="text-primary hover:underline">
                                        {{ $message->user->name }}
                                    </a>
                                @else
                                    <span>{{ $message->name }}</span>
                                    <br>
                                    <span class="text-gray-500 text-xs">{{ $message->email }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                <a href="{{ route('admin.support.show', $message->id) }}" class="text-primary hover:underline">
                                    {{ Str::limit($message->subject, 50) }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 text-xs font-semibold rounded-full
                                    @if($message->status === 'pending') bg-yellow-100 text-yellow-800
                                    @elseif($message->status === 'in_progress') bg-blue-100 text-blue-800
                                    @elseif($message->status === 'resolved') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ __('admin.support.status_' . $message->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                @if($message->attachments && count($message->attachments) > 0)
                                    <span class="inline-flex items-center gap-1 text-primary" title="{{ count($message->attachments) }} {{ __('admin.support.attachment') }}">
                                        <i class="fas fa-paperclip"></i>
                                        {{ count($message->attachments) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $message->created_at->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="{{ route('admin.support.show', $message->id) }}" class="text-primary hover:text-blue-900 ml-4">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-4 text-gray-300"></i>
                                <p>{{ __('admin.support.no_messages') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-gray-200">
            {{ $messages->links() }}
        </div>
    </div>
</div>
@endsection

