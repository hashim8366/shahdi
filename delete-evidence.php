<?php
/**
 * Delete Evidence File – نظام ادارة الشواهد الذكي
 * Deletes a single evidence file owned by the current user's program.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/supabase.php';
requireAuth();

$fileId    = trim($_GET['id']         ?? '');
$programId = trim($_GET['program_id'] ?? '');
$userId    = $_SESSION['user_id']     ?? '';

if (empty($fileId) || empty($programId) || empty($userId)) {
    header('Location: /dashboard.php');
    exit;
}

$sb = supabase();

// Verify ownership via program ownership (evidence_files RLS already checks this,
// but we add an explicit owner check in the query).
$checkRes = $sb->select(
    'evidence_files',
    'id,program_id,programs(user_id)',
    ['id' => "eq.{$fileId}", 'program_id' => "eq.{$programId}"]
);

$allowed = false;
foreach ($checkRes as $item) {
    if (is_array($item) && isset($item['id'])) {
        $owner = $item['programs']['user_id'] ?? null;
        if ($owner === $userId) {
            $allowed = true;
        }
        break;
    }
}

if (!$allowed) {
    $_SESSION['flash_manage'] = 'لا تملك صلاحية حذف هذا الملف.';
    header('Location: /manage-program.php?id=' . urlencode($programId));
    exit;
}

$res = $sb->delete('evidence_files', ['id' => "eq.{$fileId}"]);

$_SESSION['flash_manage'] = (isset($res['_http_code']) && $res['_http_code'] >= 200 && $res['_http_code'] < 300)
    ? 'تم حذف الملف بنجاح.'
    : 'حدث خطأ أثناء حذف الملف.';

header('Location: /manage-program.php?id=' . urlencode($programId));
exit;
