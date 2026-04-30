<?php
/**
 * CraftRadar — Покупка продвижения сервера
 */

$pageTitle = 'Продвижение сервера';
require_once __DIR__ . '/../includes/header.php';
require_once INCLUDES_PATH . 'yoomoney.php';

requireAuth();

$db = getDB();
$userId = currentUserId();
$serverId = getInt('id');

// Получаем сервер (только свой, только active)
$stmt = $db->prepare("SELECT * FROM servers WHERE id = ? AND user_id = ? AND status = 'active'");
$stmt->execute([$serverId, $userId]);
$server = $stmt->fetch();

if (!$server) {
    setFlash('error', 'Сервер не найден или не активен.');
    redirect(SITE_URL . '/dashboard/');
}

$paymentData = null;
$errors = [];

if (isPost()) {
    if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $errors[] = 'Ошибка безопасности.';
    } else {
        $type = post('type');
        if (!in_array($type, ['7d', '14d', '30d'])) {
            $errors[] = 'Выберите тариф.';
        }

        if (empty($errors)) {
            $result = createPayment($userId, $serverId, $type);
            if ($result['success']) {
                $paymentData = $result;
            } else {
                $errors[] = $result['error'];
            }
        }
    }
}

$prices = PROMOTE_PRICES;
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Продвижение: <?= e($server['name']) ?></h1>
        <a href="<?= SITE_URL ?>/dashboard/" class="btn btn-ghost">← Назад</a>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <p><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($server['is_promoted']): ?>
        <div class="alert alert-info">
            ⭐ Сервер уже продвигается до <?= formatDate($server['promoted_until']) ?>.
            Вы можете продлить — дни добавятся к текущему сроку.
        </div>
    <?php endif; ?>

    <?php if ($paymentData): ?>
        <!-- Форма оплаты — автоматический редирект на ЮMoney -->
        <div class="card" style="text-align: center; padding: 40px;">
            <h2 style="margin-bottom: 16px;">Переход к оплате...</h2>
            <p style="color: var(--text-muted); margin-bottom: 20px;">
                Сумма: <strong><?= $paymentData['amount'] ?> ₽</strong> — продвижение на <?= $paymentData['days'] ?> дней
            </p>

            <form id="paymentForm" method="POST" action="<?= e($paymentData['form']['action']) ?>">
                <?php foreach ($paymentData['form'] as $key => $value): ?>
                    <?php if ($key !== 'action'): ?>
                        <input type="hidden" name="<?= e($key) ?>" value="<?= e($value) ?>">
                    <?php endif; ?>
                <?php endforeach; ?>

                <p style="color: var(--text-muted); margin-bottom: 16px;">Если автоматический переход не сработал:</p>
                <button type="submit" class="btn btn-primary">Перейти к оплате →</button>
            </form>

            <script>
                // Автоматический редирект через 2 секунды
                setTimeout(function() {
                    document.getElementById('paymentForm').submit();
                }, 2000);
            </script>
        </div>

    <?php else: ?>
        <!-- Выбор тарифа -->
        <div class="promote-grid">
            <?php
            $tariffs = [
                '7d'  => ['days' => 7,  'label' => '7 дней',  'icon' => '⚡'],
                '14d' => ['days' => 14, 'label' => '14 дней', 'icon' => '🔥'],
                '30d' => ['days' => 30, 'label' => '30 дней', 'icon' => '👑'],
            ];
            ?>
            <?php foreach ($tariffs as $key => $tariff): ?>
                <div class="promote-card <?= $key === '14d' ? 'promote-card-popular' : '' ?>">
                    <?php if ($key === '14d'): ?>
                        <div class="promote-badge">Популярный</div>
                    <?php endif; ?>
                    <div class="promote-icon"><?= $tariff['icon'] ?></div>
                    <div class="promote-days"><?= $tariff['label'] ?></div>
                    <div class="promote-price"><?= $prices[$key] ?> ₽</div>
                    <div class="promote-per-day"><?= round($prices[$key] / $tariff['days'], 1) ?> ₽/день</div>
                    <ul class="promote-features">
                        <li>📌 Закрепление в топе каталога</li>
                        <li>⭐ Значок на главной странице</li>
                        <li>📈 Приоритет в поиске</li>
                    </ul>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="type" value="<?= $key ?>">
                        <button type="submit" class="btn btn-primary btn-block">Оплатить <?= $prices[$key] ?> ₽</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="card" style="margin-top: 20px;">
            <h3 style="margin-bottom: 8px;">Что даёт продвижение?</h3>
            <ul style="color: var(--text-muted); line-height: 1.8;">
                <li>Ваш сервер закрепляется в самом верху каталога и на главной странице</li>
                <li>Отображается значок ⭐ рядом с названием</li>
                <li>Приоритет в результатах поиска</li>
                <li>Если сервер уже продвигается — дни добавляются к текущему сроку</li>
            </ul>
            <p style="color: var(--text-muted); margin-top: 12px; font-size: 0.85rem;">
                Оплата через ЮMoney (банковская карта или кошелёк). Продвижение активируется автоматически после оплаты.
            </p>
        </div>
    <?php endif; ?>
</div>

<style>
    .promote-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 16px;
    }
    .promote-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 24px;
        text-align: center;
        position: relative;
        transition: border-color var(--transition);
    }
    .promote-card:hover {
        border-color: var(--accent);
    }
    .promote-card-popular {
        border-color: var(--accent);
        box-shadow: 0 0 20px rgba(0, 255, 128, 0.1);
    }
    .promote-badge {
        position: absolute;
        top: -10px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--accent);
        color: #000;
        padding: 2px 12px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 700;
    }
    .promote-icon {
        font-size: 2.5rem;
        margin-bottom: 8px;
    }
    .promote-days {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .promote-price {
        font-size: 2rem;
        font-weight: 800;
        color: var(--accent);
        margin-bottom: 4px;
    }
    .promote-per-day {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-bottom: 16px;
    }
    .promote-features {
        list-style: none;
        text-align: left;
        margin-bottom: 20px;
        font-size: 0.85rem;
        color: var(--text-muted);
    }
    .promote-features li {
        padding: 4px 0;
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
