@extends('admin.layouts.app')

@section('title', __('admin.package_requests.title'))
@section('page-title', __('admin.package_requests.index_title'))

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.package-requests.index') }}" class="px-4 py-2 rounded-lg {{ !request('status') ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">
            {{ __('admin.package_requests.all') }} ({{ $statusCounts['all'] ?? 0 }})
        </a>
        <a href="{{ route('admin.package-requests.index', ['status' => 'pending']) }}" class="px-4 py-2 rounded-lg {{ request('status') === 'pending' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">
            {{ __('admin.package_requests.pending') }} ({{ $statusCounts['pending'] ?? 0 }})
        </a>
        <a href="{{ route('admin.package-requests.index', ['status' => 'approved']) }}" class="px-4 py-2 rounded-lg {{ request('status') === 'approved' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">
            {{ __('admin.package_requests.approved') }} ({{ $statusCounts['approved'] ?? 0 }})
        </a>
        <a href="{{ route('admin.package-requests.index', ['status' => 'rejected']) }}" class="px-4 py-2 rounded-lg {{ request('status') === 'rejected' ? 'bg-primary text-white' : 'bg-gray-200 text-gray-700' }}">
            {{ __('admin.package_requests.rejected') }} ({{ $statusCounts['rejected'] ?? 0 }})
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-md p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="{{ __('admin.package_requests.search_placeholder') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg">
            <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                <i class="fas fa-search ml-2"></i>
                {{ __('admin.payments.filters.search') }}
            </button>
        </form>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-primary text-white">
                    <tr class="text-right">
                        <th class="px-6 py-4 text-sm font-semibold">#</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.package_requests.user') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.package_requests.package') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.package_requests.status') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.package_requests.created_at') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.payments.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($requests as $req)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-mono">{{ $req->id }}</td>
                            <td class="px-6 py-4">
                                @if($req->user)
                                    <a href="{{ route('admin.users.show', $req->user->id) }}" class="flex items-center gap-2 p-1 -m-1 rounded hover:bg-gray-100 transition group">
                                        <img src="{{ $req->user->avatar ? asset('storage/' . $req->user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($req->user->name) }}"
                                             alt="" class="w-8 h-8 rounded-full">
                                        <div>
                                            <span class="font-medium text-primary group-hover:underline">{{ $req->user->name }}</span>
                                            <p class="text-xs text-gray-500">{{ $req->user->email }}</p>
                                        </div>
                                        <i class="fas fa-external-link-alt text-gray-400 text-xs opacity-0 group-hover:opacity-100"></i>
                                    </a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($req->package)
                                    <span class="font-semibold">{{ $req->package->name_ar ?? $req->package->name_en ?? $req->package->name }}</span>
                                    <p class="text-xs text-gray-500">{{ number_format($req->package->price, 0) }} {{ $req->package->currency }}</p>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $req->status === 'approved' ? 'bg-green-100 text-green-700' :
                                       ($req->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                    {{ __('admin.package_requests.status_' . $req->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $req->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.package-requests.show', $req->id) }}"
                                   class="text-blue-600 hover:text-blue-800 p-2 rounded hover:bg-blue-50">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl text-gray-300 mb-3"></i>
                                <p>{{ __('admin.package_requests.empty') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
