<?php
/**
 * CraftRadar — Шапка админки
 */

require_once __DIR__ . '/admin_auth.php';

$adminPageTitle = isset($adminPageTitle) ? $adminPageTitle . ' — Админка' : 'Админка';

// Счётчики для бейджей
$db = getDB();
$pendingCount = (int)$db->query("SELECT COUNT(*) FROM servers WHERE status = 'pending'")->fetchColumn();
$newReportsCount = (int)$db->query("SELECT COUNT(*) FROM reports WHERE status = 'new'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminPageTitle) ?> — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-header">
            <a href="<?= SITE_URL ?>/" class="logo">
                <span class="logo-icon">📡</span>
                <span class="logo-text"><?= SITE_NAME ?></span>
            </a>
        </div>

        <nav class="admin-nav">
            <a href="<?= SITE_URL ?>/admin/" class="admin-nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? 'active' : '' ?>">
                📊 Дашборд
            </a>

            <div class="admin-nav-group">
                <span class="admin-nav-group-title">📡 Серверы</span>
                <a href="<?= SITE_URL ?>/admin/servers.php" class="admin-nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'servers.php' ? 'active' : '' ?>">
                    Все серверы
                </a>
                <a href="<?= SITE_URL ?>/admin/servers.php?status=pending" class="admin-nav-link">
                    На модерации <?php if ($pendingCount): ?><span class="admin-badge"><?= $pendingCount ?></span><?php endif; ?>
                </a>
                <a href="<?= SITE_URL ?>/admin/servers.php?status=banned" class="admin-nav-link">
                    Забаненные
                </a>
            </div>

            <div class="admin-nav-group">
                <span class="admin-nav-group-title">👥 Пользователи</span>
                <a href="<?= SITE_URL ?>/admin/users.php" class="admin-nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'users.php' ? 'active' : '' ?>">
                    Все пользователи
                </a>
                <a href="<?= SITE_URL ?>/admin/users.php?banned=1" class="admin-nav-link">
                    Забаненные
                </a>
            </div>

            <a href="<?= SITE_URL ?>/admin/reports.php" class="admin-nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'reports.php' ? 'active' : '' ?>">
                ⚠️ Жалобы <?php if ($newReportsCount): ?><span class="admin-badge"><?= $newReportsCount ?></span><?php endif; ?>
            </a>

            <a href="<?= SITE_URL ?>/admin/reviews.php" class="admin-nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'reviews.php' ? 'active' : '' ?>">
                💬 Отзывы
            </a>

            <?php if (isAdmin()): ?>
                <a href="<?= SITE_URL ?>/admin/categories.php" class="admin-nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'categories.php' ? 'active' : '' ?>">
                    🏷️ Категории
                </a>
                <a href="<?= SITE_URL ?>/admin/pages.php" class="admin-nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'pages.php' ? 'active' : '' ?>">
                    📄 Страницы
                </a>
                <a href="<?= SITE_URL ?>/admin/settings.php" class="admin-nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'settings.php' ? 'active' : '' ?>">
                    ⚙️ Настройки
                </a>
            <?php endif; ?>

            <a href="<?= SITE_URL ?>/admin/log.php" class="admin-nav-link <?= basename($_SERVER['SCRIPT_NAME']) === 'log.php' ? 'active' : '' ?>">
                📋 Лог действий
            </a>
        </nav>

        <div class="admin-sidebar-footer">
            <span class="admin-user"><?= e($_SESSION['username'] ?? '') ?> (<?= e(currentUserRole()) ?>)</span>
            <a href="<?= SITE_URL ?>/dashboard/" class="btn btn-ghost btn-sm">Кабинет</a>
            <a href="<?= SITE_URL ?>/logout.php" class="btn btn-ghost btn-sm">Выйти</a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <button class="burger" id="adminBurger" aria-label="Меню">
                <span></span><span></span><span></span>
            </button>
            <h1 class="admin-topbar-title"><?= e($adminPageTitle ?? 'Админка') ?></h1>
        </header>

        <div class="admin-content">
            <?= showFlash() ?>
