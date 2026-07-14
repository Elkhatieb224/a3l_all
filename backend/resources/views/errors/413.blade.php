@extends('frontend.layouts.app')

@section('title', 'حجم الطلب كبير جدًا')

@section('content')
<div class="min-h-[60vh] flex items-center justify-center bg-gray-50 py-16 px-4">
    <div class="max-w-lg w-full text-center bg-white rounded-2xl shadow-lg p-10">
        <div class="text-red-500 text-6xl font-extrabold mb-4">413</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-3">تعذّر إكمال العملية</h1>
        <p class="text-gray-600 mb-6">
            حجم البيانات المرسلة أكبر من الحد المسموح. يحدث هذا غالبًا عند رفع صورة/فيديو كبير أو إرسال محتوى طويل داخل نموذج الإعلان.
        </p>

        <div class="text-sm text-gray-700 bg-gray-100 rounded-xl p-4 mb-8">
            جرّب تقليل حجم الملف (ضغط الصورة/الفيديو) ثم أعد المحاولة.
        </div>

        <a href="{{ url()->previous() }}"
           class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-white rounded-lg shadow hover:bg-primary-dark transition">
            <i class="fas fa-arrow-right"></i>
            رجوع
        </a>
    </div>
</div>
@endsection

