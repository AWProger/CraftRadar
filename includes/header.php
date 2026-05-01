<?php
/**
 * CraftRadar — Шапка сайта
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notifications.php';
require_once __DIR__ . '/points.php';
require_once __DIR__ . '/components.php';

// Режим обслуживания
$_maintenanceFile = ROOT_PATH . 'storage/.maintenance';
if (file_exists($_maintenanceFile) && (!isLoggedIn() || currentUserRole() !== 'admin')) {
    http_response_code(503);
    header('Retry-After: 3600');
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"><title>' . SITE_NAME . ' — Обслуживание</title>';
    echo '<style>body{background:#0d1117;color:#c9d1d9;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;text-align:center;}';
    echo '.box{max-width:500px;padding:40px;border:3px solid #30363d;}.icon{font-size:4rem;margin-bottom:16px;}h1{color:#00ff80;font-size:1.2rem;margin-bottom:12px;}p{color:#8b949e;line-height:1.6;}</style>';
    echo '</head><body><div class="box"><div class="icon">🔧</div><h1>Технические работы</h1><p>Сайт временно недоступен. Мы проводим обновление и скоро вернёмся. Попробуйте зайти позже.</p></div></body></html>';
    exit;
}

$pageTitle = isset($pageTitle) ? $pageTitle . ' — ' . SITE_NAME : SITE_NAME;
$_notifCount = isLoggedIn() ? getUnreadCount(currentUserId()) : 0;

// Трекинг ежедневных визитов (безопасно — не падает если таблиц нет)
if (isLoggedIn()) {
    try {
        require_once __DIR__ . '/achievements.php';
        trackDailyVisit(currentUserId());
    } catch (\Exception $e) {
        // Таблицы ещё не созданы — пропускаем
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription ?? SITE_DESCRIPTION) ?>">

    <!-- OpenGraph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription ?? SITE_DESCRIPTION) ?>">
    <meta property="og:url" content="<?= e(SITE_URL . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
    <meta property="og:site_name" content="<?= SITE_NAME ?>">
    <?php if (!empty($pageImage)): ?>
        <meta property="og:image" content="<?= e($pageImage) ?>">
    <?php endif; ?>

    <link rel="canonical" href="<?= e(SITE_URL . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <a href="<?= SITE_URL ?>/" class="logo">
                    <span class="logo-icon">📡</span>
                    <span class="logo-text"><?= SITE_NAME ?></span>
                </a>

                <nav class="nav">
                    <a href="<?= SITE_URL ?>/" class="nav-link">Главная</a>
                    <a href="<?= SITE_URL ?>/servers.php" class="nav-link">Серверы</a>
                    <a href="<?= SITE_URL ?>/top_players.php" class="nav-link">Игроки</a>
                </nav>

                <div class="header-actions">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?= SITE_URL ?>/dashboard/notifications.php" class="header-notif" title="Уведомления">
                            🔔<?php if ($_notifCount > 0): ?><span class="header-notif-badge"><?= $_notifCount ?></span><?php endif; ?>
                        </a>
                        <a href="<?= SITE_URL ?>/dashboard/profile.php" class="btn btn-sm btn-outline">👤 Профиль</a>
                        <?php if (isModerator()): ?>
                            <a href="<?= SITE_URL ?>/admin/" class="btn btn-sm btn-outline">Админка</a>
                        <?php endif; ?>
                        <a href="<?= SITE_URL ?>/profile.php?id=<?= currentUserId() ?>" class="header-user" title="Мой публичный профиль"><?= e($_SESSION['username'] ?? '') ?></a>
                        <a href="<?= SITE_URL ?>/dashboard/points.php" class="points-display" title="Алмазы"><span class="points-icon">💎</span> <?= getUserPoints(currentUserId()) ?></a>
                        <a href="<?= SITE_URL ?>/dashboard/buy_coins.php" class="points-display" title="Монеты" style="color: var(--gold);">
                            <span class="points-icon">💰</span> <?php try { require_once INCLUDES_PATH . 'coins.php'; echo getUserCoins(currentUserId()); } catch(\Exception $e) { echo '0'; } ?>
                        </a>
                        <a href="<?= SITE_URL ?>/logout.php" class="btn btn-sm btn-ghost">Выйти</a>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/login.php" class="btn btn-sm btn-outline">Войти</a>
                        <a href="<?= SITE_URL ?>/register.php" class="btn btn-sm btn-primary">Регистрация</a>
                    <?php endif; ?>
                </div>

                <button class="burger" id="burger" aria-label="Меню">
                    <span></span><span></span><span></span>
                </button>
            </div>
        </div>
    </header>

    <main class="main">
        <div class="container">
            <?= showFlash() ?>
