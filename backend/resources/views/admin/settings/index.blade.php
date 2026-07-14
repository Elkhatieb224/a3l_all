@extends('admin.layouts.app')

@section('title', 'الإعدادات')
@section('page-title', __('admin.settings.title'))

@section('content')
<div class="max-w-4xl mx-auto">
    <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- General Settings -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-cog text-secondary"></i>
                {{ __('admin.settings.site_settings') }}
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.settings.site_name') }}
                    </label>
                    <input type="text"
                           name="site_name"
                           value="{{ $settings->get('general', collect())->where('key', 'site_name')->first()->value ?? 'أعلنها' }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.settings.site_description') }}
                    </label>
                    <textarea name="site_description"
                              rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">{{ $settings->get('general', collect())->where('key', 'site_description')->first()->value ?? '' }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('admin.settings.contact_email') }}
                        </label>
                        <input type="email"
                               name="contact_email"
                               value="{{ $settings->get('general', collect())->where('key', 'contact_email')->first()->value ?? '' }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('admin.settings.contact_phone') }}
                        </label>
                        <input type="text"
                               name="contact_phone"
                               value="{{ $settings->get('general', collect())->where('key', 'contact_phone')->first()->value ?? '' }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('admin.settings.default_language') }}
                        </label>
                        <select name="default_language"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                            <option value="ar" {{ ($settings->get('general', collect())->where('key', 'default_language')->first()->value ?? 'ar') === 'ar' ? 'selected' : '' }}>العربية</option>
                            <option value="en" {{ ($settings->get('general', collect())->where('key', 'default_language')->first()->value ?? 'ar') === 'en' ? 'selected' : '' }}>English</option>
                            <option value="tr" {{ ($settings->get('general', collect())->where('key', 'default_language')->first()->value ?? 'ar') === 'tr' ? 'selected' : '' }}>Türkçe</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('admin.settings.timezone') }}
                        </label>
                        <select name="timezone"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                            <option value="Asia/Damascus" {{ ($settings->get('general', collect())->where('key', 'timezone')->first()->value ?? 'Asia/Damascus') === 'Asia/Damascus' ? 'selected' : '' }}>Syria (UTC+3)</option>
                            <option value="Asia/Riyadh" {{ ($settings->get('general', collect())->where('key', 'timezone')->first()->value ?? 'Asia/Damascus') === 'Asia/Riyadh' ? 'selected' : '' }}>Saudi Arabia (UTC+3)</option>
                            <option value="Europe/Istanbul" {{ ($settings->get('general', collect())->where('key', 'timezone')->first()->value ?? 'Asia/Damascus') === 'Europe/Istanbul' ? 'selected' : '' }}>Turkey (UTC+3)</option>
                            <option value="Asia/Dubai" {{ ($settings->get('general', collect())->where('key', 'timezone')->first()->value ?? 'Asia/Damascus') === 'Asia/Dubai' ? 'selected' : '' }}>UAE (UTC+4)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            العملة الافتراضية
                        </label>
                        <select name="default_currency"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                            <option value="SYP" {{ ($settings->get('general', collect())->where('key', 'default_currency')->first()->value ?? 'SYP') === 'SYP' ? 'selected' : '' }}>🇸🇾 الليرة السورية (SYP)</option>
                            <option value="TRY" {{ ($settings->get('general', collect())->where('key', 'default_currency')->first()->value ?? 'SYP') === 'TRY' ? 'selected' : '' }}>🇹🇷 الليرة التركية (TRY)</option>
                            <option value="USD" {{ ($settings->get('general', collect())->where('key', 'default_currency')->first()->value ?? 'SYP') === 'USD' ? 'selected' : '' }}>🇺🇸 الدولار الأمريكي (USD)</option>
                            <option value="EUR" {{ ($settings->get('general', collect())->where('key', 'default_currency')->first()->value ?? 'SYP') === 'EUR' ? 'selected' : '' }}>🇪🇺 اليورو (EUR)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ad Settings -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-bullhorn text-secondary"></i>
                إعدادات الإعلانات
            </h3>

            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            مدة صلاحية الإعلان (أيام)
                        </label>
                        <input type="number"
                               name="ad_default_duration"
                               value="{{ $settings->get('ads', collect())->where('key', 'ad_default_duration')->first()->value ?? 30 }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            عدد الإعلانات المجانية للمستخدم
                        </label>
                        <input type="number"
                               name="free_ads_limit"
                               value="{{ $settings->get('ads', collect())->where('key', 'free_ads_limit')->first()->value ?? 3 }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            الحد الأقصى للصور لكل إعلان
                        </label>
                        <input type="number"
                               name="max_images_per_ad"
                               value="{{ $settings->get('ads', collect())->where('key', 'max_images_per_ad')->first()->value ?? 10 }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            الحد الأقصى لحجم الصورة (ميجابايت)
                        </label>
                        <input type="number"
                               name="max_image_size_mb"
                               value="{{ $settings->get('ads', collect())->where('key', 'max_image_size_mb')->first()->value ?? 5 }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            الحد الأقصى لمدة فيديو الإعلان (ثوانٍ)
                        </label>
                        <input type="number"
                               name="ad_video_max_duration_seconds"
                               min="5"
                               max="600"
                               value="{{ $settings->get('ads', collect())->where('key', 'ad_video_max_duration_seconds')->first()->value ?? 60 }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            الحد الأقصى لحجم فيديو الإعلان (ميجابايت)
                        </label>
                        <input type="number"
                               name="ad_video_max_size_mb"
                               min="1"
                               max="500"
                               value="{{ $settings->get('ads', collect())->where('key', 'ad_video_max_size_mb')->first()->value ?? 50 }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox"
                           name="require_ad_approval"
                           value="1"
                           {{ ($settings->get('ads', collect())->where('key', 'require_ad_approval')->first()->value ?? '1') === '1' ? 'checked' : '' }}
                           class="w-5 h-5 text-secondary border-gray-300 rounded focus:ring-secondary">
                    <label class="text-sm font-semibold text-gray-700">
                        يتطلب موافقة المدير قبل نشر الإعلان
                    </label>
                </div>
            </div>
        </div>

        <!-- Verification Settings -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-shield-alt text-secondary"></i>
                {{ __('admin.settings.verification_settings') }}
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.settings.verification_requirements_ar') }}
                    </label>
                    <textarea name="verification_requirements_ar"
                              rows="6"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">{{ $settings->get('general', collect())->where('key', 'verification_requirements_ar')->first()->value ?? '' }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">{{ __('admin.settings.verification_requirements_hint') }}</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.settings.verification_requirements_en') }}
                    </label>
                    <textarea name="verification_requirements_en"
                              rows="6"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">{{ $settings->get('general', collect())->where('key', 'verification_requirements_en')->first()->value ?? '' }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.settings.verification_requirements_tr') }}
                    </label>
                    <textarea name="verification_requirements_tr"
                              rows="6"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">{{ $settings->get('general', collect())->where('key', 'verification_requirements_tr')->first()->value ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <!-- App Information Settings -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-info-circle text-secondary"></i>
                معلومات التطبيق
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        اسم المنشأة
                    </label>
                    <input type="text"
                           name="app_info_establishment_name"
                           value="{{ $settings->get('app_info', collect())->where('key', 'app_info_establishment_name')->first()->value ?? 'Aalenha.com' }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        الاسم التجاري
                    </label>
                    <input type="text"
                           name="app_info_commercial_name"
                           value="{{ $settings->get('app_info', collect())->where('key', 'app_info_commercial_name')->first()->value ?? '' }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            الشخص المسؤول
                        </label>
                        <input type="text"
                               name="app_info_responsible_person"
                               value="{{ $settings->get('app_info', collect())->where('key', 'app_info_responsible_person')->first()->value ?? '' }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            رقم السجل التجاري
                        </label>
                        <input type="text"
                               name="app_info_commercial_registration_number"
                               value="{{ $settings->get('app_info', collect())->where('key', 'app_info_commercial_registration_number')->first()->value ?? '' }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            عنوان البريد الإلكتروني الرسمي (KEP)
                        </label>
                        <input type="email"
                               name="app_info_official_email"
                               value="{{ $settings->get('app_info', collect())->where('key', 'app_info_official_email')->first()->value ?? 'aalenha@hs02.kep.tr' }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            رقم MERSIS
                        </label>
                        <input type="text"
                               name="app_info_mersis_number"
                               value="{{ $settings->get('app_info', collect())->where('key', 'app_info_mersis_number')->first()->value ?? '0739014655600017' }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        المكتب الرئيسي
                    </label>
                    <textarea name="app_info_main_office"
                              rows="3"
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">{{ $settings->get('app_info', collect())->where('key', 'app_info_main_office')->first()->value ?? '' }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            مركز الاتصال
                        </label>
                        <input type="text"
                               name="app_info_call_center"
                               value="{{ $settings->get('app_info', collect())->where('key', 'app_info_call_center')->first()->value ?? '' }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            مركز الدعم
                        </label>
                        <input type="text"
                               name="app_info_support_center"
                               value="{{ $settings->get('app_info', collect())->where('key', 'app_info_support_center')->first()->value ?? 'التوجه لقسم البلاغات و المساعدة' }}"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    </div>
                </div>

                <div class="border-t pt-4">
                    <h4 class="text-lg font-bold text-gray-800 mb-4">إعدادات الخريطة</h4>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            رابط الموقع (Google Maps Location URL)
                        </label>
                        <input type="url"
                               name="app_info_map_location_url"
                               value="{{ $settings->get('app_info', collect())->where('key', 'app_info_map_location_url')->first()->value ?? '' }}"
                               placeholder="https://maps.google.com/?q=33.5138,36.2765"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                        <p class="text-xs text-gray-500 mt-1">يمكنك الحصول على الرابط من Google Maps: انقر بزر الماوس الأيمن على الموقع → نسخ الرابط</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Terms and Conditions -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-file-contract text-secondary"></i>
                الشروط والأحكام
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        الشروط والأحكام (عربي)
                    </label>
                    <div id="terms_conditions_ar" style="height: 400px; background: white;"></div>
                    <textarea name="terms_conditions_ar" id="terms_conditions_ar_textarea" style="display: none;">{{ $settings->get('legal', collect())->where('key', 'terms_conditions_ar')->first()->value ?? '' }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        الشروط والأحكام (إنجليزي)
                    </label>
                    <div id="terms_conditions_en" style="height: 400px; background: white;"></div>
                    <textarea name="terms_conditions_en" id="terms_conditions_en_textarea" style="display: none;">{{ $settings->get('legal', collect())->where('key', 'terms_conditions_en')->first()->value ?? '' }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        الشروط والأحكام (تركي)
                    </label>
                    <div id="terms_conditions_tr" style="height: 400px; background: white;"></div>
                    <textarea name="terms_conditions_tr" id="terms_conditions_tr_textarea" style="display: none;">{{ $settings->get('legal', collect())->where('key', 'terms_conditions_tr')->first()->value ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <!-- Messaging Rules -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-comments text-secondary"></i>
                قواعد المراسلة
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        قواعد المراسلة (عربي)
                    </label>
                    <div id="messaging_rules_ar" style="height: 300px; background: white;"></div>
                    <textarea name="messaging_rules_ar" id="messaging_rules_ar_textarea" style="display: none;">{{ $settings->get('general', collect())->where('key', 'messaging_rules_ar')->first()->value ?? '' }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">سيظهر هذا النص في بداية كل محادثة بين المستخدمين</p>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        قواعد المراسلة (إنجليزي)
                    </label>
                    <div id="messaging_rules_en" style="height: 300px; background: white;"></div>
                    <textarea name="messaging_rules_en" id="messaging_rules_en_textarea" style="display: none;">{{ $settings->get('general', collect())->where('key', 'messaging_rules_en')->first()->value ?? '' }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        قواعد المراسلة (تركي)
                    </label>
                    <div id="messaging_rules_tr" style="height: 300px; background: white;"></div>
                    <textarea name="messaging_rules_tr" id="messaging_rules_tr_textarea" style="display: none;">{{ $settings->get('general', collect())->where('key', 'messaging_rules_tr')->first()->value ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <!-- Email Template Settings -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-envelope text-secondary"></i>
                {{ __('admin.settings.email_template') }}
            </h3>
            <div class="space-y-4">
                <p class="text-sm text-gray-600">
                    {{ __('admin.settings.email_template_hint') }}
                </p>
                <p class="text-sm text-gray-600">
                    {{ __('admin.settings.email_logo_hint') }}
                </p>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.settings.email_header') }}
                    </label>
                    <div id="email_header" style="height: 200px; background: white; border: 1px solid #d1d5db; border-radius: 0.5rem;"></div>
                    <textarea name="email_header" id="email_header_textarea" style="display: none;">{{ $settings->get('email', collect())->where('key', 'email_header')->first()?->value ?? '' }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">{{ __('admin.settings.email_header_hint') }}</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.settings.email_footer') }}
                    </label>
                    <div id="email_footer" style="height: 200px; background: white; border: 1px solid #d1d5db; border-radius: 0.5rem;"></div>
                    <textarea name="email_footer" id="email_footer_textarea" style="display: none;">{{ $settings->get('email', collect())->where('key', 'email_footer')->first()?->value ?? '' }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">{{ __('admin.settings.email_footer_hint') }}</p>
                </div>
            </div>
        </div>

        <!-- Country Codes Settings -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-globe text-secondary"></i>
                {{ __('admin.settings.country_codes') }}
            </h3>

            <div class="space-y-4">
                <!-- Add Country Code Form -->
                <div class="flex gap-3 items-end">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            {{ __('admin.settings.add_country_code') }}
                        </label>
                        <input type="text"
                               id="new_country_code"
                               placeholder="+963"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary"
                               maxlength="10">
                        <p class="text-xs text-gray-500 mt-1">
                            {{ __('admin.settings.country_code_hint') }}
                        </p>
                    </div>
                    <button type="button"
                            id="add_country_code_btn"
                            class="btn-primary px-6 py-3 rounded-lg font-semibold whitespace-nowrap">
                        <i class="fas fa-plus ml-2"></i>
                        {{ __('admin.settings.add') }}
                    </button>
                </div>

                <!-- Country Codes List -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        {{ __('admin.settings.country_codes_list') }}
                    </label>
                    @php
                        $countryCodesSetting = $settings->get('general', collect())->where('key', 'country_codes')->first();
                        $countryCodes = [];
                        if ($countryCodesSetting && $countryCodesSetting->type === 'json') {
                            $decoded = json_decode($countryCodesSetting->value, true);
                            $countryCodes = is_array($decoded) ? $decoded : [];
                        }
                    @endphp
                    <div id="country_codes_list" class="space-y-2 min-h-[100px]">
                        @if(count($countryCodes) > 0)
                            @foreach($countryCodes as $index => $codeItem)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 country-code-item" data-code="{{ htmlspecialchars($codeItem['code'] ?? '') }}">
                                    <span class="font-semibold text-gray-800">{{ $codeItem['code'] ?? '' }}</span>
                                    <button type="button"
                                            class="delete-country-code-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors"
                                            data-code="{{ htmlspecialchars($codeItem['code'] ?? '') }}">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-info-circle text-3xl mb-2"></i>
                                <p>{{ __('admin.settings.no_country_codes') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Privacy Policy -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-shield-alt text-secondary"></i>
                سياسة الخصوصية والاستخدام
            </h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        سياسة الخصوصية (عربي)
                    </label>
                    <div id="privacy_policy_ar" style="height: 400px; background: white;"></div>
                    <textarea name="privacy_policy_ar" id="privacy_policy_ar_textarea" style="display: none;">{{ $settings->get('legal', collect())->where('key', 'privacy_policy_ar')->first()->value ?? '' }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        سياسة الخصوصية (إنجليزي)
                    </label>
                    <div id="privacy_policy_en" style="height: 400px; background: white;"></div>
                    <textarea name="privacy_policy_en" id="privacy_policy_en_textarea" style="display: none;">{{ $settings->get('legal', collect())->where('key', 'privacy_policy_en')->first()->value ?? '' }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        سياسة الخصوصية (تركي)
                    </label>
                    <div id="privacy_policy_tr" style="height: 400px; background: white;"></div>
                    <textarea name="privacy_policy_tr" id="privacy_policy_tr_textarea" style="display: none;">{{ $settings->get('legal', collect())->where('key', 'privacy_policy_tr')->first()->value ?? '' }}</textarea>
                </div>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-bold text-lg">
                <i class="fas fa-save ml-2"></i>
                {{ __('admin.settings.save') }}
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
// Initialize Quill editors
const editors = {};

// Register style attributors for email compatibility (inline styles instead of classes)
const Size = Quill.import('attributors/style/size');
Size.whitelist = ['12px', '14px', '16px', '18px', '20px', '24px', '28px', '32px'];
Quill.register(Size, true);
Quill.register(Quill.import('attributors/style/color'), true);
Quill.register(Quill.import('attributors/style/background'), true);

const defaultToolbarOptions = [
        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
        [{ 'size': ['12px', '14px', '16px', '18px', '20px', '24px', '28px', '32px'] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'align': [] }],
        ['link'],
        ['clean'],
        ['code-block']
    ];

const emailToolbarOptions = [
        [{ 'header': [1, 2, 3, false] }],
        [{ 'size': ['12px', '14px', '16px', '18px', '20px', '24px'] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'color': [] }, { 'background': [] }],
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        [{ 'align': [] }],
        ['link'],
        ['clean'],
        ['code-block']
    ];

function normalizeLegacyCodeBlocks(html) {
    if (!html) {
        return html;
    }

    // Keep code blocks and make them email-safe with inline styles.
    return html
        .replace(
            /<pre[^>]*ql-syntax[^>]*>/gi,
            '<pre style="background:#0f172a;color:#e2e8f0;padding:12px;border-radius:6px;overflow:auto;direction:ltr;text-align:left;font-family:Consolas,Monaco,monospace;font-size:13px;line-height:1.5;white-space:pre-wrap;word-break:break-word;">'
        )
        .replace(
            /<code([^>]*)>/gi,
            '<code$1 style="font-family:Consolas,Monaco,monospace;">'
        );
}

function initQuillEditor(editorId, textareaId, direction, options = {}) {
    const toolbarOptions = options.toolbarOptions || defaultToolbarOptions;

    const quill = new Quill('#' + editorId, {
        theme: 'snow',
        modules: {
            toolbar: toolbarOptions
        },
        placeholder: direction === 'rtl' ? 'ابدأ الكتابة...' : 'Start typing...',
    });

    // Set direction
    const editorElement = document.querySelector('#' + editorId + ' .ql-editor');
    if (editorElement) {
        editorElement.setAttribute('dir', direction);
        editorElement.style.textAlign = direction === 'rtl' ? 'right' : 'left';
        editorElement.style.fontFamily = 'Cairo, Arial, sans-serif';
        editorElement.style.overflowWrap = 'anywhere';
        editorElement.style.wordBreak = 'break-word';
    }

    const normalizeEditorCodeBlocks = () => {
        quill.root.querySelectorAll('pre').forEach((pre) => {
            pre.setAttribute('dir', 'ltr');
            pre.style.direction = 'ltr';
            pre.style.textAlign = 'left';
            pre.style.unicodeBidi = 'plaintext';
            pre.style.whiteSpace = 'pre-wrap';
            pre.style.wordBreak = 'break-word';
            pre.style.overflowWrap = 'anywhere';
            pre.style.background = '#0f172a';
            pre.style.color = '#e2e8f0';
            pre.style.padding = '12px';
            pre.style.borderRadius = '6px';
            pre.style.fontFamily = 'Consolas, Monaco, monospace';
            pre.style.fontSize = '13px';
            pre.style.lineHeight = '1.5';
        });
    };

    // Get textarea
    const textarea = document.getElementById(textareaId);
    if (textarea) {
        // Load initial content
        if (textarea.value) {
            const initialContent = options.normalizeLegacyCodeBlocks
                ? normalizeLegacyCodeBlocks(textarea.value)
                : textarea.value;
            quill.root.innerHTML = initialContent;
            normalizeEditorCodeBlocks();
            textarea.value = initialContent;
        }

        // Update textarea when content changes
        quill.on('text-change', function() {
            normalizeEditorCodeBlocks();
            textarea.value = quill.root.innerHTML;
        });
    }

    editors[editorId] = quill;
}

// Initialize all editors when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Terms and Conditions
    initQuillEditor('terms_conditions_ar', 'terms_conditions_ar_textarea', 'rtl');
    initQuillEditor('terms_conditions_en', 'terms_conditions_en_textarea', 'ltr');
    initQuillEditor('terms_conditions_tr', 'terms_conditions_tr_textarea', 'ltr');

    // Privacy Policy
    initQuillEditor('privacy_policy_ar', 'privacy_policy_ar_textarea', 'rtl');
    initQuillEditor('privacy_policy_en', 'privacy_policy_en_textarea', 'ltr');
    initQuillEditor('privacy_policy_tr', 'privacy_policy_tr_textarea', 'ltr');

    // Messaging Rules
    initQuillEditor('messaging_rules_ar', 'messaging_rules_ar_textarea', 'rtl');
    initQuillEditor('messaging_rules_en', 'messaging_rules_en_textarea', 'ltr');
    initQuillEditor('messaging_rules_tr', 'messaging_rules_tr_textarea', 'ltr');

    // Email Template (header & footer)
    initQuillEditor('email_header', 'email_header_textarea', '{{ app()->getLocale() === "ar" ? "rtl" : "ltr" }}', {
        toolbarOptions: emailToolbarOptions,
        normalizeLegacyCodeBlocks: true
    });
    initQuillEditor('email_footer', 'email_footer_textarea', '{{ app()->getLocale() === "ar" ? "rtl" : "ltr" }}', {
        toolbarOptions: emailToolbarOptions,
        normalizeLegacyCodeBlocks: true
    });

    // Update all textareas before form submit
    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Update all textareas with current editor content
            Object.keys(editors).forEach(function(editorId) {
                const quill = editors[editorId];
                const textareaId = editorId + '_textarea';
                const textarea = document.getElementById(textareaId);
                if (textarea && quill) {
                    textarea.value = quill.root.innerHTML;
                }
            });
        });
    }

    // Country Codes Management
    const addCountryCodeBtn = document.getElementById('add_country_code_btn');
    const newCountryCodeInput = document.getElementById('new_country_code');
    const countryCodesList = document.getElementById('country_codes_list');

    // Add Country Code
    if (addCountryCodeBtn && newCountryCodeInput) {
        addCountryCodeBtn.addEventListener('click', function() {
            const code = newCountryCodeInput.value.trim();
            
            if (!code) {
                alert('{{ __('admin.settings.country_code_required') }}');
                return;
            }

            // Disable button during request
            addCountryCodeBtn.disabled = true;
            addCountryCodeBtn.innerHTML = '<i class="fas fa-spinner fa-spin ml-2"></i> {{ __('admin.settings.adding') }}...';

            fetch('{{ route("admin.settings.country-codes.add") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ code: code })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add new item to list
                    const emptyMessage = countryCodesList.querySelector('.text-center');
                    if (emptyMessage) {
                        emptyMessage.remove();
                    }

                    const newItem = document.createElement('div');
                    newItem.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 country-code-item';
                    newItem.setAttribute('data-code', code);
                    
                    const codeSpan = document.createElement('span');
                    codeSpan.className = 'font-semibold text-gray-800';
                    codeSpan.textContent = code;
                    
                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'delete-country-code-btn text-red-600 hover:text-red-800 p-2 rounded-lg hover:bg-red-50 transition-colors';
                    deleteBtn.setAttribute('data-code', code);
                    deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                    deleteBtn.addEventListener('click', handleDeleteCountryCode);
                    
                    newItem.appendChild(codeSpan);
                    newItem.appendChild(deleteBtn);
                    countryCodesList.appendChild(newItem);


                    // Clear input
                    newCountryCodeInput.value = '';
                } else {
                    alert(data.message || '{{ __('admin.settings.error_adding_code') }}');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('{{ __('admin.settings.error_adding_code') }}');
            })
            .finally(() => {
                // Re-enable button
                addCountryCodeBtn.disabled = false;
                addCountryCodeBtn.innerHTML = '<i class="fas fa-plus ml-2"></i> {{ __('admin.settings.add') }}';
            });
        });

        // Allow Enter key to add code
        newCountryCodeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addCountryCodeBtn.click();
            }
        });
    }

    // Delete Country Code
    function handleDeleteCountryCode(e) {
        const code = e.currentTarget.getAttribute('data-code');
        
        if (!confirm('{{ __('admin.settings.confirm_delete_code') }}'.replace(':code', code))) {
            return;
        }

        const item = e.currentTarget.closest('.country-code-item');
        item.style.opacity = '0.5';
        e.currentTarget.disabled = true;

        fetch('{{ route("admin.settings.country-codes.delete", ":code") }}'.replace(':code', encodeURIComponent(code)), {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                item.remove();

                // Show empty message if no codes left
                const remainingItems = countryCodesList.querySelectorAll('.country-code-item');
                if (remainingItems.length === 0) {
                    countryCodesList.innerHTML = `
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-info-circle text-3xl mb-2"></i>
                            <p>{{ __('admin.settings.no_country_codes') }}</p>
                        </div>
                    `;
                }
            } else {
                alert(data.message || '{{ __('admin.settings.error_deleting_code') }}');
                item.style.opacity = '1';
                e.currentTarget.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('{{ __('admin.settings.error_deleting_code') }}');
            item.style.opacity = '1';
            e.currentTarget.disabled = false;
        });
    }

    // Attach delete events to existing buttons
    document.querySelectorAll('.delete-country-code-btn').forEach(function(btn) {
        btn.addEventListener('click', handleDeleteCountryCode);
    });
});
</script>
<style>
.ql-editor {
    min-height: 350px;
    font-family: 'Cairo', Arial, sans-serif;
}

.ql-container {
    font-family: 'Cairo', Arial, sans-serif;
}

[dir="rtl"] .ql-toolbar {
    text-align: right;
}

[dir="rtl"] .ql-editor {
    text-align: right;
}
</style>
@endpush
@endsection

