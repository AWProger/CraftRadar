<?php
/**
 * CraftRadar — Мои обращения (тикеты)
 */

$pageTitle = 'Мои обращения';
require_once __DIR__ . '/../includes/header.php';

requireAuth();

$db = getDB();
$userId = currentUserId();

// Создание тикета
if (isPost() && post('action') === 'create') {
    if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        setFlash('error', 'Ошибка безопасности.');
    } elseif (!checkRateLimit('ticket', 3)) {
        setFlash('error', 'Слишком много обращений. Подождите минуту.');
    } else {
        $subject = trim(post('subject'));
        $category = post('category');
        $message = trim(post('message'));
        $validCats = ['question', 'bug', 'payment', 'server', 'abuse', 'other'];

        if (empty($subject) || mb_strlen($subject) < 5 || mb_strlen($subject) > 200) {
            setFlash('error', 'Тема: от 5 до 200 символов.');
        } elseif (!in_array($category, $validCats)) {
            setFlash('error', 'Выберите категорию.');
        } elseif (empty($message) || mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
            setFlash('error', 'Сообщение: от 10 до 5000 символов.');
        } else {
            $db->prepare('INSERT INTO tickets (user_id, subject, category, status, priority, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$userId, $subject, $category, 'open', 'normal', now(), now()]);
            $ticketId = (int)$db->lastInsertId();

            $db->prepare('INSERT INTO ticket_messages (ticket_id, user_id, message, is_admin, created_at) VALUES (?, ?, ?, 0, ?)')
                ->execute([$ticketId, $userId, $message, now()]);

            setFlash('success', 'Обращение #' . $ticketId . ' создано. Мы ответим в течение 24 часов.');
            redirect(SITE_URL . '/dashboard/ticket_view.php?id=' . $ticketId);
        }
    }
    redirect(SITE_URL . '/dashboard/tickets.php');
}

// Мои тикеты
$tickets = $db->prepare('SELECT * FROM tickets WHERE user_id = ? ORDER BY updated_at DESC');
$tickets->execute([$userId]);
$tickets = $tickets->fetchAll();

$statusLabels = ['open' => '🟢 Открыт', 'answered' => '💬 Отвечен', 'waiting' => '⏳ Ожидает', 'closed' => '🔒 Закрыт'];
$catLabels = ['question' => '❓ Вопрос', 'bug' => '🐛 Баг', 'payment' => '💰 Оплата', 'server' => '📡 Сервер', 'abuse' => '⚠️ Жалоба', 'other' => '📋 Другое'];
?>

<div class="dashboard">
    <?= dashboardNav('help') ?>
    <div class="dashboard-header">
        <h1>🎫 Мои обращения</h1>
    </div>

    <!-- Создать обращение -->
    <div class="card" style="margin-bottom: 20px;">
        <h2 class="section-title">Новое обращение</h2>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                <div class="form-group">
                    <label for="subject">Тема *</label>
                    <input type="text" id="subject" name="subject" required minlength="5" maxlength="200" placeholder="Кратко опишите проблему">
                </div>
                <div class="form-group">
                    <label for="category">Категория *</label>
                    <select id="category" name="category" required>
                        <option value="">— Выберите —</option>
                        <option value="question">❓ Вопрос</option>
                        <option value="bug">🐛 Баг / Ошибка</option>
                        <option value="payment">💰 Оплата / Возврат</option>
                        <option value="server">📡 Мой сервер</option>
                        <option value="abuse">⚠️ Жалоба на пользователя</option>
                        <option value="other">📋 Другое</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="message">Сообщение *</label>
                <textarea id="message" name="message" rows="4" required minlength="10" maxlength="5000" placeholder="Подробно опишите вашу проблему или вопрос..."></textarea>
            </div>

            <button type="submit" class="btn btn-primary">📨 Отправить</button>
        </form>
    </div>

    <!-- Список обращений -->
    <?php if (!empty($tickets)): ?>
    <div class="card">
        <h2 class="section-title">Мои обращения (<?= count($tickets) ?>)</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>#</th><th>Тема</th><th>Категория</th><th>Статус</th><th>Обновлено</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $t): ?>
                    <tr>
                        <td><?= $t['id'] ?></td>
                        <td><a href="<?= SITE_URL ?>/dashboard/ticket_view.php?id=<?= $t['id'] ?>"><?= e($t['subject']) ?></a></td>
                        <td style="font-size: 0.8rem;"><?= $catLabels[$t['category']] ?? $t['category'] ?></td>
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
                        <td style="font-size: 0.8rem; color: var(--text-muted);"><?= formatDate($t['updated_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="card" style="text-align: center; padding: 30px; color: var(--text-muted);">
        <p>У вас пока нет обращений.</p>
    </div>
    <?php endif; ?>
</div>

<style>
    @media (max-width: 768px) {
        div[style*="grid-template-columns: 1fr 1fr"] { grid-template-columns: 1fr !important; }
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
