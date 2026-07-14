@extends('frontend.layouts.app')

@section('title', __('frontend.packages.my_requests'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            @include('frontend.profile.partials.sidebar')

            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.packages.my_requests') }}
                    </h1>

                    @if($requests->count() > 0)
                        <div class="space-y-4">
                            @foreach($requests as $req)
                                <a href="{{ route('profile.package-requests.show', $req->id) }}" class="block border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition">
                                    <div class="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <p class="font-semibold text-gray-800">{{ $req->package ? ($req->package->getName(app()->getLocale()) ?? $req->package->name_ar) : '—' }}</p>
                                            <p class="text-sm text-gray-500">{{ $req->created_at->format('Y-m-d H:i') }}</p>
                                        </div>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                                            {{ $req->status === 'approved' ? 'bg-green-100 text-green-700' :
                                               ($req->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                            {{ __('frontend.packages.request_status_' . $req->status) }}
                                        </span>
                                    </div>
                                    @if($req->admin_response)
                                        <p class="text-sm text-gray-600 mt-2 line-clamp-2">{{ Str::limit($req->admin_response, 80) }}</p>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                        <div class="mt-6">{{ $requests->links() }}</div>
                    @else
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-inbox text-5xl text-gray-300 mb-3"></i>
                            <p>{{ __('frontend.packages.no_requests') }}</p>
                            <a href="{{ route('packages.index') }}" class="inline-block mt-4 btn-primary px-6 py-2 rounded-lg">{{ __('frontend.packages.browse_packages') }}</a>
                        </div>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>
@endsection
