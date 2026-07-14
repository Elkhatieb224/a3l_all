# تحليل شامل لربط Flutter بالباك إند
## Aalenha - تقرير التكامل والتربيط

---

## 📋 ملخص تنفيذي

| المكون | الحالة | الملاحظات |
|--------|--------|-----------|
| **الباك إند** | ✅ جاهز | Laravel + Sanctum، 53+ API endpoint |
| **الموقع** | ✅ يعمل | Laravel Blade (server-side) |
| **Flutter** | ⚠️ UI فقط | **لا يوجد ربط بـ API حالياً** – بيانات وهمية فقط |

---

## 🏗️ هيكل المشروع

```
a3l_all/
├── backend/          # Laravel API (PHP)
│   ├── routes/api.php
│   ├── app/Http/Controllers/Api/
│   └── app/Http/Resources/
├── flutter/          # تطبيق الجوال (Dart/Flutter)
│   └── lib/
│       ├── presentation/pages/
│       ├── helpers/
│       └── core/
└── (الموقع مدمج في backend/resources/views/frontend/)
```

---

## 🔌 الباك إند – تفاصيل API

### Base URL
- **للنشر (إنتاج):** `https://aalenha.com/api/v1`
- **للتطوير المحلي:** `http://127.0.0.1:8000/api/v1` أو `http://localhost/api/v1`
- **مسار الـ API:** `/api/v1/`

### المصادقة (Sanctum)
- **طريقة:** Bearer Token
- **Header:** `Authorization: Bearer {token}`
- **الحصول على الـ Token:** من `/login` أو `/register`
- **بنية الاستجابة (Login/Register):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": { "id", "name", "email", "avatar", ... },
    "token": "1|xxxx...",
    "token_type": "Bearer"
  }
}
```

### CORS
- مسارات: `api/*`, `sanctum/csrf-cookie`
- `allowed_origins: ['*']` – جاهز للطلبات من Flutter

---

## 📡 ملخص Endpoints حسب الوظيفة

### 1. المصادقة (بدون Token)
| Method | Endpoint | الوصف |
|--------|----------|-------|
| POST | `/register` | تسجيل – `name, email, password, password_confirmation, phone?` |
| POST | `/login` | دخول – `email, password` |

### 2. المصادقة (مع Token)
| Method | Endpoint | الوصف |
|--------|----------|-------|
| POST | `/logout` | خروج |
| GET | `/me` | بيانات المستخدم الحالي |

### 3. المستخدم
| Method | Endpoint | الوصف |
|--------|----------|-------|
| GET | `/user` | الملف الشخصي |
| PUT | `/user` | تحديث الملف (JSON أو FormData للصورة) |
| PUT | `/user/password` | تغيير كلمة المرور |
| PUT | `/user/email` | تحديث البريد |
| POST | `/user/email/send-verification-code` | إرسال كود التحقق |
| POST | `/user/email/verify-code` | التحقق من كود البريد |
| PUT | `/user/phone` | تحديث الهاتف |
| GET | `/user/activities` | أنشطة المستخدم |
| POST | `/user/cancel-account` | إلغاء الحساب |
| DELETE | `/user` | حذف الحساب |

### 4. الفئات
| Method | Endpoint | الوصف |
|--------|----------|-------|
| GET | `/categories` | قائمة الفئات |
| GET | `/categories/{id}` | فئة واحدة |
| GET | `/categories/{id}/subcategories` | الفئات الفرعية |
| GET | `/subcategories/{id}/children` | أبناء الفئة الفرعية |

### 5. الإعلانات
| Method | Endpoint | الوصف |
|--------|----------|-------|
| GET | `/ads` | الإعلانات (pagination, filters) |
| GET | `/ads/search` | بحث |
| GET | `/ads/featured` | إعلانات مميزة |
| GET | `/ads/filter` | فلترة متقدمة |
| GET | `/ads/{uid}` | تفاصيل إعلان |
| GET | `/ads/{uid}/statistics` | إحصائيات الإعلان |
| POST | `/ads` | إنشاء (FormData + auth) |
| GET | `/ads/my/list` | إعلاناتي |
| PUT | `/ads/{uid}` | تحديث |
| DELETE | `/ads/{uid}` | حذف |

### 6. المفضلة
| Method | Endpoint | الوصف |
|--------|----------|-------|
| GET | `/favorites` | قائمة المفضلة |
| POST | `/favorites/{uid}/toggle` | إضافة/إزالة |
| DELETE | `/favorites/{uid}` | إزالة |

### 7. الرسائل
| Method | Endpoint | الوصف |
|--------|----------|-------|
| GET | `/messages` | قائمة المحادثات |
| POST | `/messages/create/{uid}` | بدء محادثة (ad_uid) |
| GET | `/messages/{id}` | تفاصيل محادثة |
| POST | `/messages/{id}` | إرسال رسالة |
| PUT | `/messages/{id}/read` | تحديد كمقروء |

### 8. التفاوض
| Method | Endpoint | الوصف |
|--------|----------|-------|
| GET | `/negotiations/create/{uid}` | إنشاء تفاوض |
| POST | `/negotiations/{uid}` | تقديم عرض |
| GET | `/negotiations/sent` | المرسلة |
| GET | `/negotiations/received` | المستلمة |
| POST | `/negotiations/{id}/accept` | قبول |
| POST | `/negotiations/{id}/reject` | رفض |

### 9. الباقات والمساعدة والمزيد
| Method | Endpoint | الوصف |
|--------|----------|-------|
| GET | `/packages` | الباقات |
| POST | `/packages/{id}/subscribe` | الاشتراك |
| GET | `/help` | المساعدة |
| POST | `/help/contact` | تواصل الدعم |
| GET | `/blocked-users` | المحظورين |
| POST | `/blocked-users` | حظر مستخدم |
| DELETE | `/blocked-users/{id}` | إلغاء حظر |
| GET | `/verification` | حالة التحقق |
| POST | `/verification` | طلب تحقق |
| POST | `/verification/revoke` | إلغاء التحقق |
| GET | `/reports` | البلاغات |
| POST | `/reports` | إنشاء بلاغ |

---

## 📱 Flutter – الوضع الحالي

### ما هو موجود
- واجهات مستخدم كاملة (صفحات، أزرار، فورمات)
- `CacheHelper` (SharedPreferences) للتخزين المحلي
- `showToast` للتنبيهات
- دعم RTL والعربية

### ما هو مفقود (للربط بالـ API)
- ❌ مكتبة HTTP (مثل `dio` أو `http`)
- ❌ طبقة Services/Repository
- ❌ طبقة Models موافقة لاستجابة الـ API
- ❌ إدارة Token والمصادقة
- ❌ إعداد Base URL قابل للتغيير

### صفحات Flutter وربطها بالـ API

| الصفحة | المسار | الـ APIs المطلوبة |
|--------|--------|-------------------|
| SplashScreen | splash/ | التحقق من Token → `/me` أو Login |
| LoginPage | auth/ | `POST /login` |
| RegisterPage | auth/ | `POST /register` |
| VerifyEmailPage | auth/ | `POST /user/email/verify-code` |
| HomePage | home/ | `/ads`, `/ads/featured`, `/categories` |
| AdDetailsPage | home/ | `GET /ads/{uid}`, `/favorites/{uid}/toggle` |
| SearchPage | search/ | `/ads/search`, `/ads/filter` |
| CategoryPage | home/ | `/categories/{id}/subcategories`, `/ads` |
| CreateAdPage / PostAdStepper | home/ | `POST /ads`, `/categories`, `/subcategories` |
| MyAccountPage | account/ | `GET /user`, `PUT /user` |
| EditProfilePage | account/ | `PUT /user`, تحديث الصورة |
| ChangePasswordPage | account/ | `PUT /user/password` |
| FavouriteAdsPage | account/ | `GET /favorites` |
| FavouriteSellersPage | account/ | (إن وُجد API للمتابعة) |
| MessagesPage | account/ | `GET /messages` |
| ChatPage | account/ | `GET /messages/{id}`, `POST /messages/{id}` |
| MyProductsDealsPage | account/ | `GET /ads/my/list`, `/negotiations/*` |
| OnAirAdsPage | account/ | `GET /ads/my/list` مع فلترة |
| NotPublishedAdsPage | account/ | `GET /ads/my/list` مع فلترة |
| HelpPage | account/ | `GET /help`, `POST /help/contact` |
| ProblemsPage | account/ | `POST /reports` |
| PermissionsPage | account/ | `GET /verification`, `POST /verification` |
| BlockedUsersPage | account/ | `GET /blocked-users` |
| DeleteAccPage | account/ | `POST /user/cancel-account`, `DELETE /user` |
| Wallet/AddCredit | payement/ | `GET /packages`, `POST /packages/{id}/subscribe` |

---

## 🔄 خطة التربيط على مراحل

### المرحلة 1: البنية التحتية (الأساس)
1. إضافة `dio` و `dio_smart_retry` (اختياري)
2. إنشاء `lib/core/network/`:
   - `api_client.dart` – إعداد Dio، Base URL، Interceptors
   - `api_constants.dart` – Base URL و endpoints
3. إنشاء `lib/core/storage/`:
   - `token_storage.dart` – حفظ/قراءة Token
4. تحديث `main.dart`:
   - تهيئة CacheHelper و TokenStorage و ApiClient

### المرحلة 2: المصادقة
1. إنشاء `lib/data/models/user_model.dart`
2. إنشاء `lib/data/services/auth_service.dart`
3. تحديث SplashScreen: التحقق من Token وتوجيه للـ Home أو Login
4. ربط LoginPage بـ `POST /login`
5. ربط RegisterPage بـ `POST /register`
6. ربط Logout في الملف الشخصي

### المرحلة 3: البيانات الأساسية
1. إنشاء `category_model.dart`, `ad_model.dart`
2. إنشاء `category_service.dart`, `ad_service.dart`
3. ربط HomePage بـ `/ads`, `/ads/featured`, `/categories`
4. ربط AdDetailsPage بـ `GET /ads/{uid}`
5. ربط SearchPage بـ `/ads/search`, `/ads/filter`

### المرحلة 4: الإعلانات والمستخدم
1. ربط CreateAd/PostAdStepper بـ `POST /ads`
2. ربط MyAccount بـ `GET /user`, `PUT /user`
3. ربط EditProfile بتحديث الملف والصورة
4. ربط FavouriteAds بـ `GET /favorites`, toggle

### المرحلة 5: الرسائل والتفاوض
1. إنشاء `conversation_model`, `message_model`
2. ربط MessagesPage و ChatPage
3. ربط MyProductsDeals مع Negotiations

### المرحلة 6: باقي الميزات
1. Help، Reports، Verification
2. BlockedUsers، DeleteAccount
3. Packages ودفعات المحفظة

---

## 📦 مكتبات مقترحة لإضافتها في pubspec.yaml

```yaml
dependencies:
  dio: ^5.4.0              # HTTP client
  # أو بديلاً:
  # http: ^1.2.0
```

---

## ⚠️ ملاحظات مهمة

### 1. روابط الصور
- الـ API يرجع الصور كـ `asset('storage/' . path)`
- تأكد أن Base URL يحتوي الدومين الصحيح (مثلاً `https://aalenha.com`)
- الصور ستكون مثل: `https://aalenha.com/storage/ads/xxx.jpg`

### 2. اللغات
- الـ API يدعم عربي/إنجليزي/تركي
- يمكن إرسال `Accept-Language` أو `locale` حسب إعداد الباك إند

### 3. Pagination
- الاستجابة تتضمن `meta`: `current_page`, `last_page`, `per_page`, `total`
- استخدمها لعرض المزيد (Load More / Infinite Scroll)

### 4. أخطاء الـ API
- بنية موحدة: `{ success: false, message: "...", errors?: {...} }`
- رموز: `422` للتحقق، `401` غير مصرح، `403` ممنوع

### 5. FormData لرفع الملفات
- إنشاء الإعلان يستخدم `multipart/form-data`
- الصور تحت مفتاح `images[]`

---

## ✅ الخطوة التالية المقترحة

البدء بالمرحلة 1: إعداد البنية التحتية (Dio + ApiClient + TokenStorage + تهيئة في main).

بعد الانتهاء منها يمكن الانتقال مباشرة إلى ربط Login و Register في المرحلة 2.

---

*تاريخ التقرير: 2026-01-31*
