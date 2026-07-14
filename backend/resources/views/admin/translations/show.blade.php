@extends('admin.layouts.app')

@section('title', 'تعديل ملف الترجمة')
@section('page-title', 'تعديل ملف الترجمة')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Breadcrumb -->
    <div class="mb-4">
        <a href="{{ route('admin.translations.index') }}" class="text-primary hover:underline">
            <i class="fas fa-arrow-right ml-1"></i>
            العودة إلى قائمة ملفات الترجمة
        </a>
    </div>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-green-600"><i class="fas fa-check-circle ml-1"></i> {{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-red-600"><i class="fas fa-exclamation-circle ml-1"></i> {{ session('error') }}</p>
        </div>
    @endif

    <!-- File Info -->
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-xl font-bold text-primary mb-2">
                    @if($file === 'admin')
                        <i class="fas fa-user-shield ml-2"></i>
                        لوحة التحكم
                    @elseif($file === 'frontend')
                        <i class="fas fa-globe ml-2"></i>
                        الواجهة الأمامية
                    @else
                        <i class="fas fa-check-circle ml-2"></i>
                        التحقق
                    @endif
                    - 
                    @if($locale === 'ar')
                        🇸🇦 العربية
                    @elseif($locale === 'en')
                        🇬🇧 English
                    @else
                        🇹🇷 Türkçe
                    @endif
                </h3>
                <p class="text-gray-600 text-sm">
                    المسار: <code class="bg-gray-100 px-2 py-1 rounded">lang/{{ $locale }}/{{ $file }}.php</code>
                </p>
            </div>
        </div>
    </div>

    <!-- Translation Form -->
    <form action="{{ route('admin.translations.update', [$locale, $file]) }}" method="POST" id="translationForm">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-md p-6">
            <div class="mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">مفاتيح الترجمة</h3>
                <p class="text-sm text-gray-600 mb-4">
                    استخدم النقاط (.) لفصل المستويات المتداخلة. مثال: <code class="bg-gray-100 px-2 py-1 rounded">nav.home</code>
                </p>
                
                <!-- Search Box -->
                <div class="mb-4">
                    <div class="relative">
                        <input type="text" 
                               id="searchInput"
                               placeholder="ابحث في المفاتيح أو القيم..."
                               class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                        <i class="fas fa-search absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <p class="text-sm text-gray-600">
                            <span id="resultsCount">{{ count($flatTranslations) }}</span> من <span id="totalCount">{{ count($flatTranslations) }}</span> نتيجة
                        </p>
                        <button type="button" 
                                id="clearSearch"
                                class="text-sm text-primary hover:underline hidden">
                            <i class="fas fa-times ml-1"></i>
                            مسح البحث
                        </button>
                    </div>
                </div>
            </div>

            <div class="space-y-4 max-h-[calc(100vh-400px)] overflow-y-auto" id="translationsContainer">
                @foreach($flatTranslations as $key => $value)
                    <div class="translation-item border-b border-gray-200 pb-4" 
                         data-key="{{ strtolower($key) }}" 
                         data-value="{{ strtolower($value) }}">
                        <div class="grid grid-cols-12 gap-4 items-start">
                            <div class="col-span-4">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    المفتاح
                                </label>
                                <input type="text" 
                                       value="{{ $key }}" 
                                       disabled
                                       class="translation-key w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600 text-sm font-mono">
                            </div>
                            <div class="col-span-8">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                    القيمة
                                </label>
                                <textarea name="translations[{{ $key }}]" 
                                          rows="2"
                                          class="translation-value w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">{{ old('translations.' . $key, $value) }}</textarea>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Actions -->
            <div class="mt-6 pt-6 border-t border-gray-200 flex items-center justify-between">
                <a href="{{ route('admin.translations.index') }}" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    إلغاء
                </a>
                <button type="submit" 
                        class="btn-primary px-6 py-2 rounded-lg font-bold">
                    <i class="fas fa-save ml-2"></i>
                    حفظ التعديلات
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const translationsContainer = document.getElementById('translationsContainer');
    const translationItems = document.querySelectorAll('.translation-item');
    const resultsCount = document.getElementById('resultsCount');
    const totalCount = document.getElementById('totalCount');
    const clearSearchBtn = document.getElementById('clearSearch');
    const totalItems = translationItems.length;

    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        translationItems.forEach(item => {
            const key = item.dataset.key || '';
            const value = item.dataset.value || '';
            
            // Update data-value when textarea changes
            const textarea = item.querySelector('.translation-value');
            if (textarea) {
                item.dataset.value = textarea.value.toLowerCase();
            }

            if (searchTerm === '' || key.includes(searchTerm) || value.includes(searchTerm)) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });

        resultsCount.textContent = visibleCount;
        
        // Show/hide clear button
        if (searchTerm !== '') {
            clearSearchBtn.classList.remove('hidden');
        } else {
            clearSearchBtn.classList.add('hidden');
        }

        // Highlight search term in visible items
        if (searchTerm !== '') {
            highlightSearchTerm(searchTerm);
        } else {
            removeHighlights();
        }
    }

    function highlightSearchTerm(term) {
        translationItems.forEach(item => {
            if (item.style.display !== 'none') {
                const keyInput = item.querySelector('.translation-key');
                const valueTextarea = item.querySelector('.translation-value');
                
                // Highlight in key (for display, not in disabled input)
                // For value, we could add highlighting but it's complex with textarea
                // So we'll just show/hide items for now
            }
        });
    }

    function removeHighlights() {
        // Remove any highlights if needed
    }

    // Search on input
    searchInput.addEventListener('input', performSearch);

    // Clear search
    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        performSearch();
        searchInput.focus();
    });

    // Update data-value when textarea changes (for real-time search)
    translationItems.forEach(item => {
        const textarea = item.querySelector('.translation-value');
        if (textarea) {
            textarea.addEventListener('input', function() {
                item.dataset.value = this.value.toLowerCase();
                const searchTerm = searchInput.value.toLowerCase().trim();
                if (searchTerm !== '') {
                    performSearch();
                }
            });
        }
    });

    // Form submission confirmation
    document.getElementById('translationForm').addEventListener('submit', function(e) {
        if (!confirm('هل أنت متأكد من حفظ التعديلات؟ سيتم تحديث ملف الترجمة.')) {
            e.preventDefault();
        }
    });
});
</script>
@endsection

