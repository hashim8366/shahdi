# نظام ادارة الشواهد الذكي
## Smart Certificate Management System

نظام ويب متكامل مبني بـ **PHP** و **Supabase** يتيح للمستخدمين إنشاء روابط احترافية لشواهدهم ومشاركتها بسهولة.

---

## المميزات

- 📋 **إنشاء حساب وتسجيل الدخول** عبر Supabase Auth
- 🏅 **إنشاء روابط شواهد فريدة** لكل شهادة
- 📎 **رفع ملفات الشهادات** (PDF / JPG / PNG / WEBP) إلى Supabase Storage
- 🔗 **رابط عام قابل للمشاركة** لكل شهادة
- 🗑️ **حذف الشواهد** من لوحة التحكم
- 🌐 واجهة عربية كاملة (RTL) مبنية بـ Bootstrap 5

---

## هيكل المشروع

```
shahdi/
├── index.php                  # الصفحة الرئيسية
├── register.php               # إنشاء حساب
├── login.php                  # تسجيل الدخول
├── logout.php                 # تسجيل الخروج
├── dashboard.php              # لوحة التحكم
├── create-certificate.php     # إنشاء شهادة جديدة
├── certificate.php            # عرض شهادة (رابط عام)
├── delete-certificate.php     # حذف شهادة
├── config/
│   └── supabase.php           # إعدادات Supabase + Helper Class
├── includes/
│   ├── header.php             # رأس الصفحة (Navbar)
│   └── footer.php             # تذييل الصفحة
├── assets/
│   ├── css/style.css          # الأنماط المخصصة
│   └── js/main.js             # سكريبت الواجهة
└── database/
    └── schema.sql             # مخطط قاعدة البيانات
```

---

## الإعداد والتشغيل

### 1. إنشاء مشروع Supabase

1. اذهب إلى [supabase.com](https://supabase.com) وأنشئ مشروعاً جديداً.
2. انسخ **Project URL** و **anon public key** من: Settings → API.

### 2. إنشاء جداول قاعدة البيانات

افتح **SQL Editor** في Supabase Dashboard وشغّل محتوى ملف `database/schema.sql`.

### 3. إنشاء Storage Bucket

1. اذهب إلى **Storage** → **New Bucket**.
2. أنشئ Bucket باسم `certificates` واجعله **Public**.

### 4. إعداد متغيرات البيئة

أنشئ ملف `.env` أو عدّل `config/supabase.php` مباشرة (لأغراض التطوير فقط):

```php
define('SUPABASE_URL',      'https://xxxx.supabase.co');
define('SUPABASE_ANON_KEY', 'your-anon-key');
```

### 5. تشغيل الخادم المحلي

```bash
php -S localhost:8000
```

ثم افتح المتصفح على `http://localhost:8000`.

---

## متطلبات الخادم

| المتطلب  | الإصدار |
|----------|---------|
| PHP      | 8.0+    |
| cURL     | مفعّل  |
| fileinfo | مفعّل  |

---

## الأمان

- يتم التحقق من صحة المدخلات على جهة الخادم.
- تحقق من نوع الملف بـ `finfo` (وليس الامتداد فقط).
- Row Level Security مفعّل على Supabase.
- لا يتم تخزين كلمات المرور محلياً – كل المصادقة عبر Supabase Auth.
- يجب **عدم** تضمين المفاتيح السرية في الكود المصدري.