@extends('frontend.layouts.app')

@section('title', __('frontend.profile.cancel_account'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <!-- Main Content -->
            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-6">
                        {{ __('frontend.profile.cancel_account') }}
                    </h2>

                    @if($user->account_status === 'pending_deletion')
                        <div class="bg-yellow-50 border-r-4 border-yellow-400 p-4 mb-6">
                            <div class="flex items-start gap-3">
                                <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                                <div>
                                    <h3 class="font-bold text-yellow-800 mb-2">
                                        {{ __('frontend.profile.account_pending_deletion') }}
                                    </h3>
                                    <p class="text-sm text-yellow-700">
                                        {{ __('frontend.profile.account_will_be_deleted_on', ['date' => $user->scheduled_deletion_at->format('Y-m-d')]) }}
                                    </p>
                                    <p class="text-sm text-yellow-700 mt-2">
                                        {{ __('frontend.profile.login_to_cancel_deletion') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Warning Message -->
                    <div class="bg-red-50 border-r-4 border-red-500 p-4 mb-6">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                            <div>
                                <h3 class="font-bold text-red-800 mb-2">
                                    {{ __('frontend.profile.cancel_account_warning_title') }}
                                </h3>
                                <p class="text-sm text-red-700 mb-2">
                                    {{ __('frontend.profile.cancel_account_warning_message') }}
                                </p>
                                <p class="text-sm text-red-700">
                                    {{ __('frontend.profile.cancel_account_warning_details') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    @if($user->account_status !== 'pending_deletion')
                        <!-- Cancel Account Form -->
                        <form action="{{ route('profile.cancel-account.submit') }}" method="POST" class="space-y-6">
                            @csrf

                            <div>
                                <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                    {{ __('frontend.profile.password_required_for_cancellation') }}
                                </label>
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       required
                                       autofocus
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('password') border-red-500 @enderror">
                                @error('password')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="flex items-start gap-3">
                                <input type="checkbox" 
                                       id="confirm" 
                                       name="confirm" 
                                       required
                                       class="mt-1 w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                                <label for="confirm" class="text-sm text-gray-700">
                                    {{ __('frontend.profile.confirm_account_cancellation') }}
                                </label>
                            </div>

                            <div class="flex gap-4">
                                <button type="submit" 
                                        class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                                    {{ __('frontend.profile.confirm_cancel_account') }}
                                </button>
                                <a href="{{ route('profile.index') }}" 
                                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-3 rounded-lg font-semibold transition">
                                    {{ __('frontend.profile.cancel') }}
                                </a>
                            </div>
                        </form>
                    @else
                        <div class="text-center py-8">
                            <p class="text-gray-600 mb-4">
                                {{ __('frontend.profile.account_scheduled_for_deletion') }}
                            </p>
                            <a href="{{ route('profile.index') }}" 
                               class="inline-block bg-primary hover:bg-secondary text-white px-6 py-3 rounded-lg font-semibold transition">
                                {{ __('frontend.back') }}
                            </a>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection

