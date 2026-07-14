# إعداد الإشعارات الفورية (FCM) في التطبيق

## الخطوات المطلوبة في Flutter

### 1. تشغيل FlutterFire Configure

بعد تسجيل الدخول بـ `firebase login`، نفّذ:

```bash
dart pub global run flutterfire_cli:flutterfire configure --project=aalenha-91516
```

هذا الأمر يولد ملف `lib/firebase_options.dart` تلقائياً ويضبط الإعدادات.

### 2. إضافة تطبيق Android في Firebase

1. افتح [Firebase Console](https://console.firebase.google.com) → المشروع aalenha-91516
2. Project settings → Your apps → Add app → Android
3. أدخل Package name: `com.example.aalenha`
4. حمّل `google-services.json` وضعه في:
   ```
   flutter/android/app/google-services.json
   ```

### 3. تشغيل migration في الباكند

```bash
cd backend
php artisan migrate
```

---

## ما تم إنجازه

### Flutter
- إضافة `firebase_core`, `firebase_messaging`, `flutter_local_notifications`
- تهيئة FCM في `main.dart`
- طلب إذن الإشعارات وعرضها عند الوصول للتطبيق
- إرسال توكن FCM للباكند عند تسجيل الدخول/التسجيل

### Backend
- عمود `fcm_token` في جدول `users`
- API: `POST /api/v1/user/fcm-token` مع `{ "fcm_token": "..." }`

---

## إعداد الباكند للإشعارات الفورية (تم تنفيذه)

1. نسخ ملف `Firebase_key/Firebase_key.json` إلى:
   ```
   backend/storage/app/firebase-credentials.json
   ```
2. إضافة في `.env` (اختياري إذا كان الملف في المسار أعلاه):
   ```
   FIREBASE_CREDENTIALS=/path/complete/to/backend/storage/app/firebase-credentials.json
   ```
3. تشغيل migration إن لم يُنفَّذ: `php artisan migrate`
4. الآن الإشعارات تُرسل تلقائياً كـ Push عند إنشائها (من لوحة التحكم أو عند الموافقة على إعلان)
