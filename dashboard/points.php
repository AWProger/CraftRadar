<?php
/**
 * CraftRadar — Мои баллы
 */

$pageTitle = 'Мои баллы';
require_once __DIR__ . '/../includes/header.php';
require_once INCLUDES_PATH . 'points.php';

requireAuth();

$userId = currentUserId();
$userPoints = getUserPoints($userId);
$history = getPointHistory($userId, 50);

// Мои серверы для выделения
$db = getDB();
$myServers = $db->prepare("SELECT id, name, status, highlighted_until FROM servers WHERE user_id = ? AND status IN ('active', 'pending') ORDER BY name");
$myServers->execute([$userId]);
$myServers = $myServers->fetchAll();
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>💎 Мои баллы</h1>
        <a href="<?= SITE_URL ?>/dashboard/" class="btn btn-ghost">← Кабинет</a>
    </div>

    <!-- Баланс -->
    <div class="card" style="text-align: center; margin-bottom: 20px;">
        <div style="font-size: 2.5rem; margin-bottom: 4px;">💎</div>
        <div style="font-family: var(--font-mc); font-size: 1.2rem; color: var(--gold); text-shadow: 2px 2px 0 rgba(0,0,0,0.5);">
            <?= $userPoints ?> баллов
        </div>
    </div>

    <!-- Как заработать -->
    <div class="card" style="margin-bottom: 16px;">
        <h2 class="section-title">📈 Как заработать баллы</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
            <div style="padding: 16px; border: 2px solid var(--border); text-align: center;">
                <div style="font-size: 1.5rem; margin-bottom: 6px;">👍</div>
                <div style="font-weight: 700; margin-bottom: 4px;">Голосуй за серверы</div>
                <div style="color: var(--gold); font-family: var(--font-mc); font-size: 0.7rem;">+<?= POINTS_PER_VOTE ?> 💎 за голос</div>
                <div style="color: var(--text-muted); font-size: 0.75rem; margin-top: 4px;">1 голос в 24 часа за каждый сервер</div>
            </div>
        </div>
    </div>

    <!-- На что потратить -->
    <div class="card" style="margin-bottom: 16px;">
        <h2 class="section-title">⚡ На что потратить</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
            <?php foreach (HIGHLIGHT_COSTS as $key => $cost): ?>
                <?php
                $icons = ['1h' => '⚡', '6h' => '🔥', '24h' => '💎'];
                $labels = ['1h' => '1 час', '6h' => '6 часов', '24h' => '24 часа'];
                ?>
                <div style="padding: 16px; border: 2px solid var(--border); text-align: center;">
                    <div style="font-size: 1.5rem; margin-bottom: 6px;"><?= $icons[$key] ?? '⚡' ?></div>
                    <div style="font-weight: 700; margin-bottom: 4px;">Выделение на <?= $labels[$key] ?? $key ?></div>
                    <div style="color: var(--diamond); font-family: var(--font-mc); font-size: 0.7rem;"><?= $cost['points'] ?> 💎</div>
                    <div style="color: var(--text-muted); font-size: 0.75rem; margin-top: 4px;">Алмазная рамка в каталоге</div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Выделить сервер -->
    <?php if (!empty($myServers)): ?>
    <div class="card" style="margin-bottom: 16px;">
        <h2 class="section-title">🎯 Выделить мой сервер</h2>
        <?php foreach ($myServers as $srv): ?>
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border);">
                <div>
                    <strong><?= e($srv['name']) ?></strong>
                    <?php if ($srv['highlighted_until'] && strtotime($srv['highlighted_until']) > time()): ?>
                        <span class="badge badge-diamond">⚡ до <?= formatDate($srv['highlighted_until']) ?></span>
                    <?php endif; ?>
                </div>
                <a href="<?= SITE_URL ?>/dashboard/highlight.php?id=<?= $srv['id'] ?>" class="btn btn-sm btn-gold">⚡ Выделить</a>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- История -->
    <div class="card">
        <h2 class="section-title">📋 История баллов</h2>
        <?php if (empty($history)): ?>
            <p style="color: var(--text-muted);">Транзакций пока нет. Голосуй за серверы чтобы заработать баллы!</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Дата</th><th>Баллы</th><th>Описание</th></tr></thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td><?= formatDate($h['created_at']) ?></td>
                                <td style="color: <?= $h['amount'] > 0 ? 'var(--success)' : 'var(--danger)' ?>; font-weight: 700; font-family: var(--font-mc); font-size: 0.7rem;">
                                    <?= $h['amount'] > 0 ? '+' : '' ?><?= $h['amount'] ?> 💎
                                </td>
                                <td style="color: var(--text-muted);"><?= e($h['description']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
