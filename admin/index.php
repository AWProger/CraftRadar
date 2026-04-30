<?php
/**
 * CraftRadar — Админ-панель: Дашборд
 */

$adminPageTitle = 'Дашборд';
require_once __DIR__ . '/includes/admin_header.php';

$db = getDB();
$today = date('Y-m-d');
$statsDays = STATS_PERIOD_DAYS;

// === Статистика за сегодня (prepared statements) ===
$stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE DATE(created_at) = ?');
$stmt->execute([$today]);
$newUsersToday = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM servers WHERE DATE(created_at) = ?');
$stmt->execute([$today]);
$newServersToday = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM votes WHERE DATE(voted_at) = ?');
$stmt->execute([$today]);
$votesToday = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM reviews WHERE DATE(created_at) = ?');
$stmt->execute([$today]);
$reviewsToday = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM reports WHERE DATE(created_at) = ?');
$stmt->execute([$today]);
$reportsToday = (int)$stmt->fetchColumn();

// === Общая статистика ===
$totalUsers = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$bannedUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE is_banned = 1")->fetchColumn();
$totalServers = (int)$db->query("SELECT COUNT(*) FROM servers")->fetchColumn();
$activeServers = (int)$db->query("SELECT COUNT(*) FROM servers WHERE status = 'active'")->fetchColumn();
$pendingServers = (int)$db->query("SELECT COUNT(*) FROM servers WHERE status = 'pending'")->fetchColumn();
$onlineServers = (int)$db->query("SELECT COUNT(*) FROM servers WHERE status = 'active' AND is_online = 1")->fetchColumn();
$totalPlayers = (int)$db->query("SELECT COALESCE(SUM(players_online), 0) FROM servers WHERE status = 'active' AND is_online = 1")->fetchColumn();
$totalVotes = (int)$db->query("SELECT COUNT(*) FROM votes")->fetchColumn();
$totalReviews = (int)$db->query("SELECT COUNT(*) FROM reviews WHERE status = 'active'")->fetchColumn();
$verifiedServers = (int)$db->query("SELECT COUNT(*) FROM servers WHERE is_verified = 1")->fetchColumn();

// === Доходы ===
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND DATE(paid_at) = ?");
$stmt->execute([date('Y-m-d')]);
$revenueToday = (float)$stmt->fetchColumn();
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND paid_at >= ?");
$stmt->execute([date('Y-m-01')]);
$revenueMonth = (float)$stmt->fetchColumn();
$revenueTotal = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();
$avgCheck = (float)$db->query("SELECT COALESCE(AVG(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();

// === Требует внимания ===
$newReports = (int)$db->query("SELECT COUNT(*) FROM reports WHERE status = 'new'")->fetchColumn();
$stmt = $db->prepare("SELECT COUNT(*) FROM servers WHERE status = 'active' AND is_online = 0 AND last_ping < ?");
$stmt->execute([dateAgo(7, 'day')]);
$offlineLong = (int)$stmt->fetchColumn();
$stmt = $db->prepare("SELECT COUNT(*) FROM payments WHERE status = 'pending' AND created_at < ?");
$stmt->execute([dateAgo(1, 'hour')]);
$pendingPayments = (int)$stmt->fetchColumn();

// === Топ-5 серверов ===
$topServers = $db->query("
    SELECT id, name, votes_month, players_online, is_online 
    FROM servers WHERE status = 'active' 
    ORDER BY votes_month DESC LIMIT 5
")->fetchAll();

// === Статистика по категориям ===
$categoryStats = $db->query("
    SELECT c.name, c.icon, COUNT(s.id) as cnt 
    FROM categories c 
    LEFT JOIN servers s ON s.game_mode = c.slug AND s.status = 'active'
    WHERE c.is_active = 1 
    GROUP BY c.id ORDER BY cnt DESC
")->fetchAll();

// === Распределение отзывов по звёздам ===
$ratingDistribution = $db->query("
    SELECT rating, COUNT(*) as cnt 
    FROM reviews WHERE status = 'active' 
    GROUP BY rating ORDER BY rating
")->fetchAll();

// === Графики за N дней ===
$stmt = $db->prepare("
    SELECT DATE(created_at) as day, COUNT(*) as cnt 
    FROM users WHERE created_at >= ?
    GROUP BY day ORDER BY day
");
$stmt->execute([dateAgo($statsDays, 'day')]);
$registrationsByDay = $stmt->fetchAll();

$stmt = $db->prepare("
    SELECT DATE(created_at) as day, COUNT(*) as cnt 
    FROM servers WHERE created_at >= ?
    GROUP BY day ORDER BY day
");
$stmt->execute([dateAgo($statsDays, 'day')]);
$serversByDay = $stmt->fetchAll();

$stmt = $db->prepare("
    SELECT DATE(voted_at) as day, COUNT(*) as cnt 
    FROM votes WHERE voted_at >= ?
    GROUP BY day ORDER BY day
");
$stmt->execute([dateAgo($statsDays, 'day')]);
$votesByDay = $stmt->fetchAll();

$stmt = $db->prepare("
    SELECT DATE(paid_at) as day, SUM(amount) as total 
    FROM payments WHERE status = 'completed' AND paid_at >= ?
    GROUP BY day ORDER BY day
");
$stmt->execute([dateAgo($statsDays, 'day')]);
$revenueByDay = $stmt->fetchAll();

// === Последние действия ===
$recentLog = $db->query("
    SELECT al.*, u.username 
    FROM admin_log al 
    JOIN users u ON al.admin_id = u.id 
    ORDER BY al.created_at DESC LIMIT 20
")->fetchAll();
?>

<!-- Статистика за сегодня -->
<h2 class="section-title">📅 Сегодня</h2>
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
    <div class="admin-stat-card" style="border-color: var(--gold);">
        <div class="admin-stat-value" style="color: var(--gold);"><?= number_format($revenueToday, 0, '.', ' ') ?> ₽</div>
        <div class="admin-stat-label">Доход сегодня</div>
    </div>
</div>

<!-- Общая статистика -->
<h2 class="section-title">📊 Общая статистика</h2>
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $totalUsers ?></div>
        <div class="admin-stat-label">Пользователей (<?= $bannedUsers ?> бан)</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $totalServers ?></div>
        <div class="admin-stat-label">Серверов (<?= $activeServers ?> актив.)</div>
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
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $totalReviews ?></div>
        <div class="admin-stat-label">Отзывов</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $verifiedServers ?></div>
        <div class="admin-stat-label">Верифицированных</div>
    </div>
</div>

<!-- 💰 Доходы -->
<h2 class="section-title">💰 Доходы</h2>
<div class="admin-stats-grid">
    <div class="admin-stat-card" style="border-color: var(--gold);">
        <div class="admin-stat-value" style="color: var(--gold);"><?= number_format($revenueToday, 0, '.', ' ') ?> ₽</div>
        <div class="admin-stat-label">Сегодня</div>
    </div>
    <div class="admin-stat-card" style="border-color: var(--gold);">
        <div class="admin-stat-value" style="color: var(--gold);"><?= number_format($revenueMonth, 0, '.', ' ') ?> ₽</div>
        <div class="admin-stat-label">За месяц</div>
    </div>
    <div class="admin-stat-card" style="border-color: var(--gold);">
        <div class="admin-stat-value" style="color: var(--gold);"><?= number_format($revenueTotal, 0, '.', ' ') ?> ₽</div>
        <div class="admin-stat-label">Всего</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= number_format($avgCheck, 0) ?> ₽</div>
        <div class="admin-stat-label">Средний чек</div>
    </div>
</div>

<!-- ⚠️ Требует внимания -->
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
    <?php if ($pendingPayments > 0): ?>
    <div class="attention-card" style="border-color: var(--gold);">
        <div class="attention-card-info">
            <div class="attention-card-count" style="color: var(--gold);"><?= $pendingPayments ?></div>
            <div class="attention-card-label">Зависших платежей</div>
        </div>
        <a href="<?= SITE_URL ?>/admin/payments.php?status=pending" class="btn btn-sm btn-outline">Перейти</a>
    </div>
    <?php endif; ?>
</div>

<!-- 📊 Графики -->
<h2 class="section-title">📈 Графики (<?= $statsDays ?> дней)</h2>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
    <div class="card">
        <h3 style="margin-bottom: 8px; font-size: 0.85rem; color: var(--info);">👥 Регистрации</h3>
        <canvas id="chartRegistrations" height="180"></canvas>
    </div>
    <div class="card">
        <h3 style="margin-bottom: 8px; font-size: 0.85rem; color: var(--success);">📡 Серверы</h3>
        <canvas id="chartServers" height="180"></canvas>
    </div>
    <div class="card">
        <h3 style="margin-bottom: 8px; font-size: 0.85rem; color: var(--warning);">👍 Голоса</h3>
        <canvas id="chartVotes" height="180"></canvas>
    </div>
    <div class="card">
        <h3 style="margin-bottom: 8px; font-size: 0.85rem; color: var(--gold);">💰 Доходы (₽)</h3>
        <canvas id="chartRevenue" height="180"></canvas>
    </div>
</div>

<!-- Топ-5 серверов + Категории + Рейтинги -->
<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 24px;">
    <!-- Топ-5 серверов -->
    <div class="card">
        <h3 style="margin-bottom: 12px; font-size: 0.85rem;">🏆 Топ-5 серверов</h3>
        <?php foreach ($topServers as $i => $ts): ?>
            <div style="display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid var(--border); font-size: 0.8rem;">
                <span>
                    <strong style="color: var(--text-muted);">#<?= $i + 1 ?></strong>
                    <a href="<?= SITE_URL ?>/admin/server_view.php?id=<?= $ts['id'] ?>"><?= e(truncate($ts['name'], 20)) ?></a>
                </span>
                <span style="color: var(--accent);"><?= $ts['votes_month'] ?> 👍</span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Категории -->
    <div class="card">
        <h3 style="margin-bottom: 12px; font-size: 0.85rem;">🏷️ Серверов по категориям</h3>
        <?php foreach ($categoryStats as $cs): ?>
            <div style="display: flex; justify-content: space-between; padding: 4px 0; font-size: 0.8rem;">
                <span><?= e($cs['icon'] . ' ' . $cs['name']) ?></span>
                <span style="color: var(--text-muted);"><?= $cs['cnt'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Распределение рейтингов -->
    <div class="card">
        <h3 style="margin-bottom: 12px; font-size: 0.85rem;">⭐ Распределение оценок</h3>
        <?php
        $ratingMap = array_column($ratingDistribution, 'cnt', 'rating');
        $maxRating = max(array_values($ratingMap) ?: [1]);
        for ($star = 5; $star >= 1; $star--):
            $cnt = $ratingMap[$star] ?? 0;
            $pct = $maxRating > 0 ? ($cnt / $maxRating) * 100 : 0;
        ?>
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; font-size: 0.8rem;">
                <span style="min-width: 20px; color: var(--gold);"><?= $star ?>★</span>
                <div style="flex: 1; height: 12px; background: var(--bg); border: 1px solid var(--border);">
                    <div style="height: 100%; width: <?= $pct ?>%; background: var(--gold);"></div>
                </div>
                <span style="min-width: 24px; text-align: right; color: var(--text-muted);"><?= $cnt ?></span>
            </div>
        <?php endfor; ?>
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
makeChart('chartRevenue', <?= json_encode(array_map(fn($d) => ['day' => $d['day'], 'total' => (float)$d['total']], $revenueByDay)) ?>, 'rgb(255,215,0)');
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
