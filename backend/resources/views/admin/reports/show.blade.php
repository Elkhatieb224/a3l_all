@extends('admin.layouts.app')

@section('title', __('admin.reports.show.title'))
@section('page-title', __('admin.reports.show.title'))

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    {{-- رجوع --}}
    <div>
        <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-2 text-gray-600 hover:text-primary transition">
            <i class="fas fa-arrow-right"></i>
            <span>{{ __('admin.reports.back_to_list') ?? 'العودة للبلاغات' }}</span>
        </a>
    </div>

    {{-- عنوان البلاغ والحالة --}}
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-primary mb-2">{{ __('admin.reports.show.report_number', ['id' => $report->id]) }}</h2>
                <div class="flex flex-wrap items-center gap-3">
                    <span class="px-4 py-2 rounded-full text-sm font-semibold
                        {{ $report->status === 'pending' ? 'bg-yellow-100 text-yellow-700' :
                           ($report->status === 'reviewed' ? 'bg-blue-100 text-blue-700' :
                           ($report->status === 'resolved' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700')) }}">
                        {{ __("admin.reports.show.status_options.{$report->status}") }}
                    </span>
                    <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-semibold">
                        {{ __('admin.reports.' . ($report->type ?? 'other')) }}
                    </span>
                    @if($report->created_at)
                        <span class="text-sm text-gray-500">
                            <i class="fas fa-clock ml-1"></i>
                            {{ $report->created_at->format('Y-m-d H:i') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- سبب البلاغ --}}
        <div class="mb-6">
            <p class="text-sm font-semibold text-gray-600 mb-2">{{ __('admin.reports.show.reason_label') }}</p>
            <p class="text-gray-800 bg-gray-50 p-4 rounded-lg border border-gray-100">{{ $report->reason ?? '—' }}</p>
        </div>

        {{-- صور البلاغ (المرفقات) --}}
        @if($report->images && count($report->images) > 0)
        <div class="mb-6">
            <p class="text-sm font-semibold text-gray-600 mb-2">{{ __('admin.reports.show.images_label') }}</p>
            <div class="flex flex-wrap gap-4">
                @foreach($report->images as $path)
                    @php
                        $url = is_string($path) ? (str_starts_with(trim($path), 'http') ? $path : asset('storage/' . ltrim($path, '/'))) : asset('storage/' . ltrim($path['path'] ?? $path['url'] ?? '', '/'));
                    @endphp
                    <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="block border border-gray-200 rounded-lg overflow-hidden hover:border-primary transition" title="{{ __('admin.reports.show.view_image') }}">
                        <img src="{{ $url }}" alt="" class="w-48 h-36 object-cover" loading="lazy">
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- مقدم البلاغ (المستخدم الذي قدم البلاغ) --}}
        <div class="mb-6 pb-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-primary mb-3">{{ __('admin.reports.show.reported_by_label') }}</h3>
            @if($report->user)
                <a href="{{ route('admin.users.show', $report->user->id) }}" class="flex items-center gap-4 p-4 bg-gray-50 rounded-xl hover:bg-primary/5 border border-gray-100 hover:border-primary/20 transition">
                    <img src="{{ $report->user->avatar ? asset('storage/' . $report->user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($report->user->name) }}"
                         alt="" class="w-12 h-12 rounded-full object-cover">
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-gray-800">{{ $report->user->name }}</p>
                        <p class="text-sm text-gray-600">{{ $report->user->email ?? '—' }}</p>
                        <p class="text-xs text-gray-500 mt-1">ID: {{ $report->user->id }}</p>
                    </div>
                    <span class="text-primary font-semibold text-sm">{{ __('admin.view') ?? 'عرض' }} <i class="fas fa-chevron-left"></i></span>
                </a>
            @else
                <p class="text-gray-500 p-4 bg-gray-50 rounded-lg">{{ __('admin.reports.show.user_deleted') ?? 'مستخدم محذوف' }}</p>
            @endif
        </div>

        {{-- البلاغ عن إعلان --}}
        @if($report->ad_id)
        <div class="mb-6 pb-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-primary mb-3">{{ __('admin.reports.show.reported_ad_label') }}</h3>
            @if($report->ad)
                <div class="space-y-3">
                    <a href="{{ route('admin.ads.show', $report->ad->uid) }}" class="block p-4 bg-amber-50 rounded-xl hover:bg-amber-100/80 border border-amber-100 hover:border-amber-200 transition">
                        <p class="font-bold text-gray-800 mb-1">{{ $report->ad->title }}</p>
                        <p class="text-xs text-gray-600 font-mono">UID: {{ $report->ad->uid }}</p>
                        <span class="inline-flex items-center gap-2 mt-2 text-primary font-semibold text-sm">
                            <i class="fas fa-external-link-alt"></i>
                            {{ __('admin.reports.view_ad') ?? 'عرض الإعلان في لوحة التحكم' }}
                        </span>
                    </a>
                    <a href="{{ route('ads.show', $report->ad->uid) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition text-sm">
                        <i class="fas fa-external-link-alt"></i>
                        {{ __('admin.reports.view_ad_public') ?? 'عرض الإعلان في الموقع' }}
                    </a>
                </div>
                @if($report->ad->user)
                <div class="mt-3">
                    <p class="text-sm text-gray-600 mb-2">{{ __('admin.reports.show.ad_owner_label') }}</p>
                    <a href="{{ route('admin.users.show', $report->ad->user->id) }}" class="inline-flex items-center gap-2 p-3 bg-gray-50 rounded-lg hover:bg-primary/5 transition">
                        <img src="{{ $report->ad->user->avatar ? asset('storage/' . $report->ad->user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($report->ad->user->name) }}"
                             class="w-8 h-8 rounded-full">
                        <span class="font-semibold text-primary">{{ $report->ad->user->name }}</span>
                        <i class="fas fa-chevron-left text-xs"></i>
                    </a>
                </div>
                @endif
            @else
                <p class="text-gray-500 p-4 bg-gray-50 rounded-lg">{{ __('admin.reports.show.ad_deleted') ?? 'إعلان محذوف' }}</p>
            @endif
        </div>
        @endif

        {{-- البلاغ عن مستخدم --}}
        @if($report->reported_user_id)
        <div class="mb-6 pb-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-primary mb-3">{{ __('admin.reports.show.reported_user_label') }}</h3>
            @if($report->reportedUser)
                <a href="{{ route('admin.users.show', $report->reportedUser->id) }}" class="flex items-center gap-4 p-4 bg-red-50 rounded-xl hover:bg-red-100/50 border border-red-100 hover:border-red-200 transition">
                    <img src="{{ $report->reportedUser->avatar ? asset('storage/' . $report->reportedUser->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($report->reportedUser->name) }}"
                         alt="" class="w-12 h-12 rounded-full object-cover">
                    <div class="flex-1 min-w-0">
                        <p class="font-bold text-gray-800">{{ $report->reportedUser->name }}</p>
                        <p class="text-sm text-gray-600">{{ $report->reportedUser->email ?? '—' }}</p>
                        <p class="text-xs text-gray-500 mt-1">ID: {{ $report->reportedUser->id }}</p>
                    </div>
                    <span class="text-primary font-semibold text-sm">{{ __('admin.view') ?? 'عرض' }} <i class="fas fa-chevron-left"></i></span>
                </a>
            @else
                <p class="text-gray-500 p-4 bg-gray-50 rounded-lg">{{ __('admin.reports.show.user_deleted') ?? 'مستخدم محذوف' }}</p>
            @endif
        </div>
        @endif

        {{-- محادثة مرتبطة --}}
        @if($report->conversation)
        <div class="mb-6 pb-6 border-b border-gray-200">
            <h3 class="text-lg font-bold text-primary mb-3">{{ __('admin.reports.show.conversation_label') }}</h3>
            @if($report->conversation->ad)
                <p class="text-sm text-gray-600 mb-2">{{ __('admin.reports.show.conversation_ad') ?? 'إعلان المحادثة' }}</p>
                <a href="{{ route('admin.ads.show', $report->conversation->ad->uid) }}" class="inline-flex items-center gap-2 p-3 bg-gray-50 rounded-lg hover:bg-primary/5 transition mb-4">
                    <span class="font-semibold text-primary">{{ $report->conversation->ad->title }}</span>
                    <i class="fas fa-chevron-left text-xs"></i>
                </a>
            @endif
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @if($report->conversation->sender)
                <div>
                    <p class="text-sm text-gray-600 mb-2">{{ __('admin.reports.show.sender_label') }}</p>
                    <a href="{{ route('admin.users.show', $report->conversation->sender->id) }}" class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg hover:bg-primary/5 transition">
                        <img src="{{ $report->conversation->sender->avatar ? asset('storage/' . $report->conversation->sender->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($report->conversation->sender->name) }}"
                             class="w-8 h-8 rounded-full">
                        <span class="font-semibold text-primary">{{ $report->conversation->sender->name }}</span>
                        <i class="fas fa-chevron-left text-xs"></i>
                    </a>
                </div>
                @endif
                @if($report->conversation->receiver)
                <div>
                    <p class="text-sm text-gray-600 mb-2">{{ __('admin.reports.show.receiver_label') }}</p>
                    <a href="{{ route('admin.users.show', $report->conversation->receiver->id) }}" class="flex items-center gap-2 p-3 bg-gray-50 rounded-lg hover:bg-primary/5 transition">
                        <img src="{{ $report->conversation->receiver->avatar ? asset('storage/' . $report->conversation->receiver->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($report->conversation->receiver->name) }}"
                             class="w-8 h-8 rounded-full">
                        <span class="font-semibold text-primary">{{ $report->conversation->receiver->name }}</span>
                        <i class="fas fa-chevron-left text-xs"></i>
                    </a>
                </div>
                @endif
            </div>
            @if($report->conversation_messages && count($report->conversation_messages) > 0)
                <div class="mt-4">
                    <p class="text-sm text-gray-600 mb-2">{{ __('admin.reports.last_messages') ?? 'آخر الرسائل' }}</p>
                    <div class="p-3 bg-gray-50 rounded-lg border border-gray-200 max-h-64 overflow-y-auto space-y-2">
                        @foreach($report->conversation_messages as $msg)
                            <div class="text-xs p-2 bg-white rounded border border-gray-200">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-semibold text-gray-800">{{ $msg['sender_name'] ?? '—' }}</span>
                                    <span class="text-gray-500">{{ isset($msg['created_at']) ? \Carbon\Carbon::parse($msg['created_at'])->format('H:i') : '' }}</span>
                                </div>
                                <p class="text-gray-700 whitespace-pre-wrap">{{ $msg['message'] ?? '' }}</p>
                                @if(isset($msg['attachments']) && count($msg['attachments']) > 0)
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($msg['attachments'] as $attPath)
                                            @php
                                                $attUrl = is_string($attPath) ? (str_starts_with(trim($attPath), 'http') ? $attPath : asset('storage/' . ltrim($attPath, '/'))) : asset('storage/' . ltrim($attPath['path'] ?? $attPath['url'] ?? '', '/'));
                                            @endphp
                                            <a href="{{ $attUrl }}" target="_blank" rel="noopener noreferrer" class="block border border-gray-200 rounded overflow-hidden hover:border-primary transition" title="{{ __('admin.reports.show.view_image') }}">
                                                <img src="{{ $attUrl }}" alt="" class="w-20 h-16 object-cover" loading="lazy">
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        @endif

        {{-- ملاحظات الإدارة --}}
        <div class="mb-6">
            <p class="text-sm font-semibold text-gray-600 mb-2">{{ __('admin.reports.show.admin_notes_label') }}</p>
            <p class="text-gray-800 bg-blue-50 p-4 rounded-lg border border-blue-100">{{ $report->admin_notes ?: '—' }}</p>
            @if($report->admin_attachments && count($report->admin_attachments) > 0)
            <p class="text-sm font-semibold text-gray-600 mt-3 mb-2">{{ __('admin.reports.show.admin_attachments_label') }}</p>
            <div class="flex flex-wrap gap-4 mt-2">
                @foreach($report->admin_attachments as $att)
                    @php
                        $path = is_array($att) ? ($att['path'] ?? $att['url'] ?? '') : $att;
                        $name = is_array($att) ? ($att['original_name'] ?? basename($path)) : basename($path);
                        $mime = is_array($att) ? ($att['mime'] ?? '') : '';
                        $url = $path ? asset('storage/' . ltrim($path, '/')) : '';
                    @endphp
                    @if($url)
                        @if(str_starts_with($mime, 'image/') || in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg','jpeg','png','gif','webp']))
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="block border border-gray-200 rounded-lg overflow-hidden hover:border-primary transition w-48">
                                <img src="{{ $url }}" alt="" class="w-full h-36 object-cover" loading="lazy">
                                <p class="text-xs p-2 text-gray-600 truncate">{{ $name }}</p>
                            </a>
                        @elseif(str_starts_with($mime, 'video/') || in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['mp4','webm','avi','mov']))
                            <div class="border border-gray-200 rounded-lg overflow-hidden w-64">
                                <video src="{{ $url }}" controls class="w-full max-h-48" preload="metadata"></video>
                                <p class="text-xs p-2 text-gray-600 truncate">{{ $name }}</p>
                            </div>
                        @else
                            <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 p-3 border border-gray-200 rounded-lg hover:border-primary transition">
                                <i class="fas fa-file-alt text-primary"></i>
                                <span class="text-sm truncate max-w-[180px]">{{ $name }}</span>
                            </a>
                        @endif
                    @endif
                @endforeach
            </div>
            @endif
        </div>

        {{-- مراجع البلاغ --}}
        @if($report->reviewer && $report->reviewed_at)
        <div class="text-sm text-gray-500 mb-6">
            {{ __('admin.reports.show.reviewed_by') ?? 'تمت المراجعة بواسطة' }}: {{ $report->reviewer->name ?? '—' }}
            {{ $report->reviewed_at->format('Y-m-d H:i') }}
        </div>
        @endif

        {{-- حظر مستخدم (للمشرف فقط) --}}
        @if(auth('admin')->user()->isAdmin())
        @if($report->reportedUser || ($report->conversation && $report->conversation->sender_id !== $report->user_id && $report->conversation->receiver_id !== $report->user_id))
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h3 class="text-lg font-bold text-primary mb-4">{{ __('admin.reports.block_user') }}</h3>
                @php
                    $userToBlock = $report->reportedUser ?? null;
                    if (!$userToBlock && $report->conversation) {
                        $userToBlock = $report->conversation->sender_id == $report->user_id ? $report->conversation->receiver : $report->conversation->sender;
                    }
                @endphp
                @if($userToBlock)
                    <div class="flex items-center gap-4 flex-wrap">
                        <div class="flex items-center gap-2">
                            <img src="{{ $userToBlock->avatar ? asset('storage/' . $userToBlock->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($userToBlock->name) }}"
                                 class="w-10 h-10 rounded-full">
                            <div>
                                <p class="font-semibold">{{ $userToBlock->name }}</p>
                                <p class="text-sm text-gray-600">{{ $userToBlock->email }}</p>
                            </div>
                        </div>
                        @if(!$report->user || (method_exists($report->user, 'hasBlocked') && !$report->user->hasBlocked($userToBlock->id)))
                            <form action="{{ route('admin.users.block', $userToBlock->id) }}" method="POST" class="ml-auto">
                                @csrf
                                <input type="hidden" name="report_id" value="{{ $report->id }}">
                                <button type="submit" onclick="return confirm('{{ __('admin.reports.confirm_block') }}')"
                                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                    <i class="fas fa-ban ml-2"></i>
                                    {{ __('admin.reports.block_user') }}
                                </button>
                            </form>
                        @else
                            <span class="px-4 py-2 bg-gray-200 text-gray-600 rounded-lg">
                                <i class="fas fa-ban ml-2"></i>
                                {{ __('admin.reports.already_blocked') }}
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        @endif
        @endif
    </div>

    {{-- تعديل حالة البلاغ (دائماً متاح) --}}
    @if(auth('admin')->user()->isAdmin() || auth('admin')->user()->isSupportAgent())
    <form action="{{ route('admin.reports.update', $report->id) }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-xl shadow-md p-6">
        @csrf
        @method('PUT')
        <h3 class="text-xl font-bold text-primary mb-6">{{ __('admin.reports.show.update_status_title') }}</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.reports.show.status_required') }}</label>
                <select name="status" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                    <option value="pending"   {{ $report->status === 'pending' ? 'selected' : '' }}>{{ __('admin.reports.show.status_options.pending') ?? 'قيد الانتظار' }}</option>
                    <option value="reviewed"  {{ $report->status === 'reviewed' ? 'selected' : '' }}>{{ __('admin.reports.show.status_options.reviewed') }}</option>
                    <option value="resolved"  {{ $report->status === 'resolved' ? 'selected' : '' }}>{{ __('admin.reports.show.status_options.resolved') }}</option>
                    <option value="rejected"  {{ $report->status === 'rejected' ? 'selected' : '' }}>{{ __('admin.reports.show.status_options.rejected') }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.reports.show.admin_notes_label_form') }}</label>
                <textarea name="admin_notes" rows="4" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">{{ old('admin_notes', $report->admin_notes) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.reports.show.admin_attachments_label_form') }}</label>
                <input type="file" name="admin_attachments[]" multiple accept="image/*,video/*,.pdf,application/pdf" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-primary/10 file:text-primary">
                <p class="text-xs text-gray-500 mt-1">{{ __('admin.reports.show.admin_attachments_hint') }}</p>
            </div>
            <button type="submit" class="btn-primary px-6 py-3 rounded-lg font-bold">
                <i class="fas fa-save ml-2"></i>
                {{ __('admin.reports.show.save_update_button') }}
            </button>
        </div>
    </form>
    @endif
</div>
@endsection
