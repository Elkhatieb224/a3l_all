# 🇸🇾 أعلنها - نظام الإعلانات المبوبة

<p align="center">
  <img src="https://via.placeholder.com/400x100/002C60/FFD600?text=A3lenha" alt="أعلنها">
</p>

<p align="center">
  <strong>نظام إعلانات مبوبة متكامل مصمم للسوق السوري</strong>
</p>

---

## 🎯 نظرة عامة

**أعلنها** هو نظام إعلانات مبوبة شامل مشابه لـ sahibinden.com، يتيح للمستخدمين نشر وتصفح الإعلانات مع نظام باقات متكامل ولوحة تحكم احترافية.

### 🌍 المعلومات الأساسية:
- 🇸🇾 **الدولة**: سوريا
- 💰 **العملة**: الليرة السورية (ل.س / SYP)
- ⏰ **المنطقة الزمنية**: Asia/Damascus (UTC+3)
- 🌐 **اللغات**: عربي 🇸🇦 | إنجليزي 🇬🇧 | تركي 🇹🇷
- 🎨 **الألوان**: #002C60 (أزرق) + #FFD600 (أصفر)

---

## ⚡ التثبيت السريع

```bash
# 1. تثبيت الحزم
composer install

# 2. نسخ ملف البيئة
cp .env.example .env

# 3. إنشاء database اسمه: a3lenah

# 4. تعديل .env
DB_DATABASE=a3lenah

# 5. توليد المفتاح وإنشاء الجداول
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link

# 6. تشغيل المشروع
php artisan serve
```

**افتح**: http://localhost:8000/admin

---

## 🔑 بيانات الدخول

| الدور | البريد | كلمة المرور |
|-------|--------|------------|
| Super Admin | admin@a3lenha.com | password123 |
| Admin | ahmed@a3lenha.com | password123 |
| Moderator | moderator@a3lenha.com | password123 |

---

## ✨ المميزات الرئيسية

### 🏗️ البنية:
- ✅ Laravel 10
- ✅ 11 Migrations
- ✅ 13 Models
- ✅ 13 Controllers
- ✅ 51 Routes
- ✅ 25 Blade Views

### 🎨 لوحة التحكم (10 أقسام):
1. 📊 **Dashboard** - إحصائيات شاملة
2. 👥 **Users** - إدارة المستخدمين
3. 📁 **Categories** - 9 أقسام مع حقول مخصصة
4. 📣 **Ads** - إدارة الإعلانات
5. 📦 **Packages** - 4 باقات
6. 💳 **Payments** - تتبع المدفوعات
7. 🚩 **Reports** - إدارة البلاغات
8. ⚙️ **Settings** - الإعدادات
9. 👨‍💼 **Admins** - إدارة المديرين
10. 📜 **Logs** - سجل النشاطات

### 🌐 تعدد اللغات:
- ✅ 3 لغات كاملة (750+ نص مترجم)
- ✅ زر تبديل اللغة في Navbar
- ✅ دعم RTL/LTR تلقائي
- ✅ Validation مترجم

### 📁 نظام الأقسام المتقدم:
- ✅ 9 أقسام رئيسية
- ✅ **حقول مخصصة ديناميكية** (JSON)
- ✅ **أقسام فرعية متعددة المستويات**
- ✅ **رفع أيقونات (صور)** JPG, PNG, SVG

### 💰 نظام الباقات:
- ✅ 4 باقات (مجانية → 2.5M ل.س)
- ✅ نظام اشتراكات
- ✅ تتبع المدفوعات
- ✅ إحصائيات الإيرادات

---

## 📊 الأقسام التسعة

| # | القسم | الحقول المخصصة | الأقسام الفرعية |
|---|-------|----------------|------------------|
| 1 | 🚗 المركبات | 10 حقول | - |
| 2 | 🏠 العقارات | 7 حقول | 4 |
| 3 | 💼 الوظائف | 5 حقول | - |
| 4 | 🏡 مساعدين منزليين | 5 حقول | - |
| 5 | 🏗️ الآلات والمعدات | 4 حقول | 3 |
| 6 | 🐾 الحيوانات | 4 حقول | - |
| 7 | 📚 دروس خصوصية | 3 حقول | - |
| 8 | 📦 سلع مستعملة | 3 حقول | - |
| 9 | 🔧 قطع غيار | 3 حقول | - |

---

## 🎨 التصميم

- **Tailwind CSS** - للتصميم
- **Font Awesome** - للأيقونات
- **Cairo Font** - خط عربي احترافي
- **Chart.js** - للرسوم البيانية
- تصميم متجاوب بالكامل

---

## 📚 التوثيق

### للبدء السريع:
📖 **[START_HERE.md](START_HERE.md)** ← ابدأ هنا!

### للمديرين:
📖 **[README_ADMIN.md](README_ADMIN.md)** - دليل لوحة التحكم الشامل  
📖 **[TESTING_CHECKLIST.md](TESTING_CHECKLIST.md)** - قائمة الاختبار

### للمطورين:
📖 **[COMPLETE_GUIDE.md](COMPLETE_GUIDE.md)** - الدليل الكامل  
📖 **[ROUTES_VIEWS_MAPPING.md](ROUTES_VIEWS_MAPPING.md)** - مطابقة Routes والـ Views  
📖 **[CATEGORIES_SETUP.md](CATEGORIES_SETUP.md)** - نظام الأقسام  
📖 **[LANGUAGE_SETUP.md](LANGUAGE_SETUP.md)** - نظام اللغات  
📖 **[CURRENCY_SETUP.md](CURRENCY_SETUP.md)** - نظام العملات  
📖 **[ICON_UPLOAD_GUIDE.md](ICON_UPLOAD_GUIDE.md)** - رفع الأيقونات  

---

## 🔐 الأمان

- ✅ نظام مصادقة منفصل للمديرين (Guard: `admin`)
- ✅ 3 مستويات صلاحيات
- ✅ Middleware للحماية
- ✅ CSRF Protection
- ✅ Password Hashing
- ✅ Activity Logs لكل إجراء

---

## 🚀 المتطلبات

- PHP >= 8.1
- Composer
- MySQL/MariaDB
- Node.js & NPM (اختياري)

---

## 📞 الدعم

- **البريد**: info@a3lenha.com
- **الهاتف**: +963 11 123 4567

---

## 📄 الترخيص

© 2025 أعلنها - جميع الحقوق محفوظة

---

## 🎊 الحالة

✅ **مكتمل 100%**  
✅ **51 Routes صحيحة**  
✅ **25 Views جاهزة**  
✅ **0 Errors**  
✅ **جاهز للاستخدام**  

---

**صُنع بحب في سوريا** 🇸🇾💙

