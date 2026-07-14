# متطلبات تحسين الأداء للباكند (نسخة تنفيذية مفصلة)

هذا الملف مخصص لفريق الباكند، ويحتوي متطلبات عملية دقيقة لتقليل البطء في جلب البيانات، خصوصًا في **أول فتح للتطبيق** والصفحة الرئيسية.

## 1) الوضع الحالي بالأرقام

قياس حالي (من جهاز العميل، 7 مرات لكل endpoint):

| Endpoint | Avg TTFB | P95 TTFB | Avg Total | P95 Total |
|---|---:|---:|---:|---:|
| `GET /help` | 0.932s | 0.994s | 0.940s | 1.007s |
| `GET /legal/privacy` | 0.938s | 1.048s | 0.951s | 1.059s |
| `GET /legal/terms` | 1.025s | 1.677s | 1.066s | 1.677s |
| `GET /ads?per_page=20&page=1` | 0.983s | 1.047s | 1.125s | 1.203s |

### استنتاج

- التأخير الأكبر في **TTFB**، أي قبل وصول أول بايت من السيرفر.
- المشكلة الأساسية ليست في Flutter فقط؛ الجذر في زمن تنفيذ الباكند/DB/Cache.

---

## 2) الهدف النهائي (SLO / SLA)

### أهداف فنية مطلوبة

1. `GET /help`
   - Avg TTFB <= 250ms
   - P95 Total <= 600ms

2. `GET /legal/privacy` و `GET /legal/terms`
   - Avg TTFB <= 300ms
   - P95 Total <= 700ms

3. `GET /ads?per_page=20&page=1`
   - Avg TTFB <= 400ms
   - P95 Total <= 850ms

4. ثبات الأداء تحت حمل متزامن 100 مستخدم بدون انفجار في P95/P99.

---

## 3) خطة التحسين المطلوبة من الباك (P0 -> P3)

## P0 (إجباري وفوري - أعلى عائد)

1. **Endpoint Profiling لكل واجهة حرجة**
   - تفعيل trace لكل endpoint مستهدف:
     - `app_time_ms`
     - `db_time_ms`
     - `cache_time_ms`
     - `serialize_time_ms`
   - الهدف: معرفة أين يضيع الوقت بدقة قبل أي تحسين عشوائي.

2. **تحسين استعلامات `/ads`**
   - تنفيذ `EXPLAIN ANALYZE` على الاستعلامات الأساسية.
   - إضافة/مراجعة الفهارس على الأعمدة الأكثر استخدامًا:
     - `category_id`
     - `subcategory_id`
     - `featured`
     - `urgent`
     - `status`
     - `created_at`
     - `price` (إذا يوجد فلترة حسب السعر)
   - استخدام فهارس مركبة حسب نمط الاستعلام الفعلي، مثل:
     - `(status, featured, created_at DESC)`
     - `(status, category_id, created_at DESC)`
     - `(status, subcategory_id, created_at DESC)`
   - منع `SELECT *` في القوائم، واختيار الأعمدة اللازمة فقط.

3. **منع N+1 بشكل قاطع**
   - استخدام eager loading للعلاقات المطلوبة في قائمة الإعلانات.
   - أي relationship إضافي لا يظهر في card لا يتم تحميله في list endpoint.

4. **Redis Cache للواجهات شبه الثابتة**
   - endpoints:
     - `/help`
     - `/legal/privacy`
     - `/legal/terms`
     - `/categories`
   - TTL مقترح:
     - help/legal: 30-60 دقيقة
     - categories: 10-30 دقيقة
   - invalidation عند أي تعديل من لوحة التحكم.

## P1 (أداء القوائم واستقرار الحمل)

5. **Cache للقائمة الأولى من `/ads`**
   - تخزين response لأول صفحة (حسب الفلاتر الشائعة) بمدة 30-120 ثانية.
   - مفاتيح cache يجب أن تتضمن معايير الفلترة الأساسية.
   - invalidation ذكي عند إنشاء/تعديل/حذف إعلان.

6. **تقليل payload للقائمة**
   - إرسال الحقول الضرورية فقط للـ listing card:
     - `id/uid`, `title`, `price`, `thumbnail`, `location short`, `is_featured`, `created_at`
   - نقل الحقول الثقيلة للتفاصيل:
     - الوصف الكامل
     - الصور كاملة
     - custom_fields الكبيرة
     - بيانات المالك الكاملة

7. **Pagination محسنة**
   - ضبط limit افتراضي معقول (10-20).
   - ضمان استقرار الأداء مع pages متقدمة.
   - التفكير في cursor pagination لو offset أصبح مكلفًا.

## P2 (HTTP/Infra Optimization)

8. **HTTP Caching**
   - إضافة `ETag` + `If-None-Match` للواجهات الثابتة.
   - إضافة `Cache-Control` مناسب للـ public endpoints.

9. **Compression & Transport**
   - تفعيل `gzip` أو `brotli`.
   - التأكد من keep-alive.
   - التأكد من HTTP/2 (أو HTTP/3 إن متاح).

10. **Infra Tuning**
    - ضبط PHP-FPM/Node worker pool حسب الحمل الفعلي.
    - مراجعة connection pool لقاعدة البيانات.
    - مراجعة slow logs وtimeouts.

## P3 (مراقبة واختبارات رجعية)

11. **APM + Dashboards**
    - قياس p50/p95/p99 لكل endpoint.
    - tracking لـ DB query count + total DB time per request.
    - alerting عند تجاوز حد TTFB.

12. **Load Testing قابل للتكرار**
    - سيناريوهات:
      - فتح الصفحة الرئيسية.
      - جلب قوائم الإعلانات مع فلاتر مختلفة.
      - جلب help/legal.
    - عدد مستخدمين: 50 / 100 / 200.
    - توثيق baseline ثم نتائج after optimization.

---

## 4) متطلبات فنية تفصيلية حسب Endpoint

## `GET /help`
- response من cache مباشرة (Redis) في أغلب الحالات.
- invalidation event-driven عند تعديل FAQ.
- target app_time <= 80ms.

## `GET /legal/privacy` و `GET /legal/terms`
- response جاهز من cache.
- دعم ETag لتقليل نقل البيانات.
- target app_time <= 100ms.

## `GET /categories`
- cache + invalidation عند تعديل التصنيفات.
- اختيار أعمدة محددة بدل تحميل بيانات غير مستخدمة.

## `GET /ads`
- فصل واضح بين:
  - list payload (خفيف)
  - details payload (ثقيل)
- إضافة endpoint اختياري مخصص للـ home cards إذا احتجنا:
  - مثل `/ads/home` يعيد payload خفيف جدًا ومحسّن للهوم.

---

## 5) Checklist التسليم لفريق الباك

1. تقرير profiling قبل التحسين (لكل endpoint مستهدف).
2. قائمة الفهارس الجديدة/المعدلة + سبب كل فهرس.
3. نتيجة `EXPLAIN ANALYZE` قبل/بعد.
4. سياسة cache keys + TTL + invalidation.
5. إعدادات compression وheaders (`Cache-Control`, `ETag`).
6. نتائج load test قبل/بعد (50/100/200 users).
7. Dashboard رابط/لقطة تثبت p95 الجديد.

---

## 6) تعريف النجاح النهائي

يُعتبر التحسين ناجحًا فقط عند تحقق الشروط التالية مجتمعة:

1. تحسن واضح في TTFB والـP95 حسب الأهداف المذكورة.
2. عدم وجود regressions وظيفية.
3. ثبات الأداء لمدة 7 أيام على الأقل بعد النشر.
4. وجود مراقبة وتنبيهات تمنع عودة المشكلة.

---

## 7) ما تم تنفيذه بالفعل من جهة Flutter (للتنسيق بين الفريقين)

تم تنفيذ تحسينات مهمة على التطبيق:
- تقليل logging الثقيل في `Dio`.
- Memory cache مع TTL + deduplication للطلبات المتزامنة.
- Persistent TTL cache على الجهاز (لتقليل زمن أول شاشة بعد إعادة فتح التطبيق).
- Warm-up أثناء Splash لجلب البيانات الحرجة مبكرًا.

> ملاحظة مهمة: هذه التحسينات تقلل أثر البطء على المستخدم، لكنها لا تستطيع وحدها تجاوز TTFB المرتفع من الخادم. التحسين الحقيقي المطلوب للوصول لتجربة سريعة يأتي من تنفيذ البنود السابقة في الباكند.
