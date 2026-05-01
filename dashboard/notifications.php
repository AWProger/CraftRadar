<?php
/**
 * CraftRadar — Уведомления
 */

$pageTitle = 'Уведомления';
require_once __DIR__ . '/../includes/header.php';

requireAuth();

$userId = currentUserId();

// Пометить все как прочитанные
if (isPost() && post('action') === 'mark_all_read') {
    if (verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        markAllNotificationsRead($userId);
        setFlash('success', 'Все уведомления прочитаны.');
        redirect(SITE_URL . '/dashboard/notifications.php');
    }
}

// Пометить одно как прочитанное и перейти по ссылке
if (get('read')) {
    $notifId = getInt('read');
    markNotificationRead($notifId, $userId);
    $link = get('goto');
    if ($link) {
        redirect($link);
    }
}

$notifications = getAllNotifications($userId, 50);
$unreadCount = getUnreadCount($userId);
?>

<div class="dashboard">
    <?= dashboardNav('notif') ?>
    <div class="dashboard-header">
        <h1>Уведомления <?php if ($unreadCount): ?><span class="badge badge-pending"><?= $unreadCount ?> новых</span><?php endif; ?></h1>
        <div style="display: flex; gap: 8px;">
            <?php if ($unreadCount > 0): ?>
                <form method="POST" style="display: inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="mark_all_read">
                    <button type="submit" class="btn btn-sm btn-outline">✓ Прочитать все</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($notifications)): ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <p style="color: var(--text-muted);">Уведомлений пока нет.</p>
        </div>
    <?php else: ?>
        <div class="notif-list">
            <?php foreach ($notifications as $n): ?>
                <div class="notif-item <?= $n['is_read'] ? '' : 'notif-unread' ?>">
                    <div class="notif-icon"><?= notificationIcon($n['type']) ?></div>
                    <div class="notif-content">
                        <div class="notif-title">
                            <?php if ($n['link'] && !$n['is_read']): ?>
                                <a href="<?= SITE_URL ?>/dashboard/notifications.php?read=<?= $n['id'] ?>&goto=<?= urlencode($n['link']) ?>">
                                    <?= e($n['title']) ?>
                                </a>
                            <?php elseif ($n['link']): ?>
                                <a href="<?= e($n['link']) ?>"><?= e($n['title']) ?></a>
                            <?php else: ?>
                                <?= e($n['title']) ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($n['message']): ?>
                            <div class="notif-message"><?= e($n['message']) ?></div>
                        <?php endif; ?>
                        <div class="notif-time"><?= formatDate($n['created_at']) ?></div>
                    </div>
                    <?php if (!$n['is_read']): ?>
                        <span class="notif-dot"></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .notif-list {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .notif-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 14px 16px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        transition: border-color var(--transition);
    }
    .notif-unread {
        border-left: 3px solid var(--accent);
        background: rgba(0, 255, 128, 0.02);
    }
    .notif-icon {
        font-size: 1.3rem;
        flex-shrink: 0;
        margin-top: 2px;
    }
    .notif-content {
        flex: 1;
        min-width: 0;
    }
    .notif-title {
        font-weight: 500;
        margin-bottom: 2px;
    }
    .notif-title a {
        color: var(--text);
    }
    .notif-title a:hover {
        color: var(--accent);
    }
    .notif-message {
        color: var(--text-muted);
        font-size: 0.85rem;
        margin-bottom: 4px;
    }
    .notif-time {
        color: var(--text-muted);
        font-size: 0.75rem;
    }
    .notif-dot {
        width: 8px;
        height: 8px;
        background: var(--accent);
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 8px;
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
