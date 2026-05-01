<?php
/**
 * CraftRadar — Админка: Обращения (тикеты)
 */

$adminPageTitle = 'Обращения';
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();

$page = max(1, getInt('page', 1));
$status = get('status');
$category = get('category');
$search = get('q');
$perPage = ADMIN_PER_PAGE;

$where = ['1=1'];
$params = [];

if ($status) { $where[] = 't.status = ?'; $params[] = $status; }
if ($category) { $where[] = 't.category = ?'; $params[] = $category; }
if ($search) {
    $where[] = '(t.subject LIKE ? OR u.username LIKE ? OR t.id = ?)';
    $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = (int)$search;
}

$whereSQL = implode(' AND ', $where);

$stmt = $db->prepare("SELECT COUNT(*) FROM tickets t JOIN users u ON t.user_id = u.id WHERE {$whereSQL}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$offset = ($page - 1) * $perPage;
$stmt = $db->prepare("
    SELECT t.*, u.username,
        (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = t.id) as msg_count
    FROM tickets t 
    JOIN users u ON t.user_id = u.id 
    WHERE {$whereSQL} 
    ORDER BY FIELD(t.status, 'open', 'waiting', 'answered', 'closed'), t.updated_at DESC
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Статистика
$statsByStatus = $db->query("SELECT status, COUNT(*) as cnt FROM tickets GROUP BY status")->fetchAll();
$statsMap = ['open' => 0, 'waiting' => 0, 'answered' => 0, 'closed' => 0];
foreach ($statsByStatus as $s) $statsMap[$s['status']] = (int)$s['cnt'];

$statusLabels = ['open' => '🟢 Открыт', 'answered' => '💬 Отвечен', 'waiting' => '⏳ Ожидает', 'closed' => '🔒 Закрыт'];
$catLabels = ['question' => '❓', 'bug' => '🐛', 'payment' => '💰', 'server' => '📡', 'abuse' => '⚠️', 'other' => '📋'];

$baseUrl = SITE_URL . '/admin/tickets.php?x=1';
if ($status) $baseUrl .= '&status=' . urlencode($status);
if ($category) $baseUrl .= '&category=' . urlencode($category);
if ($search) $baseUrl .= '&q=' . urlencode($search);
?>

<!-- Статистика -->
<div class="admin-stats-grid" style="margin-bottom: 16px;">
    <div class="admin-stat-card" style="border-color: var(--warning);">
        <div class="admin-stat-value" style="color: var(--warning);"><?= $statsMap['open'] ?></div>
        <div class="admin-stat-label">Открытых</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $statsMap['waiting'] ?></div>
        <div class="admin-stat-label">Ожидают ответа</div>
    </div>
    <div class="admin-stat-card" style="border-color: var(--success);">
        <div class="admin-stat-value" style="color: var(--success);"><?= $statsMap['answered'] ?></div>
        <div class="admin-stat-label">Отвечены</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $statsMap['closed'] ?></div>
        <div class="admin-stat-label">Закрытых</div>
    </div>
</div>

<!-- Фильтры -->
<form method="GET" class="admin-filters">
    <select name="status">
        <option value="">Все статусы</option>
        <option value="open" <?= $status === 'open' ? 'selected' : '' ?>>Открытые</option>
        <option value="waiting" <?= $status === 'waiting' ? 'selected' : '' ?>>Ожидают ответа</option>
        <option value="answered" <?= $status === 'answered' ? 'selected' : '' ?>>Отвечены</option>
        <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Закрытые</option>
    </select>
    <select name="category">
        <option value="">Все категории</option>
        <option value="question" <?= $category === 'question' ? 'selected' : '' ?>>❓ Вопрос</option>
        <option value="bug" <?= $category === 'bug' ? 'selected' : '' ?>>🐛 Баг</option>
        <option value="payment" <?= $category === 'payment' ? 'selected' : '' ?>>💰 Оплата</option>
        <option value="server" <?= $category === 'server' ? 'selected' : '' ?>>📡 Сервер</option>
        <option value="abuse" <?= $category === 'abuse' ? 'selected' : '' ?>>⚠️ Жалоба</option>
        <option value="other" <?= $category === 'other' ? 'selected' : '' ?>>📋 Другое</option>
    </select>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Поиск по теме, автору, ID...">
    <button type="submit" class="btn btn-sm btn-primary">Найти</button>
    <a href="<?= SITE_URL ?>/admin/tickets.php" class="btn btn-sm btn-ghost">Сбросить</a>
</form>

<?php
// Массовые действия
if (isPost() && verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
    $bulkAction = post('bulk_action');
    $ids = array_map('intval', $_POST['ids'] ?? []);
    if (!empty($ids) && $bulkAction) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        switch ($bulkAction) {
            case 'close':
                $db->prepare("UPDATE tickets SET status = 'closed', closed_by = ?, closed_at = ?, updated_at = ? WHERE id IN ({$ph})")
                    ->execute(array_merge([currentUserId(), now(), now()], $ids));
                setFlash('success', count($ids) . ' обращений закрыто.');
                break;
            case 'delete':
                if (isAdmin()) {
                    $db->prepare("DELETE FROM tickets WHERE id IN ({$ph})")->execute($ids);
                    setFlash('success', count($ids) . ' обращений удалено.');
                }
                break;
        }
        foreach ($ids as $tid) adminLog($bulkAction . '_ticket', 'ticket', $tid);
        redirect($_SERVER['REQUEST_URI']);
    }
}
?>

<!-- Таблица -->
<form method="POST">
<?= csrfField() ?>
<div style="margin-bottom: 12px; display: flex; gap: 8px; align-items: center;">
    <select name="bulk_action" style="padding:6px 12px;background:var(--bg-input);border:2px solid var(--border);color:var(--text);font-size:0.8rem;">
        <option value="">Массовое действие</option>
        <option value="close">🔒 Закрыть</option>
        <option value="delete">🗑 Удалить</option>
    </select>
    <button type="submit" class="btn btn-sm btn-outline" data-confirm="Выполнить действие?">Применить</button>
    <span id="selectedCount" style="color:var(--accent);font-size:0.8rem;font-weight:700;display:none;"></span>
</div>
<div class="table-wrap">
    <table>
        <thead>
            <tr><th><input type="checkbox" id="selectAll"></th><th>#</th><th>Тема</th><th>Автор</th><th>Кат.</th><th>💬</th><th>Статус</th><th>Создан</th><th>Обновлён</th></tr>
        </thead>
        <tbody>
            <?php if (empty($tickets)): ?>
                <tr><td colspan="9" style="text-align:center;color:var(--text-muted);padding:30px;">Обращений нет</td></tr>
            <?php endif; ?>
            <?php foreach ($tickets as $t): ?>
            <tr style="<?= $t['status'] === 'open' ? 'border-left: 3px solid var(--warning);' : ($t['status'] === 'waiting' ? 'border-left: 3px solid var(--info);' : '') ?>">
                <td><input type="checkbox" name="ids[]" value="<?= $t['id'] ?>" class="row-checkbox"></td>
                <td><?= $t['id'] ?></td>
                <td><a href="<?= SITE_URL ?>/admin/ticket_view.php?id=<?= $t['id'] ?>"><?= e(truncate($t['subject'], 50)) ?></a></td>
                <td><a href="<?= SITE_URL ?>/admin/user_view.php?id=<?= $t['user_id'] ?>"><?= e($t['username']) ?></a></td>
                <td><?= $catLabels[$t['category']] ?? '📋' ?></td>
                <td><?= $t['msg_count'] ?></td>
                <td>
                    <?php
                    $sBadge = match($t['status']) {
                        'open' => 'badge-pending',
                        'answered' => 'badge-online',
                        'waiting' => 'badge-pending',
                        default => 'badge-offline'
                    };
                    ?>
                    <span class="badge <?= $sBadge ?>"><?= $statusLabels[$t['status']] ?? $t['status'] ?></span>
                </td>
                <td style="font-size:0.75rem;"><?= formatDate($t['created_at']) ?></td>
                <td style="font-size:0.75rem;color:var(--text-muted);"><?= formatDate($t['updated_at']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</form>

<?= paginate($total, $perPage, $page, $baseUrl) ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
