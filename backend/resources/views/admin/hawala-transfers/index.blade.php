@extends('admin.layouts.app')

@section('title', __('admin.hawala.title'))
@section('page-title', __('admin.hawala.index_title'))

@section('content')
<div class="space-y-6">
    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="{{ __('admin.hawala.filters.search') }}"
                   class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">

            <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                <option value="">{{ __('admin.hawala.filters.status') }}</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('admin.hawala.pending') }}</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>{{ __('admin.hawala.approved') }}</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>{{ __('admin.hawala.rejected') }}</option>
            </select>

            <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
                <i class="fas fa-search ml-2"></i>
                {{ __('admin.payments.filters.search') }}
            </button>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-primary text-white">
                    <tr class="text-right">
                        <th class="px-6 py-4 text-sm font-semibold">#</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.hawala.user') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.hawala.amount') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.hawala.receipt_number') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.hawala.status') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.hawala.created_at') }}</th>
                        <th class="px-6 py-4 text-sm font-semibold">{{ __('admin.payments.table.actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($transfers as $t)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-mono">{{ $t->id }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <img src="{{ $t->user->avatar ? asset('storage/' . $t->user->avatar) : 'https://ui-avatars.com/api/?name=' . urlencode($t->user->name) }}"
                                         alt="{{ $t->user->name }}" class="w-8 h-8 rounded-full">
                                    <div>
                                        <span class="font-medium">{{ $t->user->name }}</span>
                                        <p class="text-xs text-gray-500">{{ $t->user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold">{{ number_format($t->amount, 2) }}</span>
                                <span class="text-gray-500">{{ $t->currency }}</span>
                            </td>
                            <td class="px-6 py-4 font-mono text-sm">{{ $t->receipt_number }}</td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $t->status === 'approved' ? 'bg-green-100 text-green-700' :
                                       ($t->status === 'rejected' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                    {{ __('admin.hawala.' . $t->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $t->created_at->format('Y-m-d H:i') }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.hawala-transfers.show', $t->id) }}"
                                   class="text-blue-600 hover:text-blue-800 p-2 rounded hover:bg-blue-50">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-exchange-alt text-4xl text-gray-300 mb-3"></i>
                                <p>{{ __('admin.hawala.empty') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $transfers->links() }}
        </div>
    </div>
</div>
@endsection
