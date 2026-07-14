@extends('admin.layouts.app')

@section('title', __('admin.verification.request_details_title'))
@section('page-title', __('admin.verification.request_details_title'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl shadow-md p-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-primary">{{ __('admin.verification.request_label', ['id' => $request->id]) }}</h2>
            <p class="text-sm text-gray-500">{{ __('admin.verification.user_label', ['name' => $request->user->name, 'email' => $request->user->email]) }}</p>
            <div class="flex flex-wrap items-center gap-2 mt-3">
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
                <a href="{{ route('admin.users.edit', $request->user_id) }}"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-secondary text-white text-sm font-semibold hover:opacity-90">
                    <i class="fas fa-user-cog"></i>
                    {{ __('admin.verification.actions.user_account') }}
                </a>
            </div>
        </div>
        <a href="{{ route('admin.users.verification-requests') }}" class="text-primary hover:text-secondary flex items-center gap-2 text-sm shrink-0">
            <i class="fas fa-arrow-right"></i> {{ __('admin.verification.back_to_requests') }}
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <!-- Business Info -->
        <div class="bg-white rounded-xl shadow-md p-6 space-y-3">
            <h3 class="text-lg font-bold text-primary mb-2">{{ __('admin.verification.business.title') }}</h3>
            <div class="text-sm text-gray-800 space-y-1">
                <p><span class="font-semibold">{{ __('admin.verification.business.name') }}:</span> {{ $request->business_name ?? '-' }}</p>
                <p><span class="font-semibold">{{ __('admin.verification.business.type') }}:</span> {{ $request->business_type ?? '-' }}</p>
                <p><span class="font-semibold">{{ __('admin.verification.business.responsible_person') }}:</span> {{ $request->responsible_person ?? '-' }}</p>
                <p><span class="font-semibold">{{ __('admin.verification.business.address') }}:</span> {{ $request->business_address ?? '-' }}</p>
                <p><span class="font-semibold">{{ __('admin.verification.business.phone') }}:</span> {{ $request->business_phone ?? '-' }}</p>
            </div>
        </div>

        <!-- Contact Links -->
        <div class="bg-white rounded-xl shadow-md p-6 space-y-3">
            <h3 class="text-lg font-bold text-primary mb-2">{{ __('admin.verification.contact.title') }}</h3>
            <div class="text-sm text-gray-800 space-y-2">
                @if($request->instagram_url)
                    <a href="{{ $request->instagram_url }}" target="_blank" class="text-blue-600 hover:text-blue-800 flex items-center gap-2">
                        <i class="fab fa-instagram"></i> {{ __('admin.verification.contact.instagram') }}
                    </a>
                @endif
                @if($request->facebook_url)
                    <a href="{{ $request->facebook_url }}" target="_blank" class="text-blue-600 hover:text-blue-800 flex items-center gap-2">
                        <i class="fab fa-facebook"></i> {{ __('admin.verification.contact.facebook') }}
                    </a>
                @endif
                @if($request->website_url)
                    <a href="{{ $request->website_url }}" target="_blank" class="text-blue-600 hover:text-blue-800 flex items-center gap-2">
                        <i class="fas fa-globe"></i> {{ __('admin.verification.contact.website') }}
                    </a>
                @endif
                @if(!$request->instagram_url && !$request->facebook_url && !$request->website_url)
                    <span class="text-gray-400">{{ __('admin.verification.contact.none') }}</span>
                @endif
            </div>
        </div>

        <!-- Files -->
        <div class="bg-white rounded-xl shadow-md p-6 space-y-3">
            <h3 class="text-lg font-bold text-primary mb-2">{{ __('admin.verification.files.title') }}</h3>
            <div class="text-sm text-gray-800 space-y-3">
                <div>
                    <p class="font-semibold mb-1">{{ __('admin.verification.files.primary') }}</p>
                    @if($request->primary_document_path)
                        @php
                            $primaryExt = strtolower(pathinfo($request->primary_document_path, PATHINFO_EXTENSION));
                            $primaryIsImage = in_array($primaryExt, ['jpg','jpeg','png','webp']);
                            $primaryIsPdf = $primaryExt === 'pdf';
                        @endphp
                        <div class="flex items-center gap-2 text-blue-600 hover:text-blue-800">
                            <i class="fas fa-file"></i>
                            <a href="{{ asset('storage/' . $request->primary_document_path) }}" target="_blank">
                                {{ $request->primary_document_type === 'cr' ? __('admin.verification.files.primary_cr') : __('admin.verification.files.primary_id') }}
                            </a>
                        </div>
                        @if($primaryIsImage)
                            <div class="mt-3">
                                <img src="{{ asset('storage/' . $request->primary_document_path) }}" alt="{{ __('admin.verification.files.primary') }}" class="max-h-64 rounded-lg border">
                            </div>
                        @elseif($primaryIsPdf)
                            <div class="mt-3 h-64 border rounded-lg overflow-hidden">
                                <iframe src="{{ asset('storage/' . $request->primary_document_path) }}" class="w-full h-full"></iframe>
                            </div>
                        @endif
                        <div class="flex items-center gap-2 text-sm mt-1">
                            <a href="{{ asset('storage/' . $request->primary_document_path) }}" download class="text-primary hover:text-secondary flex items-center gap-1">
                                <i class="fas fa-download"></i> {{ __('admin.verification.files.download') }}
                            </a>
                        </div>
                    @else
                        <span class="text-gray-400">{{ __('admin.verification.files.none') }}</span>
                    @endif
                </div>

                <div>
                    <p class="font-semibold mb-1">{{ __('admin.verification.files.storefront') }}</p>
                    @if($request->storefront_image_path)
                        @php
                            $storeExt = strtolower(pathinfo($request->storefront_image_path, PATHINFO_EXTENSION));
                            $storeIsImage = in_array($storeExt, ['jpg','jpeg','png','webp']);
                            $storeIsPdf = $storeExt === 'pdf';
                        @endphp
                        <div class="flex items-center gap-2 text-blue-600 hover:text-blue-800">
                            <i class="fas fa-image"></i>
                            <a href="{{ asset('storage/' . $request->storefront_image_path) }}" target="_blank">{{ __('admin.verification.files.view') }}</a>
                        </div>
                        @if($storeIsImage)
                            <div class="mt-3">
                                <img src="{{ asset('storage/' . $request->storefront_image_path) }}" alt="{{ __('admin.verification.files.storefront') }}" class="max-h-64 rounded-lg border">
                            </div>
                        @elseif($storeIsPdf)
                            <div class="mt-3 h-64 border rounded-lg overflow-hidden">
                                <iframe src="{{ asset('storage/' . $request->storefront_image_path) }}" class="w-full h-full"></iframe>
                            </div>
                        @endif
                        <div class="flex items-center gap-2 text-sm mt-1">
                            <a href="{{ asset('storage/' . $request->storefront_image_path) }}" download class="text-primary hover:text-secondary flex items-center gap-1">
                                <i class="fas fa-download"></i> {{ __('admin.verification.files.download') }}
                            </a>
                        </div>
                    @else
                        <span class="text-gray-400">{{ __('admin.verification.files.none') }}</span>
                    @endif
                </div>

                @if($request->documents && count($request->documents) > 0)
                    <div>
                        <p class="font-semibold mb-1">{{ __('admin.verification.files.attachments') }}</p>
                        <div class="space-y-3">
                            @foreach($request->documents as $document)
                                @php
                                    $ext = strtolower(pathinfo($document, PATHINFO_EXTENSION));
                                    $isImage = in_array($ext, ['jpg','jpeg','png','webp']);
                                    $isPdf = $ext === 'pdf';
                                @endphp
                                <div class="flex items-center gap-2 text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-file"></i>
                                    <a href="{{ asset('storage/' . $document) }}" target="_blank">{{ basename($document) }}</a>
                                    <a href="{{ asset('storage/' . $document) }}" download class="text-primary hover:text-secondary text-xs flex items-center gap-1">
                                        <i class="fas fa-download"></i> {{ __('admin.verification.files.download') }}
                                    </a>
                                </div>
                                @if($isImage)
                                    <div>
                                        <img src="{{ asset('storage/' . $document) }}" alt="{{ __('admin.verification.files.attachments') }}" class="max-h-64 rounded-lg border">
                                    </div>
                                @elseif($isPdf)
                                    <div class="h-64 border rounded-lg overflow-hidden">
                                        <iframe src="{{ asset('storage/' . $document) }}" class="w-full h-full"></iframe>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Status -->
        <div class="bg-white rounded-xl shadow-md p-6 space-y-3">
            <h3 class="text-lg font-bold text-primary mb-2">{{ __('admin.verification.status_section') }}</h3>
            <div class="flex items-center gap-3">
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
                <span class="text-sm text-gray-500">{{ __('admin.verification.meta.created_at') }}: {{ $request->created_at->format('Y-m-d H:i') }}</span>
            </div>

            @if($request->admin_notes)
                <div class="bg-gray-50 p-3 rounded-lg text-sm text-gray-800">
                    <p class="font-semibold mb-1">{{ __('admin.verification.meta.review_notes') }}:</p>
                    <p>{{ $request->admin_notes }}</p>
                </div>
            @endif

            @if($request->reviewer)
                <p class="text-sm text-gray-500">{{ __('admin.verification.meta.reviewed_by') }} {{ $request->reviewer->name }} @if($request->reviewed_at) ({{ $request->reviewed_at->format('Y-m-d H:i') }}) @endif</p>
            @endif

            @if($request->status === 'pending')
            <div class="flex items-center gap-3 mt-2">
                <form action="{{ route('admin.users.verification-requests.approve', $request->id) }}" method="POST" onsubmit="return confirm('{{ __('admin.verification.actions.approve_confirm') }}')">
                    @csrf
                    <button type="submit" class="text-green-600 hover:text-green-800 flex items-center gap-1">
                        <i class="fas fa-check-circle"></i> {{ __('admin.verification.actions.approve') }}
                    </button>
                </form>
                <form action="{{ route('admin.users.verification-requests.reject', $request->id) }}" method="POST" onsubmit="return confirm('{{ __('admin.verification.actions.reject_confirm') }}')">
                    @csrf
                    <input type="hidden" name="admin_notes" value="{{ $request->admin_notes }}">
                    <button type="submit" class="text-red-600 hover:text-red-800 flex items-center gap-1">
                        <i class="fas fa-times-circle"></i> {{ __('admin.verification.actions.reject') }}
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

