# مراجعة ربط جميع الصفحات مع API

## ✅ صفحات مرتبطة بالكامل مع API

| الصفحة | الخدمة | الـ APIs المستخدمة |
|--------|--------|-------------------|
| **login_page** | AuthService | `POST /login` |
| **register_page** | AuthService | `POST /register` |
| **splash_screen** | AuthService | `GET /me` (للتحقق) |
| **home_page** | AdService, CategoryService | `GET /ads`, `GET /ads/featured`, `GET /categories` |
| **ads_list_page** | AdService | `GET /ads` (مع category/subcategory) |
| **ad_details_page** | AdService, NegotiationService | `GET /ads/{uid}`, `POST /negotiations/{uid}` |
| **category_page** | CategoryService | `GET /categories`, `GET /categories/{id}/subcategories` |
| **subcategory_page** | CategoryService | `GET /subcategories/{id}/children` |
| **create_ad_page** | CategoryService | `GET /categories` |
| **post_ad_stepper_page** | AdService, CategoryService | `POST /ads`, `GET /categories` |
| **on_air_ads_page** | AdService | `GET /ads/my/list?status=active` |
| **not_published_ads_page** | AdService | `GET /ads/my/list?status=pending` |
| **favourite_ads_page** | FavoriteService | `GET /favorites`, `POST /favorites/{uid}/toggle` |
| **favourite_sellers_page** | FavoriteSellerService | `GET /favorite-sellers`, `DELETE /favorite-sellers/{slug}` |
| **seller_profile_page** | SellerService | `GET /sellers/{slug}`, `POST /sellers/{slug}/rate` |
| **my_account_page** | AuthService | `GET /me`, تسجيل الخروج |
| **edit_profile_page** | UserService | `GET /user`, `PUT /user` |
| **change_password_page** | UserService | `PUT /user/password` |
| **delete_acc_page** | UserService | `POST /user/cancel-account` |
| **messages_page** | MessageService | `GET /messages` |
| **chat_page** | MessageService, AdService | `POST /messages/create/{uid}`, `GET /messages/{id}`, `POST /messages/{id}` |
| **help_page** | HelpService | `GET /help` |
| **problems_page** | HelpService | `POST /help/contact` |
| **my_products_deals_page** | NegotiationService | `GET /negotiations/sent`, `GET /negotiations/received`, accept, reject |
| **quta_pages** | PackageService | `GET /packages`, `POST /packages/{id}/subscribe` |

---

## ❌ صفحات غير مرتبطة أو تحتاج ربط

### 1. **search_page** — تم الربط
- **تم:** ربط مع `AdService.getAds(search, categoryId, minPrice, maxPrice)`، عرض نتائج حقيقية، فلتر، تحميل المزيد

### 2. **notification_page** — تم الربط
- **تم:** إنشاء `GET /notifications` API في الباكند، ربط Flutter مع `NotificationService`، تحديد كمقروء

### 3. **vertify_email** — تم الربط
- **تم:** ربط مع `UserService.verifyEmailCode` و `sendEmailVerificationCode`، رمز 6 خانات

### 4. **info_about_app_page** — تم الربط
- **تم:** إنشاء `GET /app-info` API في الباكند، ربط Flutter مع `AppInfoService` لجلب معلومات التطبيق من الإعدادات

### 5. **thank_you_page** — لا يحتاج ربط
- صفحة تأكيد تُعرض بعد إنشاء الإعلان بنجاح

### 6. **add_credit_page** — يعتمد على بوابة الدفع
- **الوضع الحالي:** واجهة لإضافة البطاقة فقط
- **ملاحظة:** يتطلب تكامل مع بوابة دفع خارجية

### 7. **choose_payement_page**, **hewala_page**, **payment_gateway_page**
- واجهات تنقل وتعليمات، لا تحتاج API مباشر

### 8. **my_wallet_page**, **wallet_page**
- **الوضع الحالي:** يعرضان رصيد ثابت
- **ملاحظة:** يحتاجان API محفظة إذا كان النظام يدعم المحفظة

### 9. **permissions_page**, **share_profile_page**
- **لا يحتاجان API:** أذونات الجهاز ومشاركة محلية

---

## 🔧 APIs – تم ربطها

| API | الاستخدام |
|-----|-----------|
| `POST /reports` | زر "إبلاغ عن الإعلان" في صفحة تفاصيل الإعلان |
| `GET /ads` مع search | صفحة البحث (مستخدَم) |
| `POST /user/email/verify-code` | صفحة التحقق من البريد |
| `POST /user/email/send-verification-code` | إعادة إرسال رمز التحقق |
| `GET /blocked-users`, `POST /blocked-users`, `DELETE` | صفحة المستخدمين المحظورين + زر حظر في ملف التاجر |
| `GET /verification`, `POST /verification` | صفحة التحقق من الهوية |

---

## ملخص

| الحالة | العدد |
|--------|-------|
| مرتبطة بالكامل | 25 |
| تحتاج ربط | 4 (search, notification, vertify_email, report ad) |
| لا تحتاج API | 4 (thank_you, permissions, share, بعض صفحات الدفع) |
