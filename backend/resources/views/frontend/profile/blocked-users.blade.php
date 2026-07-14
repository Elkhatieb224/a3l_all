@extends('frontend.layouts.app')

@section('title', __('frontend.profile.blocked_users'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.profile.blocked_users') }}
                    </h1>

                    @if($blockedUsers->count() > 0)
                        <div class="space-y-4">
                            @foreach($blockedUsers as $blocked)
                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                    <div class="flex items-center gap-3">
                                        <img src="{{ $blocked->blockedUser->avatar ? asset('storage/' . $blocked->blockedUser->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($blocked->blockedUser->name) }}"
                                             alt="{{ $blocked->blockedUser->name }}"
                                             class="w-12 h-12 rounded-full">
                                        <div>
                                            <p class="font-semibold text-gray-800">{{ $blocked->blockedUser->name }}</p>
                                            <p class="text-sm text-gray-600">{{ $blocked->blockedUser->email }}</p>
                                            <p class="text-xs text-gray-500 mt-1">
                                                {{ __('frontend.profile.blocked_on') }}: {{ $blocked->created_at->format('Y-m-d H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                    <form action="{{ route('profile.blocked-users.unblock', $blocked->blocked_user_id) }}" 
                                          method="POST"
                                          onsubmit="return confirm('{{ __('frontend.profile.confirm_unblock') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                            <i class="fas fa-unlock ml-2"></i>
                                            {{ __('frontend.profile.unblock') }}
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-user-slash text-gray-300 text-6xl mb-4"></i>
                            <p class="text-gray-500">{{ __('frontend.profile.no_blocked_users') }}</p>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection
