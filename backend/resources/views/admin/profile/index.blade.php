@extends('admin.layouts.app')

@section('title', __('admin.my_profile'))
@section('page-title', __('admin.my_profile'))

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <!-- Profile Info Card -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-start justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-primary">{{ __('admin.edit_profile') }}</h2>
                <p class="text-gray-600 mt-1">{{ __('admin.welcome_user') }}، {{ auth('admin')->user()->name }}</p>
            </div>
            <a href="{{ route('admin.profile.change-password') }}" class="btn-primary px-4 py-2 rounded-lg">
                <i class="fas fa-key ml-2"></i>
                {{ __('admin.change_password_title') }}
            </a>
        </div>

        <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Avatar Section -->
            <div class="flex items-start gap-6 pb-6 border-b">
                <div class="flex-shrink-0">
                    <img src="{{ auth('admin')->user()->avatar ? asset('storage/' . auth('admin')->user()->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode(auth('admin')->user()->name) }}"
                         alt="{{ auth('admin')->user()->name }}"
                         class="w-32 h-32 rounded-full border-4 border-secondary object-cover"
                         id="avatar-preview">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.profile_picture') }}
                    </label>
                    <input type="file"
                           name="avatar"
                           accept="image/jpeg,image/png,image/jpg"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary"
                           onchange="previewAvatar(event)">
                    <p class="text-xs text-gray-500 mt-1">
                        {{ __('admin.supported_formats') }}
                    </p>
                    @error('avatar')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Basic Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.admins.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           value="{{ old('name', auth('admin')->user()->name) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.email') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="email"
                           name="email"
                           value="{{ old('email', auth('admin')->user()->email) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.phone') }}
                    </label>
                    <input type="text"
                           name="phone"
                           value="{{ old('phone', auth('admin')->user()->phone) }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Role (Read Only) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.admins.role') }}
                    </label>
                    <input type="text"
                           value="{{ auth('admin')->user()->role }}"
                           disabled
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                </div>
            </div>

            <!-- Account Info -->
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-600">{{ __('admin.admins.last_login') }}:</span>
                        <span class="font-semibold text-gray-800">
                            {{ auth('admin')->user()->last_login_at ? auth('admin')->user()->last_login_at->format('Y-m-d H:i') : __('admin.never_logged_in') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-gray-600">{{ __('admin.created_at') }}:</span>
                        <span class="font-semibold text-gray-800">
                            {{ auth('admin')->user()->created_at->format('Y-m-d') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex items-center gap-4 pt-4">
                <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-bold">
                    <i class="fas fa-save ml-2"></i>
                    {{ __('admin.save') }}
                </button>
                <a href="{{ route('admin.dashboard') }}"
                   class="px-8 py-3 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-lg transition font-semibold">
                    <i class="fas fa-times ml-2"></i>
                    {{ __('admin.cancel') }}
                </a>
            </div>
        </form>
    </div>

    <!-- Two-factor authentication -->
    <div class="bg-white rounded-xl shadow-md p-6 space-y-4">
        <h2 class="text-xl font-bold text-primary flex items-center gap-2">
            <i class="fas fa-shield-alt text-secondary"></i>
            {{ __('admin.two_factor.section_title') }}
            @if(auth('admin')->user()->two_factor_enabled)
                <span class="text-sm font-normal px-2 py-0.5 rounded bg-green-100 text-green-800">{{ __('admin.two_factor.enabled_badge') }}</span>
            @else
                <span class="text-sm font-normal px-2 py-0.5 rounded bg-gray-100 text-gray-700">{{ __('admin.two_factor.disabled_badge') }}</span>
            @endif
        </h2>

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">{{ session('error') }}</div>
        @endif

        @if(auth('admin')->user()->two_factor_enabled)
            <div class="bg-blue-50 p-4 rounded-lg text-sm">
                <strong>{{ __('admin.two_factor.verified_email_label') }}:</strong>
                <span class="font-mono">{{ auth('admin')->user()->two_factor_email }}</span>
            </div>
            <form action="{{ route('admin.profile.two-factor.disable') }}" method="POST" class="space-y-4 max-w-md border-t pt-4" onsubmit="return confirm(@json(__('admin.two_factor.disable_title')));">
                @csrf
                <h3 class="font-semibold text-gray-800">{{ __('admin.two_factor.disable_title') }}</h3>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.two_factor.disable_password') }}</label>
                    <input type="password" name="two_factor_disable_password" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('two_factor_disable_password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 text-sm font-semibold">
                    {{ __('admin.two_factor.disable_button') }}
                </button>
            </form>
        @else
            <form action="{{ route('admin.profile.two-factor.start') }}" method="POST" class="space-y-4 max-w-xl">
                @csrf
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.two_factor.start_email_label') }}</label>
                    <input type="email" name="two_factor_email" value="{{ old('two_factor_email', session('profile_2fa_setup_email')) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary"
                           placeholder="verify@example.com">
                    <p class="text-xs text-gray-500 mt-1">{{ __('admin.two_factor.start_help') }}</p>
                    @error('two_factor_email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="btn-primary px-6 py-2 rounded-lg text-sm font-bold">
                    {{ __('admin.two_factor.send_code') }}
                </button>
            </form>

            @if(session('profile_2fa_setup_email'))
                <form action="{{ route('admin.profile.two-factor.confirm') }}" method="POST" class="space-y-4 max-w-xl border-t pt-6 mt-4">
                    @csrf
                    <h3 class="font-semibold text-gray-800">{{ __('admin.two_factor.confirm_code_label') }}</h3>
                    <div>
                        <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg font-mono text-lg tracking-widest text-center focus:ring-2 focus:ring-secondary"
                               placeholder="000000" autocomplete="one-time-code">
                        @error('code')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:opacity-90 text-sm font-bold">
                        {{ __('admin.two_factor.confirm_enable') }}
                    </button>
                </form>
            @endif
        @endif
    </div>

    <!-- Activity Summary -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-xl font-bold text-primary mb-4">{{ __('admin.recent_activities') }}</h3>
        <div class="space-y-3">
            @php
                $myLogs = auth('admin')->user()->activityLogs()->latest()->take(5)->get();
            @endphp
            @forelse($myLogs as $log)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-history text-primary"></i>
                        <span class="text-sm text-gray-700">{{ $log->action }}</span>
                    </div>
                    <span class="text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</span>
                </div>
            @empty
                <p class="text-gray-500 text-center py-4">{{ __('admin.no_recent_activities') }}</p>
            @endforelse
        </div>
    </div>
</div>

@push('scripts')
<script>
function previewAvatar(event) {
    const reader = new FileReader();
    reader.onload = function() {
        const preview = document.getElementById('avatar-preview');
        preview.src = reader.result;
    }
    reader.readAsDataURL(event.target.files[0]);
}
</script>
@endpush
@endsection

