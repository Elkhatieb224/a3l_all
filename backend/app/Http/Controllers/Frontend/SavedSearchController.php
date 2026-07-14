<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\SavedSearch;
use App\Support\SavedSearchFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedSearchController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $savedSearches = SavedSearch::where('user_id', Auth::id())->latest()->paginate(20);
        return view('frontend.profile.saved-searches.index', compact('savedSearches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:255',
            'search' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer',
            'subcategory_id' => 'nullable|integer',
            'min_price' => 'nullable|numeric',
            'max_price' => 'nullable|numeric',
            'custom_filters' => 'nullable|array',
        ]);

        $filters = SavedSearchFilters::normalize([
            'search' => $data['search'] ?? '',
            'category_id' => $data['category_id'] ?? null,
            'subcategory_id' => $data['subcategory_id'] ?? null,
            'min_price' => $data['min_price'] ?? null,
            'max_price' => $data['max_price'] ?? null,
            'custom_filters' => $data['custom_filters'] ?? [],
        ]);

        if ($filters['search'] === '' && !$filters['category_id'] && !$filters['subcategory_id']) {
            return back()->withErrors(['saved_search' => __('frontend.saved_searches.empty_filters')]);
        }

        SavedSearch::create([
            'user_id' => Auth::id(),
            'name' => $data['name'] ?? null,
            'filters' => $filters,
        ]);

        return back()->with('success', __('frontend.saved_searches.saved_success'));
    }

    public function destroy(int $id)
    {
        $saved = SavedSearch::where('user_id', Auth::id())->findOrFail($id);
        $saved->delete();
        return back()->with('success', __('frontend.saved_searches.deleted_success'));
    }

    public function show(int $id)
    {
        $saved = SavedSearch::where('user_id', Auth::id())->findOrFail($id);
        $query = SavedSearchFilters::buildAdsBaseQuery();
        SavedSearchFilters::applyToAdsQuery($query, $saved->filters ?? []);
        $ads = $query->latest('published_at')->paginate(20);

        return view('frontend.profile.saved-searches.show', [
            'savedSearch' => $saved,
            'ads' => $ads,
        ]);
    }
}

