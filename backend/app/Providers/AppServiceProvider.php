<?php

namespace App\Providers;

use App\Channels\FcmChannel;
use App\Mail\MailManager;
use App\Models\UserActivityLog;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->extend('mail.manager', function ($manager) {
            return new MailManager($this->app);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Ensure full URLs for assets (fixes storage image links when behind proxy or ASSET_URL not set)
        $appUrl = config('app.url');
        if ($appUrl && !str_starts_with($appUrl, 'http://localhost')) {
            \Illuminate\Support\Facades\URL::forceRootUrl($appUrl);
            if (str_starts_with($appUrl, 'https://')) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }
        }

        Notification::extend('fcm', function ($app) {
            return new FcmChannel();
        });

        // Share recent activities with sidebar
        View::composer('frontend.profile.partials.sidebar', function ($view) {
            $recentActivities = collect();
            
            if (Auth::check()) {
                $recentActivities = UserActivityLog::where('user_id', Auth::id())
                    ->latest()
                    ->take(5)
                    ->get();
            }
            
            $view->with('recentActivities', $recentActivities);
        });
    }
}
