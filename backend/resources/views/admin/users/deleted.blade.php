@extends('admin.layouts.app')

@section('title', 'الحسابات المحذوفة')
@section('page-title', 'الحسابات المحذوفة')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-primary">الحسابات المحذوفة</h2>
            <a href="{{ route('admin.users.index') }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-bold flex items-center gap-2">
                <i class="fas fa-arrow-right"></i>
                العودة للمستخدمين
            </a>
        </div>
        
        <!-- Filters -->
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="text" 
                   name="search" 
                   value="{{ request('search') }}"
                   placeholder="البحث بالاسم أو البريد الإلكتروني" 
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
            
            <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                <i class="fas fa-search ml-2"></i>
                {{ __('admin.search') }}
            </button>
        </form>
    </div>
    
    <!-- Users Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-red-600 text-white">
                    <tr class="text-right">
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.user') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.email') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.phone') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.nav.ads') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">تاريخ الحذف المقرر</th>
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
                                         class="w-10 h-10 rounded-full border-2 border-red-500">
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ $user->name }}</p>
                                        <span class="text-xs text-red-600">
                                            <i class="fas fa-trash"></i> محذوف
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $user->email }}</td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $user->phone ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.ads.deleted-account-ads', ['user_id' => $user->id]) }}" 
                                   class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold hover:bg-blue-200">
                                    {{ $user->ads_count }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $user->scheduled_deletion_at ? $user->scheduled_deletion_at->format('Y-m-d') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $user->created_at->format('Y-m-d') }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.users.show', $user->id) }}" 
                                   class="text-blue-600 hover:text-blue-800 p-2 rounded hover:bg-blue-50"
                                   title="عرض التفاصيل">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                                <p>لا توجد حسابات محذوفة</p>
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

