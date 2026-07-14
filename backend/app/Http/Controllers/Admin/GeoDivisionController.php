<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GeoDivision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GeoDivisionController extends Controller
{
    public function index(Request $request): View
    {
        $query = GeoDivision::query()->with('parent')->orderBy('country')->orderBy('level')->orderBy('sort_order')->orderBy('id');

        if ($request->filled('country')) {
            $query->where('country', strtoupper((string) $request->country));
        }
        if ($request->filled('level') && $request->level !== '') {
            $query->where('level', (int) $request->level);
        }
        if ($request->filled('parent_id')) {
            $query->where('parent_id', (int) $request->parent_id);
        }
        if ($request->filled('q')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim((string) $request->q)).'%';
            $query->where(function ($w) use ($term) {
                $w->where('code', 'like', $term)
                    ->orWhere('name_ar', 'like', $term)
                    ->orWhere('name_en', 'like', $term)
                    ->orWhere('name_tr', 'like', $term);
            });
        }

        $items = $query->paginate(35)->withQueryString();

        $stats = [
            'total' => GeoDivision::query()->count(),
            'sy' => GeoDivision::query()->where('country', 'SY')->count(),
            'tr' => GeoDivision::query()->where('country', 'TR')->count(),
        ];

        return view('admin.geo-divisions.index', compact('items', 'stats'));
    }

    public function create(Request $request): View
    {
        $presetParent = null;
        if ($request->filled('parent_id')) {
            $presetParent = GeoDivision::query()->find((int) $request->parent_id);
        }

        $parentCandidates = GeoDivision::query()
            ->where('level', '<', GeoDivision::LEVEL_NEIGHBORHOOD)
            ->orderBy('country')
            ->orderBy('level')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $googleMapsKey = config('services.google_maps.api_key');

        return view('admin.geo-divisions.create', compact('presetParent', 'parentCandidates', 'googleMapsKey'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'country' => strtoupper((string) $request->input('country', '')),
        ]);

        $validated = $request->validate([
            'country' => ['required', Rule::in(['SY', 'TR'])],
            'parent_id' => ['nullable', 'integer', 'exists:geo_divisions,id'],
            'code' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._\-]+$/', 'unique:geo_divisions,code'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'name_tr' => ['nullable', 'string', 'max:255'],
            'extra_match_names' => ['nullable', 'string', 'max:20000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        if ($this->allNamesEmpty($validated)) {
            return back()->withInput()->withErrors([
                'name_ar' => __('admin.geo_divisions.name_required_one'),
            ]);
        }

        $parent = null;
        if (! empty($validated['parent_id'])) {
            $parent = GeoDivision::query()->find((int) $validated['parent_id']);
            if ($parent === null) {
                return back()->withInput()->withErrors(['parent_id' => __('admin.geo_divisions.invalid_parent')]);
            }
            if ($parent->country !== $validated['country']) {
                return back()->withInput()->withErrors(['parent_id' => __('admin.geo_divisions.parent_country_mismatch')]);
            }
            if ($parent->level >= GeoDivision::LEVEL_NEIGHBORHOOD) {
                return back()->withInput()->withErrors(['parent_id' => __('admin.geo_divisions.parent_max_depth')]);
            }
        }

        $level = $parent ? $parent->level + 1 : GeoDivision::LEVEL_STATE;

        $extra = $this->parseExtraMatchLines($validated['extra_match_names'] ?? null);

        GeoDivision::query()->create([
            'country' => $validated['country'],
            'parent_id' => $parent?->id,
            'level' => $level,
            'code' => $validated['code'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'name_ar' => $validated['name_ar'] ?: null,
            'name_en' => $validated['name_en'] ?: null,
            'name_tr' => $validated['name_tr'] ?: null,
            'extra_match_names' => $extra === [] ? null : $extra,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        return redirect()
            ->route('admin.geo-divisions.index', ['country' => $validated['country']])
            ->with('success', __('admin.geo_divisions.created_ok'));
    }

    public function edit(GeoDivision $geo_division): View
    {
        $googleMapsKey = config('services.google_maps.api_key');

        return view('admin.geo-divisions.edit', compact('geo_division', 'googleMapsKey'));
    }

    public function update(Request $request, GeoDivision $geo_division): RedirectResponse
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9._\-]+$/',
                Rule::unique('geo_divisions', 'code')->ignore($geo_division->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'name_tr' => ['nullable', 'string', 'max:255'],
            'extra_match_names' => ['nullable', 'string', 'max:20000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        if ($this->allNamesEmpty($validated)) {
            return back()->withInput()->withErrors([
                'name_ar' => __('admin.geo_divisions.name_required_one'),
            ]);
        }

        $extra = $this->parseExtraMatchLines($validated['extra_match_names'] ?? null);

        $geo_division->update([
            'code' => $validated['code'],
            'sort_order' => (int) ($validated['sort_order'] ?? $geo_division->sort_order),
            'name_ar' => $validated['name_ar'] ?: null,
            'name_en' => $validated['name_en'] ?: null,
            'name_tr' => $validated['name_tr'] ?: null,
            'extra_match_names' => $extra === [] ? null : $extra,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ]);

        return redirect()
            ->route('admin.geo-divisions.index', ['country' => $geo_division->country])
            ->with('success', __('admin.geo_divisions.updated_ok'));
    }

    public function destroy(GeoDivision $geo_division): RedirectResponse
    {
        $country = $geo_division->country;
        $geo_division->delete();

        return redirect()
            ->route('admin.geo-divisions.index', ['country' => $country])
            ->with('success', __('admin.geo_divisions.deleted_ok'));
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function allNamesEmpty(array $validated): bool
    {
        return trim((string) ($validated['name_ar'] ?? '')) === ''
            && trim((string) ($validated['name_en'] ?? '')) === ''
            && trim((string) ($validated['name_tr'] ?? '')) === '';
    }

    /**
     * @return list<string>
     */
    private function parseExtraMatchLines(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $t = trim((string) $line);
            if ($t !== '') {
                $out[] = $t;
            }
        }

        return array_values(array_unique($out));
    }
}
