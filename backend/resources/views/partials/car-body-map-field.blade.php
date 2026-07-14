@php
    use App\Support\CarBodyMapSupport;

    $fieldId = $fieldId ?? 'body_expertise';
    $fieldValue = $fieldValue ?? null;
    $namePrefix = $namePrefix ?? "custom_fields[{$fieldId}]";
    $normalized = CarBodyMapSupport::normalizeValue(is_array($fieldValue) ? $fieldValue : null);
    $parts = $normalized['parts'];
    $locale = app()->getLocale();

    $jsLabels = [
        'parts' => collect(CarBodyMapSupport::parts())->mapWithKeys(fn ($part) => [
            $part['id'] => $part['label'][$locale] ?? $part['label']['ar'],
        ])->all(),
        'statuses' => collect(CarBodyMapSupport::statusLabels())->mapWithKeys(fn ($label, $status) => [
            $status => $label[$locale] ?? $label['ar'],
        ])->all(),
        'groupTitles' => collect(CarBodyMapSupport::summaryGroupTitles())->mapWithKeys(fn ($label, $status) => [
            $status => $label[$locale] ?? $label['ar'],
        ])->all(),
        'allOriginalSummary' => __('frontend.car_body_map.all_original_summary'),
        'progressTemplate' => __('frontend.car_body_map.progress'),
        'listSeparator' => $locale === 'en' ? ', ' : '، ',
    ];
@endphp

<div class="car-body-map-widget relative"
     data-car-body-map
     data-field-id="{{ $fieldId }}"
     data-name-prefix="{{ $namePrefix }}"
     data-initial-parts='@json($parts)'
     data-labels='@json($jsLabels)'>

    <div class="flex flex-wrap gap-3 mb-4 text-xs">
        @foreach(CarBodyMapSupport::statusLabels() as $status => $label)
            <div class="flex items-center gap-2">
                <span class="inline-block w-4 h-4 rounded border border-gray-300"
                      style="background-color: {{ CarBodyMapSupport::statusColors()[$status] }}"></span>
                <span>{{ $label[$locale] ?? $label['ar'] }}</span>
            </div>
        @endforeach
    </div>

    <p class="text-sm text-gray-600 mb-3" data-car-body-progress>
        {{ __('frontend.car_body_map.progress', ['count' => collect($parts)->filter(fn ($s) => $s !== CarBodyMapSupport::STATUS_ORIGINAL)->count(), 'total' => count($parts)]) }}
    </p>

    <div class="mx-auto max-w-[220px] sm:max-w-[280px] car-body-map-diagram relative aspect-[420/543]">
        <img src="{{ asset(CarBodyMapSupport::svgDiagramAsset()) }}"
             alt=""
             class="absolute inset-0 w-full h-full object-contain pointer-events-none select-none"
             aria-hidden="true">

        <svg viewBox="{{ CarBodyMapSupport::svgDiagramViewBox() }}"
             preserveAspectRatio="xMidYMid meet"
             class="absolute inset-0 w-full h-full select-none car-body-map-svg"
             role="img"
             aria-label="{{ __('frontend.car_body_map.diagram_label') }}">
            <g class="car-body-map-parts">
                @foreach(CarBodyMapSupport::svgDiagramShapes() as $partId => $shape)
                    @php
                        $status = $parts[$partId] ?? CarBodyMapSupport::STATUS_ORIGINAL;
                        $isOriginal = $status === CarBodyMapSupport::STATUS_ORIGINAL;
                    @endphp
                    <path data-part-id="{{ $partId }}"
                          d="{{ $shape['d'] }}"
                          fill="{{ CarBodyMapSupport::statusColors()[$status] }}"
                          fill-opacity="{{ $isOriginal ? '0' : '0.55' }}"
                          stroke="{{ $isOriginal ? 'transparent' : CarBodyMapSupport::statusColors()[$status] }}"
                          stroke-width="1"
                          stroke-linejoin="round"
                          class="car-body-part"/>
                @endforeach
            </g>
        </svg>
    </div>

    <div data-car-body-menu
         class="hidden absolute z-20 -translate-x-1/2 -translate-y-1/2 bg-white border border-gray-200 rounded-lg shadow-lg p-1 min-w-[10rem]">
        @foreach(CarBodyMapSupport::statusLabels() as $status => $label)
            <button type="button"
                    data-status="{{ $status }}"
                    class="w-full text-start px-3 py-2 text-sm rounded hover:bg-gray-100 flex items-center gap-2">
                <span class="inline-block w-3 h-3 rounded border border-gray-300"
                      style="background-color: {{ CarBodyMapSupport::statusColors()[$status] }}"></span>
                <span>{{ $label[$locale] ?? $label['ar'] }}</span>
            </button>
        @endforeach
    </div>

    <div class="mt-4 flex items-start gap-2">
        <input type="checkbox"
               id="car_body_all_original_{{ $fieldId }}"
               data-car-body-all-original
               class="mt-1 w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
               {{ $normalized['all_original'] ? 'checked' : '' }}>
        <label for="car_body_all_original_{{ $fieldId }}" class="text-sm text-gray-700">
            {{ __('frontend.car_body_map.all_original_checkbox') }}
        </label>
    </div>

    <div class="mt-4">
        <h4 class="text-sm font-semibold text-gray-800 mb-2">{{ __('frontend.car_body_map.auto_summary_title') }}</h4>
        <div data-car-body-summary
             class="text-sm text-gray-700 bg-gray-50 border border-gray-200 rounded-lg p-3 whitespace-pre-line min-h-[3rem]">
            {{ $normalized['summary'][$locale] ?? $normalized['summary']['ar'] }}
        </div>
    </div>

    <div data-car-body-hidden></div>
</div>

@once
    @push('styles')
        <style>
            .car-body-map-widget .car-body-part {
                cursor: pointer;
                transition: fill 0.15s ease, filter 0.15s ease;
            }
            .car-body-map-widget .car-body-part:hover {
                fill-opacity: 0.25 !important;
                stroke: rgba(59, 130, 246, 0.6) !important;
            }
            .car-body-map-widget .car-body-map-diagram {
                filter: drop-shadow(0 1px 3px rgba(15, 23, 42, 0.08));
            }
        </style>
    @endpush
    @push('scripts')
        <script src="{{ asset('js/car-body-map.js') }}?v=6"></script>
    @endpush
@endonce
