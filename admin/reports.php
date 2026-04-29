<?php
/**
 * CraftRadar — Админка: Жалобы
 */

$adminPageTitle = 'Жалобы';
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();

$page = max(1, getInt('page', 1));
$status = get('status');
$perPage = 50;

$where = ['1=1'];
$params = [];

if ($status) {
    $where[] = 'r.status = ?';
    $params[] = $status;
}

$whereSQL = implode(' AND ', $where);

$stmt = $db->prepare("SELECT COUNT(*) FROM reports r WHERE {$whereSQL}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$offset = ($page - 1) * $perPage;
$stmt = $db->prepare("
    SELECT r.*, u.username as reporter_name 
    FROM reports r 
    JOIN users u ON r.reporter_id = u.id 
    WHERE {$whereSQL} 
    ORDER BY FIELD(r.status, 'new', 'in_progress', 'resolved', 'rejected'), r.created_at DESC 
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$reports = $stmt->fetchAll();

// Обработка действий
if (isPost()) {
    if (verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $reportId = (int)post('report_id');
        $action = post('action');
        $note = post('resolution_note');

        switch ($action) {
            case 'in_progress':
                $db->prepare("UPDATE reports SET status = 'in_progress' WHERE id = ?")->execute([$reportId]);
                adminLog('report_in_progress', 'report', $reportId);
                break;
            case 'resolve':
                $db->prepare("UPDATE reports SET status = 'resolved', resolved_by = ?, resolution_note = ?, resolved_at = ? WHERE id = ?")
                    ->execute([currentUserId(), $note, now(), $reportId]);
                adminLog('resolve_report', 'report', $reportId, json_encode(['note' => $note]));
                break;
            case 'reject':
                $db->prepare("UPDATE reports SET status = 'rejected', resolved_by = ?, resolution_note = ?, resolved_at = ? WHERE id = ?")
                    ->execute([currentUserId(), $note, now(), $reportId]);
                adminLog('reject_report', 'report', $reportId, json_encode(['note' => $note]));
                break;
        }
        setFlash('success', 'Жалоба обработана.');
        redirect($_SERVER['REQUEST_URI']);
    }
}

$baseUrl = SITE_URL . '/admin/reports.php?x=1';
if ($status) $baseUrl .= '&status=' . urlencode($status);
?>

<form method="GET" class="admin-filters">
    <select name="status">
        <option value="">Все статусы</option>
        <option value="new" <?= $status === 'new' ? 'selected' : '' ?>>Новые</option>
        <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>В работе</option>
        <option value="resolved" <?= $status === 'resolved' ? 'selected' : '' ?>>Решённые</option>
        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Отклонённые</option>
    </select>
    <button type="submit" class="btn btn-sm btn-primary">Фильтр</button>
</form>

<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Тип</th>
                <th>Объект</th>
                <th>От кого</th>
                <th>Причина</th>
                <th>Статус</th>
                <th>Дата</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reports as $r): ?>
                <tr>
                    <td><?= $r['id'] ?></td>
                    <td><?= e($r['target_type']) ?></td>
                    <td>
                        <?php if ($r['target_type'] === 'server'): ?>
                            <a href="<?= SITE_URL ?>/admin/server_view.php?id=<?= $r['target_id'] ?>">#<?= $r['target_id'] ?></a>
                        <?php elseif ($r['target_type'] === 'user'): ?>
                            <a href="<?= SITE_URL ?>/admin/user_view.php?id=<?= $r['target_id'] ?>">#<?= $r['target_id'] ?></a>
                        <?php else: ?>
                            #<?= $r['target_id'] ?>
                        <?php endif; ?>
                    </td>
                    <td><?= e($r['reporter_name']) ?></td>
                    <td><?= e($r['reason']) ?></td>
                    <td>
                        <?php
                        $sBadge = match($r['status']) {
                            'new' => 'badge-pending',
                            'in_progress' => 'badge-pending',
                            'resolved' => 'badge-online',
                            default => 'badge-offline'
                        };
                        ?>
                        <span class="badge <?= $sBadge ?>"><?= e($r['status']) ?></span>
                    </td>
                    <td><?= formatDate($r['created_at']) ?></td>
                    <td>
                        <?php if ($r['status'] === 'new' || $r['status'] === 'in_progress'): ?>
                            <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                                <?php if ($r['status'] === 'new'): ?>
                                    <form method="POST" style="display: inline;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                        <input type="hidden" name="action" value="in_progress">
                                        <button class="btn btn-sm btn-outline">В работу</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" style="display: inline-flex; gap: 4px;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="resolve">
                                    <input type="text" name="resolution_note" placeholder="Комментарий" style="padding: 4px 8px; background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); font-size: 0.8rem; width: 120px;">
                                    <button class="btn btn-sm btn-primary">✓</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="report_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <input type="hidden" name="resolution_note" value="Отклонено">
                                    <button class="btn btn-sm btn-ghost">✗</button>
                                </form>
                            </div>
                        <?php else: ?>
                            <span style="color: var(--text-muted); font-size: 0.8rem;"><?= e($r['resolution_note'] ?? '') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= paginate($total, $perPage, $page, $baseUrl) ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
