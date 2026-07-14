@extends('admin.layouts.app')

@section('title', __('admin.admins.add_new'))
@section('page-title', __('admin.admins.add_new'))

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.admins.index') }}"
               class="text-gray-600 hover:text-primary">
                <i class="fas fa-arrow-right text-xl"></i>
            </a>
            <h2 class="text-2xl font-bold text-primary">{{ __('admin.admins.add_new') }}</h2>
        </div>
    </div>

    <form action="{{ route('admin.admins.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- Basic Info -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-info-circle text-secondary"></i>
                المعلومات الأساسية
            </h3>

            <div class="space-y-4">
                <!-- Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.admins.name') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           value="{{ old('name') }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('name')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.admins.email') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="email"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.password') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="password"
                           name="password"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Confirmation -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.confirm_password') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="password"
                           name="password_confirmation"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                </div>

                <!-- Role -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.admins.role') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="role"
                            id="roleSelect"
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                        <option value="">{{ __('admin.select_role') }}</option>
                        <option value="moderator">{{ __('admin.roles.moderator') }}</option>
                        <option value="support_agent">{{ __('admin.roles.support_agent') }}</option>
                        <option value="admin">{{ __('admin.roles.admin') }}</option>
                        <option value="super_admin">{{ __('admin.roles.super_admin') }}</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Permissions Preview -->
        <div class="bg-white rounded-xl shadow-md p-6" id="permissionsSection" style="display: none;">
            <h3 class="text-xl font-bold text-primary mb-4 flex items-center gap-2">
                <i class="fas fa-key text-secondary"></i>
                {{ __('admin.permissions.title') }}
            </h3>
            <p class="text-sm text-gray-600 mb-4">
                {{ __('admin.permissions.description') }}
            </p>
            <div id="permissionsList" class="flex flex-wrap gap-2">
                <!-- Permissions will be dynamically inserted here -->
            </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-bold">
                <i class="fas fa-save ml-2"></i>
                {{ __('admin.save') }}
            </button>

            <a href="{{ route('admin.admins.index') }}"
               class="px-8 py-3 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-lg transition font-semibold">
                <i class="fas fa-times ml-2"></i>
                {{ __('admin.cancel') }}
            </a>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Role permissions mapping
    const rolePermissions = {
        'super_admin': [
            'manage_admins',
            'view_logs',
            'manage_categories',
            'manage_users',
            'manage_subcategories',
            'manage_ads',
            'manage_packages',
            'manage_payments',
            'manage_settings',
            'manage_translations',
            'view_reporting',
            'view_categories',
            'view_ads',
            'view_reports',
            'process_reports',
            'manage_support',
            'manage_faqs',
            'send_notifications',
        ],
        'admin': [
            'manage_users',
            'manage_subcategories',
            'manage_ads',
            'manage_packages',
            'manage_payments',
            'manage_settings',
            'manage_translations',
            'view_reporting',
            'view_categories',
            'view_ads',
            'view_reports',
            'send_notifications',
        ],
        'moderator': [
            'view_categories',
            'view_ads',
            'view_reports',
        ],
        'support_agent': [
            'view_reports',
            'process_reports',
            'manage_support',
            'manage_faqs',
        ],
    };

    // Permission translations
    const permissionTranslations = {
        'manage_admins': @json(__('admin.permissions.manage_admins')),
        'view_logs': @json(__('admin.permissions.view_logs')),
        'manage_categories': @json(__('admin.permissions.manage_categories')),
        'manage_users': @json(__('admin.permissions.manage_users')),
        'manage_subcategories': @json(__('admin.permissions.manage_subcategories')),
        'manage_ads': @json(__('admin.permissions.manage_ads')),
        'manage_packages': @json(__('admin.permissions.manage_packages')),
        'manage_payments': @json(__('admin.permissions.manage_payments')),
        'manage_settings': @json(__('admin.permissions.manage_settings')),
        'manage_translations': @json(__('admin.permissions.manage_translations')),
        'view_reporting': @json(__('admin.permissions.view_reporting')),
        'view_categories': @json(__('admin.permissions.view_categories')),
        'view_ads': @json(__('admin.permissions.view_ads')),
        'view_reports': @json(__('admin.permissions.view_reports')),
        'process_reports': @json(__('admin.permissions.process_reports')),
        'manage_support': @json(__('admin.permissions.manage_support')),
        'manage_faqs': @json(__('admin.permissions.manage_faqs')),
        'send_notifications': @json(__('admin.permissions.send_notifications')),
    };

    document.getElementById('roleSelect').addEventListener('change', function() {
        const role = this.value;
        const permissionsSection = document.getElementById('permissionsSection');
        const permissionsList = document.getElementById('permissionsList');

        if (role && rolePermissions[role]) {
            permissionsList.innerHTML = '';
            rolePermissions[role].forEach(function(permission) {
                const badge = document.createElement('span');
                badge.className = 'px-3 py-1 bg-secondary/10 text-secondary rounded-full text-sm font-medium';
                badge.textContent = permissionTranslations[permission] || permission;
                permissionsList.appendChild(badge);
            });
            permissionsSection.style.display = 'block';
        } else {
            permissionsSection.style.display = 'none';
        }
    });
</script>
@endpush
@endsection

