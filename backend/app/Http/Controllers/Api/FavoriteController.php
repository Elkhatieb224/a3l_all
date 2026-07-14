<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdResource;
use App\Models\Ad;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $favorites = $user->favorites()
            ->with(['ad.category', 'ad.subcategory', 'ad.user'])
            ->latest()
            ->paginate($request->get('per_page', 20));

        // Favorites may reference deleted/soft-deleted ads; avoid breaking the API response.
        $ads = collect($favorites->items())
            ->map(fn ($favorite) => $favorite->ad)
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $ads->map(fn ($ad) => new AdResource($ad)),
            'meta' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
            ]
        ]);
    }

    public function toggle($uid)
    {
        $user = Auth::user();
        $ad = Ad::where('uid', $uid)->firstOrFail();

        $favorite = Favorite::where('user_id', $user->id)
            ->where('ad_id', $ad->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
            $isFavorite = false;
        } else {
            Favorite::create([
                'user_id' => $user->id,
                'ad_id' => $ad->id,
            ]);
            $isFavorite = true;
        }

        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite,
            'message' => $isFavorite ? 'Added to favorites' : 'Removed from favorites',
        ]);
    }

    public function destroy($uid)
    {
        $user = Auth::user();
        $ad = Ad::where('uid', $uid)->firstOrFail();

        Favorite::where('user_id', $user->id)
            ->where('ad_id', $ad->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Removed from favorites'
        ]);
    }
}
