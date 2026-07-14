@extends('admin.layouts.app')

@section('title', __('admin.notifications.send_title'))
@section('page-title', __('admin.notifications.send_title'))

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    @if(session('success'))
        <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
            <i class="fas fa-check-circle ml-2"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-md p-6 space-y-6">
    

        <form action="{{ route('admin.notifications.store') }}" method="POST" class="space-y-6" id="notificationForm">
            @csrf

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.notifications.title') }}</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.notifications.message') }}</label>
                <textarea name="message" rows="4" required
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">{{ old('message') }}</textarea>
            </div>

            <div class="space-y-3">
                <p class="text-sm font-semibold text-gray-700">{{ __('admin.notifications.channel_type') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <label class="flex items-center gap-2 border rounded-lg p-3 cursor-pointer">
                        <input type="radio" name="channel_type" value="push" {{ old('channel_type', 'both') === 'push' ? 'checked' : '' }}>
                        <span>{{ __('admin.notifications.type_push') }}</span>
                    </label>
                    <label class="flex items-center gap-2 border rounded-lg p-3 cursor-pointer">
                        <input type="radio" name="channel_type" value="email" {{ old('channel_type', 'both') === 'email' ? 'checked' : '' }}>
                        <span>{{ __('admin.notifications.type_email') }}</span>
                    </label>
                    <label class="flex items-center gap-2 border rounded-lg p-3 cursor-pointer">
                        <input type="radio" name="channel_type" value="both" {{ old('channel_type', 'both') === 'both' ? 'checked' : '' }}>
                        <span>{{ __('admin.notifications.type_both') }}</span>
                    </label>
                </div>
            </div>

            <div class="space-y-3">
                <p class="text-sm font-semibold text-gray-700">{{ __('admin.notifications.target') }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <label class="flex items-center gap-2 border rounded-lg p-3 cursor-pointer">
                        <input type="radio" name="target_type" class="target-type-radio" value="all" {{ old('target_type', 'all') === 'all' ? 'checked' : '' }}>
                        <span>{{ __('admin.notifications.all_users') }}</span>
                    </label>
                    <label class="flex items-center gap-2 border rounded-lg p-3 cursor-pointer">
                        <input type="radio" name="target_type" class="target-type-radio" value="verified" {{ old('target_type') === 'verified' ? 'checked' : '' }}>
                        <span>{{ __('admin.notifications.verified_users') }}</span>
                    </label>
                    <label class="flex items-center gap-2 border rounded-lg p-3 cursor-pointer">
                        <input type="radio" name="target_type" class="target-type-radio" value="unverified" {{ old('target_type') === 'unverified' ? 'checked' : '' }}>
                        <span>{{ __('admin.notifications.unverified_users') }}</span>
                    </label>
                    <label class="flex items-center gap-2 border rounded-lg p-3 cursor-pointer">
                        <input type="radio" name="target_type" class="target-type-radio" value="date_range" {{ old('target_type') === 'date_range' ? 'checked' : '' }}>
                        <span>{{ __('admin.notifications.date_range') }}</span>
                    </label>
                    <label class="flex items-center gap-2 border rounded-lg p-3 cursor-pointer">
                        <input type="radio" name="target_type" class="target-type-radio" value="selected" {{ old('target_type') === 'selected' ? 'checked' : '' }}>
                        <span>{{ __('admin.notifications.selected_users') }}</span>
                    </label>
                </div>
            </div>

            <div id="dateRangeBox" class="{{ old('target_type') === 'date_range' ? '' : 'hidden' }} grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.notifications.from_date') }}</label>
                    <input type="date" name="from_date" value="{{ old('from_date') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">{{ __('admin.notifications.to_date') }}</label>
                    <input type="date" name="to_date" value="{{ old('to_date') }}"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                </div>
            </div>

            <div id="selectedUsersBox" class="{{ old('target_type') === 'selected' ? '' : 'hidden' }} space-y-2">
                <label class="block text-sm font-semibold text-gray-700">
                    {{ __('admin.notifications.user_ids_label') }}
                </label>
                <textarea name="user_ids" id="userIdsTextarea" rows="2"
                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary"
                          placeholder="{{ __('admin.notifications.user_ids_help') }}">{{ old('user_ids') }}</textarea>
                <p class="text-xs text-gray-500">{{ __('admin.notifications.user_ids_help') }}</p>

                <div class="border rounded-lg p-3">
                    <p class="text-sm font-semibold text-gray-700 mb-3">{{ __('admin.notifications.recent_users') }}</p>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-3">
                        <input id="userSearchInput" type="text"
                               class="md:col-span-2 w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary"
                               placeholder="ابحث بالاسم أو الإيميل أو ID...">
                        <select id="userVerifiedFilter"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                            <option value="all">الكل</option>
                            <option value="verified">موثّق</option>
                            <option value="unverified">غير موثّق</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <p class="text-xs text-gray-500 mb-2">المستخدمون المختارون</p>
                        <div id="selectedUsersChips" class="flex flex-wrap gap-2 text-xs"></div>
                    </div>

                    <div id="usersList" class="max-h-64 overflow-y-auto border rounded-lg p-2 space-y-2"></div>
                    <div id="usersListEmpty" class="hidden text-xs text-gray-500 mt-2">لا يوجد نتائج مطابقة.</div>
                </div>
                <div class="border rounded-lg p-3">
                    <p class="text-sm font-semibold text-gray-700 mb-2">آخر المستخدمين (عرض سريع)</p>
                    <div class="flex flex-wrap gap-2 text-xs max-h-40 overflow-y-auto">
                        @foreach($recentUsers->take(40) as $recent)
                            <button type="button"
                                    class="quick-add-user px-2 py-1 bg-gray-100 rounded border text-gray-700 hover:bg-gray-200"
                                    data-id="{{ $recent->id }}">
                                ID: {{ $recent->id }} • {{ $recent->name }} • {{ $recent->email }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end">
                <button type="submit" id="sendNotificationBtn" class="btn-primary px-6 py-3 rounded-lg font-bold">
                    <i class="fas fa-paper-plane ml-2"></i> <span class="btn-text">{{ __('admin.notifications.send') }}</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var targetRadios = document.querySelectorAll('.target-type-radio');
    var dateBox = document.getElementById('dateRangeBox');
    var selectedBox = document.getElementById('selectedUsersBox');

    function toggleBoxes() {
        var checked = document.querySelector('input[name="target_type"]:checked');
        var val = checked ? checked.value : 'all';
        dateBox.classList.toggle('hidden', val !== 'date_range');
        selectedBox.classList.toggle('hidden', val !== 'selected');
    }

    targetRadios.forEach(function(r) { r.addEventListener('change', toggleBoxes); });
    toggleBoxes();

    // Prevent double submit - show "Sending..." and disable button
    var form = document.getElementById('notificationForm');
    var btn = document.getElementById('sendNotificationBtn');
    var btnText = btn ? btn.querySelector('.btn-text') : null;
    var sendingText = @json(__('admin.notifications.sending'));

    if (form && btn) {
        form.addEventListener('submit', function() {
            if (!form.checkValidity()) return;
            btn.disabled = true;
            if (btnText) btnText.textContent = sendingText;
            var icon = btn.querySelector('i');
            if (icon) icon.className = 'fas fa-spinner fa-spin ml-2';
        });
    }

    var users = @json($selectableUsers);
    var searchInput = document.getElementById('userSearchInput');
    var verifiedFilter = document.getElementById('userVerifiedFilter');
    var usersList = document.getElementById('usersList');
    var usersListEmpty = document.getElementById('usersListEmpty');
    var selectedChips = document.getElementById('selectedUsersChips');
    var userIdsTextarea = document.getElementById('userIdsTextarea');
    var quickAddButtons = document.querySelectorAll('.quick-add-user');
    var selectedTokenSet = new Set();

    function parseTokens(rawValue) {
        return rawValue.split(',').map(function(v) { return v.trim(); }).filter(Boolean);
    }

    function syncTextareaFromSet() {
        if (!userIdsTextarea) return;
        userIdsTextarea.value = Array.from(selectedTokenSet).join(', ');
    }

    function renderSelectedChips() {
        if (!selectedChips) return;
        selectedChips.innerHTML = '';
        Array.from(selectedTokenSet).forEach(function(token) {
            var chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'px-2 py-1 rounded border bg-secondary/10 border-secondary/20 text-secondary hover:bg-secondary/20';
            chip.textContent = token + ' ×';
            chip.addEventListener('click', function() {
                selectedTokenSet.delete(token);
                syncTextareaFromSet();
                renderSelectedChips();
                renderUsersList();
            });
            selectedChips.appendChild(chip);
        });
    }

    function addToken(token) {
        if (!token) return;
        selectedTokenSet.add(String(token));
        syncTextareaFromSet();
        renderSelectedChips();
        renderUsersList();
    }

    function renderUsersList() {
        if (!usersList) return;
        var query = ((searchInput && searchInput.value) ? searchInput.value : '').trim().toLowerCase();
        var vf = (verifiedFilter && verifiedFilter.value) ? verifiedFilter.value : 'all';

        var filtered = users.filter(function(user) {
            if (vf === 'verified' && !user.is_verified) return false;
            if (vf === 'unverified' && user.is_verified) return false;
            if (!query) return true;
            return String(user.id).includes(query)
                || user.name.toLowerCase().includes(query)
                || user.email.toLowerCase().includes(query);
        });

        usersList.innerHTML = '';
        filtered.forEach(function(user) {
            var isSelected = selectedTokenSet.has(String(user.id));
            var row = document.createElement('div');
            row.className = 'flex items-center justify-between gap-2 rounded border px-2 py-2 text-xs';
            row.innerHTML = '<div class="min-w-0">' +
                '<div class="font-semibold text-gray-800 truncate">ID: ' + user.id + ' • ' + (user.name || '-') + '</div>' +
                '<div class="text-gray-500 truncate">' + (user.email || '-') + (user.is_verified ? ' • موثّق' : ' • غير موثّق') + '</div>' +
                '</div>';

            var btnAdd = document.createElement('button');
            btnAdd.type = 'button';
            btnAdd.className = 'px-2 py-1 rounded border ' + (isSelected ? 'bg-green-50 text-green-700 border-green-200' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-100');
            btnAdd.textContent = isSelected ? 'مضاف' : 'إضافة';
            btnAdd.disabled = isSelected;
            btnAdd.addEventListener('click', function() { addToken(user.id); });

            row.appendChild(btnAdd);
            usersList.appendChild(row);
        });

        if (usersListEmpty) {
            usersListEmpty.classList.toggle('hidden', filtered.length > 0);
        }
    }

    if (userIdsTextarea) {
        parseTokens(userIdsTextarea.value).forEach(function(token) { selectedTokenSet.add(token); });
        userIdsTextarea.addEventListener('blur', function() {
            selectedTokenSet.clear();
            parseTokens(userIdsTextarea.value).forEach(function(token) { selectedTokenSet.add(token); });
            syncTextareaFromSet();
            renderSelectedChips();
            renderUsersList();
        });
    }

    if (searchInput) searchInput.addEventListener('input', renderUsersList);
    if (verifiedFilter) verifiedFilter.addEventListener('change', renderUsersList);
    quickAddButtons.forEach(function(btnQuick) {
        btnQuick.addEventListener('click', function() {
            addToken(btnQuick.getAttribute('data-id'));
        });
    });

    renderSelectedChips();
    renderUsersList();
});
</script>
@endsection

