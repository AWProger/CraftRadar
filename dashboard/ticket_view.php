<?php
/**
 * CraftRadar — Просмотр обращения
 */

$pageTitle = 'Обращение';
require_once __DIR__ . '/../includes/header.php';

requireAuth();

$db = getDB();
$userId = currentUserId();
$id = getInt('id');

$stmt = $db->prepare('SELECT * FROM tickets WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $userId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    setFlash('error', 'Обращение не найдено.');
    redirect(SITE_URL . '/dashboard/tickets.php');
}

// Отправка ответа
if (isPost() && post('action') === 'reply' && $ticket['status'] !== 'closed') {
    if (verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $message = trim(post('message'));
        if (!empty($message) && mb_strlen($message) >= 2 && mb_strlen($message) <= 5000) {
            $db->prepare('INSERT INTO ticket_messages (ticket_id, user_id, message, is_admin, created_at) VALUES (?, ?, ?, 0, ?)')
                ->execute([$id, $userId, $message, now()]);
            $db->prepare("UPDATE tickets SET status = 'waiting', updated_at = ? WHERE id = ?")->execute([now(), $id]);
            setFlash('success', 'Сообщение отправлено.');
        }
        redirect(SITE_URL . '/dashboard/ticket_view.php?id=' . $id);
    }
}

// Закрытие тикета
if (isPost() && post('action') === 'close') {
    if (verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $db->prepare("UPDATE tickets SET status = 'closed', closed_by = ?, closed_at = ?, updated_at = ? WHERE id = ? AND user_id = ?")
            ->execute([$userId, now(), now(), $id, $userId]);
        setFlash('success', 'Обращение закрыто.');
        redirect(SITE_URL . '/dashboard/tickets.php');
    }
}

// Сообщения
$messages = $db->prepare('SELECT tm.*, u.username FROM ticket_messages tm JOIN users u ON tm.user_id = u.id WHERE tm.ticket_id = ? ORDER BY tm.created_at ASC');
$messages->execute([$id]);
$messages = $messages->fetchAll();

$statusLabels = ['open' => '🟢 Открыт', 'answered' => '💬 Отвечен', 'waiting' => '⏳ Ожидает ответа', 'closed' => '🔒 Закрыт'];
$catLabels = ['question' => '❓ Вопрос', 'bug' => '🐛 Баг', 'payment' => '💰 Оплата', 'server' => '📡 Сервер', 'abuse' => '⚠️ Жалоба', 'other' => '📋 Другое'];
?>

<div class="dashboard">
    <?= dashboardNav('help') ?>
    <div class="dashboard-header">
        <h1>🎫 Обращение #<?= $ticket['id'] ?></h1>
        <a href="<?= SITE_URL ?>/dashboard/tickets.php" class="btn btn-ghost btn-sm">← Все обращения</a>
    </div>

    <!-- Инфо -->
    <div class="card" style="margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <div>
                <h2 style="margin-bottom: 4px;"><?= e($ticket['subject']) ?></h2>
                <div style="display: flex; gap: 8px; font-size: 0.8rem; color: var(--text-muted);">
                    <span><?= $catLabels[$ticket['category']] ?? $ticket['category'] ?></span>
                    <span>·</span>
                    <span><?= formatDate($ticket['created_at']) ?></span>
                </div>
            </div>
            <div style="display: flex; gap: 8px; align-items: center;">
                <?php
                $sBadge = match($ticket['status']) {
                    'open' => 'badge-pending',
                    'answered' => 'badge-online',
                    'waiting' => 'badge-pending',
                    default => 'badge-offline'
                };
                ?>
                <span class="badge <?= $sBadge ?>"><?= $statusLabels[$ticket['status']] ?? $ticket['status'] ?></span>
                <?php if ($ticket['status'] !== 'closed'): ?>
                    <form method="POST" style="display:inline;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="close">
                        <button class="btn btn-sm btn-ghost" data-confirm="Закрыть обращение?">🔒 Закрыть</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Переписка -->
    <div class="ticket-messages">
        <?php foreach ($messages as $msg): ?>
        <div class="ticket-msg <?= $msg['is_admin'] ? 'ticket-msg-admin' : 'ticket-msg-user' ?>">
            <div class="ticket-msg-header">
                <strong><?= e($msg['username']) ?></strong>
                <?php if ($msg['is_admin']): ?>
                    <span class="badge badge-online" style="font-size: 0.6rem;">Поддержка</span>
                <?php endif; ?>
                <span style="color: var(--text-muted); font-size: 0.75rem; margin-left: auto;"><?= formatDate($msg['created_at']) ?></span>
            </div>
            <div class="ticket-msg-body"><?= nl2br(e($msg['message'])) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Ответ -->
    <?php if ($ticket['status'] !== 'closed'): ?>
    <div class="card" style="margin-top: 16px;">
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="reply">
            <div class="form-group">
                <label>Ваш ответ</label>
                <textarea name="message" rows="3" required minlength="2" maxlength="5000" placeholder="Напишите сообщение..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">📨 Отправить</button>
        </form>
    </div>
    <?php else: ?>
    <div class="card" style="margin-top: 16px; text-align: center; color: var(--text-muted);">
        🔒 Обращение закрыто. Если проблема не решена — создайте новое обращение.
    </div>
    <?php endif; ?>
</div>

<style>
    .ticket-messages { display: flex; flex-direction: column; gap: 12px; }
    .ticket-msg {
        padding: 14px 16px;
        border: var(--pixel-border) var(--border);
        box-shadow: var(--shadow);
    }
    .ticket-msg-user { background: var(--bg-card); }
    .ticket-msg-admin { background: rgba(0, 255, 128, 0.03); border-color: var(--accent); }
    .ticket-msg-header {
        display: flex; align-items: center; gap: 8px;
        margin-bottom: 8px; font-size: 0.85rem;
    }
    .ticket-msg-body { font-size: 0.9rem; line-height: 1.6; color: var(--text); }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
