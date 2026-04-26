<?php
/**
 * Public Certificate View – نظام ادارة الشواهد الذكي
 * URL: /certificate.php?slug=<slug>
 */
$pageTitle = 'عرض الشهادة';
include __DIR__ . '/includes/header.php';

$slug = trim($_GET['slug'] ?? '');

if (empty($slug) || strlen($slug) > 32) {
    http_response_code(404);
    $pageTitle = 'الشهادة غير موجودة';
    echo '<div class="container py-5 text-center">
            <i class="fa-solid fa-triangle-exclamation fa-4x text-warning mb-3 d-block"></i>
            <h4>الشهادة غير موجودة</h4>
            <a href="/" class="btn btn-primary mt-3">العودة للرئيسية</a>
          </div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

// Only alphanumeric slugs are valid (generated via bin2hex)
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
$res = $sb->select('certificates', '*', ['slug' => "eq.{$slug}"]);

// The result is an array of rows
$cert = null;
foreach ($res as $item) {
    if (is_array($item) && isset($item['id'])) {
        $cert = $item;
        break;
    }
}

if (!$cert) {
    http_response_code(404);
    ?>
    <div class="container py-5 text-center">
        <i class="fa-solid fa-triangle-exclamation fa-4x text-warning mb-3 d-block"></i>
        <h4 class="fw-bold">الشهادة غير موجودة</h4>
        <p class="text-muted">ربما تم حذف هذه الشهادة أو الرابط غير صحيح.</p>
        <a href="/" class="btn btn-primary mt-3">
            <i class="fa-solid fa-house me-2"></i>العودة للرئيسية
        </a>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = 'شهادة: ' . ($cert['program_name'] ?? '');
$certUrl   = $cert['certificate_url'] ?? '';
$parsedPath = $certUrl ? parse_url($certUrl, PHP_URL_PATH) : null;
$isPdf     = $parsedPath !== null && str_ends_with(strtolower($parsedPath), '.pdf');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- Certificate Card -->
            <div class="certificate-view card border-0 shadow-lg overflow-hidden">

                <!-- Decorative header -->
                <div class="cert-header text-white text-center py-5 position-relative">
                    <div class="cert-pattern"></div>
                    <i class="fa-solid fa-certificate fa-4x text-warning mb-3 d-block position-relative"></i>
                    <h2 class="fw-bold mb-1 position-relative">شهادة إتمام</h2>
                    <p class="opacity-75 mb-0 position-relative">نظام ادارة الشواهد الذكي</p>
                </div>

                <div class="card-body p-4 p-md-5">

                    <!-- Program name -->
                    <div class="text-center mb-4">
                        <p class="text-muted mb-1">البرنامج / الدورة</p>
                        <h3 class="fw-bold text-primary">
                            <?= htmlspecialchars($cert['program_name'] ?? '') ?>
                        </h3>
                    </div>

                    <hr>

                    <!-- Details grid -->
                    <div class="row g-4 my-2">
                        <div class="col-md-6">
                            <div class="detail-item d-flex align-items-start gap-3">
                                <div class="detail-icon">
                                    <i class="fa-solid fa-user-graduate text-primary fa-lg"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-1">اسم الحاصل على الشهادة</p>
                                    <h5 class="fw-bold mb-0">
                                        <?= htmlspecialchars($cert['holder_name'] ?? '') ?>
                                    </h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item d-flex align-items-start gap-3">
                                <div class="detail-icon">
                                    <i class="fa-solid fa-building-columns text-primary fa-lg"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-1">الجهة المنفذة</p>
                                    <h5 class="fw-bold mb-0">
                                        <?= htmlspecialchars($cert['organization'] ?? '') ?>
                                    </h5>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($cert['description'])): ?>
                        <div class="col-12">
                            <div class="detail-item d-flex align-items-start gap-3">
                                <div class="detail-icon">
                                    <i class="fa-solid fa-align-right text-primary fa-lg"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-1">وصف البرنامج</p>
                                    <p class="mb-0 lh-lg">
                                        <?= nl2br(htmlspecialchars($cert['description'])) ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="col-md-6">
                            <div class="detail-item d-flex align-items-start gap-3">
                                <div class="detail-icon">
                                    <i class="fa-solid fa-calendar-check text-primary fa-lg"></i>
                                </div>
                                <div>
                                    <p class="text-muted small mb-1">تاريخ الإصدار</p>
                                    <h6 class="fw-bold mb-0">
                                        <?= isset($cert['created_at'])
                                            ? date('d / m / Y', strtotime($cert['created_at']))
                                            : '' ?>
                                    </h6>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Certificate file preview -->
                    <?php if ($certUrl): ?>
                        <hr class="my-4">
                        <h6 class="fw-bold mb-3">
                            <i class="fa-solid fa-paperclip text-primary me-2"></i>ملف الشهادة
                        </h6>
                        <?php if ($isPdf): ?>
                            <div class="ratio ratio-4x3 mb-3 rounded overflow-hidden shadow-sm">
                                <iframe src="<?= htmlspecialchars($certUrl) ?>"
                                        title="ملف الشهادة"></iframe>
                            </div>
                        <?php else: ?>
                            <div class="text-center">
                                <img src="<?= htmlspecialchars($certUrl) ?>"
                                     alt="ملف الشهادة"
                                     class="img-fluid rounded shadow-sm"
                                     style="max-height:500px">
                            </div>
                        <?php endif; ?>
                        <div class="text-center mt-3">
                            <a href="<?= htmlspecialchars($certUrl) ?>"
                               class="btn btn-outline-primary"
                               target="_blank" rel="noopener noreferrer">
                                <i class="fa-solid fa-download me-2"></i>تحميل الشهادة
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Share section -->
                    <hr class="my-4">
                    <div class="share-section">
                        <h6 class="fw-bold mb-3">
                            <i class="fa-solid fa-share-nodes text-primary me-2"></i>مشاركة الشهادة
                        </h6>
                        <?php
                            $shareUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                                . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                                . '/certificate.php?slug=' . urlencode($slug);
                        ?>
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

                </div><!-- /.card-body -->

                <!-- Verified footer -->
                <div class="cert-footer bg-light py-3 px-4 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-shield-check text-success fa-lg"></i>
                    <span class="small text-muted">
                        هذه الشهادة موثّقة عبر نظام ادارة الشواهد الذكي
                    </span>
                </div>
            </div><!-- /.certificate-view -->

        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
