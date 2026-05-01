<?php
/**
 * CraftRadar — Шапка админки (v2 — прокачанная)
 */

require_once __DIR__ . '/admin_auth.php';

$adminPageTitle = isset($adminPageTitle) ? $adminPageTitle : 'Дашборд';
$adminPageTitleFull = $adminPageTitle . ' — Админка';

// Счётчики для бейджей
$db = getDB();
$pendingCount = (int)$db->query("SELECT COUNT(*) FROM servers WHERE status = 'pending'")->fetchColumn();
$newReportsCount = (int)$db->query("SELECT COUNT(*) FROM reports WHERE status = 'new'")->fetchColumn();
$newTicketsCount = 0;
try { $newTicketsCount = (int)$db->query("SELECT COUNT(*) FROM tickets WHERE status = 'open'")->fetchColumn(); } catch (\Exception $e) {}

// Системные метрики для топбара
$totalOnlinePlayers = 0;
$onlineServersCount = 0;
try {
    $sysStats = $db->query("SELECT SUM(CASE WHEN is_online=1 THEN players_online ELSE 0 END) as players, SUM(CASE WHEN is_online=1 THEN 1 ELSE 0 END) as online FROM servers WHERE status IN ('active','pending')")->fetch();
    $totalOnlinePlayers = (int)($sysStats['players'] ?? 0);
    $onlineServersCount = (int)($sysStats['online'] ?? 0);
} catch (\Exception $e) {}

// Определяем текущую страницу
$currentScript = basename($_SERVER['SCRIPT_NAME']);
$currentStatus = get('status');
$currentBanned = get('banned');

function navActive(string $script, ?string $param = null, ?string $value = null): string {
    global $currentScript, $currentStatus, $currentBanned;
    if ($currentScript !== $script) return '';
    if ($param === null) {
        if ($script === 'servers.php' && ($currentStatus || get('offline_long'))) return '';
        if ($script === 'users.php' && $currentBanned) return '';
        return 'active';
    }
    if ($param === 'status' && $currentStatus === $value) return 'active';
    if ($param === 'banned' && $currentBanned === $value) return 'active';
    return '';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($adminPageTitleFull) ?> — <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/admin.css">
</head>
<body class="admin-body" data-site-url="<?= SITE_URL ?>">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-header">
            <a href="<?= SITE_URL ?>/admin/" class="logo">
                <span class="logo-icon">📡</span>
                <span class="logo-text"><?= SITE_NAME ?></span>
            </a>
        </div>

        <nav class="admin-nav">
            <a href="<?= SITE_URL ?>/admin/" class="admin-nav-link <?= $currentScript === 'index.php' ? 'active' : '' ?>">
                📊 Дашборд
            </a>

            <div class="admin-nav-group">
                <span class="admin-nav-group-title">📡 Серверы</span>
                <a href="<?= SITE_URL ?>/admin/servers.php" class="admin-nav-link <?= navActive('servers.php') ?>">
                    Все серверы
                </a>
                <a href="<?= SITE_URL ?>/admin/servers.php?status=pending" class="admin-nav-link <?= navActive('servers.php', 'status', 'pending') ?>">
                    На модерации <?php if ($pendingCount): ?><span class="admin-badge"><?= $pendingCount ?></span><?php endif; ?>
                </a>
                <a href="<?= SITE_URL ?>/admin/servers.php?status=banned" class="admin-nav-link <?= navActive('servers.php', 'status', 'banned') ?>">
                    Забаненные
                </a>
            </div>

            <div class="admin-nav-group">
                <span class="admin-nav-group-title">👥 Пользователи</span>
                <a href="<?= SITE_URL ?>/admin/users.php" class="admin-nav-link <?= navActive('users.php') ?>">
                    Все пользователи
                </a>
                <a href="<?= SITE_URL ?>/admin/users.php?banned=1" class="admin-nav-link <?= navActive('users.php', 'banned', '1') ?>">
                    Забаненные
                </a>
            </div>

            <a href="<?= SITE_URL ?>/admin/reports.php" class="admin-nav-link <?= $currentScript === 'reports.php' ? 'active' : '' ?>">
                ⚠️ Жалобы <?php if ($newReportsCount): ?><span class="admin-badge"><?= $newReportsCount ?></span><?php endif; ?>
            </a>

            <a href="<?= SITE_URL ?>/admin/reviews.php" class="admin-nav-link <?= $currentScript === 'reviews.php' ? 'active' : '' ?>">
                💬 Отзывы
            </a>

            <a href="<?= SITE_URL ?>/admin/tickets.php" class="admin-nav-link <?= $currentScript === 'tickets.php' || $currentScript === 'ticket_view.php' ? 'active' : '' ?>">
                🎫 Обращения <?php if ($newTicketsCount): ?><span class="admin-badge"><?= $newTicketsCount ?></span><?php endif; ?>
            </a>

            <a href="<?= SITE_URL ?>/admin/payments.php" class="admin-nav-link <?= $currentScript === 'payments.php' ? 'active' : '' ?>">
                💰 Платежи
            </a>

            <?php if (isAdmin()): ?>
                <div class="admin-nav-group">
                    <span class="admin-nav-group-title">⚙️ Управление</span>
                    <a href="<?= SITE_URL ?>/admin/categories.php" class="admin-nav-link <?= $currentScript === 'categories.php' ? 'active' : '' ?>">
                        🏷️ Категории
                    </a>
                    <a href="<?= SITE_URL ?>/admin/pages.php" class="admin-nav-link <?= $currentScript === 'pages.php' || $currentScript === 'page_edit.php' ? 'active' : '' ?>">
                        📄 Страницы
                    </a>
                    <a href="<?= SITE_URL ?>/admin/settings.php" class="admin-nav-link <?= $currentScript === 'settings.php' ? 'active' : '' ?>">
                        ⚙️ Настройки
                    </a>
                    <a href="<?= SITE_URL ?>/admin/system.php" class="admin-nav-link <?= $currentScript === 'system.php' ? 'active' : '' ?>">
                        🖥 Система
                    </a>
                </div>
            <?php endif; ?>

            <a href="<?= SITE_URL ?>/admin/log.php" class="admin-nav-link <?= $currentScript === 'log.php' ? 'active' : '' ?>">
                📋 Лог действий
            </a>
        </nav>

        <div class="admin-sidebar-footer">
            <div class="admin-sidebar-user">
                <span>👤 <?= e($_SESSION['username'] ?? '') ?></span>
                <span class="badge" style="font-size:0.6rem;"><?= e(currentUserRole()) ?></span>
            </div>
            <div class="admin-sidebar-actions">
                <a href="<?= SITE_URL ?>/" class="btn btn-ghost btn-sm" title="На сайт">🌐</a>
                <a href="<?= SITE_URL ?>/dashboard/" class="btn btn-ghost btn-sm" title="Кабинет">📡</a>
                <a href="<?= SITE_URL ?>/logout.php" class="btn btn-ghost btn-sm" title="Выйти">🚪</a>
            </div>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <button class="burger" id="adminBurger" aria-label="Меню">
                    <span></span><span></span><span></span>
                </button>
                <h1 class="admin-topbar-title"><?= e($adminPageTitle) ?></h1>
            </div>
            <div class="admin-topbar-right">
                <div class="admin-topbar-stats">
                    <span title="Игроков онлайн">👥 <?= $totalOnlinePlayers ?></span>
                    <span title="Серверов онлайн">📡 <?= $onlineServersCount ?></span>
                    <?php if ($pendingCount): ?><span title="На модерации" style="color:var(--warning);">⏳ <?= $pendingCount ?></span><?php endif; ?>
                    <?php if ($newReportsCount): ?><span title="Новых жалоб" style="color:var(--danger);">⚠️ <?= $newReportsCount ?></span><?php endif; ?>
                </div>
                <div class="admin-topbar-search">
                    <input type="text" id="adminQuickSearch" placeholder="Быстрый поиск..." autocomplete="off">
                </div>
            </div>
        </header>

        <div class="admin-content">
            <?= showFlash() ?>
