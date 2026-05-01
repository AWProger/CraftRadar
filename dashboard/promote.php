<?php
/**
 * CraftRadar — Продвижение сервера за монеты
 */

$pageTitle = 'Продвижение сервера';
require_once __DIR__ . '/../includes/header.php';
require_once INCLUDES_PATH . 'coins.php';

requireAuth();

$db = getDB();
$userId = currentUserId();
$serverId = getInt('id');

$stmt = $db->prepare("SELECT * FROM servers WHERE id = ? AND user_id = ? AND status IN ('active', 'pending')");
$stmt->execute([$serverId, $userId]);
$server = $stmt->fetch();

if (!$server) {
    setFlash('error', 'Сервер не найден.');
    redirect(SITE_URL . '/dashboard/');
}

$currentCoins = getUserCoins($userId);
$errors = [];

if (isPost()) {
    if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $errors[] = 'Ошибка безопасности.';
    } else {
        $type = post('type');
        $result = promoteServerWithCoins($userId, $serverId, $type);
        if ($result['success']) {
            setFlash('success', '⭐ Сервер продвигается до ' . formatDate($result['until']) . '! Потрачено ' . $result['coins_spent'] . ' 💰');
            redirect(SITE_URL . '/dashboard/');
        } else {
            $errors[] = $result['error'];
        }
    }
    $currentCoins = getUserCoins($userId);
}

$costs = PROMOTE_COIN_COSTS;
$daysMap = ['7d' => 7, '14d' => 14, '30d' => 30];
?>

<div class="dashboard">
    <?= dashboardNav('servers') ?>
    <div class="dashboard-header">
        <h1>⭐ Продвижение: <?= e($server['name']) ?></h1>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error"><?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?></div>
    <?php endif; ?>

    <!-- Баланс -->
    <div class="card" style="text-align: center; margin-bottom: 16px;">
        <div style="font-family: var(--font-mc); font-size: 1rem; color: var(--gold);">💰 <?= $currentCoins ?> монет</div>
        <a href="<?= SITE_URL ?>/dashboard/buy_coins.php" class="btn btn-sm btn-gold" style="margin-top: 8px;">Купить монеты</a>
    </div>

    <?php if ($server['is_promoted']): ?>
        <?php $daysLeft = max(0, (int)ceil((strtotime($server['promoted_until']) - time()) / 86400)); ?>
        <div class="alert alert-info">
            ⭐ Уже продвигается до <?= formatDate($server['promoted_until']) ?> (<?= $daysLeft ?> дн.). Дни добавятся.
        </div>
    <?php endif; ?>

    <!-- Тарифы -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
        <?php
        $tariffs = [
            '7d'  => ['icon' => '⚡', 'label' => '7 дней'],
            '14d' => ['icon' => '🔥', 'label' => '14 дней', 'popular' => true],
            '30d' => ['icon' => '👑', 'label' => '30 дней'],
        ];
        foreach ($tariffs as $key => $t):
            $cost = $costs[$key];
            $days = $daysMap[$key];
            $canAfford = $currentCoins >= $cost;
        ?>
            <div class="card" style="text-align: center; <?= !empty($t['popular']) ? 'border-color: var(--gold); box-shadow: 4px 4px 0 rgba(255,215,0,0.2);' : '' ?>">
                <?php if (!empty($t['popular'])): ?>
                    <div style="background: var(--gold); color: #000; font-size: 0.65rem; font-weight: 700; padding: 2px 10px; display: inline-block; margin-bottom: 8px;">ПОПУЛЯРНЫЙ</div>
                <?php endif; ?>
                <div style="font-size: 2rem;"><?= $t['icon'] ?></div>
                <div style="font-family: var(--font-mc); font-size: 0.75rem; margin: 8px 0;"><?= $t['label'] ?></div>
                <div style="font-family: var(--font-mc); font-size: 1.2rem; color: var(--gold);"><?= $cost ?> 💰</div>
                <div style="font-size: 0.7rem; color: var(--text-muted); margin-bottom: 12px;"><?= round($cost / $days, 1) ?> 💰/день</div>
                <ul style="list-style: none; text-align: left; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 16px;">
                    <li>📌 Закрепление в топе каталога</li>
                    <li>⭐ Золотая рамка</li>
                    <li>📈 Приоритет в поиске</li>
                </ul>
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="type" value="<?= $key ?>">
                    <?php if ($canAfford): ?>
                        <button type="submit" class="btn btn-gold btn-block">Продвинуть за <?= $cost ?> 💰</button>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/dashboard/buy_coins.php" class="btn btn-ghost btn-block">Нужно <?= $cost ?> 💰</a>
                    <?php endif; ?>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Разница -->
    <div class="card" style="margin-top: 16px;">
        <h3 style="margin-bottom: 8px;">⭐ Продвижение vs ⚡ Выделение</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th></th><th>⭐ Продвижение (💰)</th><th>⚡ Выделение (💎)</th></tr></thead>
                <tbody>
                    <tr><td>Валюта</td><td style="color: var(--gold);">Монеты (платные)</td><td style="color: var(--diamond);">Алмазы (бесплатные)</td></tr>
                    <tr><td>Длительность</td><td>7-30 дней</td><td>1-24 часа</td></tr>
                    <tr><td>Рамка</td><td style="color: var(--gold);">⭐ Золотая</td><td style="color: var(--diamond);">⚡ Алмазная</td></tr>
                    <tr><td>Позиция</td><td>Самый верх каталога</td><td>Ниже продвигаемых</td></tr>
                </tbody>
            </table>
        </div>
        <p style="color: var(--text-muted); font-size: 0.75rem; margin-top: 8px;">
            <a href="<?= SITE_URL ?>/dashboard/highlight.php?id=<?= $serverId ?>">⚡ Выделить за алмазы (бесплатно)</a>
            · <a href="<?= SITE_URL ?>/page.php?slug=offer">Оферта</a>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
