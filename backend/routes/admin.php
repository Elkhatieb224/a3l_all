<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\AdController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\FaqController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

// Guest routes
Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AuthController::class, 'login'])->name('admin.login.submit');

    Route::middleware(['admin.2fa.pending'])->group(function () {
        Route::get('/two-factor/challenge', [\App\Http\Controllers\Admin\AdminTwoFactorController::class, 'showChallenge'])->name('admin.two-factor.show');
        Route::post('/two-factor/challenge', [\App\Http\Controllers\Admin\AdminTwoFactorController::class, 'verify'])
            ->middleware('throttle:15,1')
            ->name('admin.two-factor.verify');
        Route::post('/two-factor/resend', [\App\Http\Controllers\Admin\AdminTwoFactorController::class, 'resend'])
            ->middleware('throttle:3,60')
            ->name('admin.two-factor.resend');
    });
});

// Language Switcher (needs to be accessible before auth)
Route::get('/language/{locale}', [\App\Http\Controllers\Admin\LanguageController::class, 'switch'])->name('admin.language.switch');

// Authenticated admin routes
Route::middleware(['admin.auth'])->group(function () {

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard.index');

    // Users Management (Admin only)
    Route::prefix('users')->name('admin.users.')->middleware('admin.only')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/deleted', [UserController::class, 'deletedAccounts'])->name('deleted');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');

        // Verification Requests (must be before /{id} routes)
        Route::get('/verification-requests', [UserController::class, 'verificationRequests'])->name('verification-requests');
        Route::get('/verification-requests/{id}', [UserController::class, 'showVerificationRequest'])->name('verification-requests.show');
        Route::post('/verification-requests/{id}/approve', [UserController::class, 'approveVerification'])->name('verification-requests.approve');
        Route::post('/verification-requests/{id}/reject', [UserController::class, 'rejectVerification'])->name('verification-requests.reject');

        Route::get('/export', [UserController::class, 'export'])->name('export');
        Route::get('/{id}', [UserController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{id}/add-balance', [UserController::class, 'addBalance'])->name('add-balance');
        Route::post('/{id}/activate-package', [UserController::class, 'activatePackage'])->name('activate-package');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Categories Management
    Route::prefix('categories')->name('admin.categories.')->group(function () {
        // All roles can view categories
        Route::get('/', [CategoryController::class, 'index'])->name('index');

        // Super Admin only - create / delete / status / custom fields (must be before /{id} route)
        Route::middleware('super.admin')->group(function () {
            Route::get('/create', [CategoryController::class, 'create'])->name('create');
            Route::post('/', [CategoryController::class, 'store'])->name('store');
            Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('toggle-status');

            // Custom Fields Management (Super Admin only)
            Route::post('/{id}/custom-fields', [CategoryController::class, 'storeCustomField'])->name('custom-fields.store');
            Route::put('/{id}/custom-fields/{fieldIndex}', [CategoryController::class, 'updateCustomField'])->name('custom-fields.update');
            Route::delete('/{id}/custom-fields/{fieldIndex}', [CategoryController::class, 'deleteCustomField'])->name('custom-fields.delete');
            Route::post('/{id}/custom-fields/{fieldIndex}/toggle-status', [CategoryController::class, 'toggleCustomFieldStatus'])->name('custom-fields.toggle-status');
            Route::post('/{id}/custom-fields/import-json', [CategoryController::class, 'importCustomFieldsFromJson'])->name('custom-fields.import-json');
        });

        // Admin + Super Admin: تعديل القسم بما فيها طريقة صور الإعلان (رفع / معرض)
        Route::middleware('admin.only')->group(function () {
            Route::get('/{id}/edit', [CategoryController::class, 'edit'])->name('edit');
            Route::put('/{id}', [CategoryController::class, 'update'])->name('update');
        });

        // All roles can view category details (must be after /create route)
        Route::get('/{id}', [CategoryController::class, 'show'])->name('show');

        // Admin only - Subcategories Management (can add/edit subcategories only)
        Route::middleware('admin.only')->group(function () {
            Route::get('/{categoryId}/subcategories/create', [\App\Http\Controllers\Admin\SubcategoryController::class, 'create'])->name('subcategories.create');
            Route::post('/{categoryId}/subcategories/import-json', [\App\Http\Controllers\Admin\SubcategoryController::class, 'importFromJson'])->name('subcategories.import-json');
            Route::post('/{categoryId}/subcategories', [\App\Http\Controllers\Admin\SubcategoryController::class, 'store'])->name('subcategories.store');
        });
    });

    // Subcategories Management (Admin only)
    Route::prefix('subcategories')->name('admin.subcategories.')->middleware('admin.only')->group(function () {
        Route::middleware('super.admin')->group(function () {
            Route::post('/{id}/custom-fields', [\App\Http\Controllers\Admin\SubcategoryController::class, 'storeCustomField'])->name('custom-fields.store');
            Route::put('/{id}/custom-fields/{fieldIndex}', [\App\Http\Controllers\Admin\SubcategoryController::class, 'updateCustomField'])->name('custom-fields.update');
            Route::delete('/{id}/custom-fields/{fieldIndex}', [\App\Http\Controllers\Admin\SubcategoryController::class, 'deleteCustomField'])->name('custom-fields.delete');
            Route::post('/{id}/custom-fields/{fieldIndex}/toggle-status', [\App\Http\Controllers\Admin\SubcategoryController::class, 'toggleCustomFieldStatus'])->name('custom-fields.toggle-status');
            Route::post('/{id}/custom-fields/import-json', [\App\Http\Controllers\Admin\SubcategoryController::class, 'importCustomFieldsFromJson'])->name('custom-fields.import-json');
        });
        Route::post('/{id}/import-json', [\App\Http\Controllers\Admin\SubcategoryController::class, 'importChildrenFromJson'])->name('import-json');
        Route::get('/{id}', [\App\Http\Controllers\Admin\SubcategoryController::class, 'show'])->name('show');
        Route::get('/{id}/children', [\App\Http\Controllers\Admin\SubcategoryController::class, 'getChildren'])->name('children');
        Route::get('/{id}/edit', [\App\Http\Controllers\Admin\SubcategoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\App\Http\Controllers\Admin\SubcategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\SubcategoryController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/restore', [\App\Http\Controllers\Admin\SubcategoryController::class, 'restore'])->name('restore');
        Route::delete('/{id}/force-delete', [\App\Http\Controllers\Admin\SubcategoryController::class, 'forceDelete'])->name('force-delete');
    });

    // Ads Management
    Route::prefix('ads')->name('admin.ads.')->group(function () {
        // All roles can view and review ads
        Route::get('/', [AdController::class, 'index'])->name('index');

        // Admin only routes (must be before /{uid} route)
        Route::middleware('admin.only')->group(function () {
            Route::get('/deleted-account-ads', [AdController::class, 'deletedAccountAds'])->name('deleted-account-ads');
            Route::get('/create', [AdController::class, 'create'])->name('create');
            Route::post('/', [AdController::class, 'store'])->name('store');
            Route::post('/{uid}/toggle-featured', [AdController::class, 'toggleFeatured'])->name('toggle-featured');
            Route::delete('/{uid}', [AdController::class, 'destroy'])->name('destroy');
        });

        // These routes must come after specific routes like /create
        Route::get('/{uid}', [AdController::class, 'show'])->name('show');
        Route::post('/{uid}/approve', [AdController::class, 'approve'])->name('approve');
        Route::post('/{uid}/reject', [AdController::class, 'reject'])->name('reject');
    });

    // Notifications (Admin only)
    Route::prefix('notifications')->name('admin.notifications.')->middleware('admin.only')->group(function () {
        Route::get('/send', [\App\Http\Controllers\Admin\UserNotificationController::class, 'create'])->name('create');
        Route::post('/send', [\App\Http\Controllers\Admin\UserNotificationController::class, 'store'])->name('store');
    });

    // Package Requests (Admin only) - طلبات الباقات
    Route::prefix('package-requests')->name('admin.package-requests.')->middleware('admin.only')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\PackageRequestController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\Admin\PackageRequestController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [\App\Http\Controllers\Admin\PackageRequestController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [\App\Http\Controllers\Admin\PackageRequestController::class, 'reject'])->name('reject');
    });

    // Packages Management (Admin only)
    Route::prefix('packages')->name('admin.packages.')->middleware('admin.only')->group(function () {
        Route::get('/', [PackageController::class, 'index'])->name('index');
        Route::get('/create', [PackageController::class, 'create'])->name('create');
        Route::post('/', [PackageController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [PackageController::class, 'edit'])->name('edit');
        Route::put('/{id}', [PackageController::class, 'update'])->name('update');
        Route::delete('/{id}', [PackageController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [PackageController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Payments Management (Admin only)
    Route::prefix('payments')->name('admin.payments.')->middleware('admin.only')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('/{id}', [PaymentController::class, 'show'])->name('show');
    });

    // Hawala Transfer Requests (Admin only)
    Route::prefix('hawala-transfers')->name('admin.hawala-transfers.')->middleware('admin.only')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\HawalaTransferController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\Admin\HawalaTransferController::class, 'show'])->name('show');
        Route::post('/{id}/approve', [\App\Http\Controllers\Admin\HawalaTransferController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [\App\Http\Controllers\Admin\HawalaTransferController::class, 'reject'])->name('reject');
        Route::post('/{id}/activate-package', [\App\Http\Controllers\Admin\HawalaTransferController::class, 'activatePackage'])->name('activate-package');
    });

    // Reports Management
    Route::prefix('reports')->name('admin.reports.')->group(function () {
        // All roles can view reports
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/{id}', [ReportController::class, 'show'])->name('show');

        // Admins & Support Agents can process reports
        Route::middleware('support.agent')->group(function () {
            Route::put('/{id}', [ReportController::class, 'update'])->name('update');
        });
    });

    // Block Users (from reports) - Admin only
    Route::post('/users/{id}/block', [UserController::class, 'blockUser'])->middleware('admin.only')->name('admin.users.block');

    // Support Messages (Admin & Support Agent)
    Route::prefix('support')->name('admin.support.')->middleware('support.agent')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SupportMessageController::class, 'index'])->name('index');
        Route::get('/{id}', [\App\Http\Controllers\Admin\SupportMessageController::class, 'show'])->name('show');
        Route::post('/{id}/respond', [\App\Http\Controllers\Admin\SupportMessageController::class, 'respond'])->name('respond');
        Route::put('/{id}/status', [\App\Http\Controllers\Admin\SupportMessageController::class, 'updateStatus'])->name('update-status');
    });

    // Settings (Admin only)
    Route::prefix('settings')->name('admin.settings.')->middleware('admin.only')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::put('/', [SettingController::class, 'update'])->name('update');
        Route::post('/country-codes', [SettingController::class, 'addCountryCode'])->name('country-codes.add');
        Route::delete('/country-codes/{code}', [SettingController::class, 'deleteCountryCode'])->name('country-codes.delete');
    });

    // Dynamic map regions (Admin only)
    Route::prefix('dynamic-regions')->name('admin.dynamic-regions.')->middleware('admin.only')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\DynamicRegionController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\DynamicRegionController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\DynamicRegionController::class, 'store'])->name('store');
        Route::delete('/{id}', [\App\Http\Controllers\Admin\DynamicRegionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('geo-divisions')->name('admin.geo-divisions.')->middleware('admin.only')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\GeoDivisionController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\GeoDivisionController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\Admin\GeoDivisionController::class, 'store'])->name('store');
        Route::get('/{geo_division}/edit', [\App\Http\Controllers\Admin\GeoDivisionController::class, 'edit'])->name('edit');
        Route::put('/{geo_division}', [\App\Http\Controllers\Admin\GeoDivisionController::class, 'update'])->name('update');
        Route::delete('/{geo_division}', [\App\Http\Controllers\Admin\GeoDivisionController::class, 'destroy'])->name('destroy');
    });

    // Translations Management (Admin only)
    Route::prefix('translations')->name('admin.translations.')->middleware('admin.only')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\TranslationController::class, 'index'])->name('index');
        Route::get('/{locale}/{file}', [\App\Http\Controllers\Admin\TranslationController::class, 'show'])->name('show');
        Route::put('/{locale}/{file}', [\App\Http\Controllers\Admin\TranslationController::class, 'update'])->name('update');
    });

    // Admins Management (Super Admin only)
    Route::prefix('admins')->name('admin.admins.')->middleware('super.admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::get('/create', [AdminController::class, 'create'])->name('create');
        Route::post('/', [AdminController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [AdminController::class, 'edit'])->name('edit');
        Route::put('/{id}', [AdminController::class, 'update'])->name('update');
        Route::delete('/{id}', [AdminController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [AdminController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Activity Logs
    Route::prefix('logs')->name('admin.logs.')->middleware('super.admin')->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
    });

    Route::prefix('login-ip-blocks')->name('admin.login-ip-blocks.')->middleware('super.admin')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\LoginIpBlockController::class, 'index'])->name('index');
        Route::get('/{login_ip_block}', [\App\Http\Controllers\Admin\LoginIpBlockController::class, 'show'])->name('show');
        Route::post('/{login_ip_block}/unblock', [\App\Http\Controllers\Admin\LoginIpBlockController::class, 'unblock'])->name('unblock');
        Route::post('/{login_ip_block}/make-permanent', [\App\Http\Controllers\Admin\LoginIpBlockController::class, 'makePermanent'])->name('make-permanent');
        Route::put('/{login_ip_block}/notes', [\App\Http\Controllers\Admin\LoginIpBlockController::class, 'updateNotes'])->name('notes');
    });

    // Profile
    Route::prefix('profile')->name('admin.profile.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ProfileController::class, 'index'])->name('index');
        Route::put('/', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('update');
        Route::get('/change-password', [\App\Http\Controllers\Admin\ProfileController::class, 'showChangePasswordForm'])->name('change-password');
        Route::post('/change-password', [\App\Http\Controllers\Admin\ProfileController::class, 'changePassword'])->name('change-password.submit');

        Route::post('/two-factor/start', [\App\Http\Controllers\Admin\ProfileController::class, 'twoFactorStart'])
            ->middleware('throttle:8,1')
            ->name('two-factor.start');
        Route::post('/two-factor/confirm', [\App\Http\Controllers\Admin\ProfileController::class, 'twoFactorConfirm'])
            ->middleware('throttle:15,1')
            ->name('two-factor.confirm');
        Route::post('/two-factor/disable', [\App\Http\Controllers\Admin\ProfileController::class, 'twoFactorDisable'])
            ->middleware('throttle:10,1')
            ->name('two-factor.disable');
    });

    // FAQs Management (Admin and Support Agent)
    Route::prefix('faqs')->name('admin.faqs.')->middleware(['admin.auth', 'support.agent'])->group(function () {
        Route::get('/', [FaqController::class, 'index'])->name('index');
        Route::get('/create', [FaqController::class, 'create'])->name('create');
        Route::post('/', [FaqController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [FaqController::class, 'edit'])->name('edit');
        Route::put('/{id}', [FaqController::class, 'update'])->name('update');
        Route::delete('/{id}', [FaqController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-status', [FaqController::class, 'toggleStatus'])->name('toggle-status');
    });

    // Reporting & Analytics (Admin only)
    Route::prefix('reporting')->name('admin.reporting.')->middleware('admin.only')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ReportingController::class, 'index'])->name('index');
        Route::get('/users', [\App\Http\Controllers\Admin\ReportingController::class, 'usersReport'])->name('users');
        Route::get('/users/export', [\App\Http\Controllers\Admin\ReportingController::class, 'exportUsers'])->name('users.export');
        Route::get('/ads', [\App\Http\Controllers\Admin\ReportingController::class, 'adsReport'])->name('ads');
        Route::get('/ads/export', [\App\Http\Controllers\Admin\ReportingController::class, 'exportAds'])->name('ads.export');
        Route::get('/financial', [\App\Http\Controllers\Admin\ReportingController::class, 'financialReport'])->name('financial');
        Route::get('/financial/export', [\App\Http\Controllers\Admin\ReportingController::class, 'exportFinancial'])->name('financial.export');
        Route::get('/reports', [\App\Http\Controllers\Admin\ReportingController::class, 'reportsReport'])->name('reports');
        Route::get('/reports/export', [\App\Http\Controllers\Admin\ReportingController::class, 'exportReports'])->name('reports.export');
        Route::get('/activity', [\App\Http\Controllers\Admin\ReportingController::class, 'activityReport'])->name('activity');
        Route::get('/activity/export', [\App\Http\Controllers\Admin\ReportingController::class, 'exportActivity'])->name('activity.export');
        Route::get('/packages', [\App\Http\Controllers\Admin\ReportingController::class, 'packagesReport'])->name('packages');
        Route::get('/packages/export', [\App\Http\Controllers\Admin\ReportingController::class, 'exportPackages'])->name('packages.export');
    });
});

