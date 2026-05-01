<?php
/**
 * CraftRadar — Личный кабинет
 */

$pageTitle = 'Личный кабинет';
require_once __DIR__ . '/../includes/header.php';

requireAuth();

$db = getDB();
$userId = currentUserId();

// Мои серверы
$stmt = $db->prepare('
    SELECT id, name, ip, port, status, is_online, players_online, players_max, votes_month, votes_total, is_verified, highlighted_until, reject_reason, created_at
    FROM servers 
    WHERE user_id = ? 
    ORDER BY created_at DESC
');
$stmt->execute([$userId]);
$servers = $stmt->fetchAll();

// Статистика
$totalVotes = 0;
$totalOnline = 0;
foreach ($servers as $s) {
    $totalVotes += $s['votes_total'];
    if ($s['is_online']) $totalOnline += $s['players_online'];
}
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Личный кабинет</h1>
        <div style="display: flex; gap: 8px;">
            <a href="<?= SITE_URL ?>/dashboard/profile.php" class="btn btn-sm btn-outline">👤 Профиль</a>
            <a href="<?= SITE_URL ?>/dashboard/add.php" class="btn btn-primary">+ Добавить сервер</a>
        </div>
    </div>

    <!-- Сводка -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-value"><?= count($servers) ?></div>
            <div class="stat-card-label">Серверов</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-value"><?= $totalVotes ?></div>
            <div class="stat-card-label">Голосов всего</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-value"><?= $totalOnline ?></div>
            <div class="stat-card-label">Игроков онлайн</div>
        </div>
    </div>

    <!-- Мои серверы -->
    <h2 class="section-title" style="margin-top: 30px;">Мои серверы</h2>

    <?php if (empty($servers)): ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <p style="color: var(--text-muted); margin-bottom: 16px;">У вас пока нет серверов.</p>
            <a href="<?= SITE_URL ?>/dashboard/add.php" class="btn btn-primary">Добавить первый сервер</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Название</th>
                        <th>IP</th>
                        <th>Статус</th>
                        <th>Онлайн</th>
                        <th>Голоса (мес.)</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($servers as $s): ?>
                        <tr>
                            <td>
                                <a href="<?= SITE_URL ?>/server.php?id=<?= $s['id'] ?>"><?= e($s['name']) ?></a>
                            </td>
                            <td>
                                <span class="copy-ip" data-ip="<?= e($s['ip'] . ':' . $s['port']) ?>">
                                    <?= e($s['ip'] . ':' . $s['port']) ?> 📋
                                </span>
                            </td>
                            <td>
                                <?php if ($s['status'] === 'active'): ?>
                                    <?php if ($s['is_online']): ?>
                                        <span class="badge badge-online">Онлайн</span>
                                    <?php else: ?>
                                        <span class="badge badge-offline">Оффлайн</span>
                                    <?php endif; ?>
                                <?php elseif ($s['status'] === 'pending'): ?>
                                    <span class="badge badge-pending">На модерации</span>
                                <?php elseif ($s['status'] === 'rejected'): ?>
                                    <span class="badge badge-offline">Отклонён</span>
                                    <?php if (!empty($s['reject_reason'])): ?>
                                        <span style="color: var(--danger); font-size: 0.75rem;"> — <?= e($s['reject_reason']) ?></span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge badge-offline">Забанен</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $s['players_online'] ?>/<?= $s['players_max'] ?></td>
                            <td><?= $s['votes_month'] ?></td>
                            <td>
                                <a href="<?= SITE_URL ?>/dashboard/edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-outline">Редактировать</a>
                                <a href="<?= SITE_URL ?>/dashboard/stats.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-ghost">Статистика</a>
                                <?php if ($s['status'] === 'active'): ?>
                                    <a href="<?= SITE_URL ?>/dashboard/promote.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-ghost" style="color: var(--warning);">⭐ Продвинуть</a>
                                <?php endif; ?>
                                <?php if (!$s['is_verified']): ?>
                                    <a href="<?= SITE_URL ?>/dashboard/verify.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-ghost" style="color: var(--info);">🔐 Верифицировать</a>
                                <?php else: ?>
                                    <span class="badge badge-online" style="margin-left: 4px;">✓ Владелец</span>
                                <?php endif; ?>
                                <?php if ($s['status'] === 'active'): ?>
                                    <a href="<?= SITE_URL ?>/dashboard/highlight.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-ghost" style="color: var(--diamond);">⚡ Выделить</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<style>
    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
        flex-wrap: wrap;
        gap: 12px;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 12px;
    }
    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 20px;
        text-align: center;
    }
    .stat-card-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--accent);
    }
    .stat-card-label {
        font-size: 0.85rem;
        color: var(--text-muted);
        margin-top: 4px;
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
