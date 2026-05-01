<?php
/**
 * CraftRadar — Админка: Управление пользователями
 */

$adminPageTitle = 'Пользователи';
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();

$page = max(1, getInt('page', 1));
$search = get('q');
$role = get('role');
$banned = get('banned');
$perPage = ADMIN_PER_PAGE;

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = '(u.username LIKE ? OR u.email LIKE ? OR u.last_ip LIKE ? OR u.id = ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = (int)$search;
}

if ($role) {
    $where[] = 'u.role = ?';
    $params[] = $role;
}

if ($banned === '1') {
    $where[] = 'u.is_banned = 1';
}

$whereSQL = implode(' AND ', $where);

$stmt = $db->prepare("SELECT COUNT(*) FROM users u WHERE {$whereSQL}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$offset = ($page - 1) * $perPage;
$stmt = $db->prepare("
    SELECT u.*, 
        (SELECT COUNT(*) FROM servers WHERE user_id = u.id) as server_count,
        (SELECT COUNT(*) FROM votes WHERE user_id = u.id) as vote_count
    FROM users u 
    WHERE {$whereSQL} 
    ORDER BY u.created_at DESC 
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$users = $stmt->fetchAll();

$baseUrl = SITE_URL . '/admin/users.php?x=1';
if ($search) $baseUrl .= '&q=' . urlencode($search);
if ($role) $baseUrl .= '&role=' . urlencode($role);
if ($banned) $baseUrl .= '&banned=' . urlencode($banned);
?>

<div class="admin-table-header">
    <div>Всего: <?= $total ?></div>
</div>

<form method="GET" class="admin-filters">
    <select name="role">
        <option value="">Все роли</option>
        <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User</option>
        <option value="moderator" <?= $role === 'moderator' ? 'selected' : '' ?>>Moderator</option>
        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
    </select>
    <label style="display: flex; align-items: center; gap: 4px; font-size: 0.85rem; color: var(--text-muted);">
        <input type="checkbox" name="banned" value="1" <?= $banned === '1' ? 'checked' : '' ?>> Забаненные
    </label>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Поиск по логину, email, IP, ID...">
    <button type="submit" class="btn btn-sm btn-primary">Найти</button>
</form>

<?php
// Массовые действия
if (isPost() && isAdmin()) {
    if (verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $bulkAction = post('bulk_action');
        $ids = array_map('intval', $_POST['ids'] ?? []);
        if (!empty($ids) && $bulkAction) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            switch ($bulkAction) {
                case 'ban':
                    $db->prepare("UPDATE users SET is_banned = 1, ban_reason = 'Массовый бан', banned_by = ? WHERE id IN ({$placeholders})")
                        ->execute(array_merge([currentUserId()], $ids));
                    foreach ($ids as $uid) adminLog('ban_user', 'user', $uid, 'Массовый бан');
                    setFlash('success', count($ids) . ' пользователей забанено.');
                    break;
                case 'unban':
                    $db->prepare("UPDATE users SET is_banned = 0, ban_reason = NULL, banned_by = NULL WHERE id IN ({$placeholders})")
                        ->execute($ids);
                    foreach ($ids as $uid) adminLog('unban_user', 'user', $uid);
                    setFlash('success', count($ids) . ' пользователей разбанено.');
                    break;
            }
            redirect($_SERVER['REQUEST_URI']);
        }
    }
}
?>

<form method="POST">
<?= csrfField() ?>

<?php if (isAdmin()): ?>
<div style="margin-bottom: 12px; display: flex; gap: 8px; align-items: center;">
    <select name="bulk_action" style="padding: 6px 12px; background: var(--bg-input); border: 2px solid var(--border); color: var(--text); font-size: 0.8rem;">
        <option value="">Массовое действие</option>
        <option value="ban">Забанить</option>
        <option value="unban">Разбанить</option>
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
                <th>Логин</th>
                <th>Email</th>
                <th>Роль</th>
                <th>Серверов</th>
                <th>Голосов</th>
                <th>💎</th>
                <th>Регистрация</th>
                <th>Последний вход</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <?php if (isAdmin()): ?><td><input type="checkbox" name="ids[]" value="<?= $u['id'] ?>" class="row-checkbox"></td><?php endif; ?>
                    <td><?= $u['id'] ?></td>
                    <td><a href="<?= SITE_URL ?>/admin/user_view.php?id=<?= $u['id'] ?>"><?= e($u['username']) ?></a></td>
                    <td><?= e($u['email']) ?></td>
                    <td>
                        <?php
                        $roleBadge = match($u['role']) {
                            'admin' => 'badge-offline',
                            'moderator' => 'badge-pending',
                            default => 'badge-online'
                        };
                        ?>
                        <span class="badge <?= $roleBadge ?>"><?= e($u['role']) ?></span>
                    </td>
                    <td><?= $u['server_count'] ?></td>
                    <td><?= $u['vote_count'] ?></td>
                    <td style="color: var(--gold);"><?= $u['points'] ?? 0 ?></td>
                    <td><?= formatDate($u['created_at']) ?></td>
                    <td><?= $u['last_login'] ? formatDate($u['last_login']) : '—' ?></td>
                    <td>
                        <?php if ($u['is_banned']): ?>
                            <span class="badge badge-offline">Бан</span>
                        <?php else: ?>
                            <span class="badge badge-online">Активен</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?= SITE_URL ?>/admin/user_view.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-ghost">👁</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</form>

<?= paginate($total, $perPage, $page, $baseUrl) ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
