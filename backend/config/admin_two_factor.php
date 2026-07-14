<?php

return [

    'challenge_ttl_minutes' => max(5, (int) env('ADMIN_2FA_CODE_TTL_MINUTES', 10)),

    /** بعد نجاح رمز التحقق: مدة الثقة لنفس IP (بدون طلب رمز عند تسجيل الدخول التالي) */
    'trust_ip_ttl_minutes' => max(1, (int) env('ADMIN_2FA_TRUST_IP_MINUTES', 60)),

];
