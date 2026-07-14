<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdResource;
use App\Http\Resources\SellerProfileResource;
use App\Http\Resources\SellerRatingResource;
use App\Models\FavoriteSeller;
use App\Models\SellerRating;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SellerController extends Controller
{
    /**
     * عرض ملف التاجر العام (بدون مصادقة)
     * يتضمن: بيانات التاجر، الإعلانات، التقييمات والتعليقات
     */
    public function show(string $slug)
    {
        $seller = User::where('slug', $slug)
            ->where('is_active', true)
            ->withCount('ads')
            ->firstOrFail();

        // إعلانات التاجر النشطة
        $ads = $seller->ads()
            ->where('status', 'active')
            ->with(['category', 'subcategory'])
            ->latest('published_at')
            ->paginate(12);

        // التقييمات والتعليقات (آخر 20)
        $ratings = $seller->ratingsAsSeller()
            ->with('user:id,name,avatar')
            ->latest()
            ->take(20)
            ->get();

        // تقييم المستخدم الحالي (إن كان مسجلاً)
        $userRating = null;
        if (Auth::guard('sanctum')->check()) {
            $userRating = $seller->ratingsAsSeller()
                ->where('user_id', Auth::guard('sanctum')->id())
                ->first();
        }

        $sellerData = (new SellerProfileResource($seller))->resolve();
        $currentUser = Auth::guard('sanctum')->user();
        $hasBlocked = $currentUser && $currentUser->hasBlocked($seller->id);
        $sellerData['has_blocked'] = $hasBlocked;

        if (Auth::guard('sanctum')->check()) {
            $sellerData['is_favorite'] = FavoriteSeller::where('user_id', Auth::guard('sanctum')->id())
                ->where('seller_id', $seller->id)
                ->exists();
        } else {
            $sellerData['is_favorite'] = false;
        }

        // لمن قام بحظر التاجر: لا نعرض إعلاناته ولا تقييماته
        if ($hasBlocked) {
            $ads = collect();
            $ratings = collect();
            $userRating = null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'seller' => $sellerData,
                'ads' => $hasBlocked ? [] : AdResource::collection($ads),
                'ads_meta' => $hasBlocked ? [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 12,
                    'total' => 0,
                ] : [
                    'current_page' => $ads->currentPage(),
                    'last_page' => $ads->lastPage(),
                    'per_page' => $ads->perPage(),
                    'total' => $ads->total(),
                ],
                'ratings' => $hasBlocked ? [] : SellerRatingResource::collection($ratings),
                'user_rating' => $userRating ? new SellerRatingResource($userRating) : null,
            ],
        ]);
    }

    /**
     * تقييم التاجر (يحتاج مصادقة)
     */
    public function rate(Request $request, string $slug)
    {
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول للتقييم',
            ], 401);
        }

        $seller = User::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        if ($seller->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك تقييم نفسك',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ], [
            'rating.required' => 'التقييم مطلوب',
            'rating.min' => 'التقييم من 1 إلى 5',
            'rating.max' => 'التقييم من 1 إلى 5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق',
                'errors' => $validator->errors(),
            ], 422);
        }

        $existingRating = SellerRating::where('seller_id', $seller->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingRating) {
            return response()->json([
                'success' => false,
                'message' => 'You can only rate this seller once.',
            ], 422);
        }

        SellerRating::create([
            'seller_id' => $seller->id,
            'user_id' => $user->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);
        $message = 'تم إرسال التقييم بنجاح';

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
}
