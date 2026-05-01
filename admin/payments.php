<?php
/**
 * CraftRadar — Админка: Управление платежами
 */

$adminPageTitle = 'Платежи';
require_once __DIR__ . '/includes/admin_header.php';
requireAdmin();

$db = getDB();

// Экспорт CSV
if (get('export') === 'csv') {
    $allPayments = $db->query("
        SELECT p.id, u.username, s.name as server_name, p.amount, p.type, p.status, p.label, p.created_at, p.paid_at
        FROM payments p JOIN users u ON p.user_id = u.id LEFT JOIN servers s ON p.server_id = s.id
        ORDER BY p.created_at DESC
    ")->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=payments_' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['ID', 'Пользователь', 'Сервер', 'Сумма', 'Тариф', 'Статус', 'Метка', 'Создан', 'Оплачен'], ';');
    foreach ($allPayments as $p) {
        fputcsv($out, [$p['id'], $p['username'], $p['server_name'], $p['amount'], $p['type'], $p['status'], $p['label'], $p['created_at'], $p['paid_at']], ';');
    }
    fclose($out);
    exit;
}

$page = max(1, getInt('page', 1));
$status = get('status');
$search = get('q');
$perPage = ADMIN_PER_PAGE;

$where = ['1=1'];
$params = [];

if ($status) { $where[] = 'p.status = ?'; $params[] = $status; }
if ($search) {
    $where[] = '(p.label LIKE ? OR u.username LIKE ? OR s.name LIKE ? OR p.id = ?)';
    $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = (int)$search;
}

$whereSQL = implode(' AND ', $where);

$stmt = $db->prepare("SELECT COUNT(*) FROM payments p JOIN users u ON p.user_id = u.id LEFT JOIN servers s ON p.server_id = s.id WHERE {$whereSQL}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$offset = ($page - 1) * $perPage;
$stmt = $db->prepare("
    SELECT p.*, u.username, s.name as server_name 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    LEFT JOIN servers s ON p.server_id = s.id 
    WHERE {$whereSQL} 
    ORDER BY p.created_at DESC 
    LIMIT {$perPage} OFFSET {$offset}
");
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Статистика доходов
$totalRevenue = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();
$monthStart = date('Y-m-01');
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND paid_at >= ?");
$stmt->execute([$monthStart]);
$monthRevenue = (float)$stmt->fetchColumn();
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND DATE(paid_at) = ?");
$stmt->execute([date('Y-m-d')]);
$todayRevenue = (float)$stmt->fetchColumn();
$pendingCount = (int)$db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();

// Доходы по дням (30 дней) для графика
$stmtRevenue = $db->prepare("
    SELECT DATE(paid_at) as day, SUM(amount) as total, COUNT(*) as cnt
    FROM payments 
    WHERE status = 'completed' AND paid_at >= ?
    GROUP BY day ORDER BY day
");
$stmtRevenue->execute([dateAgo(30, 'day')]);
$revenueByDay = $stmtRevenue->fetchAll();

$baseUrl = SITE_URL . '/admin/payments.php?x=1';
if ($status) $baseUrl .= '&status=' . urlencode($status);
if ($search) $baseUrl .= '&q=' . urlencode($search);
?>

<!-- Статистика доходов -->
<div class="admin-stats-grid">
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= number_format($todayRevenue, 0, '.', ' ') ?> ₽</div>
        <div class="admin-stat-label">Сегодня</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= number_format($monthRevenue, 0, '.', ' ') ?> ₽</div>
        <div class="admin-stat-label">За месяц</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= number_format($totalRevenue, 0, '.', ' ') ?> ₽</div>
        <div class="admin-stat-label">Всего</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $pendingCount ?></div>
        <div class="admin-stat-label">Ожидают оплаты</div>
    </div>
</div>

<!-- График доходов -->
<div class="card" style="margin-bottom: 20px;">
    <h3 style="margin-bottom: 12px;">Доходы за 30 дней</h3>
    <canvas id="revenueChart" height="200"></canvas>
</div>

<!-- Фильтры -->
<form method="GET" class="admin-filters">
    <select name="status">
        <option value="">Все статусы</option>
        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Оплачен</option>
        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Ожидает</option>
        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Ошибка</option>
        <option value="refunded" <?= $status === 'refunded' ? 'selected' : '' ?>>Возврат</option>
    </select>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Поиск по метке, пользователю, серверу...">
    <button type="submit" class="btn btn-sm btn-primary">Найти</button>
    <a href="<?= SITE_URL ?>/admin/payments.php?export=csv" class="btn btn-sm btn-outline">📥 CSV</a>
</form>

<!-- Таблица -->
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Пользователь</th>
                <th>Сервер</th>
                <th>Тариф</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th>Метка</th>
                <th>Создан</th>
                <th>Оплачен</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><a href="<?= SITE_URL ?>/admin/user_view.php?id=<?= $p['user_id'] ?>"><?= e($p['username']) ?></a></td>
                    <td>
                        <?php if ($p['server_id']): ?>
                            <a href="<?= SITE_URL ?>/admin/server_view.php?id=<?= $p['server_id'] ?>"><?= e($p['server_name'] ?? '#' . $p['server_id']) ?></a>
                        <?php else: ?>
                            <span style="color: var(--text-muted);">—</span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($p['type']) ?></td>
                    <td><strong><?= number_format($p['amount'], 0) ?> ₽</strong></td>
                    <td>
                        <?php
                        $sBadge = match($p['status']) {
                            'completed' => 'badge-online',
                            'pending' => 'badge-pending',
                            default => 'badge-offline'
                        };
                        ?>
                        <span class="badge <?= $sBadge ?>"><?= e($p['status']) ?></span>
                    </td>
                    <td style="font-size: 0.75rem; color: var(--text-muted);"><?= e($p['label'] ?? '') ?></td>
                    <td><?= formatDate($p['created_at']) ?></td>
                    <td><?= $p['paid_at'] ? formatDate($p['paid_at']) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= paginate($total, $perPage, $page, $baseUrl) ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3"></script>
<script>
const revenueData = <?= json_encode(array_map(fn($d) => ['day' => $d['day'], 'total' => (float)$d['total'], 'count' => (int)$d['cnt']], $revenueByDay)) ?>;
if (revenueData.length > 0) {
    new Chart(document.getElementById('revenueChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: revenueData.map(d => d.day),
            datasets: [{
                label: 'Доход (₽)',
                data: revenueData.map(d => d.total),
                backgroundColor: 'rgba(0,255,128,0.3)',
                borderColor: '#00ff80',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: '#8b949e' }, grid: { color: 'rgba(48,54,61,0.5)' } },
                y: { beginAtZero: true, ticks: { color: '#8b949e', callback: v => v + ' ₽' }, grid: { color: 'rgba(48,54,61,0.5)' } }
            }
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
