@extends('admin.layouts.app')

@section('title', __('admin.categories.title'))
@section('page-title', __('admin.categories.title'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-primary">{{ __('admin.categories.all_categories') }}</h2>
            <a href="{{ route('admin.categories.create') }}" class="btn-primary px-6 py-3 rounded-lg inline-flex items-center gap-2">
                <i class="fas fa-plus"></i>
                {{ __('admin.categories.add_new') }}
            </a>
        </div>
    </div>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($categories as $category)
            <div class="stat-card bg-white rounded-xl shadow-md overflow-hidden border-r-4
                {{ $category->is_active ? 'border-green-500' : 'border-red-500' }}">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex items-center gap-3">
                            @if($category->icon)
                                <img src="{{ asset('storage/' . $category->icon) }}"
                                     alt="{{ $category->getName('ar') }}"
                                     class="w-12 h-12 object-contain rounded-lg border border-gray-200 bg-white p-1">
                            @else
                                <div class="w-12 h-12 bg-primary text-white rounded-lg flex items-center justify-center">
                                    <i class="fas fa-folder text-xl"></i>
                                </div>
                            @endif
                            <div>
                                <h3 class="text-lg font-bold text-gray-800">{{ $category->getName('ar') }}</h3>
                                <p class="text-xs text-gray-500">{{ $category->name_en }}</p>
                            </div>
                        </div>

                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $category->is_active ? __('admin.active') : __('admin.inactive') }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="bg-blue-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-600">{{ __('admin.categories.index.subcategories_count') }}</p>
                            <p class="text-xl font-bold text-primary">{{ $category->subcategories_count }}</p>
                        </div>
                        <div class="bg-green-50 p-3 rounded-lg">
                            <p class="text-xs text-gray-600">{{ __('admin.categories.index.ads_count') }}</p>
                            <p class="text-xl font-bold text-primary">{{ $category->ads_count }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 pt-4 border-t">
                        <a href="{{ route('admin.categories.show', $category->id) }}"
                           class="text-center bg-green-50 text-green-600 hover:bg-green-100 py-2 rounded-lg transition text-sm">
                            <i class="fas fa-eye"></i> {{ __('admin.view') }}
                        </a>

                        @if(auth('admin')->user()->isAdmin())
                        <a href="{{ route('admin.categories.edit', $category->id) }}#category-ad-images-settings"
                           class="text-center bg-blue-50 text-blue-600 hover:bg-blue-100 py-2 rounded-lg transition text-sm">
                            <i class="fas fa-edit"></i> {{ __('admin.edit') }}
                        </a>
                        @endif

                    </div>

                    @if(auth('admin')->user()->isSuperAdmin())
                    <div class="grid grid-cols-2 gap-2 mt-2">
                        <form action="{{ route('admin.categories.toggle-status', $category->id) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="w-full bg-yellow-50 text-yellow-600 hover:bg-yellow-100 py-2 rounded-lg transition text-sm">
                                <i class="fas fa-power-off"></i>
                                {{ $category->is_active ? __('admin.disable') : __('admin.enable') }}
                            </button>
                        </form>

                        <form action="{{ route('admin.categories.destroy', $category->id) }}"
                              method="POST"
                              onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="w-full bg-red-50 text-red-600 hover:bg-red-100 py-2 rounded-lg transition text-sm">
                                <i class="fas fa-trash"></i> {{ __('admin.delete') }}
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="col-span-3 bg-white rounded-xl shadow-md p-12 text-center">
                <i class="fas fa-folder-open text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">{{ __('admin.categories.index.no_categories') }}</p>
                <a href="{{ route('admin.categories.create') }}" class="btn-primary px-6 py-3 rounded-lg inline-block mt-4">
                    {{ __('admin.categories.index.add_new_button') }}
                </a>
            </div>
        @endforelse
    </div>
</div>
@endsection

