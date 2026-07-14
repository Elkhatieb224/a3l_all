@extends('frontend.layouts.app')

@section('title', __('frontend.help.my_support_messages'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">
                            {{ __('frontend.help.my_support_messages') }}
                        </h1>
                        <p class="text-gray-600">{{ __('frontend.help.my_support_messages_description') }}</p>
                    </div>
                    <a href="{{ route('help.contact') }}" class="btn-primary px-6 py-3 rounded-lg font-bold">
                        <i class="fas fa-plus ml-2"></i>
                        {{ __('frontend.help.send_new_message') }}
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center gap-3">
                    <i class="fas fa-check-circle"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Messages List -->
            @if($messages->count() > 0)
                <div class="bg-white rounded-xl shadow-md overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-4 text-right text-sm font-semibold text-gray-700">{{ __('frontend.help.subject') }}</th>
                                    <th class="px-6 py-4 text-right text-sm font-semibold text-gray-700">{{ __('frontend.help.status') }}</th>
                                    <th class="px-6 py-4 text-right text-sm font-semibold text-gray-700">{{ __('frontend.help.date') }}</th>
                                    <th class="px-6 py-4 text-right text-sm font-semibold text-gray-700">{{ __('frontend.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($messages as $message)
                                    <tr class="hover:bg-gray-50 transition">
                                        <td class="px-6 py-4">
                                            <div class="font-semibold text-gray-900">{{ $message->subject }}</div>
                                            <div class="text-sm text-gray-500 mt-1">{{ Str::limit($message->message, 50) }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full
                                                @if($message->status === 'pending') bg-yellow-100 text-yellow-800
                                                @elseif($message->status === 'in_progress') bg-blue-100 text-blue-800
                                                @elseif($message->status === 'resolved') bg-green-100 text-green-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ __('frontend.help.status_' . $message->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600">
                                            {{ $message->created_at->format('Y-m-d H:i') }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <a href="{{ route('profile.support-messages.show', $message->id) }}" 
                                               class="text-primary hover:text-primary-dark font-semibold">
                                                <i class="fas fa-eye ml-2"></i>
                                                {{ __('frontend.view') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $messages->links() }}
                    </div>
                </div>
            @else
                <div class="bg-white rounded-xl shadow-md p-12 text-center">
                    <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">{{ __('frontend.help.no_messages') }}</h3>
                    <p class="text-gray-500 mb-6">{{ __('frontend.help.no_messages_description') }}</p>
                    <a href="{{ route('help.contact') }}" class="btn-primary px-6 py-3 rounded-lg font-bold inline-block">
                        <i class="fas fa-plus ml-2"></i>
                        {{ __('frontend.help.send_new_message') }}
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

