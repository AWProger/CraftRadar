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

// Доходы
$revenueToday = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND DATE(paid_at) = CURDATE()")->fetchColumn();
$revenueMonth = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND paid_at >= DATE_FORMAT(NOW(), '%Y-%m-01')")->fetchColumn();
$revenueTotal = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();

// Графики за 30 дней
$registrationsByDay = $db->query("
    SELECT DATE(created_at) as day, COUNT(*) as cnt 
    FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
    GROUP BY day ORDER BY day
")->fetchAll();

$serversByDay = $db->query("
    SELECT DATE(created_at) as day, COUNT(*) as cnt 
    FROM servers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
    GROUP BY day ORDER BY day
")->fetchAll();

$votesByDay = $db->query("
    SELECT DATE(voted_at) as day, COUNT(*) as cnt 
    FROM votes WHERE voted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
    GROUP BY day ORDER BY day
")->fetchAll();

$revenueByDay = $db->query("
    SELECT DATE(paid_at) as day, SUM(amount) as total 
    FROM payments WHERE status = 'completed' AND paid_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
    GROUP BY day ORDER BY day
")->fetchAll();

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

<!-- 💰 Доходы -->
<h2 class="section-title">💰 Доходы</h2>
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-value" style="color: var(--warning);"><?= number_format($revenueToday, 0, '.', ' ') ?> ₽</div>
        <div class="admin-stat-label">Сегодня</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value" style="color: var(--warning);"><?= number_format($revenueMonth, 0, '.', ' ') ?> ₽</div>
        <div class="admin-stat-label">За месяц</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value" style="color: var(--warning);"><?= number_format($revenueTotal, 0, '.', ' ') ?> ₽</div>
        <div class="admin-stat-label">Всего</div>
    </div>
</div>

<!-- 📊 Графики за 30 дней -->
<h2 class="section-title">📊 Графики (30 дней)</h2>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
    <div class="card">
        <h3 style="margin-bottom: 8px; font-size: 0.9rem;">Регистрации</h3>
        <canvas id="chartRegistrations" height="180"></canvas>
    </div>
    <div class="card">
        <h3 style="margin-bottom: 8px; font-size: 0.9rem;">Серверы</h3>
        <canvas id="chartServers" height="180"></canvas>
    </div>
    <div class="card">
        <h3 style="margin-bottom: 8px; font-size: 0.9rem;">Голоса</h3>
        <canvas id="chartVotes" height="180"></canvas>
    </div>
    <div class="card">
        <h3 style="margin-bottom: 8px; font-size: 0.9rem;">Доходы (₽)</h3>
        <canvas id="chartRevenue" height="180"></canvas>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@3"></script>
<script>
const chartOpts = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
        x: { ticks: { color: '#8b949e', maxTicksLimit: 10 }, grid: { color: 'rgba(48,54,61,0.5)' } },
        y: { beginAtZero: true, ticks: { color: '#8b949e' }, grid: { color: 'rgba(48,54,61,0.5)' } }
    }
};

function makeChart(id, data, color) {
    if (!data.length) return;
    new Chart(document.getElementById(id).getContext('2d'), {
        type: 'line',
        data: {
            labels: data.map(d => d.day.slice(5)),
            datasets: [{
                data: data.map(d => d.cnt || d.total),
                borderColor: color,
                backgroundColor: color.replace(')', ',0.1)').replace('rgb', 'rgba'),
                fill: true, tension: 0.3, pointRadius: 2
            }]
        },
        options: chartOpts
    });
}

makeChart('chartRegistrations', <?= json_encode(array_map(fn($d) => ['day' => $d['day'], 'cnt' => (int)$d['cnt']], $registrationsByDay)) ?>, 'rgb(88,166,255)');
makeChart('chartServers', <?= json_encode(array_map(fn($d) => ['day' => $d['day'], 'cnt' => (int)$d['cnt']], $serversByDay)) ?>, 'rgb(63,185,80)');
makeChart('chartVotes', <?= json_encode(array_map(fn($d) => ['day' => $d['day'], 'cnt' => (int)$d['cnt']], $votesByDay)) ?>, 'rgb(210,153,34)');
makeChart('chartRevenue', <?= json_encode(array_map(fn($d) => ['day' => $d['day'], 'total' => (float)$d['total']], $revenueByDay)) ?>, 'rgb(248,81,73)');
</script>
