@extends('frontend.layouts.app')

@section('title', __('frontend.help.title'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <div class="bg-gradient-to-r from-primary to-blue-900 text-white py-8 sm:py-12">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-3xl sm:text-4xl font-bold mb-4">{{ __('frontend.help.title') }}</h1>
                <p class="text-blue-100 text-lg">{{ __('frontend.help.subtitle') }}</p>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <!-- FAQs Section -->
            <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8 mb-6" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                <h2 class="text-2xl font-bold text-primary mb-6 flex items-center gap-2 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-question-circle text-secondary"></i>
                    {{ __('frontend.help.faqs') }}
                </h2>

                <div class="space-y-4">
                    @forelse($faqs as $faq)
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <button class="faq-question w-full {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }} px-6 py-4 bg-gray-50 hover:bg-gray-100 transition flex items-center justify-between gap-4 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse' : '' }}" onclick="toggleFaq(this)">
                                <span class="font-semibold text-gray-800 flex-1">{{ $faq->getQuestion() }}</span>
                                <i class="fas fa-chevron-down text-primary transition-transform"></i>
                            </button>
                            <div class="faq-answer hidden px-6 py-4 bg-white">
                                <div class="text-gray-700 leading-relaxed {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                                    {!! nl2br(e($faq->getAnswer())) !!}
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-12">
                            <i class="fas fa-question-circle text-gray-300 text-6xl mb-4"></i>
                            <p class="text-gray-500 text-lg">{{ __('frontend.help.no_faqs') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Contact Support Section -->
            <div class="bg-white rounded-xl shadow-lg p-6 sm:p-8" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                <h2 class="text-2xl font-bold text-primary mb-6 flex items-center gap-2 {{ app()->getLocale() === 'ar' ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-headset text-secondary"></i>
                    {{ __('frontend.help.contact_support') }}
                </h2>

                <p class="text-gray-600 mb-6 {{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">{{ __('frontend.help.contact_description') }}</p>

                <a href="{{ route('help.contact') }}" class="btn-primary inline-flex items-center gap-2 px-6 py-3 rounded-lg font-bold {{ app()->getLocale() === 'ar' ? 'flex-row-reverse' : '' }}">
                    <i class="fas fa-comments"></i>
                    {{ __('frontend.help.chat_with_support') }}
                </a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleFaq(button) {
    const answer = button.nextElementSibling;
    const icon = button.querySelector('i');
    const locale = '{{ app()->getLocale() }}';
    
    if (answer.classList.contains('hidden')) {
        answer.classList.remove('hidden');
        // Rotate icon based on direction
        if (locale === 'ar') {
            icon.style.transform = 'rotate(180deg)';
        } else {
            icon.style.transform = 'rotate(180deg)';
        }
    } else {
        answer.classList.add('hidden');
        icon.style.transform = 'rotate(0deg)';
    }
}
</script>
@endpush
@endsection

