@extends('admin.layouts.app')

@section('title', 'إدارة ملفات الترجمة')
@section('page-title', 'إدارة ملفات الترجمة')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-600">{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-600">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Translation Files Grid -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
            <i class="fas fa-language text-secondary"></i>
            ملفات الترجمة
        </h3>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">الملف</th>
                        @foreach($locales as $locale)
                            <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">
                                @if($locale === 'ar')
                                    🇸🇦 العربية
                                @elseif($locale === 'en')
                                    🇬🇧 English
                                @else
                                    🇹🇷 Türkçe
                                @endif
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($files as $file)
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-4 py-4 text-right font-semibold text-gray-800">
                                @if($file === 'admin')
                                    <i class="fas fa-user-shield ml-2 text-primary"></i>
                                    لوحة التحكم
                                @elseif($file === 'frontend')
                                    <i class="fas fa-globe ml-2 text-primary"></i>
                                    الواجهة الأمامية
                                @else
                                    <i class="fas fa-check-circle ml-2 text-primary"></i>
                                    التحقق
                                @endif
                            </td>
                            @foreach($locales as $locale)
                                <td class="px-4 py-4 text-center">
                                    @if(isset($translations[$locale][$file]))
                                        <a href="{{ route('admin.translations.show', [$locale, $file]) }}" 
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition">
                                            <i class="fas fa-edit"></i>
                                            <span>تعديل</span>
                                        </a>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ number_format($translations[$locale][$file]['size'] / 1024, 2) }} KB
                                        </div>
                                    @else
                                        <span class="text-gray-400">غير موجود</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

