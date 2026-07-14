<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\DynamicRegion;
use App\Support\RegionCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DynamicRegionController extends Controller
{
    public function index(Request $request)
    {
        $query = DynamicRegion::query()->with('parent')->orderByDesc('id');

        if ($request->filled('country')) {
            $query->where('country', strtoupper((string) $request->country));
        }
        if ($request->filled('type')) {
            $query->where('type', (string) $request->type);
        }
        if ($request->filled('q')) {
            $q = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $request->q)).'%';
            $query->where(function ($w) use ($q) {
                $w->where('code', 'like', $q)
                    ->orWhere('name_ar', 'like', $q)
                    ->orWhere('name_en', 'like', $q)
                    ->orWhere('name_tr', 'like', $q);
            });
        }

        $regions = $query->paginate(24)->withQueryString();

        $locale = app()->getLocale();
        $stateLabels = ['SY' => [], 'TR' => []];
        foreach (['SY', 'TR'] as $cc) {
            foreach (RegionCatalog::treeForCountry($cc) as $state) {
                $code = (string) ($state['code'] ?? '');
                if ($code !== '') {
                    $stateLabels[$cc][$code] = RegionCatalog::labelForLocale($state, $locale);
                }
            }
        }

        $stats = [
            'total' => DynamicRegion::query()->count(),
            'sy' => DynamicRegion::query()->where('country', 'SY')->count(),
            'tr' => DynamicRegion::query()->where('country', 'TR')->count(),
        ];

        return view('admin.dynamic-regions.index', compact('regions', 'stateLabels', 'stats'));
    }

    public function create()
    {
        $staticSy = RegionCatalog::treeForCountry('SY');
        $staticTr = RegionCatalog::treeForCountry('TR');
        $dynamicStates = DynamicRegion::query()
            ->where('type', 'state')
            ->whereNull('parent_id')
            ->whereNull('anchor_state_code')
            ->orderBy('country')
            ->orderBy('id')
            ->get();

        $dynamicCities = DynamicRegion::query()
            ->where('type', 'city')
            ->orderBy('country')
            ->orderBy('id')
            ->get();

        return view('admin.dynamic-regions.create', compact(
            'staticSy',
            'staticTr',
            'dynamicStates',
            'dynamicCities'
        ));
    }

    public function store(Request $request)
    {
        $request->merge([
            'country' => strtoupper((string) $request->input('country', '')),
        ]);

        $validated = $request->validate([
            'country' => ['required', Rule::in(['SY', 'TR'])],
            'type' => ['required', Rule::in(['state', 'city', 'district'])],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'name_tr' => ['nullable', 'string', 'max:255'],
            'extra_match_names' => ['nullable', 'string', 'max:5000'],
            'city_parent_mode' => ['nullable', Rule::in(['anchor_static', 'dynamic_state'])],
            'anchor_state_code' => ['nullable', 'string', 'max:64'],
            'parent_state_id' => ['nullable', 'integer', 'exists:dynamic_regions,id'],
            'parent_city_id' => ['nullable', 'integer', 'exists:dynamic_regions,id'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        if (
            ($validated['name_ar'] ?? '') === ''
            && ($validated['name_en'] ?? '') === ''
            && ($validated['name_tr'] ?? '') === ''
        ) {
            return back()->withInput()->withErrors([
                'name_en' => __('admin.dynamic_regions.name_required_one'),
            ]);
        }

        if ($validated['type'] === 'city') {
            $mode = (string) ($validated['city_parent_mode'] ?? '');
            if ($mode === 'anchor_static' && ($validated['anchor_state_code'] ?? '') === '') {
                return back()->withInput()->withErrors([
                    'anchor_state_code' => __('admin.dynamic_regions.anchor_required'),
                ]);
            }
            if ($mode === 'dynamic_state' && empty($validated['parent_state_id'])) {
                return back()->withInput()->withErrors([
                    'parent_state_id' => __('admin.dynamic_regions.dynamic_state_required'),
                ]);
            }
        }
        if ($validated['type'] === 'district' && empty($validated['parent_city_id'])) {
            return back()->withInput()->withErrors([
                'parent_city_id' => __('admin.dynamic_regions.parent_city_required'),
            ]);
        }

        $country = $validated['country'];
        $extra = collect(preg_split('/\r\n|\r|\n|,/', (string) ($validated['extra_match_names'] ?? '')))
            ->map(fn ($s) => trim((string) $s))
            ->filter()
            ->values()
            ->take(80)
            ->all();

        $names = [
            'name_ar' => $validated['name_ar'] ?: null,
            'name_en' => $validated['name_en'] ?: null,
            'name_tr' => $validated['name_tr'] ?: null,
        ];

        $lat = $request->filled('latitude') ? (float) $request->latitude : null;
        $lng = $request->filled('longitude') ? (float) $request->longitude : null;

        try {
            if ($validated['type'] === 'state') {
                $row = $this->createState($country, $names, $extra, $lat, $lng);
            } elseif ($validated['type'] === 'city') {
                $mode = $validated['city_parent_mode'] ?? '';
                if (! in_array($mode, ['anchor_static', 'dynamic_state'], true)) {
                    return back()->withInput()->withErrors([
                        'city_parent_mode' => __('admin.dynamic_regions.city_parent_required'),
                    ]);
                }
                if ($mode === 'anchor_static') {
                    $anchor = (string) ($validated['anchor_state_code'] ?? '');
                    $codes = collect(RegionCatalog::treeForCountry($country))->pluck('code')->all();
                    if ($anchor === '' || ! in_array($anchor, $codes, true)) {
                        return back()->withInput()->withErrors([
                            'anchor_state_code' => __('admin.dynamic_regions.invalid_static_state'),
                        ]);
                    }
                    $row = $this->createAnchoredCity($country, $anchor, $names, $extra, $lat, $lng);
                } else {
                    $sid = (int) ($validated['parent_state_id'] ?? 0);
                    $state = DynamicRegion::query()
                        ->where('id', $sid)
                        ->where('country', $country)
                        ->where('type', 'state')
                        ->whereNull('anchor_state_code')
                        ->first();
                    if ($state === null) {
                        return back()->withInput()->withErrors([
                            'parent_state_id' => __('admin.dynamic_regions.invalid_dynamic_state'),
                        ]);
                    }
                    $row = $this->createCityUnderDynamicState($country, $state, $names, $extra, $lat, $lng);
                }
            } else {
                $cid = (int) ($validated['parent_city_id'] ?? 0);
                $city = DynamicRegion::query()
                    ->where('id', $cid)
                    ->where('country', $country)
                    ->where('type', 'city')
                    ->first();
                if ($city === null) {
                    return back()->withInput()->withErrors([
                        'parent_city_id' => __('admin.dynamic_regions.invalid_parent_city'),
                    ]);
                }
                $row = $this->createDistrict($country, $city, $names, $extra, $lat, $lng);
            }
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'duplicate') {
                return back()->withInput()->withErrors([
                    'name_en' => __('admin.dynamic_regions.duplicate'),
                ]);
            }
            throw $e;
        } catch (\Illuminate\Database\QueryException) {
            return back()->withInput()->withErrors([
                'name_en' => __('admin.dynamic_regions.duplicate_or_db'),
            ]);
        }

        ActivityLog::log('dynamic_region_created', $row, ['code' => $row->code, 'type' => $row->type]);

        return redirect()->route('admin.dynamic-regions.index')
            ->with('success', __('admin.dynamic_regions.created_ok'));
    }

    public function destroy(int $id)
    {
        $row = DynamicRegion::query()->findOrFail($id);
        if ($row->children()->exists()) {
            return back()->with('error', __('admin.dynamic_regions.has_children'));
        }
        ActivityLog::log('dynamic_region_deleted', $row, ['code' => $row->code]);
        $row->delete();

        return back()->with('success', __('admin.dynamic_regions.deleted_ok'));
    }

    /**
     * @param  array{name_ar: ?string, name_en: ?string, name_tr: ?string}  $names
     * @param  list<string>  $extra
     */
    private function createState(string $country, array $names, array $extra, ?float $lat, ?float $lng): DynamicRegion
    {
        $label = (string) ($names['name_en'] ?: $names['name_ar'] ?: $names['name_tr']);
        $hash = DynamicRegion::dedupHash($country, 'state', null, $label, null);
        $this->assertNoDuplicate($country, 'state', null, null, $hash);

        return DynamicRegion::query()->create([
            'country' => $country,
            'anchor_state_code' => null,
            'parent_id' => null,
            'type' => 'state',
            'code' => DynamicRegion::allocateCode($country),
            'dedup_hash' => $hash,
            'name_ar' => $names['name_ar'],
            'name_en' => $names['name_en'],
            'name_tr' => $names['name_tr'],
            'extra_match_names' => $extra,
            'latitude' => $lat,
            'longitude' => $lng,
            'created_by_user_id' => null,
            'use_count' => 0,
        ]);
    }

    /**
     * @param  array{name_ar: ?string, name_en: ?string, name_tr: ?string}  $names
     * @param  list<string>  $extra
     */
    private function createAnchoredCity(string $country, string $anchorStateCode, array $names, array $extra, ?float $lat, ?float $lng): DynamicRegion
    {
        $label = (string) ($names['name_en'] ?: $names['name_ar'] ?: $names['name_tr']);
        $hash = DynamicRegion::dedupHash($country, 'city', null, $label, $anchorStateCode);
        $this->assertNoDuplicate($country, 'city', null, $anchorStateCode, $hash);

        return DynamicRegion::query()->create([
            'country' => $country,
            'anchor_state_code' => $anchorStateCode,
            'parent_id' => null,
            'type' => 'city',
            'code' => DynamicRegion::allocateCode($country),
            'dedup_hash' => $hash,
            'name_ar' => $names['name_ar'],
            'name_en' => $names['name_en'],
            'name_tr' => $names['name_tr'],
            'extra_match_names' => $extra,
            'latitude' => $lat,
            'longitude' => $lng,
            'created_by_user_id' => null,
            'use_count' => 0,
        ]);
    }

    /**
     * @param  array{name_ar: ?string, name_en: ?string, name_tr: ?string}  $names
     * @param  list<string>  $extra
     */
    private function createCityUnderDynamicState(string $country, DynamicRegion $state, array $names, array $extra, ?float $lat, ?float $lng): DynamicRegion
    {
        $label = (string) ($names['name_en'] ?: $names['name_ar'] ?: $names['name_tr']);
        $hash = DynamicRegion::dedupHash($country, 'city', $state->id, $label, null);
        $this->assertNoDuplicate($country, 'city', $state->id, null, $hash);

        return DynamicRegion::query()->create([
            'country' => $country,
            'anchor_state_code' => null,
            'parent_id' => $state->id,
            'type' => 'city',
            'code' => DynamicRegion::allocateCode($country),
            'dedup_hash' => $hash,
            'name_ar' => $names['name_ar'],
            'name_en' => $names['name_en'],
            'name_tr' => $names['name_tr'],
            'extra_match_names' => $extra,
            'latitude' => $lat,
            'longitude' => $lng,
            'created_by_user_id' => null,
            'use_count' => 0,
        ]);
    }

    /**
     * @param  array{name_ar: ?string, name_en: ?string, name_tr: ?string}  $names
     * @param  list<string>  $extra
     */
    private function createDistrict(string $country, DynamicRegion $city, array $names, array $extra, ?float $lat, ?float $lng): DynamicRegion
    {
        $label = (string) ($names['name_en'] ?: $names['name_ar'] ?: $names['name_tr']);
        $hash = DynamicRegion::dedupHash($country, 'district', $city->id, $label, null);
        $this->assertNoDuplicate($country, 'district', $city->id, null, $hash);

        return DynamicRegion::query()->create([
            'country' => $country,
            'anchor_state_code' => null,
            'parent_id' => $city->id,
            'type' => 'district',
            'code' => DynamicRegion::allocateCode($country),
            'dedup_hash' => $hash,
            'name_ar' => $names['name_ar'],
            'name_en' => $names['name_en'],
            'name_tr' => $names['name_tr'],
            'extra_match_names' => $extra,
            'latitude' => $lat,
            'longitude' => $lng,
            'created_by_user_id' => null,
            'use_count' => 0,
        ]);
    }

    private function assertNoDuplicate(string $country, string $type, ?int $parentId, ?string $anchor, string $hash): void
    {
        $q = DynamicRegion::query()
            ->where('country', $country)
            ->where('type', $type)
            ->where('dedup_hash', $hash);

        if ($type === 'state') {
            $q->whereNull('parent_id')->whereNull('anchor_state_code');
        } elseif ($type === 'city' && $anchor !== null && $anchor !== '') {
            $q->whereNull('parent_id')->where('anchor_state_code', $anchor);
        } elseif ($type === 'city' && $parentId !== null) {
            $q->where('parent_id', $parentId)->whereNull('anchor_state_code');
        } elseif ($type === 'district' && $parentId !== null) {
            $q->where('parent_id', $parentId);
        }

        if ($q->exists()) {
            throw new \RuntimeException('duplicate');
        }
    }
}

