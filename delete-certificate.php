<?php
/**
 * Delete Certificate – نظام ادارة الشواهد الذكي
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

// Only allow deleting certificates that belong to the current user
$sb  = supabase();
$res = $sb->delete('certificates', [
    'id'      => "eq.{$id}",
    'user_id' => "eq.{$userId}",
]);

$_SESSION['flash'] = (isset($res['_http_code']) && $res['_http_code'] >= 200 && $res['_http_code'] < 300)
    ? 'تم حذف الشهادة بنجاح.'
    : 'حدث خطأ أثناء الحذف.';

header('Location: /dashboard.php');
exit;
