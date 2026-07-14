@extends('admin.layouts.app')

@section('title', __('admin.admins.edit'))
@section('page-title', __('admin.admins.edit'))

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.admins.index') }}" 
               class="text-gray-600 hover:text-primary">
                <i class="fas fa-arrow-right text-xl"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-primary">{{ __('admin.admins.edit') }}</h2>
                <p class="text-gray-600">{{ $admin->name }}</p>
            </div>
        </div>
    </div>

    <form action="{{ route('admin.admins.update', $admin->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

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
                           value="{{ old('name', $admin->name) }}"
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
                           value="{{ old('email', $admin->email) }}"
                           required 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('email')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password (Optional for edit) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        كلمة المرور الجديدة (اتركها فارغة إذا لم ترد التغيير)
                    </label>
                    <input type="password" 
                           name="password" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('password')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Confirmation -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        تأكيد كلمة المرور
                    </label>
                    <input type="password" 
                           name="password_confirmation" 
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                </div>

                <!-- Role -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.admins.role') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="role" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                        <option value="moderator" {{ $admin->role === 'moderator' ? 'selected' : '' }}>{{ __('admin.roles.moderator') }}</option>
                        <option value="support_agent" {{ $admin->role === 'support_agent' ? 'selected' : '' }}>{{ __('admin.roles.support_agent') }}</option>
                        <option value="admin" {{ $admin->role === 'admin' ? 'selected' : '' }}>{{ __('admin.roles.admin') }}</option>
                        <option value="super_admin" {{ $admin->role === 'super_admin' ? 'selected' : '' }}>{{ __('admin.roles.super_admin') }}</option>
                    </select>
                </div>
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
@endsection

