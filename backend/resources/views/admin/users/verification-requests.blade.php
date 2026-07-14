@extends('admin.layouts.app')

@section('title', __('admin.verification.requests_title'))
@section('page-title', __('admin.verification.requests_title'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-primary">{{ __('admin.verification.requests_title') }}</h2>
        </div>
        
        <!-- Filters -->
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                <option value="">{{ __('admin.verification.filters.all_statuses') }}</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('admin.verification.filters.pending') }}</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('admin.verification.filters.approved') }}</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('admin.verification.filters.rejected') }}</option>
            </select>
            
            <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                <i class="fas fa-search ml-2"></i>
                {{ __('admin.search') }}
            </button>
        </form>
    </div>
    
    <!-- Requests Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-primary text-white">
                    <tr class="text-right">
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.verification.table.user') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.verification.table.business') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.verification.table.contact') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.verification.table.files') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.verification.table.status') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.verification.table.requested_at') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.verification.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($requests as $request)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="{{ $request->user->avatar ? asset('storage/' . $request->user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($request->user->name) }}" 
                                         alt="{{ $request->user->name }}" 
                                         class="w-10 h-10 rounded-full border-2 border-secondary">
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ $request->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $request->user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <div class="space-y-1">
                                    <p><span class="font-semibold">{{ __('admin.verification.business.name') }}:</span> {{ $request->business_name ?? '-' }}</p>
                                    <p><span class="font-semibold">{{ __('admin.verification.business.type') }}:</span> {{ $request->business_type ?? '-' }}</p>
                                    <p><span class="font-semibold">{{ __('admin.verification.business.responsible_person') }}:</span> {{ $request->responsible_person ?? '-' }}</p>
                                    <p><span class="font-semibold">{{ __('admin.verification.business.address') }}:</span> {{ $request->business_address ?? '-' }}</p>
                                    <p><span class="font-semibold">{{ __('admin.verification.business.phone') }}:</span> {{ $request->business_phone ?? '-' }}</p>
                                    @if($request->message)
                                        <p class="text-gray-500 text-xs mt-2"><span class="font-semibold">{{ __('admin.verification.business.notes') }}:</span> {{ Str::limit($request->message, 120) }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <div class="space-y-1">
                                    @if($request->instagram_url)
                                        <a href="{{ $request->instagram_url }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs flex items-center gap-1">
                                            <i class="fab fa-instagram"></i> {{ __('admin.verification.contact.instagram') }}
                                        </a>
                                    @endif
                                    @if($request->facebook_url)
                                        <a href="{{ $request->facebook_url }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs flex items-center gap-1">
                                            <i class="fab fa-facebook"></i> {{ __('admin.verification.contact.facebook') }}
                                        </a>
                                    @endif
                                    @if($request->website_url)
                                        <a href="{{ $request->website_url }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-xs flex items-center gap-1">
                                            <i class="fas fa-globe"></i> {{ __('admin.verification.contact.website') }}
                                        </a>
                                    @endif
                                    @if(!$request->instagram_url && !$request->facebook_url && !$request->website_url)
                                        <span class="text-gray-400">{{ __('admin.verification.contact.none') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-2 text-xs">
                                    @if($request->primary_document_path)
                                        <a href="{{ asset('storage/' . $request->primary_document_path) }}" target="_blank" class="text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                            <i class="fas fa-file"></i>
                                            {{ $request->primary_document_type === 'cr' ? __('admin.verification.files.primary_cr') : __('admin.verification.files.primary_id') }}
                                        </a>
                                    @endif
                                    @if($request->storefront_image_path)
                                        <a href="{{ asset('storage/' . $request->storefront_image_path) }}" target="_blank" class="text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                            <i class="fas fa-image"></i>
                                            {{ __('admin.verification.files.storefront') }}
                                        </a>
                                    @endif
                                    @if(!$request->primary_document_path && !$request->storefront_image_path)
                                        <span class="text-gray-400">{{ __('admin.verification.files.none') }}</span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $request->status === 'approved' ? 'bg-green-100 text-green-700' : 
                                       ($request->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                    @if($request->status === 'pending')
                                        {{ __('admin.verification.status_badges.pending') }}
                                    @elseif($request->status === 'approved')
                                        {{ __('admin.verification.status_badges.approved') }}
                                    @else
                                        {{ __('admin.verification.status_badges.rejected') }}
                                    @endif
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $request->created_at->format('Y-m-d H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <a href="{{ route('admin.users.verification-requests.show', $request->id) }}"
                                           class="text-primary hover:text-secondary font-semibold inline-flex items-center gap-1 text-sm"
                                           title="{{ __('admin.verification.actions.view_request') }}">
                                            <i class="fas fa-eye"></i>
                                            <span class="hidden sm:inline">{{ __('admin.verification.actions.view_request') }}</span>
                                        </a>
                                        <a href="{{ route('admin.users.edit', $request->user_id) }}"
                                           class="text-gray-700 hover:text-primary inline-flex items-center gap-1 text-sm border border-gray-200 rounded-lg px-2 py-1 hover:border-primary"
                                           title="{{ __('admin.verification.actions.user_account') }}">
                                            <i class="fas fa-user-cog"></i>
                                            <span class="hidden sm:inline">{{ __('admin.verification.actions.user_account') }}</span>
                                        </a>
                                        @if($request->status === 'pending')
                                            <form action="{{ route('admin.users.verification-requests.approve', $request->id) }}"
                                                  method="POST"
                                                  class="inline"
                                                  onsubmit="return confirm('{{ __('admin.verification.actions.approve_confirm') }}')">
                                                @csrf
                                                <button type="submit"
                                                        class="text-green-600 hover:text-green-800 p-2 rounded hover:bg-green-50"
                                                        title="{{ __('admin.verification.actions.approve') }}">
                                                    <i class="fas fa-check-circle"></i>
                                                </button>
                                            </form>
                                            <button type="button"
                                                    onclick="openRejectModal({{ $request->id }})"
                                                    class="text-red-600 hover:text-red-800 p-2 rounded hover:bg-red-50"
                                                    title="{{ __('admin.verification.actions.reject') }}">
                                                <i class="fas fa-times-circle"></i>
                                            </button>
                                        @endif
                                    </div>
                                    @if($request->status !== 'pending')
                                        <div class="text-sm text-gray-500 border-t border-gray-100 pt-2 mt-1">
                                            @if($request->reviewer)
                                                <p>{{ __('admin.verification.meta.reviewed_by') }}</p>
                                                <p class="font-semibold">{{ $request->reviewer->name }}</p>
                                                <p class="text-xs">{{ $request->reviewed_at?->format('Y-m-d H:i') }}</p>
                                            @endif
                                            @if($request->admin_notes)
                                                <p class="mt-2 text-xs bg-gray-50 p-2 rounded">{{ $request->admin_notes }}</p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                <p>{{ __('admin.verification.empty') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $requests->links() }}
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-bold text-primary mb-4">{{ __('admin.verification.actions.reject_modal_title') }}</h3>
        <form id="rejectForm" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    {{ __('admin.verification.actions.reject_notes_label') }} <span class="text-red-500">*</span>
                </label>
                <textarea name="admin_notes" 
                          rows="4" 
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary"
                          required
                          placeholder="{{ __('admin.verification.actions.reject_placeholder') }}"></textarea>
            </div>
            <div class="flex items-center justify-end gap-3">
                <button type="button" 
                        onclick="closeRejectModal()"
                        class="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                    {{ __('admin.verification.actions.cancel') }}
                </button>
                <button type="submit" 
                        class="btn-primary px-6 py-2 rounded-lg">
                    {{ __('admin.verification.actions.reject_request') }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRejectModal(requestId) {
    const modal = document.getElementById('rejectModal');
    const form = document.getElementById('rejectForm');
    form.action = `{{ url('admin/users/verification-requests') }}/${requestId}/reject`;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeRejectModal() {
    const modal = document.getElementById('rejectModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('rejectForm').reset();
}
</script>
@endsection

