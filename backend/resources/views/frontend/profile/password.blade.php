@extends('frontend.layouts.app')

@section('title', __('frontend.profile.set_password'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.profile.set_password') }}
                    </h1>

                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-sm text-green-600">
                                <i class="fas fa-check-circle ml-1"></i> {{ session('success') }}
                            </p>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <ul class="text-sm text-red-600 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li><i class="fas fa-exclamation-circle ml-1"></i> {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('profile.password.update') }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.profile.current_password') }} <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       id="current_password" 
                                       name="current_password" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent pr-12">
                                <button type="button" 
                                        onclick="togglePassword('current_password')"
                                        class="absolute {{ app()->getLocale() === 'ar' ? 'left-4' : 'right-4' }} top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-primary">
                                    <i class="fas fa-eye" id="current_passwordToggle"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.profile.new_password') }} <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent pr-12">
                                <button type="button" 
                                        onclick="togglePassword('password')"
                                        class="absolute {{ app()->getLocale() === 'ar' ? 'left-4' : 'right-4' }} top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-primary">
                                    <i class="fas fa-eye" id="passwordToggle"></i>
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">
                                {{ __('frontend.profile.confirm_password') }} <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       required
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent pr-12">
                                <button type="button" 
                                        onclick="togglePassword('password_confirmation')"
                                        class="absolute {{ app()->getLocale() === 'ar' ? 'left-4' : 'right-4' }} top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-primary">
                                    <i class="fas fa-eye" id="password_confirmationToggle"></i>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
                            <button type="submit" class="btn-primary px-6 sm:px-8 py-3 rounded-lg font-bold text-sm sm:text-base">
                                <i class="fas fa-save ml-2"></i>
                                {{ __('frontend.profile.update') }}
                            </button>
                            <a href="{{ route('profile.index') }}" 
                               class="px-6 sm:px-8 py-3 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-lg transition font-semibold text-sm sm:text-base">
                                {{ __('frontend.cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</div>

<script>
    function togglePassword(fieldId) {
        const passwordInput = document.getElementById(fieldId);
        const toggleId = fieldId + 'Toggle';
        const passwordToggle = document.getElementById(toggleId);
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            passwordToggle.classList.remove('fa-eye');
            passwordToggle.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            passwordToggle.classList.remove('fa-eye-slash');
            passwordToggle.classList.add('fa-eye');
        }
    }
</script>
@endsection

