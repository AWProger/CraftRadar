<?php
/**
 * CraftRadar — Админ-панель: Дашборд
 */

$adminPageTitle = 'Дашборд';
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();

// Статистика за сегодня
$today = date('Y-m-d');
$newUsersToday = (int)$db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = '{$today}'")->fetchColumn();
$newServersToday = (int)$db->query("SELECT COUNT(*) FROM servers WHERE DATE(created_at) = '{$today}'")->fetchColumn();
$votesToday = (int)$db->query("SELECT COUNT(*) FROM votes WHERE DATE(voted_at) = '{$today}'")->fetchColumn();
$reviewsToday = (int)$db->query("SELECT COUNT(*) FROM reviews WHERE DATE(created_at) = '{$today}'")->fetchColumn();
$reportsToday = (int)$db->query("SELECT COUNT(*) FROM reports WHERE DATE(created_at) = '{$today}'")->fetchColumn();

// Общая статистика
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$bannedUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_banned = 1")->fetchColumn();
$totalServers = (int)$db->query("SELECT COUNT(*) FROM servers")->fetchColumn();
$activeServers = (int)$db->query("SELECT COUNT(*) FROM servers WHERE status = 'active'")->fetchColumn();
$pendingServers = (int)$db->query("SELECT COUNT(*) FROM servers WHERE status = 'pending'")->fetchColumn();
$onlineServers = (int)$db->query("SELECT COUNT(*) FROM servers WHERE status = 'active' AND is_online = 1")->fetchColumn();
$totalPlayers = (int)$db->query("SELECT COALESCE(SUM(players_online), 0) FROM servers WHERE status = 'active' AND is_online = 1")->fetchColumn();
$totalVotes = (int)$db->query("SELECT COUNT(*) FROM votes")->fetchColumn();

// Требует внимания
$newReports = (int)$db->query("SELECT COUNT(*) FROM reports WHERE status = 'new'")->fetchColumn();
$offlineLong = (int)$db->query("SELECT COUNT(*) FROM servers WHERE status = 'active' AND is_online = 0 AND last_ping < DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// Последние действия
$recentLog = $db->query("
    SELECT al.*, u.username 
    FROM admin_log al 
    JOIN users u ON al.admin_id = u.id 
    ORDER BY al.created_at DESC LIMIT 20
")->fetchAll();
?>

<!-- Статистика за сегодня -->
<h2 class="section-title">Сегодня</h2>
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $newUsersToday ?></div>
        <div class="admin-stat-label">Новых пользователей</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $newServersToday ?></div>
        <div class="admin-stat-label">Новых серверов</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $votesToday ?></div>
        <div class="admin-stat-label">Голосов</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $reviewsToday ?></div>
        <div class="admin-stat-label">Отзывов</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $reportsToday ?></div>
        <div class="admin-stat-label">Жалоб</div>
    </div>
</div>

<!-- Общая статистика -->
<h2 class="section-title">Общая статистика</h2>
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $totalUsers ?></div>
        <div class="admin-stat-label">Пользователей (<?= $bannedUsers ?> бан)</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $totalServers ?></div>
        <div class="admin-stat-label">Серверов всего</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $onlineServers ?></div>
        <div class="admin-stat-label">Серверов онлайн</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $totalPlayers ?></div>
        <div class="admin-stat-label">Игроков онлайн</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $totalVotes ?></div>
        <div class="admin-stat-label">Голосов всего</div>
    </div>
</div>

<!-- Требует внимания -->
<h2 class="section-title">⚠️ Требует внимания</h2>
<div class="attention-grid">
    <div class="attention-card">
        <div class="attention-card-info">
            <div class="attention-card-count"><?= $pendingServers ?></div>
            <div class="attention-card-label">На модерации</div>
        </div>
        <a href="<?= SITE_URL ?>/admin/servers.php?status=pending" class="btn btn-sm btn-outline">Перейти</a>
    </div>
    <div class="attention-card">
        <div class="attention-card-info">
            <div class="attention-card-count"><?= $newReports ?></div>
            <div class="attention-card-label">Новых жалоб</div>
        </div>
        <a href="<?= SITE_URL ?>/admin/reports.php?status=new" class="btn btn-sm btn-outline">Перейти</a>
    </div>
    <div class="attention-card">
        <div class="attention-card-info">
            <div class="attention-card-count"><?= $offlineLong ?></div>
            <div class="attention-card-label">Оффлайн > 7 дней</div>
        </div>
        <a href="<?= SITE_URL ?>/admin/servers.php?offline_long=1" class="btn btn-sm btn-outline">Перейти</a>
    </div>
</div>

<!-- Последние действия -->
<h2 class="section-title">📋 Последние действия</h2>
<div class="card">
    <?php if (empty($recentLog)): ?>
        <p style="color: var(--text-muted);">Действий пока нет.</p>
    <?php else: ?>
        <div class="activity-list">
            <?php foreach ($recentLog as $log): ?>
                <div class="activity-item">
                    <span class="activity-time"><?= formatDate($log['created_at']) ?></span>
                    <span class="activity-user"><?= e($log['username']) ?></span>
                    <span><?= e($log['action']) ?> → <?= e($log['target_type']) ?> #<?= $log['target_id'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
