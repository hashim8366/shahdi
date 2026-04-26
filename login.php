<?php
/**
 * Login Page – نظام ادارة الشواهد الذكي
 */
$pageTitle = 'تسجيل الدخول';
include __DIR__ . '/includes/header.php';

// Redirect if already logged in
if (!empty($_SESSION['access_token'])) {
    header('Location: /dashboard.php');
    exit;
}

$errors = [];
$old    = ['email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    $old['email'] = $email;

    // ── Validation ────────────────────────────────────────────────────────────
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'الرجاء إدخال بريد إلكتروني صحيح.';
    }
    if (empty($password)) {
        $errors[] = 'الرجاء إدخال كلمة المرور.';
    }

    if (empty($errors)) {
        $sb  = new Supabase();
        $res = $sb->signIn($email, $password);

        if (!empty($res['access_token'])) {
            // Successful login – store tokens in session
            $_SESSION['access_token']  = $res['access_token'];
            $_SESSION['refresh_token'] = $res['refresh_token'] ?? '';
            $_SESSION['user_id']       = $res['user']['id']     ?? '';
            $_SESSION['user_email']    = $res['user']['email']  ?? $email;
            $_SESSION['user_name']     = $res['user']['user_metadata']['full_name']
                                         ?? $res['user']['email']
                                         ?? $email;

            header('Location: /dashboard.php');
            exit;
        } else {
            $msg = $res['error_description'] ?? $res['message'] ?? $res['msg'] ?? 'بيانات الدخول غير صحيحة.';
            $errors[] = $msg;
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
                            <i class="fa-solid fa-right-to-bracket fa-3x text-primary"></i>
                        </div>
                        <h4 class="fw-bold">تسجيل الدخول</h4>
                        <p class="text-muted small">أدخل بياناتك للوصول إلى لوحة التحكم</p>
                    </div>

                    <!-- Alerts -->
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
                    <form method="POST" action="/login.php" novalidate>
                        <div class="mb-3">
                            <label class="form-label fw-semibold" for="email">
                                <i class="fa-solid fa-envelope text-muted me-1"></i>البريد الإلكتروني
                            </label>
                            <input type="email" id="email" name="email"
                                   class="form-control form-control-lg"
                                   placeholder="example@email.com"
                                   value="<?= $old['email'] ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="password">
                                <i class="fa-solid fa-lock text-muted me-1"></i>كلمة المرور
                            </label>
                            <div class="input-group">
                                <input type="password" id="password" name="password"
                                       class="form-control form-control-lg"
                                       placeholder="أدخل كلمة المرور" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button"
                                        data-target="password">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold">
                            <i class="fa-solid fa-right-to-bracket me-2"></i>تسجيل الدخول
                        </button>
                    </form>

                    <hr class="my-4">
                    <p class="text-center text-muted small">
                        ليس لديك حساب؟
                        <a href="/register.php" class="text-primary fw-semibold">إنشاء حساب جديد</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
