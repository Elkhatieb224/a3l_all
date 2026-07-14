<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class CheckUserFcmTokens extends Command
{
    protected $signature = 'fcm:check-user {email : البريد الإلكتروني (مثل a2@a2.com)}';

    protected $description = 'التحقق من وجود FCM tokens لمستخدم (مفيد للاختبار على a2@a2.com)';

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("المستخدم غير موجود: {$email}");
            return self::FAILURE;
        }

        $tokens = $user->fcmTokens()->get();
        $this->info("المستخدم: {$user->email} (ID: {$user->id})");
        $this->info("عدد FCM tokens: " . $tokens->count());

        if ($tokens->isEmpty()) {
            $this->warn("لا يوجد توكن مسجّل → Push لن يصل حتى يسجّل التطبيق التوكن عبر POST /api/v1/fcm-token أو استخدم: php artisan fcm:register-token {$email} \"FCM_TOKEN\"");
            return self::SUCCESS;
        }

        foreach ($tokens as $t) {
            $this->line("  - id: {$t->id}, device: " . ($t->device_type ?? 'n/a') . ", token: " . substr($t->token, 0, 40) . "...");
        }

        return self::SUCCESS;
    }
}
