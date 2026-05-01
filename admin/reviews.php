<?php
/**
 * CraftRadar — Админка: Управление отзывами
 */

$adminPageTitle = 'Отзывы';
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();

$page = max(1, getInt('page', 1));
$status = get('status');
$search = get('q');
$perPage = ADMIN_PER_PAGE;

$where = ['1=1'];
$params = [];

if ($status) { $where[] = 'r.status = ?'; $params[] = $status; }
if ($search) {
    $where[] = '(r.text LIKE ? OR u.username LIKE ? OR s.name LIKE ?)';
    $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = "%{$search}%";
}

if (get('rating')) {
    $where[] = 'r.rating = ?';
    $params[] = (int)get('rating');
}

$whereSQL = implode(' AND ', $where);

$stmt = $db->prepare("SELECT COUNT(*) FROM reviews r JOIN users u ON r.user_id = u.id JOIN servers s ON r.server_id = s.id WHERE {$whereSQL}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$offset = ($page - 1) * $perPage;
$stmt = $db->prepare("
    SELECT r.*, u.username, s.name as server_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    JOIN servers s ON r.server_id = s.id 
    WHERE {$whereSQL} 
    ORDER BY r.created_at DESC 
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Действия
if (isPost()) {
    if (verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $reviewId = (int)post('review_id');
        $action = post('action');

        switch ($action) {
            case 'hide':
                $reason = post('reason');
                $db->prepare("UPDATE reviews SET status = 'hidden', hidden_by = ?, hidden_reason = ? WHERE id = ?")
                    ->execute([currentUserId(), $reason, $reviewId]);
                // Пересчёт рейтинга
                $serverId = (int)$db->query("SELECT server_id FROM reviews WHERE id = {$reviewId}")->fetchColumn();
                recalcRating($db, $serverId);
                adminLog('hide_review', 'review', $reviewId, json_encode(['reason' => $reason]));
                break;
            case 'restore':
                $db->prepare("UPDATE reviews SET status = 'active', hidden_by = NULL, hidden_reason = NULL WHERE id = ?")->execute([$reviewId]);
                $serverId = (int)$db->query("SELECT server_id FROM reviews WHERE id = {$reviewId}")->fetchColumn();
                recalcRating($db, $serverId);
                adminLog('restore_review', 'review', $reviewId);
                break;
            case 'delete':
                if (isAdmin()) {
                    $serverId = (int)$db->query("SELECT server_id FROM reviews WHERE id = {$reviewId}")->fetchColumn();
                    $db->prepare("DELETE FROM reviews WHERE id = ?")->execute([$reviewId]);
                    recalcRating($db, $serverId);
                    adminLog('delete_review', 'review', $reviewId);
                }
                break;
        }
        setFlash('success', 'Действие выполнено.');
        redirect($_SERVER['REQUEST_URI']);
    }
}

function recalcRating(PDO $db, int $serverId): void
{
    $stmt = $db->prepare("SELECT AVG(rating) as avg_r, COUNT(*) as cnt FROM reviews WHERE server_id = ? AND status = 'active'");
    $stmt->execute([$serverId]);
    $data = $stmt->fetch();
    $db->prepare('UPDATE servers SET rating = ?, reviews_count = ? WHERE id = ?')
        ->execute([round($data['avg_r'] ?? 0, 2), $data['cnt'], $serverId]);
}

$baseUrl = SITE_URL . '/admin/reviews.php?x=1';
if ($status) $baseUrl .= '&status=' . urlencode($status);
if ($search) $baseUrl .= '&q=' . urlencode($search);
?>

<form method="GET" class="admin-filters">
    <select name="status">
        <option value="">Все статусы</option>
        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
        <option value="hidden" <?= $status === 'hidden' ? 'selected' : '' ?>>Hidden</option>
    </select>
    <select name="rating">
        <option value="">Все оценки</option>
        <?php for ($i = 1; $i <= 5; $i++): ?>
            <option value="<?= $i ?>" <?= get('rating') === (string)$i ? 'selected' : '' ?>><?= $i ?>★</option>
        <?php endfor; ?>
    </select>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Поиск по тексту, автору, серверу...">
    <button type="submit" class="btn btn-sm btn-primary">Найти</button>
</form>

<?php
// Массовое скрытие
if (isPost() && post('bulk_action') === 'hide_all') {
    if (verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        if (!empty($ids)) {
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $db->prepare("UPDATE reviews SET status = 'hidden', hidden_by = ?, hidden_reason = 'Массовое скрытие' WHERE id IN ({$ph})")
                ->execute(array_merge([currentUserId()], $ids));
            foreach ($ids as $rid) adminLog('hide_review', 'review', $rid, 'Массовое скрытие');
            setFlash('success', count($ids) . ' отзывов скрыто.');
            redirect($_SERVER['REQUEST_URI']);
        }
    }
}
?>

<form method="POST">
<?= csrfField() ?>
<div style="margin-bottom: 12px; display: flex; gap: 8px; align-items: center;">
    <input type="hidden" name="bulk_action" value="hide_all">
    <button type="submit" class="btn btn-sm btn-outline" data-confirm="Скрыть выбранные отзывы?">🙈 Скрыть выбранные</button>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr><th><input type="checkbox" id="selectAll"></th><th>ID</th><th>Сервер</th><th>Автор</th><th>⭐</th><th>Текст</th><th>Статус</th><th>Дата</th><th>Действия</th></tr>
        </thead>
        <tbody>
            <?php foreach ($reviews as $r): ?>
                <tr>
                    <td><input type="checkbox" name="ids[]" value="<?= $r['id'] ?>" class="row-checkbox"></td>
                    <td><?= $r['id'] ?></td>
                    <td><a href="<?= SITE_URL ?>/admin/server_view.php?id=<?= $r['server_id'] ?>"><?= e(truncate($r['server_name'], 20)) ?></a></td>
                    <td><a href="<?= SITE_URL ?>/admin/user_view.php?id=<?= $r['user_id'] ?>"><?= e($r['username']) ?></a></td>
                    <td><?= $r['rating'] ?></td>
                    <td><?= e(truncate($r['text'], 80)) ?></td>
                    <td><span class="badge <?= $r['status'] === 'active' ? 'badge-online' : 'badge-offline' ?>"><?= e($r['status']) ?></span></td>
                    <td><?= formatDate($r['created_at']) ?></td>
                    <td>
                        <div class="action-btns">
                            <?php if ($r['status'] === 'active'): ?>
                                <form method="POST" style="display: inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="hide">
                                    <input type="hidden" name="reason" value="Нарушение правил">
                                    <button class="btn btn-sm btn-ghost" title="Скрыть">🙈</button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display: inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="restore">
                                    <button class="btn btn-sm btn-ghost" title="Восстановить">👁</button>
                                </form>
                            <?php endif; ?>
                            <?php if (isAdmin()): ?>
                                <form method="POST" style="display: inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button class="btn btn-sm btn-ghost" title="Удалить" data-confirm="Удалить отзыв?">🗑</button>
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
