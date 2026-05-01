<?php
/**
 * CraftRadar — Выделить сервер за баллы
 */

$pageTitle = 'Выделить сервер';
require_once __DIR__ . '/../includes/header.php';
require_once INCLUDES_PATH . 'points.php';

requireAuth();

$db = getDB();
$userId = currentUserId();
$serverId = getInt('id');

$stmt = $db->prepare("SELECT * FROM servers WHERE id = ? AND user_id = ? AND status = 'active'");
$stmt->execute([$serverId, $userId]);
$server = $stmt->fetch();

if (!$server) {
    setFlash('error', 'Сервер не найден.');
    redirect(SITE_URL . '/dashboard/');
}

$userPoints = getUserPoints($userId);
$errors = [];
$result = null;

if (isPost()) {
    if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $errors[] = 'Ошибка безопасности.';
    } else {
        $duration = post('duration');
        $result = highlightServer($userId, $serverId, $duration);
        if ($result['success']) {
            setFlash('success', "Сервер выделен до " . formatDate($result['until']) . "! Потрачено {$result['points_spent']} баллов.");
            redirect(SITE_URL . '/dashboard/');
        } else {
            $errors[] = $result['error'];
        }
    }
    $userPoints = getUserPoints($userId);
}

$isCurrentlyHighlighted = isHighlighted($server);
$history = getPointHistory($userId, 10);
?>

<div class="dashboard">
    <?= dashboardNav('servers') ?>
    <?= breadcrumbs([
        ['url' => SITE_URL . '/', 'label' => 'Главная'],
        ['url' => SITE_URL . '/dashboard/', 'label' => 'Кабинет'],
        ['url' => '', 'label' => 'Выделить сервер']
    ]) ?>
    <div class="dashboard-header">
        <h1>⚡ Выделить сервер</h1>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Баланс -->
    <div class="card" style="margin-bottom: 16px; text-align: center;">
        <div class="points-display" style="font-size: 1rem; justify-content: center;">
            <span class="points-icon">💎</span>
            <span>Ваши баллы: <?= $userPoints ?></span>
        </div>
        <?php
        // Прогресс до следующего тарифа
        $nextCost = 0;
        foreach (HIGHLIGHT_COSTS as $c) {
            if ($c['points'] > $userPoints) { $nextCost = $c['points']; break; }
        }
        if ($nextCost > 0):
            $progress = min(100, round(($userPoints / $nextCost) * 100));
        ?>
        <div style="margin-top: 8px;">
            <div style="display: flex; justify-content: space-between; font-size: 0.7rem; color: var(--text-muted); margin-bottom: 4px;">
                <span><?= $userPoints ?> 💎</span>
                <span><?= $nextCost ?> 💎 (следующий тариф)</span>
            </div>
            <div style="height: 8px; background: var(--bg); border: 2px solid var(--border);">
                <div style="height: 100%; width: <?= $progress ?>%; background: var(--diamond); transition: width 0.3s;"></div>
            </div>
        </div>
        <?php endif; ?>
        <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 4px;">
            Баллы начисляются за голосование за серверы (1 балл за голос)
        </p>
    </div>

    <?php if ($isCurrentlyHighlighted): ?>
        <div class="alert alert-info">
            ⚡ Сервер «<?= e($server['name']) ?>» уже выделен до <?= formatDate($server['highlighted_until']) ?>.
            Вы можете продлить — время добавится.
        </div>
    <?php endif; ?>

    <!-- Тарифы -->
    <div class="promote-grid">
        <?php
        $options = [];
        foreach (HIGHLIGHT_COSTS as $key => $c) {
            $icons = ['1h' => '⚡', '6h' => '🔥', '24h' => '💎'];
            $labels = ['1h' => '1 час', '6h' => '6 часов', '24h' => '24 часа'];
            $options[$key] = [
                'hours' => $c['hours'],
                'cost'  => $c['points'],
                'icon'  => $icons[$key] ?? '⚡',
                'label' => $labels[$key] ?? $c['hours'] . 'ч',
            ];
        }
        ?>
        <?php foreach ($options as $key => $opt): ?>
            <div class="promote-card <?= $key === '6h' ? 'promote-card-popular' : '' ?>">
                <?php if ($key === '6h'): ?>
                    <div class="promote-badge">Выгодно</div>
                <?php endif; ?>
                <div class="promote-icon"><?= $opt['icon'] ?></div>
                <div class="promote-days"><?= $opt['label'] ?></div>
                <div class="promote-price" style="color: var(--diamond);"><?= $opt['cost'] ?> 💎</div>
                <div class="promote-per-day"><?= round($opt['cost'] / $opt['hours'], 1) ?> баллов/час</div>
                <ul class="promote-features">
                    <li>⚡ Цветная рамка в каталоге</li>
                    <li>📊 Выше в результатах поиска</li>
                    <li>👁 Привлекает внимание игроков</li>
                </ul>
                <form method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="duration" value="<?= $key ?>">
                    <button type="submit" class="btn <?= $userPoints >= $opt['cost'] ? 'btn-gold' : 'btn-ghost' ?> btn-block"
                            <?= $userPoints < $opt['cost'] ? 'disabled' : '' ?>>
                        <?php if ($userPoints >= $opt['cost']): ?>
                            Выделить за <?= $opt['cost'] ?> 💎
                        <?php else: ?>
                            Нужно ещё <?= $opt['cost'] - $userPoints ?> 💎
                        <?php endif; ?>
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- История баллов -->
    <?php if (!empty($history)): ?>
        <div class="card" style="margin-top: 20px;">
            <h3 class="section-title">История баллов</h3>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Дата</th><th>Баллы</th><th>Тип</th><th>Описание</th></tr></thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?= formatDate($h['created_at']) ?></td>
                                <td style="color: <?= $h['amount'] > 0 ? 'var(--success)' : 'var(--danger)' ?>; font-weight: 700;">
                                    <?= $h['amount'] > 0 ? '+' : '' ?><?= $h['amount'] ?>
                                </td>
                                <td><?= e($h['type']) ?></td>
                                <td style="color: var(--text-muted);"><?= e($h['description']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .promote-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; }
    .promote-card { background: var(--bg-card); border: var(--pixel-border) var(--border); padding: 24px; text-align: center; position: relative; box-shadow: var(--shadow); transition: border-color var(--transition); }
    .promote-card:hover { border-color: var(--diamond); }
    .promote-card-popular { border-color: var(--diamond); box-shadow: 4px 4px 0 rgba(92, 225, 230, 0.2); }
    .promote-badge { position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: var(--diamond); color: #000; padding: 2px 12px; font-size: 0.7rem; font-weight: 700; }
    .promote-icon { font-size: 2.5rem; margin-bottom: 8px; }
    .promote-days { font-family: var(--font-mc); font-size: 0.7rem; margin-bottom: 4px; }
    .promote-price { font-family: var(--font-mc); font-size: 1rem; font-weight: 800; margin-bottom: 4px; }
    .promote-per-day { font-size: 0.75rem; color: var(--text-muted); margin-bottom: 16px; }
    .promote-features { list-style: none; text-align: left; margin-bottom: 20px; font-size: 0.8rem; color: var(--text-muted); }
    .promote-features li { padding: 4px 0; }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
