@extends('admin.layouts.app')

@php
    use Illuminate\Support\Str;
@endphp

@section('title', __('admin.faqs.title'))
@section('page-title', __('admin.faqs.title'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-primary">{{ __('admin.faqs.list_title') }}</h2>
            <a href="{{ route('admin.faqs.create') }}" class="btn-primary px-6 py-3 rounded-lg inline-flex items-center gap-2">
                <i class="fas fa-plus"></i>
                {{ __('admin.faqs.add_new') }}
            </a>
        </div>
    </div>
    
    <!-- FAQs Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-primary text-white">
                    <tr class="text-right">
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.faqs.order_label') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.faqs.question_ar_label') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.faqs.question_en_label') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.faqs.status_label') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.faqs.created_at') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.faqs.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($faqs as $faq)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold">
                                    {{ $faq->order }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-gray-800">{{ Str::limit($faq->question_ar, 50) }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-gray-700">{{ Str::limit($faq->question_en, 50) }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $faq->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $faq->is_active ? __('admin.active') : __('admin.inactive') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $faq->created_at->format('Y-m-d') }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.faqs.edit', $faq->id) }}" 
                                       class="text-blue-600 hover:text-blue-800 p-2 rounded hover:bg-blue-50"
                                       title="{{ __('admin.edit') }}">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <form action="{{ route('admin.faqs.toggle-status', $faq->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="text-yellow-600 hover:text-yellow-800 p-2 rounded hover:bg-yellow-50"
                                                title="{{ $faq->is_active ? __('admin.disable') : __('admin.enable') }}">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>
                                    
                                    <form action="{{ route('admin.faqs.destroy', $faq->id) }}" 
                                          method="POST" 
                                          class="inline"
                                          onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="text-red-600 hover:text-red-800 p-2 rounded hover:bg-red-50">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-question-circle text-4xl text-gray-300 mb-3"></i>
                                <p>{{ __('admin.faqs.no_faqs') }}</p>
                                <a href="{{ route('admin.faqs.create') }}" class="btn-primary px-6 py-3 rounded-lg inline-block mt-4">
                                    {{ __('admin.faqs.create_button') }}
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($faqs->hasPages())
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $faqs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

