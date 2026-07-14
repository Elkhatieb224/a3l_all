@extends('admin.layouts.app')

@section('title', __('admin.faqs.edit_title'))
@section('page-title', __('admin.faqs.edit_title'))

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-md p-6 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.faqs.index') }}"
               class="text-gray-600 hover:text-primary">
                <i class="fas fa-arrow-right text-xl"></i>
            </a>
            <h2 class="text-2xl font-bold text-primary">{{ __('admin.faqs.edit_title') }}</h2>
        </div>
    </div>

    <form action="{{ route('admin.faqs.update', $faq->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Questions -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-question-circle text-secondary"></i>
                {{ __('admin.faqs.questions_section') }}
            </h3>

            <div class="space-y-4">
                <!-- Arabic Question -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.faqs.question_ar_label') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="question_ar"
                           value="{{ old('question_ar', $faq->question_ar) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('question_ar')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- English Question -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.faqs.question_en_label') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="question_en"
                           value="{{ old('question_en', $faq->question_en) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('question_en')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Turkish Question -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.faqs.question_tr_label') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="question_tr"
                           value="{{ old('question_tr', $faq->question_tr) }}"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    @error('question_tr')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Answers -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-comment-dots text-secondary"></i>
                {{ __('admin.faqs.answers_section') }}
            </h3>

            <div class="space-y-4">
                <!-- Arabic Answer -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.faqs.answer_ar_label') }} <span class="text-red-500">*</span>
                    </label>
                    <textarea name="answer_ar"
                              rows="5"
                              required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">{{ old('answer_ar', $faq->answer_ar) }}</textarea>
                    @error('answer_ar')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- English Answer -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.faqs.answer_en_label') }} <span class="text-red-500">*</span>
                    </label>
                    <textarea name="answer_en"
                              rows="5"
                              required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">{{ old('answer_en', $faq->answer_en) }}</textarea>
                    @error('answer_en')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Turkish Answer -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.faqs.answer_tr_label') }} <span class="text-red-500">*</span>
                    </label>
                    <textarea name="answer_tr"
                              rows="5"
                              required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">{{ old('answer_tr', $faq->answer_tr) }}</textarea>
                    @error('answer_tr')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Settings -->
        <div class="bg-white rounded-xl shadow-md p-6">
            <h3 class="text-xl font-bold text-primary mb-6 flex items-center gap-2">
                <i class="fas fa-cog text-secondary"></i>
                {{ __('admin.faqs.settings_section') }}
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Order -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.faqs.order_label') }}
                    </label>
                    <input type="number"
                           name="order"
                           value="{{ old('order', $faq->order) }}"
                           min="0"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    <p class="text-xs text-gray-500 mt-1">{{ __('admin.faqs.order_hint') }}</p>
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        {{ __('admin.faqs.status_label') }}
                    </label>
                    <div class="flex items-center gap-3 mt-2">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox"
                                   name="is_active"
                                   value="1"
                                   {{ old('is_active', $faq->is_active) ? 'checked' : '' }}
                                   class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                            <span class="text-sm text-gray-700">{{ __('admin.faqs.active_label') }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center gap-4">
            <button type="submit" class="btn-primary px-8 py-3 rounded-lg font-bold">
                <i class="fas fa-save ml-2"></i>
                {{ __('admin.faqs.save_changes') }}
            </button>
            
            <a href="{{ route('admin.faqs.index') }}" 
               class="px-8 py-3 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-lg transition font-semibold">
                <i class="fas fa-times ml-2"></i>
                {{ __('admin.faqs.cancel') }}
            </a>
        </div>
    </form>
</div>
@endsection

