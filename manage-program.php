<?php
/**
 * Manage Program Evidence Files – نظام ادارة الشواهد الذكي
 * Allows the program owner to upload and delete evidence files.
 */
$pageTitle = 'إدارة شواهد البرنامج';
include __DIR__ . '/includes/header.php';
requireAuth();

$sb        = supabase();
$userId    = $_SESSION['user_id'] ?? '';
$programId = trim($_GET['id'] ?? '');

if (empty($programId) || empty($userId)) {
    header('Location: /dashboard.php');
    exit;
}

// Verify ownership
$progRes = $sb->select('programs', '*', [
    'id'      => "eq.{$programId}",
    'user_id' => "eq.{$userId}",
]);
$program = null;
foreach ($progRes as $item) {
    if (is_array($item) && isset($item['id'])) {
        $program = $item;
        break;
    }
}

if (!$program) {
    $_SESSION['flash'] = 'البرنامج غير موجود أو لا تملك صلاحية الوصول.';
    header('Location: /dashboard.php');
    exit;
}

// ── Allowed MIME types ────────────────────────────────────────────────────────
const ALLOWED_MIME = [
    // Images
    'image/jpeg'      => ['ext' => 'jpg',  'type' => 'image'],
    'image/png'       => ['ext' => 'png',  'type' => 'image'],
    'image/webp'      => ['ext' => 'webp', 'type' => 'image'],
    'image/gif'       => ['ext' => 'gif',  'type' => 'image'],
    // Videos
    'video/mp4'       => ['ext' => 'mp4',  'type' => 'video'],
    'video/webm'      => ['ext' => 'webm', 'type' => 'video'],
    'video/quicktime' => ['ext' => 'mov',  'type' => 'video'],
    'video/x-msvideo' => ['ext' => 'avi',  'type' => 'video'],
    // Documents
    'application/pdf' => ['ext' => 'pdf',  'type' => 'pdf'],
];

const MAX_IMAGE_SIZE = 20  * 1024 * 1024; // 20 MB
const MAX_VIDEO_SIZE = 200 * 1024 * 1024; // 200 MB
const MAX_DOC_SIZE   = 20  * 1024 * 1024; // 20 MB

// ── Handle file upload POST ───────────────────────────────────────────────────
$errors  = [];
$uploads = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['evidence_files'])) {
    $files = $_FILES['evidence_files'];

    // Normalize to array for multiple uploads
    $count = is_array($files['name']) ? count($files['name']) : 1;
    for ($idx = 0; $idx < $count; $idx++) {
        $name  = is_array($files['name'])     ? $files['name'][$idx]     : $files['name'];
        $tmp   = is_array($files['tmp_name']) ? $files['tmp_name'][$idx] : $files['tmp_name'];
        $size  = is_array($files['size'])     ? $files['size'][$idx]     : $files['size'];
        $err   = is_array($files['error'])    ? $files['error'][$idx]    : $files['error'];

        if ($err === UPLOAD_ERR_NO_FILE) continue;

        if ($err !== UPLOAD_ERR_OK) {
            $errors[] = "خطأ في رفع الملف \"{$name}\" (رمز: {$err}).";
            continue;
        }

        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmp);
        finfo_close($finfo);

        if (!array_key_exists($mimeType, ALLOWED_MIME)) {
            $errors[] = "نوع الملف \"{$name}\" غير مسموح.";
            continue;
        }

        $fileInfo = ALLOWED_MIME[$mimeType];
        $maxSize  = $fileInfo['type'] === 'video' ? MAX_VIDEO_SIZE : ($fileInfo['type'] === 'image' ? MAX_IMAGE_SIZE : MAX_DOC_SIZE);

        if ($size > $maxSize) {
            $maxMb = round($maxSize / 1024 / 1024);
            $errors[] = "الملف \"{$name}\" يتجاوز الحجم المسموح ({$maxMb} MB).";
            continue;
        }

        // Upload to Supabase Storage
        $fileId   = bin2hex(random_bytes(8));
        $ext      = $fileInfo['ext'];
        $path     = "{$userId}/{$programId}/{$fileId}.{$ext}";
        $upRes    = $sb->uploadFile('evidence', $path, $tmp, $mimeType);

        if (!empty($upRes['error']) && $upRes['_http_code'] !== 200) {
            $errors[] = "فشل رفع الملف \"{$name}\": " . ($upRes['message'] ?? 'خطأ غير معروف');
            continue;
        }

        $fileUrl = $sb->getPublicUrl('evidence', $path);

        // Insert record into evidence_files table
        $row = [
            'program_id' => $programId,
            'file_name'  => basename($name),
            'file_url'   => $fileUrl,
            'file_type'  => $fileInfo['type'],
            'file_size'  => $size,
        ];

        $insRes = $sb->insert('evidence_files', $row);
        if (!empty($insRes['error']) || !empty($insRes['code'])) {
            $errors[] = "فشل حفظ سجل الملف \"{$name}\": " . ($insRes['message'] ?? 'خطأ غير معروف');
        } else {
            $uploads[] = basename($name);
        }
    }

    if (!empty($uploads) && empty($errors)) {
        $_SESSION['flash_manage'] = 'تم رفع ' . count($uploads) . ' ملف(ات) بنجاح.';
    }

    // Redirect to avoid re-POST on refresh
    if (empty($errors)) {
        header('Location: /manage-program.php?id=' . urlencode($programId));
        exit;
    }
}

// Flash message for this page
$flashManage = $_SESSION['flash_manage'] ?? '';
unset($_SESSION['flash_manage']);

// ── Fetch existing evidence files ─────────────────────────────────────────────
$filesRes      = $sb->select('evidence_files', '*', [
    'program_id' => "eq.{$programId}",
    'order'      => 'created_at.desc',
]);
$evidenceFiles = array_filter(is_array($filesRes) ? $filesRes : [], fn($v) => is_array($v) && isset($v['id']));

$shareUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . '/program.php?slug=' . urlencode($program['slug'] ?? '');

$iconForType = [
    'image' => ['icon' => 'fa-image',    'color' => 'text-primary'],
    'video' => ['icon' => 'fa-film',     'color' => 'text-danger'],
    'pdf'   => ['icon' => 'fa-file-pdf', 'color' => 'text-danger'],
    'other' => ['icon' => 'fa-file',     'color' => 'text-secondary'],
];
?>

<div class="container py-5">

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="/dashboard.php"><i class="fa-solid fa-gauge-high me-1"></i>لوحة التحكم</a>
            </li>
            <li class="breadcrumb-item active">إدارة شواهد البرنامج</li>
        </ol>
    </nav>

    <!-- Program info -->
    <div class="dashboard-banner card border-0 shadow-sm mb-4 p-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="fa-solid fa-folder-open text-warning me-2"></i>
                    <?= htmlspecialchars($program['program_name']) ?>
                </h4>
                <p class="text-muted mb-0">
                    <i class="fa-solid fa-building me-1"></i>
                    <?= htmlspecialchars($program['organization']) ?>
                </p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="<?= htmlspecialchars($shareUrl) ?>" target="_blank"
                   class="btn btn-outline-primary">
                    <i class="fa-solid fa-eye me-1"></i>عرض الشواهد
                </a>
                <button class="btn btn-outline-secondary copy-btn"
                        data-url="<?= htmlspecialchars($shareUrl) ?>">
                    <i class="fa-solid fa-copy me-1"></i>نسخ الرابط
                </button>
            </div>
        </div>
    </div>

    <!-- Flash / Error messages -->
    <?php if ($flashManage): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            <?= htmlspecialchars($flashManage) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- Upload panel -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-primary text-white py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="fa-solid fa-cloud-arrow-up me-2"></i>رفع ملفات شواهد
                    </h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST"
                          action="/manage-program.php?id=<?= urlencode($programId) ?>"
                          enctype="multipart/form-data" novalidate>

                        <div class="mb-3">
                            <div class="upload-area" id="uploadArea">
                                <input type="file" id="evidence_files" name="evidence_files[]"
                                       class="d-none" multiple
                                       accept=".jpg,.jpeg,.png,.webp,.gif,.mp4,.webm,.mov,.avi,.pdf">
                                <div class="upload-placeholder text-center py-4" id="uploadPlaceholder">
                                    <i class="fa-solid fa-cloud-arrow-up fa-3x text-muted mb-2"></i>
                                    <p class="mb-1 fw-semibold">اسحب الملفات هنا أو</p>
                                    <button type="button" class="btn btn-outline-primary btn-sm"
                                            onclick="document.getElementById('evidence_files').click()">
                                        اختر ملفات
                                    </button>
                                </div>
                                <div id="uploadPreview" class="d-none text-center py-3">
                                    <i class="fa-solid fa-file-circle-check fa-3x text-success mb-2"></i>
                                    <p class="mb-1 fw-semibold" id="uploadFileName"></p>
                                    <button type="button" class="btn btn-sm btn-outline-danger mt-1" id="removeFile">
                                        <i class="fa-solid fa-times me-1"></i>إزالة
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p class="text-muted small mb-3">
                            <i class="fa-solid fa-info-circle me-1"></i>
                            صور (20MB)، فيديوهات (200MB)، PDF (20MB)<br>
                            يمكن اختيار عدة ملفات دفعة واحدة.
                        </p>

                        <button type="submit" class="btn btn-primary w-100 fw-bold">
                            <i class="fa-solid fa-upload me-2"></i>رفع الملفات
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Evidence files list -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
                    <h6 class="fw-bold mb-0">
                        <i class="fa-solid fa-photo-film text-primary me-2"></i>
                        الملفات المرفوعة
                        <span class="badge bg-primary rounded-pill ms-1"><?= count($evidenceFiles) ?></span>
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($evidenceFiles)): ?>
                        <div class="text-center py-5">
                            <i class="fa-solid fa-folder-open fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted">لا توجد ملفات مرفوعة بعد. ارفع أول ملف شاهد!</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($evidenceFiles as $ef): ?>
                                <?php
                                    $type    = $ef['file_type'] ?? 'other';
                                    $typeInfo = $iconForType[$type] ?? $iconForType['other'];
                                    $sizeMb  = $ef['file_size'] ? round($ef['file_size'] / 1024 / 1024, 2) : null;
                                ?>
                                <div class="list-group-item list-group-item-action px-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <!-- Thumbnail / icon -->
                                        <div class="file-thumb flex-shrink-0">
                                            <?php if ($type === 'image'): ?>
                                                <img src="<?= htmlspecialchars($ef['file_url']) ?>"
                                                     alt="<?= htmlspecialchars($ef['file_name']) ?>"
                                                     class="rounded" style="width:52px;height:52px;object-fit:cover"
                                                     loading="lazy">
                                            <?php else: ?>
                                                <div class="file-icon-box <?= htmlspecialchars($typeInfo['color']) ?>">
                                                    <i class="fa-solid <?= htmlspecialchars($typeInfo['icon']) ?> fa-2x"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- File info -->
                                        <div class="flex-grow-1 overflow-hidden">
                                            <p class="mb-0 fw-semibold text-truncate">
                                                <?= htmlspecialchars($ef['file_name']) ?>
                                            </p>
                                            <small class="text-muted">
                                                <?php
                                                    $typeLabel = ['image' => 'صورة', 'video' => 'فيديو', 'pdf' => 'PDF', 'other' => 'ملف'];
                                                    echo htmlspecialchars($typeLabel[$type] ?? 'ملف');
                                                    if ($sizeMb !== null) echo " · {$sizeMb} MB";
                                                    if (!empty($ef['created_at'])) {
                                                        echo ' · ' . date('Y/m/d', strtotime($ef['created_at']));
                                                    }
                                                ?>
                                            </small>
                                        </div>

                                        <!-- Actions -->
                                        <div class="d-flex gap-2 flex-shrink-0">
                                            <a href="<?= htmlspecialchars($ef['file_url']) ?>"
                                               class="btn btn-sm btn-outline-primary"
                                               target="_blank" rel="noopener noreferrer"
                                               title="عرض / تحميل">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                            <a href="/delete-evidence.php?id=<?= urlencode($ef['id']) ?>&program_id=<?= urlencode($programId) ?>"
                                               class="btn btn-sm btn-outline-danger confirm-delete"
                                               title="حذف الملف">
                                                <i class="fa-solid fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- /.row -->
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
