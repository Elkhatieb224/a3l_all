@extends('admin.layouts.app')

@section('title', __('admin.admins.title'))
@section('page-title', __('admin.admins.title'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-primary">{{ __('admin.admins.all_admins') }}</h2>
            @if(auth('admin')->user()->isSuperAdmin())
                <a href="{{ route('admin.admins.create') }}" class="btn-primary px-6 py-3 rounded-lg inline-flex items-center gap-2">
                    <i class="fas fa-plus"></i>
                    {{ __('admin.admins.add_new') }}
                </a>
            @endif
        </div>
    </div>
    
    <!-- Admins Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($admins as $admin)
            <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-xl transition">
                <div class="flex items-start gap-4 mb-4">
                    <img src="{{ $admin->avatar ? asset('storage/' . $admin->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($admin->name) }}" 
                         alt="{{ $admin->name }}" 
                         class="w-16 h-16 rounded-full border-4 border-secondary">
                    
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-800">{{ $admin->name }}</h3>
                        <p class="text-sm text-gray-600 mb-2">{{ $admin->email }}</p>
                        
                        <div class="flex items-center gap-2 mb-3">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $admin->role === 'super_admin' ? 'bg-purple-100 text-purple-700' : 
                                   ($admin->role === 'admin' ? 'bg-blue-100 text-blue-700' : 
                                   ($admin->role === 'support_agent' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700')) }}">
                                {{ __('admin.roles.' . $admin->role) }}
                            </span>
                            
                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                {{ $admin->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                {{ $admin->is_active ? __('admin.active') : __('admin.inactive') }}
                            </span>
                        </div>

                        <!-- Permissions -->
                        <div class="mt-3 pt-3 border-t border-gray-200">
                            <p class="text-xs font-semibold text-gray-700 mb-2">
                                <i class="fas fa-key text-primary"></i>
                                {{ __('admin.permissions.title') }}:
                            </p>
                            <div class="flex flex-wrap gap-1">
                                @php
                                    $permissions = \App\Http\Controllers\Admin\AdminController::getRolePermissions($admin->role);
                                @endphp
                                @foreach($permissions as $permission)
                                    <span class="px-2 py-1 bg-gray-50 text-gray-600 rounded text-xs">
                                        {{ __('admin.permissions.' . $permission) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-sm text-gray-600 mb-4 space-y-2">
                    @if($admin->phone)
                        <div class="flex items-center gap-2">
                            <i class="fas fa-phone text-primary"></i>
                            <span>{{ $admin->phone }}</span>
                        </div>
                    @endif
                    
                    <div class="flex items-center gap-2">
                        <i class="fas fa-clock text-primary"></i>
                        <span>{{ __('admin.last_login') }}: {{ $admin->last_login_at ? $admin->last_login_at->diffForHumans() : __('admin.never_logged_in') }}</span>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <i class="fas fa-calendar text-primary"></i>
                        <span>{{ __('admin.joined') }}: {{ $admin->created_at->format('Y-m-d') }}</span>
                    </div>
                </div>
                
                @if(auth('admin')->user()->isSuperAdmin() && auth('admin')->user()->id !== $admin->id)
                    <div class="flex items-center gap-2 pt-4 border-t">
                        <a href="{{ route('admin.admins.edit', $admin->id) }}" 
                           class="flex-1 text-center bg-blue-50 text-blue-600 hover:bg-blue-100 py-2 rounded-lg transition">
                            <i class="fas fa-edit"></i> {{ __('admin.edit') }}
                        </a>
                        
                        <form action="{{ route('admin.admins.toggle-status', $admin->id) }}" method="POST" class="flex-1">
                            @csrf
                            <button type="submit" 
                                    class="w-full bg-yellow-50 text-yellow-600 hover:bg-yellow-100 py-2 rounded-lg transition">
                                <i class="fas fa-power-off"></i>
                                {{ $admin->is_active ? __('admin.disable') : __('admin.enable') }}
                            </button>
                        </form>
                        
                        <form action="{{ route('admin.admins.destroy', $admin->id) }}" 
                              method="POST"
                              onsubmit="return confirm('{{ __('admin.confirm_delete') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded-lg transition">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @empty
            <div class="col-span-3 bg-white rounded-xl shadow-md p-12 text-center">
                <i class="fas fa-user-shield text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 text-lg">{{ __('admin.no_admins') }}</p>
            </div>
        @endforelse
    </div>
    
    <!-- Pagination -->
    @if($admins->hasPages())
        <div class="bg-white rounded-xl shadow-md p-4">
            {{ $admins->links() }}
        </div>
    @endif
</div>
@endsection

