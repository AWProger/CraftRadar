<?php
/**
 * CraftRadar — Админка: Просмотр обращения
 */

$adminPageTitle = 'Обращение';
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$id = getInt('id');

$stmt = $db->prepare('SELECT t.*, u.username, u.email FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?');
$stmt->execute([$id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    setFlash('error', 'Обращение не найдено.');
    redirect(SITE_URL . '/admin/tickets.php');
}

// Действия
if (isPost()) {
    if (verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $action = post('action');

        switch ($action) {
            case 'reply':
                $message = trim(post('message'));
                if (!empty($message)) {
                    $db->prepare('INSERT INTO ticket_messages (ticket_id, user_id, message, is_admin, created_at) VALUES (?, ?, ?, 1, ?)')
                        ->execute([$id, currentUserId(), $message, now()]);
                    $db->prepare("UPDATE tickets SET status = 'answered', updated_at = ? WHERE id = ?")->execute([now(), $id]);
                    adminLog('reply_ticket', 'ticket', $id);

                    // Уведомление пользователю
                    try {
                        require_once INCLUDES_PATH . 'notifications.php';
                        createNotification($ticket['user_id'], 'ticket_reply',
                            '💬 Ответ на обращение #' . $id,
                            'Администрация ответила на ваше обращение «' . truncate($ticket['subject'], 50) . '»',
                            SITE_URL . '/dashboard/ticket_view.php?id=' . $id);
                    } catch (\Exception $e) {}

                    setFlash('success', 'Ответ отправлен.');
                }
                break;

            case 'close':
                $db->prepare("UPDATE tickets SET status = 'closed', closed_by = ?, closed_at = ?, updated_at = ? WHERE id = ?")
                    ->execute([currentUserId(), now(), now(), $id]);
                adminLog('close_ticket', 'ticket', $id);
                setFlash('success', 'Обращение закрыто.');
                break;

            case 'reopen':
                $db->prepare("UPDATE tickets SET status = 'open', closed_by = NULL, closed_at = NULL, updated_at = ? WHERE id = ?")
                    ->execute([now(), $id]);
                adminLog('reopen_ticket', 'ticket', $id);
                setFlash('success', 'Обращение переоткрыто.');
                break;

            case 'set_priority':
                $priority = post('priority');
                if (in_array($priority, ['low', 'normal', 'high'])) {
                    $db->prepare('UPDATE tickets SET priority = ?, updated_at = ? WHERE id = ?')->execute([$priority, now(), $id]);
                    adminLog('set_priority', 'ticket', $id, $priority);
                    setFlash('success', 'Приоритет изменён.');
                }
                break;
        }
        redirect(SITE_URL . '/admin/ticket_view.php?id=' . $id);
    }
}

// Обновляем данные
$stmt = $db->prepare('SELECT t.*, u.username, u.email FROM tickets t JOIN users u ON t.user_id = u.id WHERE t.id = ?');
$stmt->execute([$id]);
$ticket = $stmt->fetch();

// Сообщения
$messages = $db->prepare('SELECT tm.*, u.username, u.role FROM ticket_messages tm JOIN users u ON tm.user_id = u.id WHERE tm.ticket_id = ? ORDER BY tm.created_at ASC');
$messages->execute([$id]);
$messages = $messages->fetchAll();

$statusLabels = ['open' => '🟢 Открыт', 'answered' => '💬 Отвечен', 'waiting' => '⏳ Ожидает', 'closed' => '🔒 Закрыт'];
$catLabels = ['question' => '❓ Вопрос', 'bug' => '🐛 Баг', 'payment' => '💰 Оплата', 'server' => '📡 Сервер', 'abuse' => '⚠️ Жалоба', 'other' => '📋 Другое'];
$priorityLabels = ['low' => '🟢 Низкий', 'normal' => '🟡 Обычный', 'high' => '🔴 Высокий'];
?>

<div style="margin-bottom: 16px;">
    <a href="<?= SITE_URL ?>/admin/tickets.php" class="btn btn-ghost btn-sm">← Все обращения</a>
</div>

<!-- Инфо о тикете -->
<div class="card" style="margin-bottom: 16px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="margin-bottom: 4px;">#<?= $ticket['id'] ?> — <?= e($ticket['subject']) ?></h2>
            <div style="display: flex; gap: 12px; font-size: 0.8rem; color: var(--text-muted); flex-wrap: wrap;">
                <span>👤 <a href="<?= SITE_URL ?>/admin/user_view.php?id=<?= $ticket['user_id'] ?>"><?= e($ticket['username']) ?></a> (<?= e($ticket['email']) ?>)</span>
                <span><?= $catLabels[$ticket['category']] ?? $ticket['category'] ?></span>
                <span><?= formatDate($ticket['created_at']) ?></span>
            </div>
        </div>
        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <?php
            $sBadge = match($ticket['status']) {
                'open' => 'badge-pending',
                'answered' => 'badge-online',
                'waiting' => 'badge-pending',
                default => 'badge-offline'
            };
            ?>
            <span class="badge <?= $sBadge ?>"><?= $statusLabels[$ticket['status']] ?? $ticket['status'] ?></span>
            <span class="badge"><?= $priorityLabels[$ticket['priority']] ?? $ticket['priority'] ?></span>

            <!-- Приоритет -->
            <form method="POST" style="display:inline-flex;gap:4px;align-items:center;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="set_priority">
                <select name="priority" style="padding:4px 8px;background:var(--bg-input);border:2px solid var(--border);color:var(--text);font-size:0.75rem;">
                    <option value="low" <?= $ticket['priority'] === 'low' ? 'selected' : '' ?>>Низкий</option>
                    <option value="normal" <?= $ticket['priority'] === 'normal' ? 'selected' : '' ?>>Обычный</option>
                    <option value="high" <?= $ticket['priority'] === 'high' ? 'selected' : '' ?>>Высокий</option>
                </select>
                <button class="btn btn-sm btn-ghost">💾</button>
            </form>

            <!-- Закрыть/Переоткрыть -->
            <?php if ($ticket['status'] !== 'closed'): ?>
                <form method="POST" style="display:inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="close">
                    <button class="btn btn-sm btn-ghost" data-confirm="Закрыть обращение?">🔒</button>
                </form>
            <?php else: ?>
                <form method="POST" style="display:inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="reopen">
                    <button class="btn btn-sm btn-outline">🔓 Переоткрыть</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Переписка -->
<div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 16px;">
    <?php foreach ($messages as $msg): ?>
    <div class="card" style="<?= $msg['is_admin'] ? 'border-color: var(--accent); background: rgba(0,255,128,0.02);' : '' ?>">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 0.85rem;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <strong><?= e($msg['username']) ?></strong>
                <?php if ($msg['is_admin']): ?>
                    <span class="badge badge-online" style="font-size: 0.6rem;">Поддержка</span>
                <?php else: ?>
                    <span class="badge" style="font-size: 0.6rem;">Пользователь</span>
                <?php endif; ?>
            </div>
            <span style="color: var(--text-muted); font-size: 0.75rem;"><?= formatDate($msg['created_at']) ?></span>
        </div>
        <div style="font-size: 0.9rem; line-height: 1.7; color: var(--text);"><?= nl2br(e($msg['message'])) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Ответ админа -->
<?php if ($ticket['status'] !== 'closed'): ?>
<div class="card">
    <h3 style="margin-bottom: 12px;">💬 Ответить</h3>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="reply">
        <div class="form-group">
            <textarea name="message" rows="4" required placeholder="Ваш ответ пользователю..."></textarea>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-primary">📨 Отправить ответ</button>
            <button type="submit" name="action" value="close" class="btn btn-ghost" data-confirm="Отправить и закрыть?">📨 Отправить и закрыть</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
