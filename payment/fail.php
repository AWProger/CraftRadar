<?php
/**
 * CraftRadar — Ошибка оплаты
 */

$pageTitle = 'Ошибка оплаты';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card" style="max-width: 500px; text-align: center;">
        <div style="font-size: 4rem; margin-bottom: 16px;">❌</div>
        <h1 class="auth-title">Оплата не прошла</h1>
        <p style="color: var(--text-muted); margin-bottom: 20px;">
            Платёж был отменён или произошла ошибка. Деньги не списаны.
            Вы можете попробовать снова.
        </p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <a href="<?= SITE_URL ?>/dashboard/" class="btn btn-primary">В кабинет</a>
            <a href="javascript:history.back()" class="btn btn-outline">Попробовать снова</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
