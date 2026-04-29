<?php
/**
 * CraftRadar — Админка: Карточка пользователя
 */

$adminPageTitle = 'Пользователь';
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$id = getInt('id');

$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'Пользователь не найден.');
    redirect(SITE_URL . '/admin/users.php');
}

// Обработка действий
if (isPost() && isAdmin()) {
    if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        setFlash('error', 'Ошибка безопасности.');
    } else {
        $action = post('action');

        switch ($action) {
            case 'change_role':
                $newRole = post('new_role');
                if (in_array($newRole, ['user', 'moderator', 'admin'])) {
                    $oldRole = $user['role'];
                    $db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$newRole, $id]);
                    adminLog('change_role', 'user', $id, json_encode(['old' => $oldRole, 'new' => $newRole]));
                    setFlash('success', 'Роль изменена.');
                }
                break;

            case 'ban':
                $reason = post('ban_reason');
                $until = post('ban_until') ?: null;
                $db->prepare('UPDATE users SET is_banned = 1, ban_reason = ?, ban_until = ?, banned_by = ? WHERE id = ?')
                    ->execute([$reason, $until, currentUserId(), $id]);
                adminLog('ban_user', 'user', $id, json_encode(['reason' => $reason, 'until' => $until]));
                setFlash('success', 'Пользователь забанен.');
                break;

            case 'unban':
                $db->prepare('UPDATE users SET is_banned = 0, ban_reason = NULL, ban_until = NULL, banned_by = NULL WHERE id = ?')
                    ->execute([$id]);
                adminLog('unban_user', 'user', $id);
                setFlash('success', 'Пользователь разбанен.');
                break;

            case 'delete':
                adminLog('delete_user', 'user', $id, json_encode(['username' => $user['username']]));
                $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
                setFlash('success', 'Пользователь удалён.');
                redirect(SITE_URL . '/admin/users.php');
                break;
        }

        redirect(SITE_URL . '/admin/user_view.php?id=' . $id);
    }
}

// Обновляем данные
$stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();

// Серверы пользователя
$servers = $db->prepare('SELECT id, name, ip, port, status, is_online, players_online, votes_month FROM servers WHERE user_id = ? ORDER BY created_at DESC');
$servers->execute([$id]);
$servers = $servers->fetchAll();

// Отзывы пользователя
$reviews = $db->prepare('SELECT r.*, s.name as server_name FROM reviews r JOIN servers s ON r.server_id = s.id WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 20');
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();
?>

<div style="margin-bottom: 16px;">
    <a href="<?= SITE_URL ?>/admin/users.php" class="btn btn-ghost btn-sm">← Назад к пользователям</a>
</div>

<!-- Профиль -->
<div class="card" style="margin-bottom: 16px;">
    <h2><?= e($user['username']) ?></h2>
    <div class="table-wrap" style="margin-top: 12px;">
        <table>
            <tr><td style="color: var(--text-muted);">ID</td><td><?= $user['id'] ?></td></tr>
            <tr><td style="color: var(--text-muted);">Email</td><td><?= e($user['email']) ?></td></tr>
            <tr><td style="color: var(--text-muted);">Роль</td><td><span class="badge"><?= e($user['role']) ?></span></td></tr>
            <tr><td style="color: var(--text-muted);">Регистрация</td><td><?= formatDate($user['created_at']) ?></td></tr>
            <tr><td style="color: var(--text-muted);">Последний вход</td><td><?= $user['last_login'] ? formatDate($user['last_login']) : '—' ?></td></tr>
            <tr><td style="color: var(--text-muted);">Последний IP</td><td><?= e($user['last_ip'] ?? '—') ?></td></tr>
            <tr>
                <td style="color: var(--text-muted);">Статус</td>
                <td>
                    <?php if ($user['is_banned']): ?>
                        <span class="badge badge-offline">Забанен</span>
                        <?php if ($user['ban_reason']): ?> — <?= e($user['ban_reason']) ?><?php endif; ?>
                        <?php if ($user['ban_until']): ?> (до <?= formatDate($user['ban_until']) ?>)<?php endif; ?>
                    <?php else: ?>
                        <span class="badge badge-online">Активен</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
    </div>
</div>

<!-- Действия -->
<?php if (isAdmin()): ?>
<div class="card" style="margin-bottom: 16px;">
    <h3 style="margin-bottom: 12px;">Действия</h3>
    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
        <!-- Смена роли -->
        <form method="POST" style="display: inline-flex; gap: 4px; align-items: center;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="change_role">
            <select name="new_role" style="padding: 6px; background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text);">
                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                <option value="moderator" <?= $user['role'] === 'moderator' ? 'selected' : '' ?>>Moderator</option>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline">Сменить роль</button>
        </form>

        <!-- Бан/разбан -->
        <?php if (!$user['is_banned']): ?>
            <form method="POST" style="display: inline-flex; gap: 4px; align-items: center;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="ban">
                <input type="text" name="ban_reason" placeholder="Причина" style="padding: 6px; background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text);">
                <input type="datetime-local" name="ban_until" style="padding: 6px; background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text);">
                <button type="submit" class="btn btn-sm btn-danger" data-confirm="Забанить пользователя?">Забанить</button>
            </form>
        <?php else: ?>
            <form method="POST" style="display: inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="unban">
                <button type="submit" class="btn btn-sm btn-primary">Разбанить</button>
            </form>
        <?php endif; ?>

        <!-- Удаление -->
        <form method="POST" style="display: inline;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="btn btn-sm btn-danger" data-confirm="Удалить пользователя и все его данные?">🗑 Удалить</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Серверы -->
<div class="card" style="margin-bottom: 16px;">
    <h3 style="margin-bottom: 12px;">Серверы (<?= count($servers) ?>)</h3>
    <?php if (empty($servers)): ?>
        <p style="color: var(--text-muted);">Нет серверов.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Название</th><th>IP</th><th>Статус</th><th>Онлайн</th><th>Голоса</th></tr></thead>
                <tbody>
                    <?php foreach ($servers as $s): ?>
                        <tr>
                            <td><a href="<?= SITE_URL ?>/admin/server_view.php?id=<?= $s['id'] ?>"><?= e($s['name']) ?></a></td>
                            <td><?= e($s['ip'] . ':' . $s['port']) ?></td>
                            <td><span class="badge"><?= e($s['status']) ?></span></td>
                            <td><?= $s['is_online'] ? $s['players_online'] : '—' ?></td>
                            <td><?= $s['votes_month'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Отзывы -->
<div class="card">
    <h3 style="margin-bottom: 12px;">Отзывы (<?= count($reviews) ?>)</h3>
    <?php if (empty($reviews)): ?>
        <p style="color: var(--text-muted);">Нет отзывов.</p>
    <?php else: ?>
        <?php foreach ($reviews as $r): ?>
            <div style="padding: 8px 0; border-bottom: 1px solid var(--border);">
                <a href="<?= SITE_URL ?>/server.php?id=<?= $r['server_id'] ?>"><?= e($r['server_name']) ?></a>
                <span class="stars"><?= str_repeat('★', $r['rating']) ?></span>
                <span style="color: var(--text-muted); font-size: 0.8rem;"><?= formatDate($r['created_at']) ?></span>
                <p style="margin-top: 4px; font-size: 0.9rem;"><?= e(truncate($r['text'], 150)) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
