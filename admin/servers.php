<?php
/**
 * CraftRadar — Админка: Управление серверами
 */

$adminPageTitle = 'Серверы';
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();

// Экспорт CSV
if (get('export') === 'csv' && isAdmin()) {
    $all = $db->query("SELECT s.id, s.name, s.ip, s.port, s.status, s.is_online, s.players_online, s.players_max, s.votes_month, s.votes_total, s.is_verified, u.username as owner, s.created_at FROM servers s JOIN users u ON s.user_id = u.id ORDER BY s.id")->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=servers_' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['ID', 'Название', 'IP', 'Порт', 'Статус', 'Онлайн', 'Игроков', 'Макс', 'Голоса мес', 'Голоса всего', 'Верифицирован', 'Владелец', 'Создан'], ';');
    foreach ($all as $r) fputcsv($out, array_values($r), ';');
    fclose($out);
    exit;
}

// Параметры
$page = max(1, getInt('page', 1));
$status = get('status');
$search = get('q');
$perPage = ADMIN_PER_PAGE;

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
    $where[] = "s.status = 'active' AND s.is_online = 0 AND s.last_ping < ?";
    $params[] = dateAgo(7, 'day');
}

if (get('verified') !== '') {
    $where[] = 's.is_verified = ?';
    $params[] = (int)get('verified');
}

if (get('date_from')) {
    $where[] = 's.created_at >= ?';
    $params[] = get('date_from') . ' 00:00:00';
}
if (get('date_to')) {
    $where[] = 's.created_at <= ?';
    $params[] = get('date_to') . ' 23:59:59';
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
    ORDER BY " . match(get('sort', 'date')) {
        'name' => 's.name ASC',
        'votes' => 's.votes_month DESC',
        'online' => 's.players_online DESC',
        'status' => 's.status ASC',
        default => 's.created_at DESC',
    } . "
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
    <select name="verified">
        <option value="">Верификация</option>
        <option value="1" <?= get('verified') === '1' ? 'selected' : '' ?>>✓ Верифицированные</option>
        <option value="0" <?= get('verified') === '0' ? 'selected' : '' ?>>Не верифицированные</option>
    </select>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Поиск по названию, IP, ID...">
    <input type="date" name="date_from" value="<?= e(get('date_from')) ?>" title="Дата от">
    <input type="date" name="date_to" value="<?= e(get('date_to')) ?>" title="Дата до">
    <button type="submit" class="btn btn-sm btn-primary">Найти</button>
    <a href="<?= SITE_URL ?>/admin/servers.php?export=csv" class="btn btn-sm btn-outline">📥 CSV</a>
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
                    <th><a href="?sort=name<?= $status ? '&status=' . e($status) : '' ?>" style="color:inherit;">Название ↕</a></th>
                    <th>IP:Порт</th>
                    <th>Владелец</th>
                    <th><a href="?sort=status<?= $status ? '&status=' . e($status) : '' ?>" style="color:inherit;">Статус ↕</a></th>
                    <th><a href="?sort=online<?= $status ? '&status=' . e($status) : '' ?>" style="color:inherit;">Онлайн ↕</a></th>
                    <th><a href="?sort=votes<?= $status ? '&status=' . e($status) : '' ?>" style="color:inherit;">Голоса ↕</a></th>
                    <th>Пинг</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($servers)): ?>
                    <tr><td colspan="12" style="text-align: center; color: var(--text-muted); padding: 30px;">Серверы не найдены</td></tr>
                <?php endif; ?>
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
                        <td style="font-size: 0.75rem; color: var(--text-muted);"><?= $s['last_ping'] ? formatDate($s['last_ping']) : '—' ?></td>
                        <td><?= formatDate($s['created_at']) ?></td>
                        <td>
                            <div class="action-btns">
                                <a href="<?= SITE_URL ?>/admin/server_view.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-ghost">👁</a>
                                <a href="<?= SITE_URL ?>/server.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-ghost" target="_blank">🔗</a>
                                <?php if ($s['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="bulk_action" value="approve">
                                        <input type="hidden" name="ids[]" value="<?= $s['id'] ?>">
                                        <button class="btn btn-sm btn-primary" title="Одобрить">✅</button>
                                    </form>
                                <?php endif; ?>
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
