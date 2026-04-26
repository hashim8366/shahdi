<?php
/**
 * Create Certificate – نظام ادارة الشواهد الذكي
 */
$pageTitle = 'إنشاء شهادة جديدة';
include __DIR__ . '/includes/header.php';
requireAuth();

$errors  = [];
$success = '';
$old = [
    'program_name'  => '',
    'description'   => '',
    'holder_name'   => '',
    'organization'  => '',
];

// ── Allowed file MIME types ───────────────────────────────────────────────────
const ALLOWED_MIME = [
    'application/pdf'  => 'pdf',
    'image/jpeg'       => 'jpg',
    'image/png'        => 'png',
    'image/webp'       => 'webp',
];
const MAX_FILE_SIZE = 10 * 1024 * 1024; // 10 MB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $programName  = trim($_POST['program_name']  ?? '');
    $description  = trim($_POST['description']   ?? '');
    $holderName   = trim($_POST['holder_name']   ?? '');
    $organization = trim($_POST['organization']  ?? '');

    $old['program_name']  = $programName;
    $old['description']   = $description;
    $old['holder_name']   = $holderName;
    $old['organization']  = $organization;

    // ── Text validation ───────────────────────────────────────────────────────
    if (empty($programName))  $errors[] = 'الرجاء إدخال اسم البرنامج.';
    if (empty($holderName))   $errors[] = 'الرجاء إدخال الاسم.';
    if (empty($organization)) $errors[] = 'الرجاء إدخال الجهة المنفذة.';

    // ── File validation ───────────────────────────────────────────────────────
    $fileUrl = null;
    if (!empty($_FILES['certificate_file']['name'])) {
        $file     = $_FILES['certificate_file'];
        $tmpPath  = $file['tmp_name'];
        $origName = basename($file['name']);
        $fileSize = $file['size'];
        $fileError = $file['error'];

        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = 'حدث خطأ أثناء رفع الملف (رمز الخطأ: ' . $fileError . ').';
        } elseif ($fileSize > MAX_FILE_SIZE) {
            $errors[] = 'حجم الملف يتجاوز الحد المسموح (10 ميغابايت).';
        } else {
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $tmpPath);
            finfo_close($finfo);

            if (!array_key_exists($mimeType, ALLOWED_MIME)) {
                $errors[] = 'نوع الملف غير مسموح. المسموح: PDF، JPG، PNG، WEBP.';
            }
        }
    }

    if (empty($errors)) {
        $sb     = supabase();
        $userId = $_SESSION['user_id'] ?? '';
        $slug   = bin2hex(random_bytes(8)); // 16-char unique slug

        // Upload file to Supabase Storage (if provided)
        if (!empty($_FILES['certificate_file']['name']) && isset($tmpPath, $mimeType)) {
            $ext      = ALLOWED_MIME[$mimeType];
            $filePath = "{$userId}/{$slug}.{$ext}";
            $upRes    = $sb->uploadFile('certificates', $filePath, $tmpPath, $mimeType);

            if (!empty($upRes['error'])) {
                $errors[] = 'فشل رفع الملف: ' . ($upRes['message'] ?? 'خطأ غير معروف');
            } else {
                $fileUrl = $sb->getPublicUrl('certificates', $filePath);
            }
        }

        if (empty($errors)) {
            $row = [
                'user_id'          => $userId,
                'program_name'     => $programName,
                'description'      => $description,
                'holder_name'      => $holderName,
                'organization'     => $organization,
                'certificate_url'  => $fileUrl,
                'slug'             => $slug,
            ];

            $res = $sb->insert('certificates', $row);

            if (!empty($res['error']) || !empty($res['code'])) {
                $errors[] = 'فشل حفظ الشهادة: ' . ($res['message'] ?? $res['error'] ?? 'خطأ غير معروف');
            } else {
                $_SESSION['flash'] = 'تم إنشاء الشهادة بنجاح! يمكنك الآن مشاركة الرابط.';
                header('Location: /dashboard.php');
                exit;
            }
        }
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="/dashboard.php"><i class="fa-solid fa-gauge-high me-1"></i>لوحة التحكم</a>
                    </li>
                    <li class="breadcrumb-item active">إنشاء شهادة جديدة</li>
                </ol>
            </nav>

            <div class="card shadow border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0 fw-bold">
                        <i class="fa-solid fa-plus-circle me-2"></i>إنشاء رابط شاهد جديد
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

                    <form method="POST" action="/create-certificate.php"
                          enctype="multipart/form-data" novalidate>

                        <!-- Program name -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold required-label" for="program_name">
                                <i class="fa-solid fa-book text-primary me-1"></i>اسم البرنامج
                            </label>
                            <input type="text" id="program_name" name="program_name"
                                   class="form-control form-control-lg"
                                   placeholder="مثال: دورة تطوير الويب"
                                   value="<?= $old['program_name'] ?>" required>
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold" for="description">
                                <i class="fa-solid fa-align-right text-primary me-1"></i>وصف البرنامج
                            </label>
                            <textarea id="description" name="description"
                                      class="form-control" rows="4"
                                      placeholder="أدخل وصفاً مختصراً للبرنامج..."><?= $old['description'] ?></textarea>
                        </div>

                        <!-- Holder name -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold required-label" for="holder_name">
                                <i class="fa-solid fa-user-graduate text-primary me-1"></i>اسم الحاصل على الشهادة
                            </label>
                            <input type="text" id="holder_name" name="holder_name"
                                   class="form-control form-control-lg"
                                   placeholder="الاسم الكامل"
                                   value="<?= $old['holder_name'] ?>" required>
                        </div>

                        <!-- Organization -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold required-label" for="organization">
                                <i class="fa-solid fa-building text-primary me-1"></i>الجهة المنفذة للبرنامج
                            </label>
                            <input type="text" id="organization" name="organization"
                                   class="form-control form-control-lg"
                                   placeholder="مثال: جامعة الملك عبدالله"
                                   value="<?= $old['organization'] ?>" required>
                        </div>

                        <!-- Certificate file -->
                        <div class="mb-5">
                            <label class="form-label fw-semibold" for="certificate_file">
                                <i class="fa-solid fa-paperclip text-primary me-1"></i>رفع ملف الشهادة
                                <span class="text-muted small fw-normal">(اختياري – PDF / JPG / PNG / WEBP – حجم أقصى 10MB)</span>
                            </label>
                            <div class="upload-area" id="uploadArea">
                                <input type="file" id="certificate_file" name="certificate_file"
                                       class="d-none" accept=".pdf,.jpg,.jpeg,.png,.webp">
                                <div class="upload-placeholder text-center py-4" id="uploadPlaceholder">
                                    <i class="fa-solid fa-cloud-arrow-up fa-3x text-muted mb-2"></i>
                                    <p class="mb-1 fw-semibold">اسحب الملف هنا أو</p>
                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="document.getElementById('certificate_file').click()">
                                        اختر ملفاً
                                    </button>
                                </div>
                                <div class="upload-preview text-center py-3 d-none" id="uploadPreview">
                                    <i class="fa-solid fa-file-circle-check fa-3x text-success mb-2"></i>
                                    <p class="mb-1 fw-semibold" id="uploadFileName"></p>
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-1"
                                            id="removeFile">
                                        <i class="fa-solid fa-times me-1"></i>إزالة الملف
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3 flex-wrap">
                            <button type="submit" class="btn btn-primary btn-lg px-5 fw-bold">
                                <i class="fa-solid fa-paper-plane me-2"></i>إنشاء الشهادة
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
