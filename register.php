<?php
/**
 * Registration Page – نظام ادارة الشواهد الذكي
 */
$pageTitle = 'إنشاء حساب';
include __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (!empty($_SESSION['access_token'])) {
    header('Location: /dashboard.php');
    exit;
}

$errors  = [];
$success = '';
$old     = ['email' => '', 'full_name' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email     = trim($_POST['email']     ?? '');
    $fullName  = trim($_POST['full_name'] ?? '');
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';

    $old['email']     = $email;
    $old['full_name'] = $fullName;

    // ── Validation ────────────────────────────────────────────────────────────
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'الرجاء إدخال بريد إلكتروني صحيح.';
    }
    if (empty($fullName)) {
        $errors[] = 'الرجاء إدخال الاسم الكامل.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'يجب أن تكون كلمة المرور 8 أحرف على الأقل.';
    }
    if ($password !== $password2) {
        $errors[] = 'كلمتا المرور غير متطابقتين.';
    }

    if (empty($errors)) {
        $sb  = new Supabase();
        $res = $sb->signUp($email, $password, ['full_name' => $fullName]);

        if (!empty($res['error']) || !empty($res['msg'])) {
            $msg = $res['message'] ?? $res['msg'] ?? $res['error'] ?? 'حدث خطأ. حاول مرة أخرى.';
            $errors[] = $msg;
        } elseif (!empty($res['user']) || !empty($res['id'])) {
            $success = 'تم إنشاء حسابك بنجاح! يمكنك الآن تسجيل الدخول.';
        } else {
            $errors[] = 'حدث خطأ غير متوقع. حاول مرة أخرى.';
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="auth-card card shadow-lg border-0">
                <div class="card-body p-5">
                    <!-- Card header -->
                    <div class="text-center mb-4">
                        <div class="auth-icon mb-3">
                            <i class="fa-solid fa-user-plus fa-3x text-primary"></i>
                        </div>
                        <h4 class="fw-bold">إنشاء حساب جديد</h4>
                        <p class="text-muted small">سجّل الآن وابدأ في إدارة شواهدك</p>
                    </div>

                    <!-- Alerts -->
                    <?php if ($success): ?>
                        <div class="alert alert-success d-flex align-items-center gap-2">
                            <i class="fa-solid fa-circle-check"></i>
                            <?= htmlspecialchars($success) ?>
                        </div>
                        <div class="text-center">
                            <a href="/login.php" class="btn btn-primary px-4">
                                <i class="fa-solid fa-right-to-bracket me-2"></i>تسجيل الدخول
                            </a>
                        </div>
                    <?php else: ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0 ps-3">
                                    <?php foreach ($errors as $e): ?>
                                        <li><?= htmlspecialchars($e) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <!-- Form -->
                        <form method="POST" action="/register.php" novalidate>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="full_name">
                                    <i class="fa-solid fa-user text-muted me-1"></i>الاسم الكامل
                                </label>
                                <input type="text" id="full_name" name="full_name"
                                       class="form-control form-control-lg"
                                       placeholder="أدخل اسمك الكامل"
                                       value="<?= $old['full_name'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="email">
                                    <i class="fa-solid fa-envelope text-muted me-1"></i>البريد الإلكتروني
                                </label>
                                <input type="email" id="email" name="email"
                                       class="form-control form-control-lg"
                                       placeholder="example@email.com"
                                       value="<?= $old['email'] ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" for="password">
                                    <i class="fa-solid fa-lock text-muted me-1"></i>كلمة المرور
                                </label>
                                <div class="input-group">
                                    <input type="password" id="password" name="password"
                                           class="form-control form-control-lg"
                                           placeholder="8 أحرف على الأقل" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button"
                                            data-target="password">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold" for="password2">
                                    <i class="fa-solid fa-lock text-muted me-1"></i>تأكيد كلمة المرور
                                </label>
                                <div class="input-group">
                                    <input type="password" id="password2" name="password2"
                                           class="form-control form-control-lg"
                                           placeholder="أعد إدخال كلمة المرور" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button"
                                            data-target="password2">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                                <i class="fa-solid fa-user-plus me-2"></i>إنشاء الحساب
                            </button>
                        </form>

                        <hr class="my-4">
                        <p class="text-center text-muted small">
                            لديك حساب بالفعل؟
                            <a href="/login.php" class="text-primary fw-semibold">تسجيل الدخول</a>
                        </p>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
