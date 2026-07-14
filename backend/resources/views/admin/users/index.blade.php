@extends('admin.layouts.app')

@section('title', 'إدارة المستخدمين')
@section('page-title', __('admin.users.title'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-primary">{{ __('admin.users.all_users') }}</h2>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.users.deleted') }}" 
                   class="bg-red-500 hover:bg-red-600 text-white px-6 py-3 rounded-lg font-bold flex items-center gap-2">
                    <i class="fas fa-trash"></i>
                    الحسابات المحذوفة
                </a>
                <a href="{{ route('admin.users.verification-requests') }}" 
                   class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-lg font-bold flex items-center gap-2">
                    <i class="fas fa-shield-alt"></i>
                    طلبات التوثيق
                </a>
                <a href="{{ route('admin.users.create') }}" 
                   class="btn-primary px-6 py-3 rounded-lg font-bold flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    إضافة مستخدم جديد
                </a>
            </div>
        </div>
        
        <!-- Filters -->
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <input type="text" 
                       name="search" 
                       value="{{ request('search') }}"
                       placeholder="{{ __('admin.search_by_name_or_email') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                
                <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    <option value="">{{ __('admin.all') }}</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>{{ __('admin.active') }}</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>{{ __('admin.inactive') }}</option>
                </select>
                
                <select name="verified" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    <option value="">{{ __('admin.all') }}</option>
                    <option value="yes" {{ request('verified') === 'yes' ? 'selected' : '' }}>{{ __('admin.verified') }}</option>
                    <option value="no" {{ request('verified') === 'no' ? 'selected' : '' }}>{{ __('admin.unverified') }}</option>
                </select>

                <input type="date" 
                       name="from_date" 
                       value="{{ request('from_date') }}"
                       placeholder="{{ __('admin.from_date') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">

                <input type="date" 
                       name="to_date" 
                       value="{{ request('to_date') }}"
                       placeholder="{{ __('admin.to_date') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
            </div>
            
            <div class="flex items-center gap-3">
                <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                    <i class="fas fa-search ml-2"></i>
                    {{ __('admin.search') }}
                </button>
                
                <a href="{{ route('admin.users.export', request()->query()) }}" 
                   class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded-lg font-semibold flex items-center gap-2">
                    <i class="fas fa-file-excel"></i>
                    {{ __('admin.export_excel') }}
                </a>
            </div>
        </form>
    </div>
    
    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-primary text-white">
                    <tr class="text-right">
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.user') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.email') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.phone') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.nav.ads') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.status') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.join_date') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $user->avatar ? asset('storage/' . $user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}" 
                                         alt="{{ $user->name }}" 
                                         class="w-10 h-10 rounded-full border-2 border-secondary">
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ $user->name }}</p>
                                        @if($user->is_verified)
                                            <span class="text-xs text-green-600">
                                                <i class="fas fa-check-circle"></i> {{ __('admin.verified') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $user->email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $user->phone ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold">
                                    {{ $user->ads_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $user->is_active ? __('admin.active') : __('admin.inactive') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $user->created_at->format('Y-m-d') }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.users.show', $user->id) }}" 
                                       class="text-blue-600 hover:text-blue-800 p-2 rounded hover:bg-blue-50"
                                       title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    
                                    <a href="{{ route('admin.users.edit', $user->id) }}" 
                                       class="text-green-600 hover:text-green-800 p-2 rounded hover:bg-green-50"
                                       title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="text-yellow-600 hover:text-yellow-800 p-2 rounded hover:bg-yellow-50"
                                                title="{{ $user->is_active ? __('admin.disable') : __('admin.enable') }}">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </form>
                                    
                                    <form action="{{ route('admin.users.destroy', $user->id) }}" 
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
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                                <p>{{ __('admin.no_users') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection

