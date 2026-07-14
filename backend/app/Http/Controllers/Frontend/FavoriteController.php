<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\Favorite;
use App\Models\UserActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $favorites = $user->favorites()->with(['ad.category', 'ad.subcategory', 'ad.user'])->latest()->paginate(12);
        
        return view('frontend.profile.favorites.index', compact('favorites'));
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
            
            // Log activity
            UserActivityLog::log(
                'favorite_added',
                __('frontend.profile.activity.favorite_added_description', ['title' => $ad->title]),
                $ad,
                ['ad_id' => $ad->id, 'ad_title' => $ad->title]
            );
        }
        
        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite,
            'message' => $isFavorite ? __('frontend.favorites.added') : __('frontend.favorites.removed'),
        ]);
    }

    public function destroy($uid)
    {
        $user = Auth::user();
        $ad = Ad::where('uid', $uid)->firstOrFail();
        
        Favorite::where('user_id', $user->id)
            ->where('ad_id', $ad->id)
            ->delete();
        
        return redirect()->route('favorites.index')
            ->with('success', __('frontend.favorites.removed'));
    }
}
