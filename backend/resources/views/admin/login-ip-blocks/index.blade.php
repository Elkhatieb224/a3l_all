@extends('admin.layouts.app')

@section('title', __('admin.login_ip_blocks.title'))
@section('page-title', __('admin.login_ip_blocks.title'))

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-md p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div>
                <label class="block text-sm text-gray-600 mb-1">{{ __('admin.login_ip_blocks.filter_ip') }}</label>
                <input type="text" name="ip_address" value="{{ request('ip_address') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary"
                       placeholder="192.168.">
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">{{ __('admin.login_ip_blocks.filter_channel') }}</label>
                <select name="channel" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    <option value="">{{ __('admin.all') }}</option>
                    <option value="admin" @selected(request('channel') === 'admin')>Admin</option>
                    <option value="web" @selected(request('channel') === 'web')>Web</option>
                    <option value="api" @selected(request('channel') === 'api')>API</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="permanent_only" value="1" id="permanent_only" @checked(request()->boolean('permanent_only'))>
                <label for="permanent_only" class="text-sm">{{ __('admin.login_ip_blocks.filter_permanent_only') }}</label>
            </div>
            <div class="flex items-center gap-2">
                <input type="checkbox" name="active_only" value="1" id="active_only" @checked(request()->boolean('active_only'))>
                <label for="active_only" class="text-sm">{{ __('admin.login_ip_blocks.filter_active_only') }}</label>
            </div>
            <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                <i class="fas fa-search ml-2"></i> {{ __('admin.search') }}
            </button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-primary text-white">
                    <tr class="{{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.login_ip_blocks.ip') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.login_ip_blocks.channel') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.login_ip_blocks.cycles') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.login_ip_blocks.status') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.login_ip_blocks.until') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.login_ip_blocks.last_failed') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($blocks as $block)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-mono text-sm">{{ $block->ip_address }}</td>
                            <td class="px-6 py-4">{{ $block->channelLabel() }}</td>
                            <td class="px-6 py-4">{{ $block->lockout_cycles }}</td>
                            <td class="px-6 py-4">
                                @if($block->is_permanent)
                                    <span class="px-2 py-1 bg-red-100 text-red-800 rounded text-xs font-semibold">{{ __('admin.login_ip_blocks.permanent') }}</span>
                                @elseif($block->blocked_until && $block->blocked_until->isFuture())
                                    <span class="px-2 py-1 bg-amber-100 text-amber-800 rounded text-xs font-semibold">{{ __('admin.login_ip_blocks.temp_blocked') }}</span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">{{ __('admin.login_ip_blocks.not_blocked') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">
                                @if($block->blocked_until && $block->blocked_until->isFuture())
                                    {{ $block->blocked_until->timezone(config('app.timezone'))->format('Y-m-d H:i') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm">{{ $block->last_failed_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.login-ip-blocks.show', $block) }}" class="text-primary hover:underline font-semibold text-sm">
                                    {{ __('admin.login_ip_blocks.view_details') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">{{ __('admin.login_ip_blocks.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100">{{ $blocks->links() }}</div>
    </div>
</div>
@endsection
