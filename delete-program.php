<?php
/**
 * Delete Program – نظام ادارة الشواهد الذكي
 * Deletes a program (and all its evidence_files via ON DELETE CASCADE).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config/supabase.php';
requireAuth();

$id     = trim($_GET['id'] ?? '');
$userId = $_SESSION['user_id'] ?? '';

if (empty($id) || empty($userId)) {
    header('Location: /dashboard.php');
    exit;
}

$sb  = supabase();
$res = $sb->delete('programs', [
    'id'      => "eq.{$id}",
    'user_id' => "eq.{$userId}",
]);

$_SESSION['flash'] = (isset($res['_http_code']) && $res['_http_code'] >= 200 && $res['_http_code'] < 300)
    ? 'تم حذف البرنامج وجميع شواهده بنجاح.'
    : 'حدث خطأ أثناء الحذف.';

header('Location: /dashboard.php');
exit;

