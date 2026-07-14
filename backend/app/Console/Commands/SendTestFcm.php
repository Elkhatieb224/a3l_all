<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FirebaseService;
use Illuminate\Console\Command;

class SendTestFcm extends Command
{
    protected $signature = 'fcm:test-send 
                            {email : البريد الإلكتروني (مثل a@x.com)} 
                            {--title=اختبار : عنوان الإشعار} 
                            {--body=رسالة اختبار : نص الإشعار}';

    protected $description = 'إرسال إشعار Push تجريبي لمستخدم (لمعرفة إن كان FCM يعمل أو رسالة الخطأ).';

    public function handle(): int
    {
        $email = $this->argument('email');
        $title = $this->option('title');
        $body = $this->option('body');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("المستخدم غير موجود: {$email}");
            return self::FAILURE;
        }

        $tokens = $user->fcmTokens()->pluck('token')->toArray();
        if (empty($tokens)) {
            $this->error("لا يوجد FCM token لهذا المستخدم. سجّل التوكن من التطبيق (تسجيل دخول مع fcm_token) أو استخدم: php artisan fcm:register-token {$email} \"TOKEN\"");
            return self::FAILURE;
        }

        $this->info("إرسال إشعار Push إلى {$email} (عدد الأجهزة: " . count($tokens) . ")...");

        try {
            $firebase = app(FirebaseService::class);
            $data = ['type' => 'admin_notification', 'title' => $title, 'message' => $body];
            $result = $firebase->sendToMultipleDevices($tokens, $title, $body, $data);

            if ($result['success'] > 0) {
                $this->info("تم قبول الإرسال من FCM: نجح {$result['success']}, فشل {$result['failed']}. إذا لم يظهر على الجهاز تحقق من: قناة الإشعارات في التطبيق (channel_id)، وإعدادات الجهاز، وأن التطبيق من نفس مشروع Firebase (aalenha-91516).");
            }
            if ($result['failed'] > 0) {
                $this->warn("فشل الإرسال لـ {$result['failed']} جهاز. راجع storage/logs/laravel.log لتفاصيل الخطأ (توكن منتهي أو مشروع مختلف).");
            }
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("خطأ: " . $e->getMessage());
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
