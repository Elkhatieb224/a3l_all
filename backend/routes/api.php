<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public Routes
Route::prefix('v1')->group(function () {
    // Home (mobile app)
    Route::get('/home', [\App\Http\Controllers\Api\HomeController::class, 'index']);

    // Authentication
    Route::post('/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/password/forgot', [\App\Http\Controllers\Api\AuthController::class, 'sendPasswordResetCode']);
    Route::post('/password/reset', [\App\Http\Controllers\Api\AuthController::class, 'resetPassword']);

    // Categories
    Route::get('/categories', [\App\Http\Controllers\Api\CategoryController::class, 'index']);
    Route::get('/categories/{id}', [\App\Http\Controllers\Api\CategoryController::class, 'show']);
    Route::get('/categories/{id}/subcategories', [\App\Http\Controllers\Api\CategoryController::class, 'subcategories']);
    Route::get('/subcategories/{id}/children', [\App\Http\Controllers\Api\CategoryController::class, 'subcategoryChildren']);
    Route::get('/subcategories/{id}', [\App\Http\Controllers\Api\CategoryController::class, 'subcategoryShow']);

    Route::get('/regions/{country}', [\App\Http\Controllers\Api\RegionController::class, 'show']);
    Route::get('/geo-tree/{country}', [\App\Http\Controllers\Api\GeoDivisionChildrenController::class, 'fullTree'])
        ->middleware('throttle:60,1');
    Route::get('/states', [\App\Http\Controllers\Api\GeoDivisionChildrenController::class, 'states'])
        ->middleware('throttle:120,1');
    Route::get('/districts/{parentId}', [\App\Http\Controllers\Api\GeoDivisionChildrenController::class, 'districts'])
        ->whereNumber('parentId')
        ->middleware('throttle:120,1');
    Route::get('/neighborhoods/{parentId}', [\App\Http\Controllers\Api\GeoDivisionChildrenController::class, 'neighborhoods'])
        ->whereNumber('parentId')
        ->middleware('throttle:120,1');
    Route::get('/syria-geojson/manifest', [\App\Http\Controllers\Api\SyriaGeoJsonController::class, 'manifest'])
        ->middleware('throttle:60,1');
    // GET /geo-coords?country=SY&state_code=...&city_code=...&district_code=...
    // إرجاع latitude/longitude من جدول geo_divisions حسب الأكواد المختارة (بدون اعتماد على Google/Nominatim).
    Route::get('/geo-coords', [\App\Http\Controllers\Api\GeoDivisionCoordsController::class, 'show'])
        ->middleware('throttle:120,1');
    Route::post('/regions/discover-map', [\App\Http\Controllers\Api\RegionDiscoverController::class, 'store'])
        ->middleware('throttle:60,1');
    Route::get('/reverse-geocode', [\App\Http\Controllers\Api\ReverseGeocodeController::class, 'reverse']);

    Route::get('/ads', [\App\Http\Controllers\Api\AdController::class, 'index']);
    Route::get('/ads/search', [\App\Http\Controllers\Api\AdController::class, 'search']);
    Route::get('/ads/search-categories', [\App\Http\Controllers\Api\AdController::class, 'searchCategories']); // عام: إرجاع الأقسام التي تحتوي إعلانات مطابقة للبحث
    Route::get('/ads/featured', [\App\Http\Controllers\Api\AdController::class, 'featured']);
    Route::get('/ads/filter', [\App\Http\Controllers\Api\AdController::class, 'filter']);
    Route::get('/ads/{uid}/statistics', [\App\Http\Controllers\Api\AdController::class, 'statistics']);
    Route::get('/ads/{uid}', [\App\Http\Controllers\Api\AdController::class, 'show']);
    Route::get('/sellers/{slug}', [\App\Http\Controllers\Api\SellerController::class, 'show']);

    // Packages
    Route::get('/packages', [\App\Http\Controllers\Api\PackageController::class, 'index']);

    // Help
    Route::get('/help', [\App\Http\Controllers\Api\HelpController::class, 'index']);
    Route::post('/help/contact', [\App\Http\Controllers\Api\HelpController::class, 'sendMessage']);

    // App Info (معلومات التطبيق)
    Route::get('/app-info', [\App\Http\Controllers\Api\AppInfoController::class, 'index']);

    // Legal (الشروط والخصوصية)
    Route::get('/legal/privacy', [\App\Http\Controllers\Api\LegalController::class, 'privacy']);
    Route::get('/legal/terms', [\App\Http\Controllers\Api\LegalController::class, 'terms']);

    // Authenticated Routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/me', [\App\Http\Controllers\Api\AuthController::class, 'me']);

        // User Profile
        Route::get('/user', [\App\Http\Controllers\Api\UserController::class, 'show']);
        Route::put('/user', [\App\Http\Controllers\Api\UserController::class, 'update']);
        Route::post('/user', [\App\Http\Controllers\Api\UserController::class, 'update']); // لتحديث الملف مع الصورة (PHP يستقبل الملفات فقط مع POST)
        Route::put('/user/password', [\App\Http\Controllers\Api\UserController::class, 'updatePassword']);
        Route::put('/user/email', [\App\Http\Controllers\Api\UserController::class, 'updateEmail']);
        Route::post('/user/email/send-verification-code', [\App\Http\Controllers\Api\UserController::class, 'sendEmailVerificationCode']);
        Route::post('/user/email/verify-code', [\App\Http\Controllers\Api\UserController::class, 'verifyEmailCode']);
        Route::post('/user/email/request-change', [\App\Http\Controllers\Api\UserController::class, 'requestEmailChange']);
        Route::post('/user/email/verify-change', [\App\Http\Controllers\Api\UserController::class, 'verifyEmailChangeCode']);
        Route::put('/user/phone', [\App\Http\Controllers\Api\UserController::class, 'updatePhone']);
        Route::get('/user/activities', [\App\Http\Controllers\Api\UserController::class, 'activities']);
        Route::post('/user/cancel-account', [\App\Http\Controllers\Api\UserController::class, 'cancelAccount']);
        Route::delete('/user', [\App\Http\Controllers\Api\UserController::class, 'deleteAccount']);

        // Ads (Authenticated)
        Route::post('/ads', [\App\Http\Controllers\Api\AdController::class, 'store']);
        Route::get('/ads/my/list', [\App\Http\Controllers\Api\AdController::class, 'myAds']);
        Route::get('/ads/my/{uid}', [\App\Http\Controllers\Api\AdController::class, 'myAdShow']);
        Route::match(['put', 'post'], '/ads/{uid}', [\App\Http\Controllers\Api\AdController::class, 'update']);
        Route::post('/ads/{uid}/suspend', [\App\Http\Controllers\Api\AdController::class, 'suspend']);
        Route::post('/ads/{uid}/unsuspend', [\App\Http\Controllers\Api\AdController::class, 'unsuspend']);
        Route::post('/ads/{uid}/set-featured', [\App\Http\Controllers\Api\AdController::class, 'setFeatured']);
        Route::post('/ads/{uid}/set-urgent', [\App\Http\Controllers\Api\AdController::class, 'setUrgent']);
        Route::delete('/ads/{uid}', [\App\Http\Controllers\Api\AdController::class, 'destroy']);

        // Messages
        Route::get('/messages', [\App\Http\Controllers\Api\MessageController::class, 'index']);
        Route::get('/messages/{id}', [\App\Http\Controllers\Api\MessageController::class, 'show']);
        Route::post('/messages/create/seller/{slug}', [\App\Http\Controllers\Api\MessageController::class, 'createWithSeller']);
        Route::post('/messages/create/{uid}', [\App\Http\Controllers\Api\MessageController::class, 'create']);
        Route::post('/messages/{id}', [\App\Http\Controllers\Api\MessageController::class, 'store']);
        Route::put('/messages/{id}/read', [\App\Http\Controllers\Api\MessageController::class, 'markAsRead']);

        // Favorites
        Route::get('/favorites', [\App\Http\Controllers\Api\FavoriteController::class, 'index']);
        Route::post('/favorites/{uid}/toggle', [\App\Http\Controllers\Api\FavoriteController::class, 'toggle']);
        Route::delete('/favorites/{uid}', [\App\Http\Controllers\Api\FavoriteController::class, 'destroy']);

        // Favorite Sellers
        Route::get('/favorite-sellers', [\App\Http\Controllers\Api\FavoriteSellerController::class, 'index']);
        Route::post('/favorite-sellers/{slug}/toggle', [\App\Http\Controllers\Api\FavoriteSellerController::class, 'toggle']);
        Route::delete('/favorite-sellers/{slug}', [\App\Http\Controllers\Api\FavoriteSellerController::class, 'destroy']);

        // Negotiations
        Route::get('/negotiations/create/{uid}', [\App\Http\Controllers\Api\NegotiationController::class, 'create']);
        Route::post('/negotiations/{uid}', [\App\Http\Controllers\Api\NegotiationController::class, 'store']);
        Route::get('/negotiations/sent', [\App\Http\Controllers\Api\NegotiationController::class, 'sent']);
        Route::get('/negotiations/received', [\App\Http\Controllers\Api\NegotiationController::class, 'received']);
        Route::post('/negotiations/{id}/accept', [\App\Http\Controllers\Api\NegotiationController::class, 'accept']);
        Route::post('/negotiations/{id}/reject', [\App\Http\Controllers\Api\NegotiationController::class, 'reject']);

        // Reports
        Route::get('/reports', [\App\Http\Controllers\Api\ReportController::class, 'index']);
        Route::post('/reports', [\App\Http\Controllers\Api\ReportController::class, 'store']);
        Route::get('/reports/{id}', [\App\Http\Controllers\Api\ReportController::class, 'show']);

        // Verification
        Route::get('/verification', [\App\Http\Controllers\Api\VerificationController::class, 'index']);
        Route::post('/verification', [\App\Http\Controllers\Api\VerificationController::class, 'store']);
        Route::post('/verification/revoke', [\App\Http\Controllers\Api\VerificationController::class, 'revoke']);

        // Seller Rating (تقييم التاجر)
        Route::post('/sellers/{slug}/rate', [\App\Http\Controllers\Api\SellerController::class, 'rate']);

        // Blocked Users
        Route::get('/blocked-users', [\App\Http\Controllers\Api\BlockedUserController::class, 'index']);
        Route::post('/blocked-users', [\App\Http\Controllers\Api\BlockedUserController::class, 'store']);
        Route::delete('/blocked-users/{id}', [\App\Http\Controllers\Api\BlockedUserController::class, 'destroy']);

        // Package request (no auto-subscribe; admin approves)
        Route::post('/packages/{id}/request', [\App\Http\Controllers\Api\PackageController::class, 'request']);
        Route::get('/package-requests', [\App\Http\Controllers\Api\PackageController::class, 'myRequests']);
        Route::get('/package-requests/{id}', [\App\Http\Controllers\Api\PackageController::class, 'showRequest']);

        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\Api\NotificationController::class, 'index']);
        Route::post('/notifications/read-all', [\App\Http\Controllers\Api\NotificationController::class, 'markAllAsRead']);
        Route::get('/notifications/unread-count', [\App\Http\Controllers\Api\NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read', [\App\Http\Controllers\Api\NotificationController::class, 'markAsRead']);

        // Saved searches
        Route::get('/saved-searches', [\App\Http\Controllers\Api\SavedSearchController::class, 'index']);
        Route::post('/saved-searches', [\App\Http\Controllers\Api\SavedSearchController::class, 'store']);
        Route::delete('/saved-searches/{id}', [\App\Http\Controllers\Api\SavedSearchController::class, 'destroy']);
        Route::get('/saved-searches/{id}/results', [\App\Http\Controllers\Api\SavedSearchController::class, 'results']);

        // FCM Tokens
        Route::post('/fcm-token', [\App\Http\Controllers\Api\FcmTokenController::class, 'store']);
        Route::delete('/fcm-token', [\App\Http\Controllers\Api\FcmTokenController::class, 'destroy']);
        Route::delete('/fcm-token/{id}', [\App\Http\Controllers\Api\FcmTokenController::class, 'destroy']);

        // Wallet & Hawala
        Route::get('/wallet', [\App\Http\Controllers\Api\WalletController::class, 'index']);
        Route::get('/wallet/transactions', [\App\Http\Controllers\Api\WalletController::class, 'transactions']);
        Route::post('/hawala-transfers', [\App\Http\Controllers\Api\HawalaTransferController::class, 'store']);
    });
});
