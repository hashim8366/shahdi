<?php
/**
 * User Dashboard – نظام ادارة الشواهد الذكي
 */
$pageTitle = 'لوحة التحكم';
include __DIR__ . '/includes/header.php';
requireAuth();

$sb       = supabase();
$userId   = $_SESSION['user_id']   ?? '';
$userName = $_SESSION['user_name'] ?? 'المستخدم';

// ── Fetch user's certificates ─────────────────────────────────────────────────
$certs = [];
if ($userId) {
    $res = $sb->select(
        'certificates',
        'id,program_name,holder_name,organization,created_at,slug',
        ['user_id' => "eq.{$userId}", 'order' => 'created_at.desc']
    );
    // Supabase returns an array of rows (or an error object)
    if (isset($res[0]) || (is_array($res) && !isset($res['error']))) {
        // Filter out internal _http_code key
        $certs = array_filter($res, fn($v) => is_array($v));
    }
}

// ── Flash message ─────────────────────────────────────────────────────────────
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);
?>

<div class="container py-5">

    <!-- Welcome banner -->
    <div class="dashboard-banner card border-0 shadow-sm mb-4 p-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div>
                <h4 class="fw-bold mb-1">
                    <i class="fa-solid fa-hand-wave text-warning me-2"></i>
                    مرحباً، <?= htmlspecialchars($userName) ?>!
                </h4>
                <p class="text-muted mb-0">إدارة شواهدك في مكان واحد</p>
            </div>
            <a href="/create-certificate.php"
               class="btn btn-primary btn-lg px-4 shadow-sm fw-bold create-btn">
                <i class="fa-solid fa-plus me-2"></i>أنشئ رابط شاهد جديد
            </a>
        </div>
    </div>

    <!-- Flash message -->
    <?php if ($flash): ?>
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            <?= htmlspecialchars($flash) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Stats row -->
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="stat-card card border-0 shadow-sm p-3 text-center">
                <div class="stat-icon mb-2">
                    <i class="fa-solid fa-certificate fa-2x text-primary"></i>
                </div>
                <h3 class="fw-bold mb-0"><?= count($certs) ?></h3>
                <p class="text-muted small mb-0">إجمالي الشواهد</p>
            </div>
        </div>
    </div>

    <!-- Certificates table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
            <h5 class="fw-bold mb-0">
                <i class="fa-solid fa-list-check text-primary me-2"></i>شواهدي
            </h5>
            <a href="/create-certificate.php" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-plus me-1"></i>جديد
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($certs)): ?>
                <div class="text-center py-5">
                    <i class="fa-solid fa-inbox fa-4x text-muted mb-3 d-block"></i>
                    <h6 class="text-muted">لا توجد شواهد بعد</h6>
                    <p class="text-muted small">اضغط على "أنشئ رابط شاهد جديد" للبدء</p>
                    <a href="/create-certificate.php" class="btn btn-primary mt-2">
                        <i class="fa-solid fa-plus me-2"></i>أنشئ أول شهادة
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>اسم البرنامج</th>
                                <th>اسم الحاصل</th>
                                <th>الجهة المنفذة</th>
                                <th>التاريخ</th>
                                <th>الرابط</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($certs as $cert): ?>
                                <?php if (!isset($cert['id'])) continue; ?>
                                <tr>
                                    <td class="text-muted"><?= $i++ ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($cert['program_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($cert['holder_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($cert['organization'] ?? '') ?></td>
                                    <td class="text-muted small">
                                        <?= isset($cert['created_at'])
                                            ? date('Y/m/d', strtotime($cert['created_at']))
                                            : '' ?>
                                    </td>
                                    <td>
                                        <?php $slug = $cert['slug'] ?? ''; ?>
                                        <?php if ($slug):
                                            $shareUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                                                . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                                                . '/certificate.php?slug=' . urlencode($slug);
                                        ?>
                                            <div class="input-group input-group-sm" style="min-width:220px">
                                                <input type="text" class="form-control share-url"
                                                       value="<?= htmlspecialchars($shareUrl) ?>"
                                                       readonly>
                                                <button class="btn btn-outline-secondary copy-btn" type="button"
                                                        title="نسخ الرابط">
                                                    <i class="fa-solid fa-copy"></i>
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/certificate.php?slug=<?= urlencode($cert['slug'] ?? '') ?>"
                                           class="btn btn-sm btn-outline-primary" target="_blank"
                                           title="عرض الشهادة">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="/delete-certificate.php?id=<?= urlencode($cert['id']) ?>"
                                           class="btn btn-sm btn-outline-danger ms-1 confirm-delete"
                                           title="حذف">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
