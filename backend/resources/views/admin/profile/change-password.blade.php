@extends('admin.layouts.app')

@section('title', __('admin.change_password_title'))
@section('page-title', __('admin.change_password_title'))

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('admin.profile.index') }}"
               class="text-gray-600 hover:text-primary">
                <i class="fas fa-arrow-right text-xl"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-primary">{{ __('admin.change_password_title') }}</h2>
                <p class="text-gray-600 mt-1">{{ __('admin.welcome_user') }}، {{ auth('admin')->user()->name }}</p>
            </div>
        </div>

        <form action="{{ route('admin.profile.change-password.submit') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Current Password -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    {{ __('admin.current_password') }} <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="password"
                           name="current_password"
                           id="current_password"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary pr-12">
                    <button type="button"
                            onclick="togglePassword('current_password')"
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-primary">
                        <i class="fas fa-eye" id="current_password-icon"></i>
                    </button>
                </div>
                @error('current_password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- New Password -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    {{ __('admin.new_password') }} <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="password"
                           name="password"
                           id="password"
                           required
                           minlength="6"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary pr-12">
                    <button type="button"
                            onclick="togglePassword('password')"
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-primary">
                        <i class="fas fa-eye" id="password-icon"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">الحد الأدنى 6 أحرف</p>
                @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    {{ __('admin.confirm_password') }} <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <input type="password"
                           name="password_confirmation"
                           id="password_confirmation"
                           required
                           minlength="6"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary pr-12">
                    <button type="button"
                            onclick="togglePassword('password_confirmation')"
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-primary">
                        <i class="fas fa-eye" id="password_confirmation-icon"></i>
                    </button>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex items-start gap-3">
                    <i class="fas fa-shield-alt text-yellow-600 mt-1"></i>
                    <div class="text-sm text-yellow-800">
                        <p class="font-semibold mb-1">نصائح الأمان:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>استخدم كلمة مرور قوية (أحرف كبيرة وصغيرة وأرقام)</li>
                            <li>لا تشارك كلمة المرور مع أحد</li>
                            <li>غيّر كلمة المرور بشكل دوري</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center gap-4 pt-4">
                <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-bold">
                    <i class="fas fa-key ml-2"></i>
                    {{ __('admin.save') }}
                </button>
                <a href="{{ route('admin.profile.index') }}"
                   class="px-8 py-3 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-lg transition font-semibold">
                    <i class="fas fa-times ml-2"></i>
                    {{ __('admin.cancel') }}
                </a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + '-icon');

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
@endpush
@endsection

