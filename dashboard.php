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

// ── Fetch user's programs with evidence file count ─────────────────────────
$programs = [];
if ($userId) {
    $res = $sb->select(
        'programs',
        'id,program_name,organization,created_at,slug,evidence_files(id)',
        ['user_id' => "eq.{$userId}", 'order' => 'created_at.desc']
    );
    if (is_array($res)) {
        $programs = array_filter($res, fn($v) => is_array($v) && isset($v['id']));
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
                <p class="text-muted mb-0">إدارة شواهد برامجك وفعالياتك</p>
            </div>
            <a href="/create-program.php"
               class="btn btn-primary btn-lg px-4 shadow-sm fw-bold create-btn">
                <i class="fa-solid fa-plus me-2"></i>برنامج / فعالية جديدة
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
                    <i class="fa-solid fa-folder-open fa-2x text-primary"></i>
                </div>
                <h3 class="fw-bold mb-0"><?= count($programs) ?></h3>
                <p class="text-muted small mb-0">إجمالي البرامج</p>
            </div>
        </div>
        <div class="col-sm-6 col-md-3">
            <div class="stat-card card border-0 shadow-sm p-3 text-center">
                <div class="stat-icon mb-2">
                    <i class="fa-solid fa-photo-film fa-2x text-success"></i>
                </div>
                <?php
                    $totalFiles = 0;
                    foreach ($programs as $p) {
                        $totalFiles += is_array($p['evidence_files'] ?? null)
                            ? count($p['evidence_files'])
                            : 0;
                    }
                ?>
                <h3 class="fw-bold mb-0"><?= $totalFiles ?></h3>
                <p class="text-muted small mb-0">إجمالي ملفات الشواهد</p>
            </div>
        </div>
    </div>

    <!-- Programs table -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
            <h5 class="fw-bold mb-0">
                <i class="fa-solid fa-list-check text-primary me-2"></i>برامجي وفعالياتي
            </h5>
            <a href="/create-program.php" class="btn btn-sm btn-primary">
                <i class="fa-solid fa-plus me-1"></i>جديد
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($programs)): ?>
                <div class="text-center py-5">
                    <i class="fa-solid fa-folder-open fa-4x text-muted mb-3 d-block"></i>
                    <h6 class="text-muted">لا توجد برامج بعد</h6>
                    <p class="text-muted small">اضغط على "برنامج / فعالية جديدة" للبدء</p>
                    <a href="/create-program.php" class="btn btn-primary mt-2">
                        <i class="fa-solid fa-plus me-2"></i>أنشئ أول برنامج
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>اسم البرنامج</th>
                                <th>الجهة المنفذة</th>
                                <th>الشواهد</th>
                                <th>التاريخ</th>
                                <th>رابط المشاركة</th>
                                <th>إجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($programs as $prog): ?>
                                <?php if (!isset($prog['id'])) continue; ?>
                                <?php
                                    $slug      = $prog['slug'] ?? '';
                                    $fileCount = is_array($prog['evidence_files'] ?? null)
                                        ? count($prog['evidence_files'])
                                        : 0;
                                    $shareUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http')
                                        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
                                        . '/program.php?slug=' . urlencode($slug);
                                ?>
                                <tr>
                                    <td class="text-muted"><?= $i++ ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($prog['program_name'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($prog['organization'] ?? '') ?></td>
                                    <td>
                                        <span class="badge bg-primary rounded-pill">
                                            <i class="fa-solid fa-photo-film me-1"></i><?= $fileCount ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        <?= isset($prog['created_at'])
                                            ? date('Y/m/d', strtotime($prog['created_at']))
                                            : '' ?>
                                    </td>
                                    <td>
                                        <?php if ($slug): ?>
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
                                        <a href="/manage-program.php?id=<?= urlencode($prog['id']) ?>"
                                           class="btn btn-sm btn-outline-success"
                                           title="إدارة الشواهد">
                                            <i class="fa-solid fa-folder-plus"></i>
                                        </a>
                                        <a href="/program.php?slug=<?= urlencode($slug) ?>"
                                           class="btn btn-sm btn-outline-primary ms-1" target="_blank"
                                           title="عرض الشواهد">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="/delete-program.php?id=<?= urlencode($prog['id']) ?>"
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

