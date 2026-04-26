<?php
/**
 * Shared header – included at the top of every page.
 * نظام ادارة الشواهد الذكي
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/supabase.php';

$appName   = 'نظام ادارة الشواهد الذكي';
$pageTitle = $pageTitle ?? $appName;
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | <?= htmlspecialchars($appName) ?></title>

    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css"
          crossorigin="anonymous">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          crossorigin="anonymous">

    <!-- Google Fonts – Cairo (Arabic) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap"
          rel="stylesheet">

    <!-- Custom styles -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- ── Navbar ──────────────────────────────────────────────────────────── -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/">
            <i class="fa-solid fa-certificate fs-4"></i>
            <span class="fw-bold"><?= htmlspecialchars($appName) ?></span>
        </a>
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <?php if (!empty($_SESSION['access_token'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/dashboard.php">
                            <i class="fa-solid fa-gauge-high me-1"></i>لوحة التحكم
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-warning" href="/logout.php">
                            <i class="fa-solid fa-right-from-bracket me-1"></i>تسجيل الخروج
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/login.php">
                            <i class="fa-solid fa-right-to-bracket me-1"></i>تسجيل الدخول
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/register.php">
                            <i class="fa-solid fa-user-plus me-1"></i>إنشاء حساب
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<!-- ── End Navbar ──────────────────────────────────────────────────────── -->
