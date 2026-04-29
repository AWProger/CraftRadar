<?php
/**
 * CraftRadar — Админка: Управление серверами
 */

$adminPageTitle = 'Серверы';
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();

// Параметры
$page = max(1, getInt('page', 1));
$status = get('status');
$search = get('q');
$perPage = 50;

// Построение запроса
$where = ['1=1'];
$params = [];

if ($status) {
    $where[] = 's.status = ?';
    $params[] = $status;
}

if ($search) {
    $where[] = '(s.name LIKE ? OR s.ip LIKE ? OR s.id = ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = (int)$search;
}

if (get('offline_long') === '1') {
    $where[] = "s.status = 'active' AND s.is_online = 0 AND s.last_ping < DATE_SUB(NOW(), INTERVAL 7 DAY)";
}

$whereSQL = implode(' AND ', $where);

// Подсчёт
$stmt = $db->prepare("SELECT COUNT(*) FROM servers s WHERE {$whereSQL}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

// Выборка
$offset = ($page - 1) * $perPage;
$stmt = $db->prepare("
    SELECT s.*, u.username as owner_name 
    FROM servers s 
    JOIN users u ON s.user_id = u.id 
    WHERE {$whereSQL} 
    ORDER BY s.created_at DESC 
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$servers = $stmt->fetchAll();

// Массовые действия
if (isPost() && isAdmin()) {
    if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        setFlash('error', 'Ошибка безопасности.');
    } else {
        $action = post('bulk_action');
        $ids = array_map('intval', $_POST['ids'] ?? []);

        if (!empty($ids) && $action) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            switch ($action) {
                case 'approve':
                    $db->prepare("UPDATE servers SET status = 'active' WHERE id IN ({$placeholders})")->execute($ids);
                    foreach ($ids as $sid) adminLog('approve_server', 'server', $sid);
                    setFlash('success', 'Серверы одобрены.');
                    break;
                case 'reject':
                    $db->prepare("UPDATE servers SET status = 'rejected' WHERE id IN ({$placeholders})")->execute($ids);
                    foreach ($ids as $sid) adminLog('reject_server', 'server', $sid);
                    setFlash('success', 'Серверы отклонены.');
                    break;
                case 'ban':
                    $db->prepare("UPDATE servers SET status = 'banned' WHERE id IN ({$placeholders})")->execute($ids);
                    foreach ($ids as $sid) adminLog('ban_server', 'server', $sid);
                    setFlash('success', 'Серверы забанены.');
                    break;
            }
            redirect($_SERVER['REQUEST_URI']);
        }
    }
}

$baseUrl = SITE_URL . '/admin/servers.php?x=1';
if ($status) $baseUrl .= '&status=' . urlencode($status);
if ($search) $baseUrl .= '&q=' . urlencode($search);
?>

<div class="admin-table-header">
    <div>Всего: <?= $total ?></div>
</div>

<!-- Фильтры -->
<form method="GET" class="admin-filters">
    <select name="status">
        <option value="">Все статусы</option>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        <option value="banned" <?= $status === 'banned' ? 'selected' : '' ?>>Banned</option>
    </select>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Поиск по названию, IP, ID...">
    <button type="submit" class="btn btn-sm btn-primary">Найти</button>
</form>

<!-- Таблица -->
<form method="POST">
    <?= csrfField() ?>

    <?php if (isAdmin()): ?>
        <div style="margin-bottom: 12px; display: flex; gap: 8px; align-items: center;">
            <select name="bulk_action" class="admin-filters" style="margin: 0;">
                <option value="">Массовое действие</option>
                <option value="approve">Одобрить</option>
                <option value="reject">Отклонить</option>
                <option value="ban">Забанить</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline">Применить</button>
        </div>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <?php if (isAdmin()): ?><th><input type="checkbox" id="selectAll"></th><?php endif; ?>
                    <th>ID</th>
                    <th>Название</th>
                    <th>IP:Порт</th>
                    <th>Владелец</th>
                    <th>Статус</th>
                    <th>Онлайн</th>
                    <th>Голоса</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($servers as $s): ?>
                    <tr>
                        <?php if (isAdmin()): ?>
                            <td><input type="checkbox" name="ids[]" value="<?= $s['id'] ?>" class="row-checkbox"></td>
                        <?php endif; ?>
                        <td><?= $s['id'] ?></td>
                        <td><a href="<?= SITE_URL ?>/admin/server_view.php?id=<?= $s['id'] ?>"><?= e($s['name']) ?></a></td>
                        <td><?= e($s['ip'] . ':' . $s['port']) ?></td>
                        <td><a href="<?= SITE_URL ?>/admin/user_view.php?id=<?= $s['user_id'] ?>"><?= e($s['owner_name']) ?></a></td>
                        <td>
                            <?php
                            $statusBadge = match($s['status']) {
                                'active' => 'badge-online',
                                'pending' => 'badge-pending',
                                default => 'badge-offline'
                            };
                            ?>
                            <span class="badge <?= $statusBadge ?>"><?= e($s['status']) ?></span>
                        </td>
                        <td>
                            <?php if ($s['is_online']): ?>
                                <?= $s['players_online'] ?>/<?= $s['players_max'] ?>
                            <?php else: ?>
                                <span style="color: var(--text-muted);">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $s['votes_month'] ?></td>
                        <td><?= formatDate($s['created_at']) ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="<?= SITE_URL ?>/admin/server_view.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-ghost">👁</a>
                                <a href="<?= SITE_URL ?>/server.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-ghost" target="_blank">🔗</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</form>

<?= paginate($total, $perPage, $page, $baseUrl) ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
