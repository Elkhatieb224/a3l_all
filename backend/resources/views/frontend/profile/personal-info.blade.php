@extends('frontend.layouts.app')

@section('title', __('frontend.profile.personal_info'))

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 sm:px-4 py-4 sm:py-8">
        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6">
            <!-- Sidebar -->
            @include('frontend.profile.partials.sidebar')

            <!-- Main Content -->
            <main class="flex-1">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-800 mb-4 sm:mb-6">
                        {{ __('frontend.profile.personal_info') }}
                    </h1>

                    <!-- Success Message -->
                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-sm text-green-600">
                                <i class="fas fa-check-circle ml-1"></i> {{ session('success') }}
                            </p>
                        </div>
                    @endif

                    <!-- Error Messages -->
                    @if($errors->any())
                        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                            <ul class="text-sm text-red-600 space-y-1">
                                @foreach($errors->all() as $error)
                                    <li><i class="fas fa-exclamation-circle ml-1"></i> {{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('profile.personal-info.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6 sm:space-y-8">
                        @csrf
                        @method('PUT')

                        <!-- Profile Picture Section -->
                        <div>
                            <h2 class="text-lg sm:text-xl font-bold text-gray-800 mb-4">
                                {{ __('frontend.profile.profile_picture') }}
                            </h2>
                            
                            <div class="flex flex-col sm:flex-row gap-4 sm:gap-6">
                                <!-- Avatar Upload -->
                                <div class="flex-shrink-0">
                                    <label for="avatar" class="cursor-pointer">
                                        <div class="w-48 h-48 sm:w-64 sm:h-64 border-2 border-dashed border-gray-300 rounded-lg flex flex-col items-center justify-center hover:border-primary transition relative overflow-hidden bg-gray-50">
                                            @if($user->avatar)
                                                <img src="{{ asset('storage/' . $user->avatar) }}" 
                                                     alt="Profile Picture"
                                                     class="w-full h-full object-cover"
                                                     id="avatarPreview">
                                            @else
                                                <div class="text-center" id="avatarPlaceholder">
                                                    <div class="w-16 h-16 sm:w-20 sm:h-20 bg-primary rounded-full flex items-center justify-center mx-auto mb-3">
                                                        <i class="fas fa-plus text-white text-2xl sm:text-3xl"></i>
                                                    </div>
                                                    <p class="text-gray-600 font-semibold">{{ __('frontend.profile.add_picture') }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </label>
                                    <input type="file" 
                                           id="avatar" 
                                           name="avatar" 
                                           accept="image/jpeg,image/png,image/jpg"
                                           class="hidden"
                                           onchange="previewAvatar(this)">
                                </div>

                                <!-- Guidelines -->
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-700 mb-3">{{ __('frontend.profile.picture_guidelines.line1') }}</h3>
                                    <ul class="space-y-2 text-sm text-gray-600">
                                        <li class="flex items-start gap-2">
                                            <i class="fas fa-check text-green-500 mt-1"></i>
                                            <span>{{ __('frontend.profile.picture_guidelines.line1') }}</span>
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <i class="fas fa-check text-green-500 mt-1"></i>
                                            <span>{{ __('frontend.profile.picture_guidelines.line2') }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- User Information Section -->
                        <div>
                            <h2 class="text-lg sm:text-xl font-bold text-gray-800 mb-4">
                                {{ __('frontend.profile.user_info') }}
                            </h2>

                            <div class="space-y-4">
                                <!-- Username (Read-only) -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.profile.username') }}
                                    </label>
                                    <input type="text" 
                                           value="{{ $user->email }}" 
                                           disabled
                                           class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 text-gray-500">
                                    <p class="text-xs text-gray-500 mt-1">لا يمكن تغيير اسم المستخدم</p>
                                </div>

                                <!-- Name -->
                                <div>
                                    <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.profile.name') }} <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="text" 
                                               id="name" 
                                               name="name" 
                                               value="{{ old('name', $user->name) }}"
                                               required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent pr-12">
                                        <button type="button" 
                                                class="absolute {{ app()->getLocale() === 'ar' ? 'left-4' : 'right-4' }} top-1/2 transform -translate-y-1/2 text-primary hover:text-secondary">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- Bio -->
                                <div>
                                    <label for="bio" class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.profile.bio') }} <span class="text-gray-500 text-xs">({{ __('frontend.optional') }})</span>
                                    </label>
                                    <textarea name="bio" 
                                              id="bio" 
                                              rows="4" 
                                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                              placeholder="{{ __('frontend.profile.bio_placeholder') }}">{{ old('bio', $user->bio) }}</textarea>
                                    @error('bio')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Country -->
                                <div>
                                    <label for="location_country" class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.profile.country') }} <span class="text-gray-500 text-xs">({{ __('frontend.optional') }})</span>
                                    </label>
                                    <select name="location_country" 
                                            id="location_country" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                                        <option value="">{{ __('frontend.profile.select_country') }}</option>
                                        <option value="SY" {{ old('location_country', $user->location_country) === 'SY' ? 'selected' : '' }}>{{ __('frontend.profile.syria') }}</option>
                                        <option value="TR" {{ old('location_country', $user->location_country) === 'TR' ? 'selected' : '' }}>{{ __('frontend.profile.turkey') }}</option>
                                    </select>
                                    @error('location_country')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- State -->
                                <div>
                                    <label for="location_state_code" class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.ads.location_state') }} <span class="text-gray-500 text-xs">({{ __('frontend.optional') }})</span>
                                    </label>
                                    <select id="location_state_code"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                            {{ !$user->location_country ? 'disabled' : '' }}>
                                        <option value="">{{ __('frontend.ads.select_state_first') }}</option>
                                    </select>
                                </div>

                                <!-- City -->
                                <div>
                                    <label for="location_city" class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.ads.location_city_level') }} <span class="text-gray-500 text-xs">({{ __('frontend.optional') }})</span>
                                    </label>
                                    <select name="location_city"
                                            id="location_city"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                            {{ !$user->location_country ? 'disabled' : '' }}>
                                        <option value="">{{ __('frontend.ads.select_city_after_state') }}</option>
                                    </select>
                                    @error('location_city')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- District -->
                                <div>
                                    <label for="location_district" class="block text-sm font-semibold text-gray-700 mb-2">
                                        {{ __('frontend.ads.location_district_level') }} <span class="text-gray-500 text-xs">({{ __('frontend.optional') }})</span>
                                    </label>
                                    <select name="location_district"
                                            id="location_district"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                                            {{ !$user->location_country ? 'disabled' : '' }}>
                                        <option value="">{{ __('frontend.ads.select_district_after_city') }}</option>
                                    </select>
                                    @error('location_district')
                                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="flex items-center gap-4 pt-4 border-t border-gray-200">
                            <button type="submit" class="btn-primary px-6 sm:px-8 py-3 rounded-lg font-bold text-sm sm:text-base">
                                <i class="fas fa-save ml-2"></i>
                                {{ __('frontend.profile.save') }}
                            </button>
                            <a href="{{ route('profile.index') }}" 
                               class="px-6 sm:px-8 py-3 bg-gray-200 text-gray-700 hover:bg-gray-300 rounded-lg transition font-semibold text-sm sm:text-base">
                                {{ __('frontend.cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</div>

<script>
    function previewAvatar(input) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const placeholder = document.getElementById('avatarPlaceholder');
                if (placeholder) {
                    placeholder.style.display = 'none';
                }
                
                let preview = document.getElementById('avatarPreview');
                if (!preview) {
                    const container = input.closest('label').querySelector('div');
                    preview = document.createElement('img');
                    preview.id = 'avatarPreview';
                    preview.className = 'w-full h-full object-cover';
                    container.appendChild(preview);
                }
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    }

    const geoApi = @json(url('/api/v1'));
    const pageLocale = @json(app()->getLocale());
    const oldCountry = @json(old('location_country', $user->location_country));
    const oldCity = @json(old('location_city', $user->location_city));
    const oldDistrict = @json(old('location_district', $user->location_district));

    const countrySelect = document.getElementById('location_country');
    const stateSelect = document.getElementById('location_state_code');
    const citySelect = document.getElementById('location_city');
    const districtSelect = document.getElementById('location_district');

    function effectiveLocale(countryCode) {
      if ((countryCode || '').toUpperCase() === 'TR') return 'tr';
      return (pageLocale || 'ar').toLowerCase();
    }

    function pickDisplayName(item, countryCode) {
      if (!item) return '';
      if (item.name) return item.name;
      const loc = effectiveLocale(countryCode);
      if (loc === 'tr' && item.name_tr) return item.name_tr;
      if (loc === 'en' && item.name_en) return item.name_en;
      return item.name_ar || item.name_en || item.name_tr || item.code || '';
    }

    function fetchGeoJson(url, countryCode) {
      const loc = effectiveLocale(countryCode);
      return fetch(url, {
        headers: {
          'Accept': 'application/json',
          'X-Locale': loc,
          'Accept-Language': loc === 'en' ? 'en' : (loc === 'tr' ? 'tr' : 'ar'),
        },
      }).then(r => r.json());
    }

    function resetSelect(selectEl, placeholder, disabled = true) {
      selectEl.innerHTML = `<option value="">${placeholder}</option>`;
      selectEl.disabled = disabled;
    }

    function populateSelectByNames(selectEl, names, selectedName, placeholder) {
      resetSelect(selectEl, placeholder, names.length === 0);
      names.forEach(name => {
        const option = document.createElement('option');
        option.value = name;
        option.textContent = name;
        if (selectedName && name === selectedName) option.selected = true;
        selectEl.appendChild(option);
      });
    }

    async function loadDistricts(cityGeoId, countryCode, selectedDistrictName) {
      resetSelect(districtSelect, @json(__('frontend.ads.select_district_after_city')), true);
      if (!cityGeoId) return;
      try {
        const resp = await fetchGeoJson(`${geoApi}/neighborhoods/${encodeURIComponent(cityGeoId)}`, countryCode);
        const items = (resp && resp.success && resp.data && Array.isArray(resp.data.items)) ? resp.data.items : [];
        const names = items.map(i => pickDisplayName(i, countryCode)).filter(Boolean);
        populateSelectByNames(
          districtSelect,
          names,
          selectedDistrictName || null,
          @json(__('frontend.ads.select_district_after_city'))
        );
      } catch (_) {
        resetSelect(districtSelect, @json(__('frontend.ads.select_district_after_city')), true);
      }
    }

    async function loadCities(stateGeoId, countryCode, selectedCityName, selectedDistrictName) {
      resetSelect(citySelect, @json(__('frontend.ads.select_city_after_state')), true);
      resetSelect(districtSelect, @json(__('frontend.ads.select_district_after_city')), true);
      if (!stateGeoId) return;
      try {
        const resp = await fetchGeoJson(`${geoApi}/districts/${encodeURIComponent(stateGeoId)}`, countryCode);
        const items = (resp && resp.success && resp.data && Array.isArray(resp.data.items)) ? resp.data.items : [];
        const mapped = items.map(i => ({
          id: i.id,
          name: pickDisplayName(i, countryCode),
        })).filter(i => i.name);
        citySelect.disabled = mapped.length === 0;
        citySelect.innerHTML = `<option value="">${@json(__('frontend.ads.select_city_after_state'))}</option>`;
        let selectedGeoId = null;
        mapped.forEach(item => {
          const option = document.createElement('option');
          option.value = item.name;
          option.textContent = item.name;
          option.dataset.geoId = String(item.id ?? '');
          if (selectedCityName && item.name === selectedCityName) {
            option.selected = true;
            selectedGeoId = item.id;
          }
          citySelect.appendChild(option);
        });
        if (selectedGeoId) {
          await loadDistricts(selectedGeoId, countryCode, selectedDistrictName);
        }
      } catch (_) {
        resetSelect(citySelect, @json(__('frontend.ads.select_city_after_state')), true);
      }
    }

    async function loadStates(countryCode, selectedCityName, selectedDistrictName) {
      resetSelect(stateSelect, @json(__('frontend.ads.select_state_first')), true);
      resetSelect(citySelect, @json(__('frontend.ads.select_city_after_state')), true);
      resetSelect(districtSelect, @json(__('frontend.ads.select_district_after_city')), true);

      if (!countryCode || (countryCode !== 'SY' && countryCode !== 'TR')) return;
      try {
        const resp = await fetchGeoJson(`${geoApi}/states?country=${encodeURIComponent(countryCode)}`, countryCode);
        const items = (resp && resp.success && resp.data && Array.isArray(resp.data.items)) ? resp.data.items : [];
        const mapped = items.map(i => ({
          id: i.id,
          name: pickDisplayName(i, countryCode),
        })).filter(i => i.name);
        stateSelect.disabled = mapped.length === 0;
        stateSelect.innerHTML = `<option value="">${@json(__('frontend.ads.select_state_first'))}</option>`;

        mapped.forEach(item => {
          const option = document.createElement('option');
          option.value = String(item.id ?? '');
          option.textContent = item.name;
          stateSelect.appendChild(option);
        });

        // إذا فيه مدينة محفوظة، جرّب تلقائياً تحميل كل ولاية وإيجادها
        if (selectedCityName) {
          for (const st of mapped) {
            await loadCities(st.id, countryCode, selectedCityName, selectedDistrictName);
            if (citySelect.value === selectedCityName) {
              stateSelect.value = String(st.id ?? '');
              break;
            }
          }
        }
      } catch (_) {
        resetSelect(stateSelect, @json(__('frontend.ads.select_state_first')), true);
      }
    }

    countrySelect.addEventListener('change', function () {
      loadStates(this.value, null, null);
    });

    stateSelect.addEventListener('change', function () {
      loadCities(this.value, countrySelect.value, null, null);
    });

    citySelect.addEventListener('change', function () {
      const selectedOption = citySelect.options[citySelect.selectedIndex];
      const geoId = selectedOption ? selectedOption.dataset.geoId : '';
      loadDistricts(geoId, countrySelect.value, null);
    });

    document.addEventListener('DOMContentLoaded', function () {
      loadStates(oldCountry || countrySelect.value, oldCity, oldDistrict);
    });
</script>
@endsection

