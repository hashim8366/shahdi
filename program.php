<?php
/**
 * Public Program Evidence Gallery – نظام ادارة الشواهد الذكي
 * URL: /program.php?slug=<slug>
 */
$pageTitle = 'عرض الشواهد';
include __DIR__ . '/includes/header.php';

$slug = trim($_GET['slug'] ?? '');

if (empty($slug) || strlen($slug) > 32) {
    http_response_code(404);
    echo '<div class="container py-5 text-center">
            <i class="fa-solid fa-triangle-exclamation fa-4x text-warning mb-3 d-block"></i>
            <h4>الرابط غير موجود</h4>
            <a href="/" class="btn btn-primary mt-3">العودة للرئيسية</a>
          </div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

if (!preg_match('/^[a-f0-9]+$/', $slug)) {
    http_response_code(400);
    echo '<div class="container py-5 text-center">
            <h4>رابط غير صحيح</h4>
            <a href="/" class="btn btn-primary mt-3">العودة للرئيسية</a>
          </div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$sb  = new Supabase(); // public read – no auth token needed
$res = $sb->select('programs', '*', ['slug' => "eq.{$slug}"]);

$program = null;
foreach ($res as $item) {
    if (is_array($item) && isset($item['id'])) {
        $program = $item;
        break;
    }
}

if (!$program) {
    http_response_code(404);
    ?>
    <div class="container py-5 text-center">
        <i class="fa-solid fa-triangle-exclamation fa-4x text-warning mb-3 d-block"></i>
        <h4 class="fw-bold">البرنامج غير موجود</h4>
        <p class="text-muted">ربما تم حذف هذا البرنامج أو الرابط غير صحيح.</p>
        <a href="/" class="btn btn-primary mt-3">
            <i class="fa-solid fa-house me-2"></i>العودة للرئيسية
        </a>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Fetch evidence files for this program
$filesRes     = $sb->select('evidence_files', '*', [
    'program_id' => "eq.{$program['id']}",
    'order'      => 'created_at.asc',
]);
$evidenceFiles = array_filter(is_array($filesRes) ? $filesRes : [], fn($v) => is_array($v) && isset($v['id']));

$pageTitle = 'شواهد: ' . ($program['program_name'] ?? '');

$shareUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . '/program.php?slug=' . urlencode($slug);

// Helpers
$iconForType = [
    'image' => 'fa-image',
    'video' => 'fa-film',
    'pdf'   => 'fa-file-pdf',
    'other' => 'fa-file',
];
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <!-- Program header card -->
            <div class="program-view card border-0 shadow-lg overflow-hidden mb-4">
                <div class="prog-header text-white text-center py-5 position-relative">
                    <div class="prog-pattern"></div>
                    <i class="fa-solid fa-photo-film fa-4x text-warning mb-3 d-block position-relative"></i>
                    <h2 class="fw-bold mb-1 position-relative">
                        <?= htmlspecialchars($program['program_name'] ?? '') ?>
                    </h2>
                    <p class="opacity-75 mb-0 position-relative">
                        <i class="fa-solid fa-building me-1"></i>
                        <?= htmlspecialchars($program['organization'] ?? '') ?>
                    </p>
                </div>

                <div class="card-body p-4">
                    <div class="row g-3">
                        <?php if (!empty($program['description'])): ?>
                        <div class="col-md-8">
                            <div class="detail-item d-flex align-items-start gap-3">
                                <div class="detail-icon">
                                    <i class="fa-solid fa-align-right text-primary fa-lg"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-1">وصف البرنامج</p>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($program['description'])) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-4">
                            <div class="detail-item d-flex align-items-start gap-3">
                                <div class="detail-icon">
                                    <i class="fa-solid fa-calendar-check text-primary fa-lg"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-1">تاريخ الإنشاء</p>
                                    <h6 class="fw-bold mb-0">
                                        <?= isset($program['created_at'])
                                            ? date('d / m / Y', strtotime($program['created_at']))
                                            : '' ?>
                                    </h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Share URL -->
                    <hr class="my-4">
                    <h6 class="fw-bold mb-3">
                        <i class="fa-solid fa-share-nodes text-primary me-2"></i>مشاركة هذه الشواهد
                    </h6>
                    <div class="input-group">
                        <input type="text" class="form-control share-url"
                               id="shareUrlInput"
                               value="<?= htmlspecialchars($shareUrl) ?>" readonly>
                        <button class="btn btn-primary copy-btn" type="button"
                                data-url="<?= htmlspecialchars($shareUrl) ?>">
                            <i class="fa-solid fa-copy me-1"></i>نسخ الرابط
                        </button>
                    </div>
                </div>
            </div>

            <!-- Evidence files gallery -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fa-solid fa-photo-film text-primary me-2"></i>
                        ملفات الشواهد
                        <span class="badge bg-primary rounded-pill ms-2"><?= count($evidenceFiles) ?></span>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($evidenceFiles)): ?>
                        <div class="text-center py-5">
                            <i class="fa-solid fa-folder-open fa-4x text-muted mb-3 d-block"></i>
                            <p class="text-muted">لا توجد ملفات شواهد في هذا البرنامج بعد.</p>
                        </div>
                    <?php else: ?>

                        <!-- Images sub-section -->
                        <?php
                            $images = array_filter($evidenceFiles, fn($f) => ($f['file_type'] ?? '') === 'image');
                            $videos = array_filter($evidenceFiles, fn($f) => ($f['file_type'] ?? '') === 'video');
                            $pdfs   = array_filter($evidenceFiles, fn($f) => ($f['file_type'] ?? '') === 'pdf');
                            $others = array_filter($evidenceFiles, fn($f) => !in_array($f['file_type'] ?? '', ['image', 'video', 'pdf']));
                        ?>

                        <?php if (!empty($images)): ?>
                            <h6 class="fw-bold text-muted mb-3">
                                <i class="fa-solid fa-images text-primary me-2"></i>الصور
                                <span class="badge bg-secondary"><?= count($images) ?></span>
                            </h6>
                            <div class="row g-3 mb-4" id="imageGallery">
                                <?php foreach ($images as $img): ?>
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="gallery-item card border-0 shadow-sm overflow-hidden"
                                             data-src="<?= htmlspecialchars($img['file_url']) ?>"
                                             data-name="<?= htmlspecialchars($img['file_name']) ?>">
                                            <img src="<?= htmlspecialchars($img['file_url']) ?>"
                                                 alt="<?= htmlspecialchars($img['file_name']) ?>"
                                                 class="img-fluid gallery-thumb"
                                                 loading="lazy">
                                            <div class="gallery-overlay">
                                                <i class="fa-solid fa-magnifying-glass-plus fa-lg text-white"></i>
                                            </div>
                                        </div>
                                        <p class="text-muted small text-center mt-1 text-truncate px-1">
                                            <?= htmlspecialchars($img['file_name']) ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($videos)): ?>
                            <h6 class="fw-bold text-muted mb-3">
                                <i class="fa-solid fa-film text-danger me-2"></i>الفيديوهات
                                <span class="badge bg-secondary"><?= count($videos) ?></span>
                            </h6>
                            <div class="row g-3 mb-4">
                                <?php foreach ($videos as $vid): ?>
                                    <div class="col-12 col-md-6">
                                        <div class="card border-0 shadow-sm overflow-hidden">
                                            <video controls class="w-100" style="max-height:300px"
                                                   preload="metadata">
                                                <source src="<?= htmlspecialchars($vid['file_url']) ?>">
                                                متصفحك لا يدعم تشغيل الفيديو.
                                            </video>
                                            <div class="card-body py-2 px-3">
                                                <p class="mb-0 small text-muted text-truncate">
                                                    <i class="fa-solid fa-film text-danger me-1"></i>
                                                    <?= htmlspecialchars($vid['file_name']) ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($pdfs) || !empty($others)): ?>
                            <h6 class="fw-bold text-muted mb-3">
                                <i class="fa-solid fa-file text-warning me-2"></i>ملفات أخرى
                                <span class="badge bg-secondary"><?= count($pdfs) + count($others) ?></span>
                            </h6>
                            <div class="row g-3 mb-2">
                                <?php foreach (array_merge(array_values($pdfs), array_values($others)) as $file): ?>
                                    <div class="col-12 col-md-6">
                                        <div class="card border-0 shadow-sm p-3 d-flex flex-row align-items-center gap-3">
                                            <div class="file-type-icon <?= $file['file_type'] === 'pdf' ? 'text-danger' : 'text-secondary' ?>">
                                                <i class="fa-solid <?= htmlspecialchars($iconForType[$file['file_type']] ?? 'fa-file') ?> fa-2x"></i>
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden">
                                                <p class="mb-0 fw-semibold text-truncate">
                                                    <?= htmlspecialchars($file['file_name']) ?>
                                                </p>
                                            </div>
                                            <a href="<?= htmlspecialchars($file['file_url']) ?>"
                                               class="btn btn-sm btn-outline-primary flex-shrink-0"
                                               target="_blank" rel="noopener noreferrer">
                                                <i class="fa-solid fa-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
            </div>

            <!-- Verified footer note -->
            <div class="text-center mt-4">
                <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">
                    <i class="fa-solid fa-shield-check me-1"></i>
                    موثّق عبر نظام ادارة الشواهد الذكي
                </span>
            </div>

        </div>
    </div>
</div>

<!-- Lightbox modal -->
<div class="modal fade" id="lightboxModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-0">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-white" id="lightboxTitle"></h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-3">
                <img id="lightboxImg" src="" alt="" class="img-fluid rounded" style="max-height:80vh">
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <a id="lightboxDownload" href="#" class="btn btn-outline-light btn-sm"
                   target="_blank" rel="noopener noreferrer" download>
                    <i class="fa-solid fa-download me-1"></i>تحميل
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

