<?php

namespace App\Console\Commands;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Console\Command;

class RegisterFcmToken extends Command
{
    protected $signature = 'fcm:register-token
                            {email : البريد الإلكتروني للمستخدم (مثل a2@a2.com)}
                            {token : FCM token من التطبيق}
                            {--device=android : android أو ios أو web}';

    protected $description = 'تسجيل FCM token لمستخدم (للاختبار). المستخدم يجب أن يسجّل التوكن من التطبيق عادةً عبر POST /api/v1/fcm-token.';

    public function handle(): int
    {
        $email = $this->argument('email');
        $token = $this->argument('token');
        $device = $this->option('device');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("المستخدم غير موجود: {$email}");
            return self::FAILURE;
        }

        $tokenHash = hash('sha256', $token);
        $fcmToken = FcmToken::updateOrCreate(

            [
                'user_id' => $user->id,
                'token_hash' => $tokenHash,
            ],
            [
                'token' => $token,
                'device_type' => $device,
                'last_used_at' => now(),
            ]
        );
        $this->info("تم تسجيل/تحديث التوكن للمستخدم {$email} (user_id: {$user->id}). أرسل إشعاراً من لوحة الإدارة (مستخدمون محددون: {$email}) لاختبار Push.");

        return self::SUCCESS;
    }
}
