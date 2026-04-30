<?php
/**
 * CraftRadar — Успешная оплата
 */

$pageTitle = 'Оплата успешна';
require_once __DIR__ . '/../includes/header.php';

requireAuth();

$label = get('label');
$payment = null;

if ($label) {
    $db = getDB();
    $stmt = $db->prepare('SELECT p.*, s.name as server_name FROM payments p LEFT JOIN servers s ON p.server_id = s.id WHERE p.label = ? AND p.user_id = ?');
    $stmt->execute([$label, currentUserId()]);
    $payment = $stmt->fetch();
}
?>

<div class="auth-page">
    <div class="auth-card" style="max-width: 500px; text-align: center;">
        <div style="font-size: 4rem; margin-bottom: 16px;">✅</div>
        <h1 class="auth-title">Оплата успешна!</h1>

        <?php if ($payment): ?>
            <p style="color: var(--text-muted); margin-bottom: 8px;">
                Сервер: <strong><?= e($payment['server_name'] ?? 'Неизвестен') ?></strong>
            </p>
            <p style="color: var(--text-muted); margin-bottom: 8px;">
                Сумма: <strong><?= $payment['amount'] ?> ₽</strong>
            </p>
            <p style="color: var(--text-muted); margin-bottom: 20px;">
                <?php if ($payment['status'] === 'completed'): ?>
                    Продвижение уже активировано! ⭐
                <?php else: ?>
                    Продвижение активируется автоматически в течение нескольких минут.
                <?php endif; ?>
            </p>
        <?php else: ?>
            <p style="color: var(--text-muted); margin-bottom: 20px;">
                Продвижение активируется автоматически после подтверждения платежа.
                Обычно это занимает несколько минут.
            </p>
        <?php endif; ?>

        <div style="display: flex; gap: 12px; justify-content: center;">
            <a href="<?= SITE_URL ?>/dashboard/" class="btn btn-primary">В кабинет</a>
            <a href="<?= SITE_URL ?>/servers.php" class="btn btn-outline">Каталог</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
