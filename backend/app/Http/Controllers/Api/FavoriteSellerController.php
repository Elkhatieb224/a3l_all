<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SellerProfileResource;
use App\Models\FavoriteSeller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FavoriteSellerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $favorites = FavoriteSeller::where('user_id', $user->id)
            ->with(['seller' => fn ($q) => $q->withCount('ads')])
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $favorites->map(fn ($f) => new SellerProfileResource($f->seller)),
            'meta' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
            ]
        ]);
    }

    public function toggle(string $slug)
    {
        $user = Auth::user();
        $seller = User::where('slug', $slug)->where('is_active', true)->firstOrFail();

        if ($seller->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن إضافة نفسك للمفضلة'
            ], 422);
        }

        $fav = FavoriteSeller::where('user_id', $user->id)
            ->where('seller_id', $seller->id)
            ->first();

        if ($fav) {
            $fav->delete();
            return response()->json([
                'success' => true,
                'is_favorite' => false,
                'message' => 'تم الحذف من المفضلة'
            ]);
        }

        FavoriteSeller::create([
            'user_id' => $user->id,
            'seller_id' => $seller->id,
        ]);

        return response()->json([
            'success' => true,
            'is_favorite' => true,
            'message' => 'تمت الإضافة للمفضلة'
        ]);
    }

    public function destroy(string $slug)
    {
        $user = Auth::user();
        $seller = User::where('slug', $slug)->firstOrFail();

        FavoriteSeller::where('user_id', $user->id)
            ->where('seller_id', $seller->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم الحذف من المفضلة'
        ]);
    }
}
