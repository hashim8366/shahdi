<?php
/**
 * Create Program – نظام ادارة الشواهد الذكي
 * Creates a new program/event record (no file upload here – done on manage-program page).
 */
$pageTitle = 'إنشاء برنامج جديد';
include __DIR__ . '/includes/header.php';
requireAuth();

$errors = [];
$old    = [
    'program_name' => '',
    'description'  => '',
    'organization' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programName  = trim($_POST['program_name']  ?? '');
    $description  = trim($_POST['description']   ?? '');
    $organization = trim($_POST['organization']  ?? '');

    $old['program_name']  = $programName;
    $old['description']   = $description;
    $old['organization']  = $organization;

    if (empty($programName))  $errors[] = 'الرجاء إدخال اسم البرنامج أو الفعالية.';
    if (empty($organization)) $errors[] = 'الرجاء إدخال الجهة المنفذة.';

    if (empty($errors)) {
        $sb     = supabase();
        $userId = $_SESSION['user_id'] ?? '';
        $slug   = bin2hex(random_bytes(8)); // 16-char unique slug

        $row = [
            'user_id'      => $userId,
            'program_name' => $programName,
            'description'  => $description,
            'organization' => $organization,
            'slug'         => $slug,
        ];

        $res = $sb->insert('programs', $row);

        if (!empty($res['error']) || !empty($res['code'])) {
            $errors[] = 'فشل حفظ البرنامج: ' . ($res['message'] ?? $res['error'] ?? 'خطأ غير معروف');
        } else {
            // Retrieve inserted ID from response (Supabase returns array of rows)
            $insertedId = null;
            foreach ($res as $item) {
                if (is_array($item) && isset($item['id'])) {
                    $insertedId = $item['id'];
                    break;
                }
            }

            if ($insertedId) {
                $_SESSION['flash'] = 'تم إنشاء البرنامج بنجاح! أضف الآن ملفات الشواهد.';
                header('Location: /manage-program.php?id=' . urlencode($insertedId));
            } else {
                $_SESSION['flash'] = 'تم إنشاء البرنامج بنجاح.';
                header('Location: /dashboard.php');
            }
            exit;
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="/dashboard.php"><i class="fa-solid fa-gauge-high me-1"></i>لوحة التحكم</a>
                    </li>
                    <li class="breadcrumb-item active">إنشاء برنامج جديد</li>
                </ol>
            </nav>

            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-folder-plus me-2"></i>إنشاء برنامج / فعالية جديدة
                    </h5>
                </div>
                <div class="card-body p-4 p-md-5">

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= htmlspecialchars($e) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="/create-program.php" novalidate>

                        <div class="mb-4">
                            <label class="form-label fw-semibold required-label" for="program_name">
                                <i class="fa-solid fa-bookmark text-primary me-1"></i>اسم البرنامج أو الفعالية
                            </label>
                            <input type="text" id="program_name" name="program_name"
                                   class="form-control form-control-lg"
                                   placeholder="مثال: ورشة تطوير الكوادر 2025"
                                   value="<?= htmlspecialchars($old['program_name']) ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold required-label" for="organization">
                                <i class="fa-solid fa-building text-primary me-1"></i>الجهة المنفذة
                            </label>
                            <input type="text" id="organization" name="organization"
                                   class="form-control form-control-lg"
                                   placeholder="مثال: إدارة التدريب والتطوير"
                                   value="<?= htmlspecialchars($old['organization']) ?>" required>
                        </div>

                        <div class="mb-5">
                            <label class="form-label fw-semibold" for="description">
                                <i class="fa-solid fa-align-right text-primary me-1"></i>وصف البرنامج
                                <span class="text-muted fw-normal small">(اختياري)</span>
                            </label>
                            <textarea id="description" name="description"
                                      class="form-control" rows="4"
                                      placeholder="أدخل وصفاً مختصراً..."><?= htmlspecialchars($old['description']) ?></textarea>
                        </div>

                        <div class="d-flex gap-3 flex-wrap">
                            <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold">
                                <i class="fa-solid fa-arrow-left me-2"></i>إنشاء والانتقال لرفع الشواهد
                            </button>
                            <a href="/dashboard.php" class="btn btn-outline-secondary btn-lg px-4">
                                إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

