<?php
/**
 * CraftRadar — Админка: Лог действий
 */

$adminPageTitle = 'Лог действий';
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();

$page = max(1, getInt('page', 1));
$adminFilter = get('admin_id');
$actionFilter = get('action');
$perPage = 50;

$where = ['1=1'];
$params = [];

// Модераторы видят только свои логи
if (!isAdmin()) {
    $where[] = 'al.admin_id = ?';
    $params[] = currentUserId();
}

if ($adminFilter) {
    $where[] = 'al.admin_id = ?';
    $params[] = (int)$adminFilter;
}

if ($actionFilter) {
    $where[] = 'al.action LIKE ?';
    $params[] = "%{$actionFilter}%";
}

$whereSQL = implode(' AND ', $where);

$stmt = $db->prepare("SELECT COUNT(*) FROM admin_log al WHERE {$whereSQL}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$offset = ($page - 1) * $perPage;
$stmt = $db->prepare("
    SELECT al.*, u.username 
    FROM admin_log al 
    JOIN users u ON al.admin_id = u.id 
    WHERE {$whereSQL} 
    ORDER BY al.created_at DESC 
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$baseUrl = SITE_URL . '/admin/log.php?x=1';
if ($adminFilter) $baseUrl .= '&admin_id=' . urlencode($adminFilter);
if ($actionFilter) $baseUrl .= '&action=' . urlencode($actionFilter);
?>

<form method="GET" class="admin-filters">
    <?php if (isAdmin()): ?>
        <input type="text" name="admin_id" value="<?= e($adminFilter) ?>" placeholder="ID администратора">
    <?php endif; ?>
    <input type="text" name="action" value="<?= e($actionFilter) ?>" placeholder="Тип действия...">
    <button type="submit" class="btn btn-sm btn-primary">Фильтр</button>
</form>

<div class="table-wrap">
    <table>
        <thead>
            <tr><th>Дата</th><th>Администратор</th><th>Действие</th><th>Объект</th><th>ID</th><th>IP</th><th>Детали</th></tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= formatDate($log['created_at']) ?></td>
                    <td><a href="<?= SITE_URL ?>/admin/user_view.php?id=<?= $log['admin_id'] ?>"><?= e($log['username']) ?></a></td>
                    <td><?= e($log['action']) ?></td>
                    <td><?= e($log['target_type']) ?></td>
                    <td>
                        <?php if ($log['target_type'] === 'server'): ?>
                            <a href="<?= SITE_URL ?>/admin/server_view.php?id=<?= $log['target_id'] ?>">#<?= $log['target_id'] ?></a>
                        <?php elseif ($log['target_type'] === 'user'): ?>
                            <a href="<?= SITE_URL ?>/admin/user_view.php?id=<?= $log['target_id'] ?>">#<?= $log['target_id'] ?></a>
                        <?php else: ?>
                            #<?= $log['target_id'] ?>
                        <?php endif; ?>
                    </td>
                    <td style="font-size: 0.8rem; color: var(--text-muted);"><?= e($log['ip_address'] ?? '') ?></td>
                    <td style="font-size: 0.8rem; color: var(--text-muted);"><?= e(truncate($log['details'] ?? '', 60)) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= paginate($total, $perPage, $page, $baseUrl) ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
