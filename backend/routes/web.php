<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\CategoryController;
use App\Http\Controllers\Frontend\AdController;
use App\Http\Controllers\Frontend\LanguageController;
use App\Http\Controllers\Frontend\AuthController;
use App\Http\Controllers\Frontend\ProfileController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Serve storage files (when Apache returns 403 for symlinks or direct access fails)
Route::get('/storage/{path}', function (string $path) {
    $path = str_replace(['../', '..\\'], '', $path);
    if (!Storage::disk('public')->exists($path)) {
        abort(404);
    }
    return response()->file(Storage::disk('public')->path($path));
})->where('path', '.*')->name('storage.serve');

// Language Switcher
Route::get('/language/{locale}', [LanguageController::class, 'switch'])->name('language.switch');

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    // Forgot Password
    Route::get('/password/forgot', [AuthController::class, 'showForgotPasswordForm'])->name('password.forgot');
    Route::post('/password/forgot', [AuthController::class, 'sendPasswordResetCode'])->name('password.send-code');
    Route::get('/password/verify-code', [AuthController::class, 'showVerifyCodeForm'])->name('password.verify-code');
    Route::post('/password/verify-code', [AuthController::class, 'verifyPasswordResetCode'])->name('password.verify-code.submit');
    Route::get('/password/reset', [AuthController::class, 'showResetPasswordForm'])->name('password.reset-form');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.reset');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Home Page
Route::get('/', [HomeController::class, 'index'])->name('home');

// App Information
Route::get('/app-info', [\App\Http\Controllers\Frontend\AppInfoController::class, 'index'])->name('app-info');

// Legal Pages
Route::get('/terms', [\App\Http\Controllers\Frontend\LegalController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [\App\Http\Controllers\Frontend\LegalController::class, 'privacy'])->name('legal.privacy');

// Help & Support
Route::get('/help', [\App\Http\Controllers\Frontend\HelpController::class, 'index'])->name('help.index');
Route::get('/help/contact', [\App\Http\Controllers\Frontend\HelpController::class, 'contact'])->name('help.contact');
Route::post('/help/send-message', [\App\Http\Controllers\Frontend\HelpController::class, 'sendMessage'])->name('help.send-message');

// Categories
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/categories/{categorySlug}/{subcategorySlug}', [CategoryController::class, 'showSubcategory'])->name('categories.subcategory');

// Packages
Route::get('/packages', [\App\Http\Controllers\Frontend\PackageController::class, 'index'])->name('packages.index');
Route::middleware('auth')->post('/packages/{id}/request', [\App\Http\Controllers\Frontend\PackageController::class, 'requestPackage'])->name('packages.request');

// Ads
Route::get('/ads', [AdController::class, 'index'])->name('ads.index');
Route::get('/ads/search-categories-json', [AdController::class, 'searchCategoriesJson'])->name('ads.search-categories-json');

// Create Ad - Multi-step (must be before /ads/{uid} route)
Route::middleware('auth')->group(function () {
    Route::get('/ads/create', [AdController::class, 'create'])->name('ads.create');
    Route::post('/ads/create/category', [AdController::class, 'selectCategory'])->name('ads.create.category');
    Route::get('/ads/create/subcategory', [AdController::class, 'selectSubcategory'])->name('ads.create.subcategory');
    Route::post('/ads/create/subcategory', [AdController::class, 'processSubcategory'])->name('ads.create.subcategory.process');
    Route::get('/ads/create/details', [AdController::class, 'createDetails'])->name('ads.create.details');
    Route::post('/ads', [AdController::class, 'store'])->name('ads.store');
});

// Show Ad (must be after /ads/create routes)
Route::get('/ads/{uid}', [AdController::class, 'show'])->name('ads.show');

// Seller Profile (Public)
Route::get('/seller/{slug}', [ProfileController::class, 'showSeller'])->name('seller.show');
Route::middleware('auth')->post('/seller/{slug}/rate', [ProfileController::class, 'storeRating'])->name('seller.rate');

// Messages (public route to create conversation)
Route::middleware('auth')->post('/messages/create/{uid}', [\App\Http\Controllers\Frontend\MessageController::class, 'create'])->name('messages.create');
Route::middleware('auth')->post('/messages/create/seller/{slug}', [\App\Http\Controllers\Frontend\MessageController::class, 'createWithSeller'])->name('messages.create.seller');

// Messages & Favorites (Authenticated - outside profile prefix)
Route::middleware('auth')->group(function () {
    Route::get('/messages', [\App\Http\Controllers\Frontend\MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{id}', [\App\Http\Controllers\Frontend\MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{id}', [\App\Http\Controllers\Frontend\MessageController::class, 'store'])->name('messages.store');

    Route::get('/favorites', [\App\Http\Controllers\Frontend\FavoriteController::class, 'index'])->name('favorites.index');
    Route::post('/favorites/{uid}/toggle', [\App\Http\Controllers\Frontend\FavoriteController::class, 'toggle'])->name('favorites.toggle');
    Route::delete('/favorites/{uid}', [\App\Http\Controllers\Frontend\FavoriteController::class, 'destroy'])->name('favorites.destroy');

    // Negotiations
    Route::get('/negotiations/create/{uid}', [\App\Http\Controllers\Frontend\NegotiationController::class, 'create'])->name('negotiations.create');
    Route::post('/negotiations/{uid}', [\App\Http\Controllers\Frontend\NegotiationController::class, 'store'])->name('negotiations.store');
    Route::get('/negotiations/sent', [\App\Http\Controllers\Frontend\NegotiationController::class, 'sent'])->name('negotiations.sent');
    Route::get('/negotiations/received', [\App\Http\Controllers\Frontend\NegotiationController::class, 'received'])->name('negotiations.received');
    Route::post('/negotiations/{id}/accept', [\App\Http\Controllers\Frontend\NegotiationController::class, 'accept'])->name('negotiations.accept');
    Route::post('/negotiations/{id}/reject', [\App\Http\Controllers\Frontend\NegotiationController::class, 'reject'])->name('negotiations.reject');
});

// Profile (Authenticated)
Route::middleware('auth')->prefix('profile')->name('profile.')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])->name('index');
    Route::get('/personal-info', [ProfileController::class, 'personalInfo'])->name('personal-info');
    Route::put('/personal-info', [ProfileController::class, 'updatePersonalInfo'])->name('personal-info.update');
    Route::get('/email', [ProfileController::class, 'email'])->name('email');
    Route::put('/email', [ProfileController::class, 'updateEmail'])->name('email.update');
    Route::post('/email/send-verification-code', [ProfileController::class, 'sendEmailVerificationCode'])->name('email.send-verification-code');
    Route::post('/email/verify-code', [ProfileController::class, 'verifyEmailCode'])->name('email.verify-code');
    Route::get('/phone', [ProfileController::class, 'phone'])->name('phone');
    Route::put('/phone', [ProfileController::class, 'updatePhone'])->name('phone.update');
    Route::get('/password', [ProfileController::class, 'password'])->name('password');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
    Route::get('/verification', [ProfileController::class, 'accountVerification'])->name('verification');
    Route::post('/verification', [ProfileController::class, 'submitVerificationRequest'])->name('verification.submit');
    Route::post('/verification/revoke', [ProfileController::class, 'revokeVerification'])->name('verification.revoke');
    Route::get('/business-profile', [ProfileController::class, 'businessProfile'])->name('business-profile');
    Route::put('/business-profile', [ProfileController::class, 'updateBusinessProfile'])->name('business-profile.update');
    Route::get('/security', [ProfileController::class, 'security'])->name('security');
    Route::get('/saved-cards', [ProfileController::class, 'savedCards'])->name('saved-cards');
    Route::get('/activities', [ProfileController::class, 'activities'])->name('activities');
    Route::get('/blocked-users', [ProfileController::class, 'blockedUsers'])->name('blocked-users');
    Route::post('/blocked-users/{id}', [ProfileController::class, 'blockUser'])->name('blocked-users.block');
    Route::delete('/blocked-users/{id}', [ProfileController::class, 'unblockUser'])->name('blocked-users.unblock');

    // Cancel Account
    Route::get('/cancel-account', [ProfileController::class, 'cancelAccount'])->name('cancel-account');
    Route::post('/cancel-account', [ProfileController::class, 'submitCancelAccount'])->name('cancel-account.submit');

    // User Ads
    Route::get('/ads', [ProfileController::class, 'adsIndex'])->name('ads.index');
    Route::get('/ads/{uid}', [ProfileController::class, 'adsShow'])->name('ads.show');
    Route::get('/ads/{uid}/edit', [ProfileController::class, 'adsEdit'])->name('ads.edit');

    // My Ratings
    Route::get('/ratings', [ProfileController::class, 'ratings'])->name('ratings');
    Route::put('/ads/{uid}', [ProfileController::class, 'adsUpdate'])->name('ads.update');
    Route::post('/ads/{uid}/suspend', [ProfileController::class, 'adsSuspend'])->name('ads.suspend');
    Route::post('/ads/{uid}/unsuspend', [ProfileController::class, 'adsUnsuspend'])->name('ads.unsuspend');
    Route::post('/ads/{uid}/set-featured', [ProfileController::class, 'adsSetFeatured'])->name('ads.set-featured');
    Route::post('/ads/{uid}/set-urgent', [ProfileController::class, 'adsSetUrgent'])->name('ads.set-urgent');
    Route::delete('/ads/{uid}', [ProfileController::class, 'adsDestroy'])->name('ads.destroy');
    Route::get('/ads/{uid}/stats', [ProfileController::class, 'adsStats'])->name('ads.stats');

    // Reports
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Frontend\ReportController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Frontend\ReportController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Frontend\ReportController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\Frontend\ReportController::class, 'show'])->name('show');
    });

    // Saved searches
    Route::get('/saved-searches', [\App\Http\Controllers\Frontend\SavedSearchController::class, 'index'])->name('saved-searches.index');
    Route::post('/saved-searches', [\App\Http\Controllers\Frontend\SavedSearchController::class, 'store'])->name('saved-searches.store');
    Route::delete('/saved-searches/{id}', [\App\Http\Controllers\Frontend\SavedSearchController::class, 'destroy'])->name('saved-searches.destroy');
    Route::get('/saved-searches/{id}', [\App\Http\Controllers\Frontend\SavedSearchController::class, 'show'])->name('saved-searches.show');

    // Support Messages
    Route::get('/support-messages', [\App\Http\Controllers\Frontend\HelpController::class, 'myMessages'])->name('support-messages.index');
    Route::get('/support-messages/{id}', [\App\Http\Controllers\Frontend\HelpController::class, 'showMessage'])->name('support-messages.show');
    Route::post('/support-messages/{id}/reply', [\App\Http\Controllers\Frontend\HelpController::class, 'reply'])->name('support-messages.reply');

    // Package Requests (طلبات الباقات — عرض الطلبات ورد الإدارة)
    Route::get('/package-requests', [\App\Http\Controllers\Frontend\PackageRequestController::class, 'index'])->name('package-requests.index');
    Route::get('/package-requests/{id}', [\App\Http\Controllers\Frontend\PackageRequestController::class, 'show'])->name('package-requests.show');

    // Wallet & Hawala (تقديم الحوالات في الموقع)
    Route::get('/hawala', [\App\Http\Controllers\Frontend\HawalaController::class, 'index'])->name('hawala.index');
    Route::get('/hawala/create', [\App\Http\Controllers\Frontend\HawalaController::class, 'create'])->name('hawala.create');
    Route::post('/hawala', [\App\Http\Controllers\Frontend\HawalaController::class, 'store'])->name('hawala.store');

    // Notifications
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Frontend\NotificationController::class, 'index'])->name('index');
        Route::post('/{id}/read', [\App\Http\Controllers\Frontend\NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [\App\Http\Controllers\Frontend\NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::get('/unread-count', [\App\Http\Controllers\Frontend\NotificationController::class, 'unreadCount'])->name('unread-count');
        Route::get('/latest', [\App\Http\Controllers\Frontend\NotificationController::class, 'latest'])->name('latest');
    });

});

// Fallback 404 (keeps locale & layout)
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(base_path('routes/admin.php'));
