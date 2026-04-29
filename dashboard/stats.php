<?php
/**
 * CraftRadar — Статистика сервера
 */

$pageTitle = 'Статистика сервера';
require_once __DIR__ . '/../includes/header.php';

requireAuth();

$db = getDB();
$userId = currentUserId();
$id = getInt('id');

// Получаем сервер (только свой)
$stmt = $db->prepare('SELECT * FROM servers WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $userId]);
$server = $stmt->fetch();

if (!$server) {
    setFlash('error', 'Сервер не найден.');
    redirect(SITE_URL . '/dashboard/');
}

$period = get('period', '24h');

// Данные для графика онлайна
switch ($period) {
    case '7d':
        $stmt = $db->prepare('
            SELECT DATE_FORMAT(recorded_at, "%Y-%m-%d %H:00") as time_group,
                   ROUND(AVG(players_online)) as players,
                   MAX(is_online) as is_online
            FROM server_stats 
            WHERE server_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY time_group ORDER BY time_group
        ');
        break;
    case '30d':
        $stmt = $db->prepare('
            SELECT DATE_FORMAT(recorded_at, "%Y-%m-%d %H:00") as time_group,
                   ROUND(AVG(players_online)) as players,
                   MAX(is_online) as is_online
            FROM server_stats 
            WHERE server_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(recorded_at), FLOOR(HOUR(recorded_at)/3) ORDER BY time_group
        ');
        break;
    default: // 24h
        $stmt = $db->prepare('
            SELECT recorded_at as time_group, players_online as players, is_online
            FROM server_stats 
            WHERE server_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY recorded_at
        ');
        break;
}
$stmt->execute([$id]);
$onlineData = $stmt->fetchAll();

// Голоса по дням (30 дней)
$stmt = $db->prepare('
    SELECT DATE(voted_at) as day, COUNT(*) as cnt
    FROM votes 
    WHERE server_id = ? AND voted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY day ORDER BY day
');
$stmt->execute([$id]);
$votesData = $stmt->fetchAll();

// Uptime за 30 дней
$stmt = $db->prepare('
    SELECT COUNT(*) as total, SUM(is_online) as online_count
    FROM server_stats 
    WHERE server_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
');
$stmt->execute([$id]);
$uptimeData = $stmt->fetch();
$uptime = $uptimeData['total'] > 0 ? round(($uptimeData['online_count'] / $uptimeData['total']) * 100, 1) : 0;

// Пиковый и средний онлайн
$stmt = $db->prepare('
    SELECT MAX(players_online) as peak, ROUND(AVG(players_online)) as avg_online
    FROM server_stats 
    WHERE server_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND is_online = 1
');
$stmt->execute([$id]);
$peakData = $stmt->fetch();
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Статистика: <?= e($server['name']) ?></h1>
        <a href="<?= SITE_URL ?>/dashboard/" class="btn btn-ghost">← Назад</a>
    </div>

    <!-- Сводка -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-value"><?= $server['players_online'] ?>/<?= $server['players_max'] ?></div>
            <div class="stat-card-label">Сейчас онлайн</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-value"><?= (int)($peakData['peak'] ?? 0) ?></div>
            <div class="stat-card-label">Пик (30 дней)</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-value"><?= (int)($peakData['avg_online'] ?? 0) ?></div>
            <div class="stat-card-label">Средний онлайн</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-value"><?= $uptime ?>%</div>
            <div class="stat-card-label">Uptime (30 дней)</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-value"><?= $server['votes_month'] ?></div>
            <div class="stat-card-label">Голосов (месяц)</div>
        </div>
        <div class="stat-card">
            <div class="stat-card-value"><?= $server['votes_total'] ?></div>
            <div class="stat-card-label">Голосов всего</div>
        </div>
    </div>

    <!-- График онлайна -->
    <div class="card" style="margin-top: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 class="section-title" style="margin: 0;">График онлайна</h2>
            <div class="sort-tabs">
                <a href="?id=<?= $id ?>&period=24h" class="sort-tab <?= $period === '24h' ? 'active' : '' ?>">24ч</a>
                <a href="?id=<?= $id ?>&period=7d" class="sort-tab <?= $period === '7d' ? 'active' : '' ?>">7 дней</a>
                <a href="?id=<?= $id ?>&period=30d" class="sort-tab <?= $period === '30d' ? 'active' : '' ?>">30 дней</a>
            </div>
        </div>
        <canvas id="onlineChart" height="250"></canvas>
    </div>

    <!-- График голосов -->
    <div class="card" style="margin-top: 16px;">
        <h2 class="section-title">Голоса по дням (30 дней)</h2>
        <canvas id="votesChart" height="200"></canvas>
    </div>
</div>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
    }
    .stat-card {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 20px;
        text-align: center;
    }
    .stat-card-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--accent);
    }
    .stat-card-label {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 4px;
    }
    .sort-tabs {
        display: flex;
        gap: 4px;
    }
    .sort-tab {
        padding: 4px 12px;
        border-radius: var(--radius-sm);
        font-size: 0.8rem;
        color: var(--text-muted);
    }
    .sort-tab:hover, .sort-tab.active {
        background: var(--accent-bg);
        color: var(--accent);
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3"></script>
<script>
const chartOptions = {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: {
        x: { ticks: { color: '#8b949e', maxTicksLimit: 15 }, grid: { color: 'rgba(48,54,61,0.5)' } },
        y: { beginAtZero: true, ticks: { color: '#8b949e' }, grid: { color: 'rgba(48,54,61,0.5)' } }
    }
};

// График онлайна
const onlineData = <?= json_encode(array_map(fn($d) => ['time' => $d['time_group'], 'players' => (int)$d['players']], $onlineData)) ?>;
if (onlineData.length > 0) {
    new Chart(document.getElementById('onlineChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: onlineData.map(d => {
                const date = new Date(d.time);
                return date.toLocaleDateString('ru', {day:'2-digit', month:'2-digit'}) + ' ' + date.getHours().toString().padStart(2,'0') + ':00';
            }),
            datasets: [{
                label: 'Игроков',
                data: onlineData.map(d => d.players),
                borderColor: '#00ff80',
                backgroundColor: 'rgba(0,255,128,0.1)',
                fill: true, tension: 0.3, pointRadius: 1
            }]
        },
        options: chartOptions
    });
}

// График голосов
const votesData = <?= json_encode(array_map(fn($d) => ['day' => $d['day'], 'count' => (int)$d['cnt']], $votesData)) ?>;
if (votesData.length > 0) {
    new Chart(document.getElementById('votesChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: votesData.map(d => d.day),
            datasets: [{
                label: 'Голосов',
                data: votesData.map(d => d.count),
                backgroundColor: 'rgba(0,255,128,0.3)',
                borderColor: '#00ff80',
                borderWidth: 1
            }]
        },
        options: chartOptions
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
