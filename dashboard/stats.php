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

// Позиция в рейтинге по голосам за месяц
$stmt = $db->prepare("SELECT COUNT(*) + 1 FROM servers WHERE status IN ('active', 'pending') AND votes_month > ?");
$stmt->execute([$server['votes_month']]);
$rankPosition = (int)$stmt->fetchColumn();

$period = get('period', '24h');

// Формат даты для группировки (совместимость MySQL/SQLite)
$dateGroupExpr = isSQLite()
    ? "STRFTIME('%Y-%m-%d %H:00', recorded_at)"
    : "DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00')";

// Данные для графика онлайна
switch ($period) {
    case '7d':
        $stmt = $db->prepare("
            SELECT {$dateGroupExpr} as time_group,
                   ROUND(AVG(players_online)) as players,
                   MAX(is_online) as is_online
            FROM server_stats 
            WHERE server_id = ? AND recorded_at >= ?
            GROUP BY time_group ORDER BY time_group
        ");
        $stmt->execute([$id, dateAgo(7, 'day')]);
        break;
    case '30d':
        if (isSQLite()) {
            $groupBy = "STRFTIME('%Y-%m-%d', recorded_at), CAST(STRFTIME('%H', recorded_at) AS INTEGER) / 3";
        } else {
            $groupBy = "DATE(recorded_at), FLOOR(HOUR(recorded_at)/3)";
        }
        $stmt = $db->prepare("
            SELECT {$dateGroupExpr} as time_group,
                   ROUND(AVG(players_online)) as players,
                   MAX(is_online) as is_online
            FROM server_stats 
            WHERE server_id = ? AND recorded_at >= ?
            GROUP BY {$groupBy} ORDER BY time_group
        ");
        $stmt->execute([$id, dateAgo(30, 'day')]);
        break;
    default: // 24h
        $stmt = $db->prepare('
            SELECT recorded_at as time_group, players_online as players, is_online
            FROM server_stats 
            WHERE server_id = ? AND recorded_at >= ?
            ORDER BY recorded_at
        ');
        $stmt->execute([$id, dateAgo(24, 'hour')]);
        break;
}
$onlineData = $stmt->fetchAll();

// Голоса по дням (30 дней)
$stmt = $db->prepare('
    SELECT DATE(voted_at) as day, COUNT(*) as cnt
    FROM votes 
    WHERE server_id = ? AND voted_at >= ?
    GROUP BY day ORDER BY day
');
$stmt->execute([$id, dateAgo(30, 'day')]);
$votesData = $stmt->fetchAll();

// Uptime за 30 дней
$stmt = $db->prepare('
    SELECT COUNT(*) as total, SUM(is_online) as online_count
    FROM server_stats 
    WHERE server_id = ? AND recorded_at >= ?
');
$stmt->execute([$id, dateAgo(30, 'day')]);
$uptimeData = $stmt->fetch();
$uptime = $uptimeData['total'] > 0 ? round(($uptimeData['online_count'] / $uptimeData['total']) * 100, 1) : 0;

// Пиковый и средний онлайн
$stmt = $db->prepare('
    SELECT MAX(players_online) as peak, ROUND(AVG(players_online)) as avg_online
    FROM server_stats 
    WHERE server_id = ? AND recorded_at >= ? AND is_online = 1
');
$stmt->execute([$id, dateAgo(30, 'day')]);
$peakData = $stmt->fetch();
?>

<div class="dashboard">
    <?= dashboardNav('servers') ?>
    <div class="dashboard-header">
        <h1>Статистика: <?= e($server['name']) ?></h1>
    </div>

    <!-- Сводка -->
    <div class="stats-grid">
        <div class="stat-card" style="border-color: var(--gold);">
            <div class="stat-card-value" style="color: var(--gold);">#<?= $rankPosition ?></div>
            <div class="stat-card-label">В рейтинге</div>
        </div>
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
        <?php
        // Голоса за эту неделю vs прошлую
        $stmt = $db->prepare('SELECT COUNT(*) FROM votes WHERE server_id = ? AND voted_at >= ?');
        $stmt->execute([$id, dateAgo(7, 'day')]);
        $votesThisWeek = (int)$stmt->fetchColumn();
        $stmt = $db->prepare('SELECT COUNT(*) FROM votes WHERE server_id = ? AND voted_at >= ? AND voted_at < ?');
        $stmt->execute([$id, dateAgo(14, 'day'), dateAgo(7, 'day')]);
        $votesLastWeek = (int)$stmt->fetchColumn();
        $weekDiff = $votesThisWeek - $votesLastWeek;
        ?>
        <div class="stat-card">
            <div class="stat-card-value"><?= $votesThisWeek ?></div>
            <div class="stat-card-label">За неделю
                <?php if ($weekDiff !== 0): ?>
                    <span style="color: <?= $weekDiff > 0 ? 'var(--success)' : 'var(--danger)' ?>; font-size: 0.6rem;">
                        (<?= $weekDiff > 0 ? '+' : '' ?><?= $weekDiff ?>)
                    </span>
                <?php endif; ?>
            </div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@3"></script>
<script>
const chartOptions = {
    responsive: true,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { display: false },
        tooltip: {
            backgroundColor: 'rgba(13,17,23,0.95)',
            borderColor: '#00ff80',
            borderWidth: 1,
            titleColor: '#00ff80',
            bodyColor: '#c9d1d9',
            padding: 10,
            displayColors: false,
        }
    },
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
                fill: true, tension: 0.3, pointRadius: 3, pointHoverRadius: 6, pointHitRadius: 20, pointBackgroundColor: '#00ff80', borderWidth: 2,
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
