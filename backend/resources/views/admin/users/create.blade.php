@extends('admin.layouts.app')

@section('title', 'إضافة مستخدم جديد')
@section('page-title', 'إضافة مستخدم جديد')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-check-circle ml-2"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-circle ml-2"></i>
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.index') }}"
               class="text-gray-600 hover:text-primary">
                <i class="fas fa-arrow-right text-xl"></i>
            </a>
            <h2 class="text-2xl font-bold text-primary">إضافة مستخدم جديد</h2>
        </div>
    </div>

    <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-6">
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
                        الاسم <span class="text-red-500">*</span>
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
                        البريد الإلكتروني <span class="text-red-500">*</span>
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
                        كلمة المرور <span class="text-red-500">*</span>
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
                        تأكيد كلمة المرور <span class="text-red-500">*</span>
                    </label>
                    <input type="password"
                           name="password_confirmation"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        رقم الهاتف
                    </label>
                    <input type="text"
                           name="phone"
                           value="{{ old('phone') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('phone')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Additional Info -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-user-circle text-secondary"></i>
                معلومات إضافية
            </h3>

            <div class="space-y-4">
                <!-- Bio -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        السيرة الذاتية
                    </label>
                    <textarea name="bio"
                              rows="4"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">{{ old('bio') }}</textarea>
                    @error('bio')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Location Country -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        الدولة
                    </label>
                    <input type="text"
                           name="location_country"
                           value="{{ old('location_country') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('location_country')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Location City -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        المدينة
                    </label>
                    <input type="text"
                           name="location_city"
                           value="{{ old('location_city') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('location_city')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-toggle-on text-secondary"></i>
                الحالة
            </h3>

            <div class="space-y-4">
                <!-- Verified -->
                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_verified" value="0">
                    <input type="checkbox"
                           name="is_verified"
                           value="1"
                           {{ old('is_verified') ? 'checked' : '' }}
                           class="w-5 h-5 text-secondary border-gray-300 rounded focus:ring-secondary">
                    <label class="text-sm font-semibold text-gray-700">
                        حساب موثق
                    </label>
                </div>

                <!-- Active -->
                <div class="flex items-center gap-3">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox"
                           name="is_active"
                           value="1"
                           {{ old('is_active', true) ? 'checked' : '' }}
                           class="w-5 h-5 text-secondary border-gray-300 rounded focus:ring-secondary">
                    <label class="text-sm font-semibold text-gray-700">
                        حساب نشط
                    </label>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-bold">
                <i class="fas fa-save ml-2"></i>
                حفظ المستخدم
            </button>

            <a href="{{ route('admin.users.index') }}"
               class="px-8 py-3 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-lg transition font-semibold">
                <i class="fas fa-times ml-2"></i>
                إلغاء
            </a>
        </div>
    </form>
</div>
@endsection

