<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdListResource;
use App\Models\SavedSearch;
use App\Support\SavedSearchFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SavedSearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index()
    {
        $items = SavedSearch::where('user_id', Auth::id())
            ->latest()
            ->get()
            ->map(fn (SavedSearch $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'filters' => SavedSearchFilters::normalize($s->filters ?? []),
                'created_at' => $s->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'filters' => 'required|array',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $filters = SavedSearchFilters::normalize($request->input('filters', []));
        if ($filters['search'] === '' && !$filters['category_id'] && !$filters['subcategory_id']) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.saved_searches.empty_filters'),
            ], 422);
        }

        $saved = SavedSearch::create([
            'user_id' => Auth::id(),
            'name' => $request->input('name'),
            'filters' => $filters,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('frontend.saved_searches.saved_success'),
            'data' => [
                'id' => $saved->id,
                'name' => $saved->name,
                'filters' => $saved->filters,
                'created_at' => $saved->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    public function destroy(int $id)
    {
        $saved = SavedSearch::where('user_id', Auth::id())->findOrFail($id);
        $saved->delete();

        return response()->json([
            'success' => true,
            'message' => __('frontend.saved_searches.deleted_success'),
        ]);
    }

    public function results(Request $request, int $id)
    {
        $saved = SavedSearch::where('user_id', Auth::id())->findOrFail($id);
        $perPage = max(1, min((int) $request->input('per_page', 20), 50));

        $query = SavedSearchFilters::buildAdsBaseQuery();
        SavedSearchFilters::applyToAdsQuery($query, $saved->filters ?? []);
        $query->latest('published_at');

        $ads = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'saved_search' => [
                'id' => $saved->id,
                'name' => $saved->name,
                'filters' => SavedSearchFilters::normalize($saved->filters ?? []),
            ],
            'data' => AdListResource::collection($ads),
            'meta' => [
                'current_page' => $ads->currentPage(),
                'last_page' => $ads->lastPage(),
                'per_page' => $ads->perPage(),
                'total' => $ads->total(),
            ],
        ]);
    }
}

