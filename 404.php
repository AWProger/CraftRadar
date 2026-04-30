<?php
/**
 * CraftRadar — Страница 404
 */

http_response_code(404);

$pageTitle = 'Страница не найдена';
require_once __DIR__ . '/includes/header.php';
?>

<div class="error-page">
    <div class="error-code">404</div>
    <h1 class="error-title">Страница не найдена</h1>
    <p class="error-text">Возможно, она была удалена или вы ввели неверный адрес.</p>
    <div class="error-actions">
        <a href="<?= SITE_URL ?>/" class="btn btn-primary">На главную</a>
        <a href="<?= SITE_URL ?>/servers.php" class="btn btn-outline">Каталог серверов</a>
    </div>
</div>

<style>
    .error-page {
        text-align: center;
        padding: 80px 20px;
    }
    .error-code {
        font-size: 6rem;
        font-weight: 800;
        color: var(--accent);
        line-height: 1;
        opacity: 0.3;
    }
    .error-title {
        font-size: 1.8rem;
        margin: 16px 0 8px;
    }
    .error-text {
        color: var(--text-muted);
        margin-bottom: 24px;
    }
    .error-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
