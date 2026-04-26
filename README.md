# نظام ادارة الشواهد الذكي
## Smart Evidence Management System

نظام ويب متكامل مبني بـ **PHP** و **Supabase** يتيح رفع وإدارة ملفات الشواهد (صور، فيديوهات، ملفات) لأي برنامج أو فعالية، وإنشاء روابط مشاركة احترافية لعرضها.

---

## ما هو "الشاهد"؟

الشاهد هو **ملف إثبات وتوثيق** (صورة، فيديو، أو ملف) يُرفق ببرنامج أو فعالية معينة ليُثبت تنفيذه.

---

## المميزات

- 📋 **إنشاء حساب وتسجيل الدخول** عبر Supabase Auth
- 📁 **إنشاء برامج / فعاليات** وتنظيم الشواهد تحتها
- 📸 **رفع الصور** (JPG / PNG / WEBP / GIF – حتى 20MB)
- 🎬 **رفع الفيديوهات** (MP4 / WEBM / MOV / AVI – حتى 200MB)
- 📄 **رفع ملفات PDF** (حتى 20MB)
- 🔗 **رابط مشاركة عام** لكل برنامج يعرض معرض الشواهد
- 🗑️ **حذف الملفات والبرامج** من لوحة التحكم
- 🌐 واجهة عربية RTL مبنية بـ Bootstrap 5

---

## هيكل المشروع

```
shahdi/
├── index.php                  # الصفحة الرئيسية
├── register.php               # إنشاء حساب
├── login.php                  # تسجيل الدخول
├── logout.php                 # تسجيل الخروج
├── dashboard.php              # لوحة التحكم (قائمة البرامج)
├── create-program.php         # إنشاء برنامج / فعالية جديدة
├── manage-program.php         # رفع وإدارة ملفات الشواهد
├── program.php                # عرض شواهد البرنامج (رابط عام)
├── delete-program.php         # حذف برنامج
├── delete-evidence.php        # حذف ملف شاهد واحد
├── config/
│   └── supabase.php           # إعدادات Supabase + Helper Class
├── includes/
│   ├── header.php             # رأس الصفحة
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
2. أنشئ Bucket باسم `evidence` واجعله **Public**.

### 4. إعداد متغيرات البيئة

عدّل القيم في `config/supabase.php` (لأغراض التطوير):

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

## مخطط قاعدة البيانات

| الجدول | الأعمدة الرئيسية |
|--------|-----------------|
| `programs` | id, user_id, program_name, description, organization, slug, created_at |
| `evidence_files` | id, program_id, file_name, file_url, file_type, file_size, created_at |

---

## متطلبات الخادم

| المتطلب | الإصدار |
|---------|---------|
| PHP     | 8.0+   |
| cURL    | مفعّل  |
| fileinfo | مفعّل |

---

## الأمان

- التحقق من نوع الملف بـ `finfo` (ليس الامتداد فقط).
- Row Level Security مفعّل على Supabase (المستخدم يرى برامجه فقط).
- الرابط العام يعتمد على slug عشوائي (16 حرفاً hex) كآلية وصول.
- لا تُخزَّن كلمات المرور محلياً – المصادقة الكاملة عبر Supabase Auth.
