<?php
/**
 * Landing Page – نظام ادارة الشواهد الذكي
 */
$pageTitle = 'الرئيسية';
include __DIR__ . '/includes/header.php';

if (!empty($_SESSION['access_token'])) {
    header('Location: /dashboard.php');
    exit;
}
?>

<!-- Hero Section -->
<section class="hero-section d-flex align-items-center">
    <div class="container text-center text-white py-5">
        <div class="hero-icon mb-4">
            <i class="fa-solid fa-photo-film fa-5x text-warning"></i>
        </div>
        <h1 class="display-4 fw-bold mb-3">نظام ادارة الشواهد الذكي</h1>
        <p class="lead mb-4 opacity-90">
            ارفع وأدِر ملفات الإثبات والتوثيق لبرامجك وفعالياتك، وأنشئ روابط مشاركة احترافية
        </p>
        <div class="d-flex justify-content-center gap-3 flex-wrap">
            <a href="/register.php"
               class="btn btn-warning btn-lg px-5 shadow fw-bold">
                <i class="fa-solid fa-user-plus me-2"></i>إنشاء حساب مجاني
            </a>
            <a href="/login.php"
               class="btn btn-outline-light btn-lg px-5">
                <i class="fa-solid fa-right-to-bracket me-2"></i>تسجيل الدخول
            </a>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-6 bg-light">
    <div class="container">
        <h2 class="text-center fw-bold mb-5 section-title">لماذا نظام ادارة الشواهد الذكي؟</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 text-center p-4">
                    <div class="feature-icon mb-3">
                        <i class="fa-solid fa-images fa-3x text-primary"></i>
                    </div>
                    <h5 class="fw-bold">صور وفيديوهات وملفات</h5>
                    <p class="text-muted">
                        ارفع الصور، الفيديوهات، وملفات PDF كشواهد إثبات لبرنامجك
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 text-center p-4">
                    <div class="feature-icon mb-3">
                        <i class="fa-solid fa-folder-open fa-3x text-success"></i>
                    </div>
                    <h5 class="fw-bold">تنظيم حسب البرنامج</h5>
                    <p class="text-muted">
                        نظّم شواهدك ضمن برامج وفعاليات منفصلة لسهولة الإدارة
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card card border-0 shadow-sm h-100 text-center p-4">
                    <div class="feature-icon mb-3">
                        <i class="fa-solid fa-share-nodes fa-3x text-warning"></i>
                    </div>
                    <h5 class="fw-bold">روابط مشاركة فورية</h5>
                    <p class="text-muted">
                        احصل على رابط عام لعرض جميع شواهد البرنامج وشاركه مع أي شخص
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How it works -->
<section class="py-6">
    <div class="container">
        <h2 class="text-center fw-bold mb-5 section-title">كيف يعمل النظام؟</h2>
        <div class="row g-4 align-items-center">
            <div class="col-md-3 text-center">
                <div class="step-circle mx-auto mb-3">1</div>
                <h6 class="fw-bold">إنشاء حساب</h6>
                <p class="text-muted small">سجّل بريدك وكلمة مرور</p>
            </div>
            <div class="col-md-1 text-center d-none d-md-block">
                <i class="fa-solid fa-arrow-left fa-2x text-muted"></i>
            </div>
            <div class="col-md-3 text-center">
                <div class="step-circle mx-auto mb-3">2</div>
                <h6 class="fw-bold">أنشئ برنامجاً</h6>
                <p class="text-muted small">أدخل اسم البرنامج والجهة المنفذة</p>
            </div>
            <div class="col-md-1 text-center d-none d-md-block">
                <i class="fa-solid fa-arrow-left fa-2x text-muted"></i>
            </div>
            <div class="col-md-3 text-center">
                <div class="step-circle mx-auto mb-3">3</div>
                <h6 class="fw-bold">ارفع الشواهد وشاركها</h6>
                <p class="text-muted small">أضف الصور والفيديوهات واحصل على رابط مشاركة</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-5 bg-primary text-white text-center">
    <div class="container">
        <h3 class="fw-bold mb-3">ابدأ الآن مجاناً</h3>
        <p class="mb-4 opacity-90">وثّق برامجك وفعالياتك بصورة احترافية</p>
        <a href="/register.php" class="btn btn-warning btn-lg px-5 fw-bold">
            <i class="fa-solid fa-user-plus me-2"></i>إنشاء حساب مجاني
        </a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

