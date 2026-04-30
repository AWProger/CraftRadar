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

$pageTitle = isset($pageTitle) ? $pageTitle . ' — ' . SITE_NAME : SITE_NAME;
$_notifCount = isLoggedIn() ? getUnreadCount(currentUserId()) : 0;
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
                </nav>

                <div class="header-actions">
                    <?php if (isLoggedIn()): ?>
                        <a href="<?= SITE_URL ?>/dashboard/notifications.php" class="header-notif" title="Уведомления">
                            🔔<?php if ($_notifCount > 0): ?><span class="header-notif-badge"><?= $_notifCount ?></span><?php endif; ?>
                        </a>
                        <a href="<?= SITE_URL ?>/dashboard/" class="btn btn-sm btn-outline">Кабинет</a>
                        <?php if (isModerator()): ?>
                            <a href="<?= SITE_URL ?>/admin/" class="btn btn-sm btn-outline">Админка</a>
                        <?php endif; ?>
                        <span class="header-user"><?= e($_SESSION['username'] ?? '') ?></span>
                        <span class="points-display" title="Ваши баллы"><span class="points-icon">💎</span> <?= getUserPoints(currentUserId()) ?></span>
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
