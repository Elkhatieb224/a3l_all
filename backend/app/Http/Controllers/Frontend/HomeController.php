<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ad;
use App\Models\Subcategory;
use App\Models\SearchHistory;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function index()
    {
        // Get active categories with subcategories count
        $categories = Category::active()
            ->with(['subcategories' => function($q) {
                $q->whereNull('parent_subcategory_id')
                  ->active()
                  ->withCount('ads')
                  ->orderBy('order')
                  ->take(5);
            }])
            ->withCount(['subcategories' => function($q) {
                $q->whereNull('parent_subcategory_id')->active();
            }, 'ads'])
            ->ordered()
            ->get();

        // Get featured ads (latest 12)
        $featuredAds = Ad::where('status', 'active')
            ->where('is_featured', true)
            ->with(['category', 'subcategory', 'user'])
            ->latest('published_at')
            ->take(12)
            ->get();

        // Get latest ads (latest 20)
        $latestAds = Ad::where('status', 'active')
            ->with(['category', 'subcategory', 'user'])
            ->latest('published_at')
            ->take(20)
            ->get();

        // Get urgent ads
        $urgentAds = Ad::where('status', 'active')
            ->where('is_urgent', true)
            ->with(['category', 'subcategory', 'user'])
            ->latest('published_at')
            ->take(8)
            ->get();

        // Get last 5 search histories for authenticated user
        $recentSearches = collect();
        if (Auth::check()) {
            $recentSearches = SearchHistory::where('user_id', Auth::id())
                ->orderBy('updated_at', 'desc')
                ->get()
                ->unique('search_term')
                ->take(5)
                ->pluck('search_term');
        }

        return view('frontend.home', compact('categories', 'featuredAds', 'latestAds', 'urgentAds', 'recentSearches'));
    }
}
