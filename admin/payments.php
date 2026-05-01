<?php
/**
 * CraftRadar — Админка: Управление платежами (только реальные деньги)
 */

$adminPageTitle = 'Платежи';
require_once __DIR__ . '/includes/admin_header.php';
requireAdmin();

$db = getDB();

// Экспорт CSV
if (get('export') === 'csv') {
    $allPayments = $db->query("
        SELECT p.id, u.username, s.name as server_name, p.amount, p.type, p.status, p.label, 
               p.payment_id, p.yoomoney_operation_id, p.created_at, p.paid_at
        FROM payments p JOIN users u ON p.user_id = u.id LEFT JOIN servers s ON p.server_id = s.id
        ORDER BY p.created_at DESC
    ")->fetchAll();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=payments_' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['ID', 'Пользователь', 'Сервер', 'Сумма', 'Тариф', 'Статус', 'Метка', 'Payment ID', 'YooMoney ID', 'Создан', 'Оплачен'], ';');
    foreach ($allPayments as $p) fputcsv($out, array_values($p), ';');
    fclose($out);
    exit;
}

// Действия
if (isPost() && verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
    $action = post('action');
    $payId = (int)post('payment_id');

    switch ($action) {
        case 'refund':
            $db->prepare("UPDATE payments SET status = 'refunded' WHERE id = ? AND status = 'completed'")->execute([$payId]);
            adminLog('refund_payment', 'payment', $payId);
            setFlash('success', 'Платёж #' . $payId . ' помечен как возврат.');
            break;

        case 'delete':
            // Удаляем только pending/failed платежи (не completed)
            $stmt = $db->prepare("SELECT status FROM payments WHERE id = ?");
            $stmt->execute([$payId]);
            $payStatus = $stmt->fetchColumn();
            if (in_array($payStatus, ['pending', 'failed'])) {
                $db->prepare("DELETE FROM payments WHERE id = ?")->execute([$payId]);
                adminLog('delete_payment', 'payment', $payId);
                setFlash('success', 'Платёж #' . $payId . ' удалён.');
            } else {
                setFlash('error', 'Нельзя удалить оплаченный или возвращённый платёж.');
            }
            break;

        case 'mark_failed':
            $db->prepare("UPDATE payments SET status = 'failed' WHERE id = ? AND status = 'pending'")->execute([$payId]);
            adminLog('fail_payment', 'payment', $payId);
            setFlash('success', 'Платёж #' . $payId . ' помечен как ошибка.');
            break;

        case 'mark_completed':
            $db->prepare("UPDATE payments SET status = 'completed', paid_at = ? WHERE id = ? AND status = 'pending'")->execute([now(), $payId]);
            adminLog('complete_payment', 'payment', $payId);
            setFlash('success', 'Платёж #' . $payId . ' помечен как оплаченный.');
            break;
    }
    redirect($_SERVER['REQUEST_URI']);
}

$page = max(1, getInt('page', 1));
$status = get('status');
$search = get('q');
$perPage = ADMIN_PER_PAGE;

$where = ['1=1'];
$params = [];

if ($status) { $where[] = 'p.status = ?'; $params[] = $status; }
if ($search) {
    $where[] = '(p.label LIKE ? OR u.username LIKE ? OR s.name LIKE ? OR p.id = ? OR p.payment_id LIKE ?)';
    $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = "%{$search}%"; $params[] = (int)$search; $params[] = "%{$search}%";
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

// Статистика (только реальные деньги — таблица payments)
$totalRevenue = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();
$monthStart = date('Y-m-01');
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND paid_at >= ?");
$stmt->execute([$monthStart]);
$monthRevenue = (float)$stmt->fetchColumn();
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND DATE(paid_at) = ?");
$stmt->execute([date('Y-m-d')]);
$todayRevenue = (float)$stmt->fetchColumn();
$completedCount = (int)$db->query("SELECT COUNT(*) FROM payments WHERE status = 'completed'")->fetchColumn();
$pendingCount = (int)$db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
$refundedCount = (int)$db->query("SELECT COUNT(*) FROM payments WHERE status = 'refunded'")->fetchColumn();
$avgCheck = $completedCount > 0 ? round($totalRevenue / $completedCount) : 0;

// График
$stmtRevenue = $db->prepare("
    SELECT DATE(paid_at) as day, SUM(amount) as total, COUNT(*) as cnt
    FROM payments WHERE status = 'completed' AND paid_at >= ?
    GROUP BY day ORDER BY day
");
$stmtRevenue->execute([dateAgo(30, 'day')]);
$revenueByDay = $stmtRevenue->fetchAll();

$baseUrl = SITE_URL . '/admin/payments.php?x=1';
if ($status) $baseUrl .= '&status=' . urlencode($status);
if ($search) $baseUrl .= '&q=' . urlencode($search);
?>

<!-- Статистика -->
<div class="admin-stats-grid">
    <div class="admin-stat-card" style="border-color: var(--gold);">
        <div class="admin-stat-value" style="color: var(--gold);"><?= number_format($todayRevenue, 0, '.', ' ') ?> ₽</div>
        <div class="admin-stat-label">Сегодня</div>
    </div>
    <div class="admin-stat-card" style="border-color: var(--gold);">
        <div class="admin-stat-value" style="color: var(--gold);"><?= number_format($monthRevenue, 0, '.', ' ') ?> ₽</div>
        <div class="admin-stat-label">За месяц</div>
    </div>
    <div class="admin-stat-card" style="border-color: var(--gold);">
        <div class="admin-stat-value" style="color: var(--gold);"><?= number_format($totalRevenue, 0, '.', ' ') ?> ₽</div>
        <div class="admin-stat-label">Всего</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $avgCheck ?> ₽</div>
        <div class="admin-stat-label">Средний чек</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $completedCount ?></div>
        <div class="admin-stat-label">Оплачено</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $pendingCount ?></div>
        <div class="admin-stat-label">Ожидают</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $refundedCount ?></div>
        <div class="admin-stat-label">Возвратов</div>
    </div>
</div>

<!-- График -->
<div class="card" style="margin-bottom: 20px;">
    <h3 style="margin-bottom: 12px;">💰 Доходы за 30 дней</h3>
    <canvas id="revenueChart" height="200"></canvas>
</div>

<!-- Фильтры -->
<form method="GET" class="admin-filters">
    <select name="status">
        <option value="">Все статусы</option>
        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>✅ Оплачен</option>
        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>⏳ Ожидает</option>
        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>❌ Ошибка</option>
        <option value="refunded" <?= $status === 'refunded' ? 'selected' : '' ?>>↩️ Возврат</option>
    </select>
    <input type="text" name="q" value="<?= e($search) ?>" placeholder="Поиск по ID, метке, пользователю, серверу...">
    <button type="submit" class="btn btn-sm btn-primary">Найти</button>
    <a href="<?= SITE_URL ?>/admin/payments.php" class="btn btn-sm btn-ghost">Сбросить</a>
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
                <th>Метка / Payment ID</th>
                <th>Создан</th>
                <th>Оплачен</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($payments)): ?>
                <tr><td colspan="10" style="text-align:center;color:var(--text-muted);padding:30px;">Платежей нет</td></tr>
            <?php endif; ?>
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
                    <td><?= e($p['type'] ?? '—') ?></td>
                    <td><strong style="color: var(--gold);"><?= number_format($p['amount'], 0) ?> ₽</strong></td>
                    <td>
                        <?php
                        $sBadge = match($p['status']) {
                            'completed' => 'badge-online',
                            'pending' => 'badge-pending',
                            'refunded' => 'badge-offline',
                            default => 'badge-offline'
                        };
                        $sIcon = match($p['status']) {
                            'completed' => '✅',
                            'pending' => '⏳',
                            'refunded' => '↩️',
                            default => '❌'
                        };
                        ?>
                        <span class="badge <?= $sBadge ?>"><?= $sIcon ?> <?= e($p['status']) ?></span>
                    </td>
                    <td style="font-size: 0.7rem; color: var(--text-muted);">
                        <?= e($p['label'] ?? '') ?>
                        <?php if (!empty($p['payment_id'])): ?>
                            <br><span style="font-family:monospace;"><?= e(truncate($p['payment_id'], 20)) ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size: 0.75rem;"><?= formatDate($p['created_at']) ?></td>
                    <td style="font-size: 0.75rem;"><?= $p['paid_at'] ? formatDate($p['paid_at']) : '<span style="color:var(--text-muted);">—</span>' ?></td>
                    <td>
                        <div class="action-btns">
                            <!-- Просмотр деталей -->
                            <button class="btn btn-sm btn-ghost" title="Детали" onclick="showPaymentDetails(<?= $p['id'] ?>, this)" 
                                    data-details="<?= e(json_encode([
                                        'ID' => $p['id'],
                                        'Пользователь' => $p['username'] . ' (#' . $p['user_id'] . ')',
                                        'Сервер' => ($p['server_name'] ?? '—') . ($p['server_id'] ? ' (#' . $p['server_id'] . ')' : ''),
                                        'Сумма' => $p['amount'] . ' ₽',
                                        'Тариф' => $p['type'] ?? '—',
                                        'Статус' => $p['status'],
                                        'Метка' => $p['label'] ?? '—',
                                        'Payment ID' => $p['payment_id'] ?? '—',
                                        'YooMoney ID' => $p['yoomoney_operation_id'] ?? '—',
                                        'Описание' => $p['description'] ?? '—',
                                        'Создан' => $p['created_at'],
                                        'Оплачен' => $p['paid_at'] ?? '—',
                                    ])) ?>">👁</button>

                            <?php if ($p['status'] === 'completed'): ?>
                                <form method="POST" style="display:inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="refund">
                                    <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                                    <button class="btn btn-sm btn-ghost" data-confirm="Пометить как возврат?" title="Возврат">↩️</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($p['status'] === 'pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="mark_completed">
                                    <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                                    <button class="btn btn-sm btn-ghost" data-confirm="Подтвердить оплату вручную?" title="Подтвердить">✅</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="mark_failed">
                                    <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                                    <button class="btn btn-sm btn-ghost" title="Ошибка">❌</button>
                                </form>
                            <?php endif; ?>

                            <?php if (in_array($p['status'], ['pending', 'failed'])): ?>
                                <form method="POST" style="display:inline;">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                                    <button class="btn btn-sm btn-ghost" data-confirm="Удалить платёж #<?= $p['id'] ?>? Это необратимо!" title="Удалить">🗑</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?= paginate($total, $perPage, $page, $baseUrl) ?>

<!-- Модалка деталей платежа -->
<div id="paymentModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:200;align-items:center;justify-content:center;">
    <div class="card" style="max-width:500px;width:90%;max-height:80vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <h3>💰 Детали платежа</h3>
            <button onclick="document.getElementById('paymentModal').style.display='none'" class="btn btn-sm btn-ghost">✕</button>
        </div>
        <div id="paymentDetails"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3"></script>
<script>
// График
const revenueData = <?= json_encode(array_map(fn($d) => ['day' => $d['day'], 'total' => (float)$d['total'], 'count' => (int)$d['cnt']], $revenueByDay)) ?>;
if (revenueData.length > 0) {
    new Chart(document.getElementById('revenueChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: revenueData.map(d => d.day.slice(5)),
            datasets: [{
                label: 'Доход (₽)',
                data: revenueData.map(d => d.total),
                backgroundColor: 'rgba(255,215,0,0.3)',
                borderColor: '#ffd700',
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

// Просмотр деталей
function showPaymentDetails(id, btn) {
    var data = JSON.parse(btn.dataset.details);
    var html = '<table style="width:100%;">';
    for (var key in data) {
        html += '<tr><td style="color:var(--text-muted);padding:6px 8px;white-space:nowrap;">' + key + '</td>';
        html += '<td style="padding:6px 8px;word-break:break-all;">' + (data[key] || '—') + '</td></tr>';
    }
    html += '</table>';
    document.getElementById('paymentDetails').innerHTML = html;
    document.getElementById('paymentModal').style.display = 'flex';
}

// Закрытие модалки по Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') document.getElementById('paymentModal').style.display = 'none';
});
document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
