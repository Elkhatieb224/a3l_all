@extends('admin.layouts.app')

@section('title', __('admin.login_ip_blocks.detail_title'))
@section('page-title', __('admin.login_ip_blocks.detail_title'))

@section('content')
<div class="space-y-6">
    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap gap-3 items-center">
        <a href="{{ route('admin.login-ip-blocks.index') }}" class="text-primary hover:underline text-sm font-semibold">
            ← {{ __('admin.login_ip_blocks.back_list') }}
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 space-y-4">
        <h2 class="text-lg font-bold text-primary">{{ __('admin.login_ip_blocks.summary') }}</h2>
        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <dt class="text-gray-500">{{ __('admin.login_ip_blocks.ip') }}</dt>
                <dd class="font-mono font-semibold">{{ $block->ip_address }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">{{ __('admin.login_ip_blocks.channel') }}</dt>
                <dd>{{ $block->channelLabel() }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">{{ __('admin.login_ip_blocks.cycles') }}</dt>
                <dd>{{ $block->lockout_cycles }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">{{ __('admin.login_ip_blocks.status') }}</dt>
                <dd>
                    @if($block->is_permanent)
                        <span class="text-red-700 font-semibold">{{ __('admin.login_ip_blocks.permanent') }}</span>
                    @elseif($block->blocked_until && $block->blocked_until->isFuture())
                        {{ __('admin.login_ip_blocks.temp_blocked') }} ({{ $block->blocked_until->format('Y-m-d H:i') }})
                    @else
                        {{ __('admin.login_ip_blocks.not_blocked') }}
                    @endif
                </dd>
            </div>
            <div>
                <dt class="text-gray-500">{{ __('admin.login_ip_blocks.last_failed') }}</dt>
                <dd>{{ $block->last_failed_at?->format('Y-m-d H:i:s') ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-gray-500">{{ __('admin.login_ip_blocks.last_lockout') }}</dt>
                <dd>{{ $block->last_lockout_at?->format('Y-m-d H:i:s') ?? '—' }}</dd>
            </div>
        </dl>

        <div class="flex flex-wrap gap-3 pt-4 border-t border-gray-100">
            <form method="POST" action="{{ route('admin.login-ip-blocks.unblock', $block) }}" onsubmit="return confirm(@json(__('admin.login_ip_blocks.confirm_unblock')));">
                @csrf
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-semibold">
                    {{ __('admin.login_ip_blocks.btn_unblock') }}
                </button>
            </form>
            <form method="POST" action="{{ route('admin.login-ip-blocks.make-permanent', $block) }}" onsubmit="return confirm(@json(__('admin.login_ip_blocks.confirm_permanent')));">
                @csrf
                <button type="submit" class="px-4 py-2 bg-red-700 text-white rounded-lg hover:bg-red-800 text-sm font-semibold">
                    {{ __('admin.login_ip_blocks.btn_permanent') }}
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 space-y-4">
        <h2 class="text-lg font-bold text-primary">{{ __('admin.login_ip_blocks.admin_notes') }}</h2>
        <form method="POST" action="{{ route('admin.login-ip-blocks.notes', $block) }}">
            @csrf
            @method('PUT')
            <textarea name="admin_notes" rows="4" class="w-full border border-gray-300 rounded-lg p-3 text-sm" placeholder="{{ __('admin.login_ip_blocks.notes_placeholder') }}">{{ old('admin_notes', $block->admin_notes) }}</textarea>
            <button type="submit" class="btn-primary mt-2 px-4 py-2 rounded-lg text-sm">{{ __('admin.save') }}</button>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6 space-y-4">
        <h2 class="text-lg font-bold text-primary">{{ __('admin.login_ip_blocks.attempt_log') }}</h2>
        <p class="text-sm text-gray-600">{{ __('admin.login_ip_blocks.attempt_log_hint') }}</p>
        <div class="overflow-x-auto max-h-[32rem] overflow-y-auto border border-gray-200 rounded-lg">
            <table class="w-full text-xs">
                <thead class="bg-gray-100 sticky top-0">
                    <tr class="{{ app()->getLocale() === 'ar' ? 'text-right' : 'text-left' }}">
                        <th class="px-3 py-2 whitespace-nowrap">{{ __('admin.login_ip_blocks.log_time') }}</th>
                        <th class="px-3 py-2 whitespace-nowrap">{{ __('admin.login_ip_blocks.log_email') }}</th>
                        <th class="px-3 py-2">{{ __('admin.login_ip_blocks.log_ua') }}</th>
                        <th class="px-3 py-2">{{ __('admin.login_ip_blocks.log_extra') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @php $logs = array_reverse($block->attempt_logs ?? []); @endphp
                    @forelse($logs as $entry)
                        <tr class="align-top hover:bg-gray-50">
                            <td class="px-3 py-2 whitespace-nowrap font-mono">{{ $entry['at'] ?? '—' }}</td>
                            <td class="px-3 py-2 whitespace-nowrap">{{ $entry['email'] ?? '—' }}</td>
                            <td class="px-3 py-2 break-all max-w-md">{{ $entry['user_agent'] ?? '—' }}</td>
                            <td class="px-3 py-2 break-all max-w-lg">
                                <details>
                                    <summary class="cursor-pointer text-primary font-semibold">{{ __('admin.login_ip_blocks.log_raw') }}</summary>
                                    <pre class="mt-2 p-2 bg-gray-50 rounded text-[10px] whitespace-pre-wrap break-all">{{ json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-6 text-center text-gray-500">{{ __('admin.login_ip_blocks.no_logs') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
