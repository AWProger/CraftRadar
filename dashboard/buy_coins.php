<?php
/**
 * CraftRadar — Покупка монет за реальные деньги
 */

$pageTitle = 'Купить монеты';
require_once __DIR__ . '/../includes/header.php';
require_once INCLUDES_PATH . 'coins.php';
require_once INCLUDES_PATH . 'yoomoney.php';

requireAuth();

$userId = currentUserId();
$currentCoins = getUserCoins($userId);
$errors = [];
$paymentData = null;

// Пакеты монет
$packages = [
    '100'  => ['coins' => 100,  'price' => 100,  'icon' => '💰', 'bonus' => 0],
    '300'  => ['coins' => 300,  'price' => 270,  'icon' => '💰💰', 'bonus' => 30],
    '500'  => ['coins' => 550,  'price' => 450,  'icon' => '💰💰💰', 'bonus' => 100],
    '1000' => ['coins' => 1200, 'price' => 900,  'icon' => '👑', 'bonus' => 300],
];

if (isPost()) {
    if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $errors[] = 'Ошибка безопасности.';
    } else {
        $package = post('package');
        if (!isset($packages[$package])) {
            $errors[] = 'Выберите пакет.';
        } else {
            $pkg = $packages[$package];
            $label = 'coins_' . $userId . '_' . $package . '_' . time();

            // Создаём платёж
            $db = getDB();
            $db->prepare('INSERT INTO payments (user_id, amount, type, status, label, description, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$userId, $pkg['price'], 'coins_' . $package, 'pending', $label, 'Покупка ' . $pkg['coins'] . ' монет', now()]);

            $yoomoney = new YooMoney();
            $paymentData = [
                'form' => $yoomoney->createPaymentForm($pkg['price'], $label, 'Покупка ' . $pkg['coins'] . ' монет на CraftRadar'),
                'price' => $pkg['price'],
                'coins' => $pkg['coins'],
            ];
        }
    }
}
?>

<div class="dashboard">
    <?= dashboardNav('coins') ?>
    <div class="dashboard-header">
        <h1>💰 Купить монеты</h1>
        <a href="<?= SITE_URL ?>/dashboard/profile.php" class="btn btn-ghost">← Профиль</a>
    </div>

    <!-- Баланс -->
    <div class="card" style="text-align: center; margin-bottom: 20px;">
        <div style="display: flex; justify-content: center; gap: 30px;">
            <div>
                <div style="font-size: 2rem;">💰</div>
                <div style="font-family: var(--font-mc); font-size: 1rem; color: var(--gold);"><?= $currentCoins ?></div>
                <div style="font-size: 0.7rem; color: var(--text-muted);">МОНЕТ</div>
            </div>
            <div>
                <div style="font-size: 2rem;">💎</div>
                <div style="font-family: var(--font-mc); font-size: 1rem; color: var(--diamond);"><?= getUserPoints($userId) ?></div>
                <div style="font-size: 0.7rem; color: var(--text-muted);">АЛМАЗОВ</div>
            </div>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error"><?php foreach ($errors as $e): ?><p><?= e($e) ?></p><?php endforeach; ?></div>
    <?php endif; ?>

    <?php if ($paymentData): ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <h2 style="margin-bottom: 16px;">Переход к оплате...</h2>
            <p style="color: var(--text-muted);"><?= $paymentData['coins'] ?> монет за <?= $paymentData['price'] ?> ₽</p>
            <form id="payForm" method="POST" action="<?= e($paymentData['form']['action']) ?>">
                <?php foreach ($paymentData['form'] as $k => $v): ?>
                    <?php if ($k !== 'action'): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endif; ?>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary" style="margin-top: 16px;">Перейти к оплате →</button>
            </form>
            <script>setTimeout(function(){document.getElementById('payForm').submit();}, 2000);</script>
        </div>
    <?php else: ?>

    <!-- Разница валют -->
    <div class="card" style="margin-bottom: 16px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div style="padding: 12px; border: 2px solid var(--diamond); text-align: center;">
                <div style="font-size: 1.5rem;">💎</div>
                <div style="font-weight: 700; color: var(--diamond);">Алмазы</div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Бесплатно</div>
                <div style="font-size: 0.7rem; color: var(--text-muted);">Голосование, достижения</div>
                <div style="font-size: 0.7rem; color: var(--diamond); margin-top: 4px;">→ Выделение (1-24ч)</div>
            </div>
            <div style="padding: 12px; border: 2px solid var(--gold); text-align: center;">
                <div style="font-size: 1.5rem;">💰</div>
                <div style="font-weight: 700; color: var(--gold);">Монеты</div>
                <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">За реальные деньги</div>
                <div style="font-size: 0.7rem; color: var(--text-muted);">1 монета = 1 рубль</div>
                <div style="font-size: 0.7rem; color: var(--gold); margin-top: 4px;">→ Продвижение (7-30 дней)</div>
            </div>
        </div>
    </div>

    <!-- Пакеты -->
    <h2 class="section-title">Выберите пакет</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 20px;">
        <?php foreach ($packages as $key => $pkg): ?>
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="package" value="<?= $key ?>">
                <div class="card" style="text-align: center; cursor: pointer; transition: border-color 0.15s; padding: 20px;"
                     onmouseover="this.style.borderColor='var(--gold)'" onmouseout="this.style.borderColor='var(--border)'">
                    <div style="font-size: 1.5rem;"><?= $pkg['icon'] ?></div>
                    <div style="font-family: var(--font-mc); font-size: 0.9rem; color: var(--gold); margin: 8px 0;">
                        <?= $pkg['coins'] ?> 💰
                    </div>
                    <?php if ($pkg['bonus'] > 0): ?>
                        <div style="color: var(--success); font-size: 0.75rem; margin-bottom: 4px;">+<?= $pkg['bonus'] ?> бонус!</div>
                    <?php endif; ?>
                    <div style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 12px;"><?= $pkg['price'] ?> ₽</div>
                    <button type="submit" class="btn btn-gold btn-block btn-sm">Купить</button>
                </div>
            </form>
        <?php endforeach; ?>
    </div>

    <p style="color: var(--text-muted); font-size: 0.75rem; text-align: center;">
        Оплата через ЮMoney (карта или кошелёк). Монеты зачисляются автоматически.
        <a href="<?= SITE_URL ?>/page.php?slug=offer">Оферта</a> · <a href="<?= SITE_URL ?>/page.php?slug=contacts">Контакты</a>
    </p>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
