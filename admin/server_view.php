<?php
/**
 * CraftRadar — Админка: Карточка сервера
 */

$adminPageTitle = 'Сервер';
require_once __DIR__ . '/includes/admin_header.php';
require_once INCLUDES_PATH . 'minecraft_ping.php';

$db = getDB();
$id = getInt('id');

$stmt = $db->prepare('SELECT s.*, u.username as owner_name FROM servers s JOIN users u ON s.user_id = u.id WHERE s.id = ?');
$stmt->execute([$id]);
$server = $stmt->fetch();

if (!$server) {
    setFlash('error', 'Сервер не найден.');
    redirect(SITE_URL . '/admin/servers.php');
}

// Обработка действий
if (isPost()) {
    if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        setFlash('error', 'Ошибка безопасности.');
    } else {
        $action = post('action');

        switch ($action) {
            case 'approve':
                $db->prepare("UPDATE servers SET status = 'active' WHERE id = ?")->execute([$id]);
                adminLog('approve_server', 'server', $id);
                setFlash('success', 'Сервер одобрен.');
                break;

            case 'reject':
                $reason = post('reason');
                $db->prepare("UPDATE servers SET status = 'rejected', reject_reason = ? WHERE id = ?")->execute([$reason, $id]);
                adminLog('reject_server', 'server', $id, json_encode(['reason' => $reason]));
                setFlash('success', 'Сервер отклонён.');
                break;

            case 'ban':
                $reason = post('reason');
                $db->prepare("UPDATE servers SET status = 'banned', reject_reason = ? WHERE id = ?")->execute([$reason, $id]);
                adminLog('ban_server', 'server', $id, json_encode(['reason' => $reason]));
                setFlash('success', 'Сервер забанен.');
                break;

            case 'unban':
                $db->prepare("UPDATE servers SET status = 'active', reject_reason = NULL WHERE id = ?")->execute([$id]);
                adminLog('unban_server', 'server', $id);
                setFlash('success', 'Сервер разбанен.');
                break;

            case 'delete':
                if (isAdmin()) {
                    adminLog('delete_server', 'server', $id, json_encode(['name' => $server['name']]));
                    $db->prepare('DELETE FROM servers WHERE id = ?')->execute([$id]);
                    setFlash('success', 'Сервер удалён.');
                    redirect(SITE_URL . '/admin/servers.php');
                }
                break;

            case 'ping':
                $result = pingMinecraftServer($server['ip'], $server['port'], PING_TIMEOUT);
                if ($result) {
                    $db->prepare('UPDATE servers SET is_online = 1, players_online = ?, players_max = ?, motd = ?, last_ping = ?, consecutive_fails = 0 WHERE id = ?')
                        ->execute([$result['players'], $result['max_players'], mb_substr($result['motd'], 0, 255), now(), $id]);
                    setFlash('success', "Пинг успешен: {$result['players']}/{$result['max_players']} ({$result['ping_ms']}ms)");
                } else {
                    setFlash('error', 'Сервер не отвечает.');
                }
                break;

            case 'reset_votes':
                if (isAdmin()) {
                    $type = post('reset_type');
                    if ($type === 'month') {
                        $db->prepare('UPDATE servers SET votes_month = 0 WHERE id = ?')->execute([$id]);
                    } else {
                        $db->prepare('UPDATE servers SET votes_total = 0, votes_month = 0 WHERE id = ?')->execute([$id]);
                    }
                    adminLog('reset_votes', 'server', $id, json_encode(['type' => $type]));
                    setFlash('success', 'Голоса сброшены.');
                }
                break;

            case 'promote':
                if (isAdmin()) {
                    $until = post('promoted_until');
                    $db->prepare('UPDATE servers SET is_promoted = 1, promoted_until = ? WHERE id = ?')->execute([$until ?: null, $id]);
                    adminLog('promote_server', 'server', $id);
                    setFlash('success', 'Сервер закреплён в топе.');
                }
                break;

            case 'unpromote':
                if (isAdmin()) {
                    $db->prepare('UPDATE servers SET is_promoted = 0, promoted_until = NULL WHERE id = ?')->execute([$id]);
                    adminLog('unpromote_server', 'server', $id);
                    setFlash('success', 'Сервер откреплён.');
                }
                break;
        }

        redirect(SITE_URL . '/admin/server_view.php?id=' . $id);
    }
}

// Обновляем данные после действий
$stmt = $db->prepare('SELECT s.*, u.username as owner_name FROM servers s JOIN users u ON s.user_id = u.id WHERE s.id = ?');
$stmt->execute([$id]);
$server = $stmt->fetch();

// Отзывы
$reviews = $db->prepare('SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.server_id = ? ORDER BY r.created_at DESC');
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();

// Жалобы на этот сервер
$reports = $db->prepare("SELECT r.*, u.username as reporter_name FROM reports r JOIN users u ON r.reporter_id = u.id WHERE r.target_type = 'server' AND r.target_id = ? ORDER BY r.created_at DESC");
$reports->execute([$id]);
$reports = $reports->fetchAll();
?>

<div style="margin-bottom: 16px;">
    <a href="<?= SITE_URL ?>/admin/servers.php" class="btn btn-ghost btn-sm">← Назад к серверам</a>
</div>

<!-- Информация -->
<div class="card" style="margin-bottom: 16px;">
    <div style="display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap;">
        <?php if ($server['icon']): ?>
            <img src="<?= SITE_URL . '/' . e($server['icon']) ?>" alt="" style="width: 64px; height: 64px; border-radius: var(--radius);">
        <?php endif; ?>
        <div style="flex: 1;">
            <h2><?= e($server['name']) ?></h2>
            <p style="color: var(--text-muted);"><?= e($server['ip'] . ':' . $server['port']) ?></p>
            <div style="display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap;">
                <?php
                $statusBadge = match($server['status']) {
                    'active' => 'badge-online',
                    'pending' => 'badge-pending',
                    default => 'badge-offline'
                };
                ?>
                <span class="badge <?= $statusBadge ?>"><?= e($server['status']) ?></span>
                <?php if ($server['is_online']): ?>
                    <span class="badge badge-online"><?= $server['players_online'] ?>/<?= $server['players_max'] ?></span>
                <?php else: ?>
                    <span class="badge badge-offline">Оффлайн</span>
                <?php endif; ?>
                <?php if ($server['is_promoted']): ?>
                    <span class="badge badge-pending">⭐ Promoted</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="table-wrap" style="margin-top: 16px;">
        <table>
            <tr><td style="color: var(--text-muted);">Владелец</td><td><a href="<?= SITE_URL ?>/admin/user_view.php?id=<?= $server['user_id'] ?>"><?= e($server['owner_name']) ?></a></td></tr>
            <tr><td style="color: var(--text-muted);">Версия</td><td><?= e($server['version'] ?? '—') ?></td></tr>
            <tr><td style="color: var(--text-muted);">Режим</td><td><?= e($server['game_mode'] ?? '—') ?></td></tr>
            <tr><td style="color: var(--text-muted);">MOTD</td><td><?= e($server['motd'] ?? '—') ?></td></tr>
            <tr><td style="color: var(--text-muted);">Голоса (месяц/всего)</td><td><?= $server['votes_month'] ?> / <?= $server['votes_total'] ?></td></tr>
            <tr><td style="color: var(--text-muted);">Рейтинг</td><td><?= $server['rating'] ?> (<?= $server['reviews_count'] ?> отзывов)</td></tr>
            <tr><td style="color: var(--text-muted);">Добавлен</td><td><?= formatDate($server['created_at']) ?></td></tr>
            <tr><td style="color: var(--text-muted);">Последний пинг</td><td><?= $server['last_ping'] ? formatDate($server['last_ping']) : '—' ?></td></tr>
            <tr><td style="color: var(--text-muted);">Неудачных пингов подряд</td><td><?= $server['consecutive_fails'] ?></td></tr>
            <?php if ($server['reject_reason']): ?>
                <tr><td style="color: var(--danger);">Причина отклонения/бана</td><td><?= e($server['reject_reason']) ?></td></tr>
            <?php endif; ?>
        </table>
    </div>

    <?php if ($server['description']): ?>
        <div style="margin-top: 16px; padding: 12px; background: var(--bg); border-radius: var(--radius);">
            <strong>Описание:</strong><br>
            <?= nl2br(e($server['description'])) ?>
        </div>
    <?php endif; ?>
</div>

<!-- График онлайна (24ч) -->
<div class="card" style="margin-bottom: 16px;">
    <h3 style="margin-bottom: 12px;">📊 Онлайн за 24 часа</h3>
    <canvas id="adminServerChart" height="150"></canvas>
</div>

<!-- Действия -->
<div class="card" style="margin-bottom: 16px;">
    <h3 style="margin-bottom: 12px;">Действия</h3>
    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <!-- Пинг -->
        <form method="POST" style="display: inline;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="ping">
            <button type="submit" class="btn btn-sm btn-outline">🔄 Пинг</button>
        </form>

        <?php if ($server['status'] === 'pending'): ?>
            <form method="POST" style="display: inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-sm btn-primary">✅ Одобрить</button>
            </form>
            <form method="POST" style="display: inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="reject">
                <input type="text" name="reason" placeholder="Причина отклонения" style="padding: 6px; background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text);">
                <button type="submit" class="btn btn-sm btn-danger">❌ Отклонить</button>
            </form>
        <?php endif; ?>

        <?php if ($server['status'] !== 'banned'): ?>
            <form method="POST" style="display: inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="ban">
                <input type="text" name="reason" placeholder="Причина бана">
                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Забанить сервер?">🚫 Забанить</button>
            </form>
        <?php else: ?>
            <form method="POST" style="display: inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="unban">
                <button type="submit" class="btn btn-sm btn-primary">Разбанить</button>
            </form>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
            <?php if (!$server['is_promoted']): ?>
                <form method="POST" style="display: inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="promote">
                    <input type="datetime-local" name="promoted_until" style="padding: 6px; background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text);">
                    <button type="submit" class="btn btn-sm btn-outline">⭐ Закрепить</button>
                </form>
            <?php else: ?>
                <form method="POST" style="display: inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="unpromote">
                    <button type="submit" class="btn btn-sm btn-ghost">Открепить</button>
                </form>
            <?php endif; ?>

            <form method="POST" style="display: inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Удалить сервер навсегда?">🗑 Удалить</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Отзывы -->
<div class="card" style="margin-bottom: 16px;">
    <h3 style="margin-bottom: 12px;">Отзывы (<?= count($reviews) ?>)</h3>
    <?php if (empty($reviews)): ?>
        <p style="color: var(--text-muted);">Нет отзывов.</p>
    <?php else: ?>
        <?php foreach ($reviews as $r): ?>
            <div style="padding: 8px 0; border-bottom: 1px solid var(--border);">
                <strong><?= e($r['username']) ?></strong>
                <span class="stars"><?= str_repeat('★', $r['rating']) ?></span>
                <span style="color: var(--text-muted); font-size: 0.8rem;"><?= formatDate($r['created_at']) ?></span>
                <span class="badge <?= $r['status'] === 'active' ? 'badge-online' : 'badge-offline' ?>"><?= e($r['status']) ?></span>
                <p style="margin-top: 4px; font-size: 0.9rem;"><?= e(truncate($r['text'], 200)) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Жалобы -->
<div class="card">
    <h3 style="margin-bottom: 12px;">Жалобы (<?= count($reports) ?>)</h3>
    <?php if (empty($reports)): ?>
        <p style="color: var(--text-muted);">Нет жалоб.</p>
    <?php else: ?>
        <?php foreach ($reports as $r): ?>
            <div style="padding: 8px 0; border-bottom: 1px solid var(--border);">
                <strong><?= e($r['reporter_name']) ?></strong> — <?= e($r['reason']) ?>
                <span class="badge <?= $r['status'] === 'new' ? 'badge-pending' : 'badge-online' ?>"><?= e($r['status']) ?></span>
                <span style="color: var(--text-muted); font-size: 0.8rem;"><?= formatDate($r['created_at']) ?></span>
                <p style="margin-top: 4px; font-size: 0.9rem;"><?= e(truncate($r['description'], 200)) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
// Данные для графика
$chartStmt = $db->prepare('SELECT recorded_at as time, players_online as players FROM server_stats WHERE server_id = ? AND recorded_at >= ? ORDER BY recorded_at');
$chartStmt->execute([$id, dateAgo(24, 'hour')]);
$chartData = $chartStmt->fetchAll();
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3"></script>
<script>
var chartData = <?= json_encode(array_map(fn($d) => ['time' => $d['time'], 'players' => (int)$d['players']], $chartData)) ?>;
if (chartData.length > 0) {
    new Chart(document.getElementById('adminServerChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: chartData.map(function(d) { var dt = new Date(d.time); return dt.getHours().toString().padStart(2,'0') + ':' + dt.getMinutes().toString().padStart(2,'0'); }),
            datasets: [{
                data: chartData.map(function(d) { return d.players; }),
                borderColor: '#00ff80', backgroundColor: 'rgba(0,255,128,0.1)',
                fill: true, tension: 0.3, pointRadius: 1
            }]
        },
        options: { responsive: true, plugins: { legend: { display: false } },
            scales: { x: { ticks: { color: '#8b949e', maxTicksLimit: 12 }, grid: { color: 'rgba(48,54,61,0.5)' } },
                      y: { beginAtZero: true, ticks: { color: '#8b949e' }, grid: { color: 'rgba(48,54,61,0.5)' } } } }
    });
} else {
    document.getElementById('adminServerChart').parentElement.innerHTML += '<p style="color:var(--text-muted);text-align:center;">Нет данных</p>';
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
