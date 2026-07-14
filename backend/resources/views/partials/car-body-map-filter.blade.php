@php
    use App\Support\CarBodyMapSupport;

    $fid = $field['id'] ?? null;
    $label = $field['label'][app()->getLocale()] ?? ($field['label']['ar'] ?? $fid);
    $locale = app()->getLocale();
@endphp

@if($fid)
    <details class="border border-gray-200 rounded-lg">
        <summary class="cursor-pointer px-3 py-2 font-semibold text-gray-700 bg-gray-50 rounded-lg">
            {{ $label }}
        </summary>
        <div class="p-3 space-y-3 max-h-72 overflow-y-auto">
            <p class="text-xs text-gray-500">{{ __('frontend.car_body_map.filter_hint') }}</p>
            @foreach(CarBodyMapSupport::parts() as $partId => $part)
                @php
                    $param = CarBodyMapSupport::filterParamName($fid, $partId);
                    $current = request($param);
                    $partLabel = $part['label'][$locale] ?? $part['label']['ar'];
                @endphp
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">{{ $partLabel }}</label>
                    <select name="{{ $param }}" class="w-full px-2 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-secondary">
                        <option value="">{{ __('frontend.select_option') }}</option>
                        @foreach(CarBodyMapSupport::statusLabels() as $status => $statusLabel)
                            <option value="{{ $status }}" {{ (string) $current === (string) $status ? 'selected' : '' }}>
                                {{ $statusLabel[$locale] ?? $statusLabel['ar'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endforeach
        </div>
    </details>
@endif
