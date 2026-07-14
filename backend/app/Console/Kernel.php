<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Process account deletions daily
        $schedule->command('accounts:process-deletions')->daily();

        $schedule->command('images:convert-to-webp')
            ->dailyAt('03:15')
            ->withoutOverlapping(240);

        // تعليق الإعلانات الزائدة عند انتهاء الاشتراكات والعودة للمجانية
        $schedule->command('subscriptions:enforce-expired-ads-limit')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
