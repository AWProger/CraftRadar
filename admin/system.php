<?php
/**
 * CraftRadar — Админка: Системная панель
 */

$adminPageTitle = 'Система';
require_once __DIR__ . '/includes/admin_header.php';
requireAdmin();

$db = getDB();

// === Действия ===
if (isPost() && verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
    $action = post('action');

    switch ($action) {
        // Запуск крон-задач
        case 'run_ping':
            $output = [];
            $url = SITE_URL . '/cron/ping_servers.php?key=' . CRON_SECRET_KEY;
            $result = @file_get_contents($url);
            adminLog('run_cron', 'setting', 0, 'ping_servers');
            setFlash('success', 'Пинг запущен. Результат: ' . ($result ? truncate($result, 200) : 'OK'));
            break;

        case 'run_cleanup':
            $url = SITE_URL . '/cron/cleanup_stats.php?key=' . CRON_SECRET_KEY;
            $result = @file_get_contents($url);
            adminLog('run_cron', 'setting', 0, 'cleanup_stats');
            setFlash('success', 'Очистка запущена. ' . ($result ? truncate($result, 200) : 'OK'));
            break;

        case 'run_reset':
            $url = SITE_URL . '/cron/reset_monthly.php?key=' . CRON_SECRET_KEY;
            $result = @file_get_contents($url);
            adminLog('run_cron', 'setting', 0, 'reset_monthly');
            setFlash('success', 'Сброс месячных голосов запущен. ' . ($result ? truncate($result, 200) : 'OK'));
            break;

        // Очистка кэша
        case 'clear_cache':
            require_once INCLUDES_PATH . 'cache.php';
            cacheClear();
            adminLog('clear_cache', 'setting', 0);
            setFlash('success', 'Кэш очищен.');
            break;

        // Массовое уведомление
        case 'mass_notify':
            $title = post('notify_title');
            $message = post('notify_message');
            $link = post('notify_link');
            if ($title && $message) {
                require_once INCLUDES_PATH . 'notifications.php';
                $users = $db->query('SELECT id FROM users WHERE is_banned = 0')->fetchAll();
                $count = 0;
                foreach ($users as $u) {
                    createNotification($u['id'], 'system', $title, $message, $link ?: null);
                    $count++;
                }
                adminLog('mass_notify', 'setting', 0, json_encode(['count' => $count, 'title' => $title]));
                setFlash('success', "Уведомление отправлено {$count} пользователям.");
            }
            break;

        // Режим обслуживания
        case 'toggle_maintenance':
            $file = ROOT_PATH . 'storage/.maintenance';
            if (file_exists($file)) {
                unlink($file);
                adminLog('maintenance_off', 'setting', 0);
                setFlash('success', 'Режим обслуживания ВЫКЛЮЧЕН.');
            } else {
                if (!is_dir(dirname($file))) @mkdir(dirname($file), 0755, true);
                file_put_contents($file, json_encode(['since' => now(), 'by' => currentUserId()]));
                adminLog('maintenance_on', 'setting', 0);
                setFlash('success', 'Режим обслуживания ВКЛЮЧЁН. Сайт недоступен для пользователей.');
            }
            break;

        // SQL-запрос
        case 'run_sql':
            $sql = trim(post('sql_query'));
            if ($sql) {
                // Только SELECT разрешён для безопасности
                $sqlUpper = strtoupper(ltrim($sql));
                if (!str_starts_with($sqlUpper, 'SELECT') && !str_starts_with($sqlUpper, 'SHOW') && !str_starts_with($sqlUpper, 'DESCRIBE')) {
                    setFlash('error', 'Разрешены только SELECT, SHOW, DESCRIBE запросы. Для изменений используйте phpMyAdmin.');
                } else {
                    try {
                        $stmt = $db->query($sql);
                        $_SESSION['sql_result'] = $stmt->fetchAll();
                        $_SESSION['sql_query'] = $sql;
                        adminLog('run_sql', 'setting', 0, truncate($sql, 200));
                    } catch (\PDOException $e) {
                        setFlash('error', 'SQL ошибка: ' . $e->getMessage());
                    }
                }
            }
            break;
    }

    if ($action !== 'run_sql') {
        redirect(SITE_URL . '/admin/system.php');
    }
}

// === Системная информация ===
$phpVersion = phpversion();
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$dbVersion = $db->query('SELECT VERSION()')->fetchColumn();
$dbSize = 0;
try {
    $tables = $db->query("SHOW TABLE STATUS")->fetchAll();
    foreach ($tables as $t) $dbSize += ($t['Data_length'] ?? 0) + ($t['Index_length'] ?? 0);
} catch (\Exception $e) { $tables = []; }
$dbSizeStr = $dbSize > 1048576 ? round($dbSize / 1048576, 2) . ' MB' : round($dbSize / 1024, 1) . ' KB';

// Кэш
$cacheDir = ROOT_PATH . 'storage/cache/';
$cacheFiles = is_dir($cacheDir) ? count(glob($cacheDir . '*.json')) : 0;
$cacheSize = 0;
if (is_dir($cacheDir)) foreach (glob($cacheDir . '*.json') as $f) $cacheSize += filesize($f);
$cacheSizeStr = $cacheSize > 1024 ? round($cacheSize / 1024, 1) . ' KB' : $cacheSize . ' B';

// Диск
$diskFree = @disk_free_space(ROOT_PATH);
$diskTotal = @disk_total_space(ROOT_PATH);
$diskFreeStr = $diskFree ? round($diskFree / 1073741824, 2) . ' GB' : '?';
$diskTotalStr = $diskTotal ? round($diskTotal / 1073741824, 2) . ' GB' : '?';
$diskPct = ($diskFree && $diskTotal) ? round((1 - $diskFree / $diskTotal) * 100, 1) : 0;

// Режим обслуживания
$maintenanceFile = ROOT_PATH . 'storage/.maintenance';
$isMaintenanceOn = file_exists($maintenanceFile);
$maintenanceInfo = $isMaintenanceOn ? json_decode(file_get_contents($maintenanceFile), true) : null;

// Логи крона
$cronLogFile = ROOT_PATH . 'storage/cron.log';
$cronLog = '';
if (file_exists($cronLogFile)) {
    $lines = file($cronLogFile);
    $cronLog = implode('', array_slice($lines, -20));
}

// SQL результат из сессии
$sqlResult = $_SESSION['sql_result'] ?? null;
$sqlQuery = $_SESSION['sql_query'] ?? '';
unset($_SESSION['sql_result'], $_SESSION['sql_query']);
?>

<!-- Системная информация -->
<div class="admin-stats-grid" style="margin-bottom: 20px;">
    <div class="admin-stat-card">
        <div class="admin-stat-value" style="font-size: 0.7rem;">PHP <?= $phpVersion ?></div>
        <div class="admin-stat-label">Версия PHP</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value" style="font-size: 0.7rem;"><?= $dbVersion ?></div>
        <div class="admin-stat-label">MySQL</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $dbSizeStr ?></div>
        <div class="admin-stat-label">Размер БД</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $cacheSizeStr ?></div>
        <div class="admin-stat-label">Кэш (<?= $cacheFiles ?> файлов)</div>
    </div>
    <div class="admin-stat-card">
        <div class="admin-stat-value"><?= $diskFreeStr ?></div>
        <div class="admin-stat-label">Свободно на диске (<?= $diskPct ?>% занято)</div>
    </div>
    <div class="admin-stat-card" style="border-color: <?= $isMaintenanceOn ? 'var(--danger)' : 'var(--success)' ?>;">
        <div class="admin-stat-value" style="color: <?= $isMaintenanceOn ? 'var(--danger)' : 'var(--success)' ?>;">
            <?= $isMaintenanceOn ? '🔴 ВКЛ' : '🟢 ВЫКЛ' ?>
        </div>
        <div class="admin-stat-label">Режим обслуживания</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">

    <!-- Быстрые действия -->
    <div class="card">
        <h3 style="margin-bottom: 12px;">⚡ Быстрые действия</h3>
        <div style="display: flex; flex-direction: column; gap: 8px;">
            <form method="POST" style="display:inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="run_ping">
                <button class="btn btn-sm btn-outline btn-block">📡 Запустить пинг серверов</button>
            </form>
            <form method="POST" style="display:inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="run_cleanup">
                <button class="btn btn-sm btn-outline btn-block">🧹 Очистка старых данных</button>
            </form>
            <form method="POST" style="display:inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="run_reset">
                <button class="btn btn-sm btn-outline btn-block" data-confirm="Сбросить месячные голоса? Это необратимо!">🔄 Сброс месячных голосов</button>
            </form>
            <form method="POST" style="display:inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="clear_cache">
                <button class="btn btn-sm btn-outline btn-block">🗑 Очистить кэш</button>
            </form>
            <form method="POST" style="display:inline;">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="toggle_maintenance">
                <button class="btn btn-sm <?= $isMaintenanceOn ? 'btn-primary' : 'btn-danger' ?> btn-block" data-confirm="<?= $isMaintenanceOn ? 'Выключить режим обслуживания?' : 'Включить режим обслуживания? Сайт станет недоступен!' ?>">
                    <?= $isMaintenanceOn ? '🟢 Выключить обслуживание' : '🔴 Включить обслуживание' ?>
                </button>
            </form>
        </div>
        <?php if ($isMaintenanceOn && $maintenanceInfo): ?>
            <p style="color: var(--danger); font-size: 0.75rem; margin-top: 8px;">
                Включён с <?= $maintenanceInfo['since'] ?? '?' ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Массовое уведомление -->
    <div class="card">
        <h3 style="margin-bottom: 12px;">📢 Массовое уведомление</h3>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="mass_notify">
            <div class="form-group">
                <label>Заголовок</label>
                <input type="text" name="notify_title" required placeholder="📢 Важное обновление!" maxlength="200">
            </div>
            <div class="form-group">
                <label>Сообщение</label>
                <textarea name="notify_message" rows="3" required placeholder="Текст уведомления..." maxlength="1000"></textarea>
            </div>
            <div class="form-group">
                <label>Ссылка (необязательно)</label>
                <input type="url" name="notify_link" placeholder="https://craftradar.ru/page.php?slug=...">
            </div>
            <button type="submit" class="btn btn-sm btn-primary" data-confirm="Отправить уведомление ВСЕМ пользователям?">📨 Отправить всем</button>
        </form>
    </div>
</div>

<!-- Таблицы БД -->
<div class="card" style="margin-bottom: 20px;">
    <h3 style="margin-bottom: 12px;">🗄 Таблицы базы данных</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Таблица</th><th>Строк</th><th>Размер</th><th>Движок</th><th>Кодировка</th></tr></thead>
            <tbody>
                <?php foreach ($tables as $t): ?>
                <tr>
                    <td><strong><?= e($t['Name']) ?></strong></td>
                    <td><?= number_format($t['Rows'] ?? 0) ?></td>
                    <td><?= round((($t['Data_length'] ?? 0) + ($t['Index_length'] ?? 0)) / 1024, 1) ?> KB</td>
                    <td style="color: var(--text-muted);"><?= e($t['Engine'] ?? '?') ?></td>
                    <td style="color: <?= ($t['Collation'] ?? '') === 'utf8mb4_unicode_ci' ? 'var(--success)' : 'var(--danger)' ?>;">
                        <?= e($t['Collation'] ?? '?') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- SQL-консоль -->
<div class="card" style="margin-bottom: 20px;">
    <h3 style="margin-bottom: 12px;">💻 SQL-консоль (только SELECT)</h3>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="run_sql">
        <div class="form-group">
            <textarea name="sql_query" rows="3" style="font-family: monospace; font-size: 0.85rem;" placeholder="SELECT * FROM users LIMIT 10"><?= e($sqlQuery) ?></textarea>
        </div>
        <button type="submit" class="btn btn-sm btn-primary">▶ Выполнить</button>
    </form>

    <?php if ($sqlResult !== null): ?>
    <div style="margin-top: 12px;">
        <p style="color: var(--success); font-size: 0.8rem; margin-bottom: 8px;">✅ Результат: <?= count($sqlResult) ?> строк</p>
        <?php if (!empty($sqlResult)): ?>
        <div class="table-wrap">
            <table>
                <thead><tr><?php foreach (array_keys($sqlResult[0]) as $col): ?><th><?= e($col) ?></th><?php endforeach; ?></tr></thead>
                <tbody>
                    <?php foreach (array_slice($sqlResult, 0, 100) as $row): ?>
                    <tr><?php foreach ($row as $val): ?><td style="font-size:0.75rem;max-width:200px;overflow:hidden;text-overflow:ellipsis;"><?= e(truncate((string)($val ?? 'NULL'), 100)) ?></td><?php endforeach; ?></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($sqlResult) > 100): ?>
            <p style="color: var(--text-muted); font-size: 0.75rem;">Показано 100 из <?= count($sqlResult) ?> строк</p>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Лог крона -->
<?php if ($cronLog): ?>
<div class="card">
    <h3 style="margin-bottom: 12px;">📋 Лог крона (последние 20 строк)</h3>
    <pre style="background: var(--bg); padding: 12px; font-size: 0.75rem; color: var(--text-muted); overflow-x: auto; max-height: 300px; border: 2px solid var(--border);"><?= e($cronLog) ?></pre>
</div>
<?php endif; ?>

<!-- PHP Info -->
<div class="card" style="margin-top: 20px;">
    <h3 style="margin-bottom: 12px;">🔧 Окружение</h3>
    <div class="table-wrap">
        <table>
            <tr><td style="color:var(--text-muted);">PHP</td><td><?= $phpVersion ?></td></tr>
            <tr><td style="color:var(--text-muted);">Сервер</td><td><?= e($serverSoftware) ?></td></tr>
            <tr><td style="color:var(--text-muted);">ОС</td><td><?= e(php_uname('s') . ' ' . php_uname('r')) ?></td></tr>
            <tr><td style="color:var(--text-muted);">Часовой пояс</td><td><?= date_default_timezone_get() ?></td></tr>
            <tr><td style="color:var(--text-muted);">Время сервера</td><td><?= date('Y-m-d H:i:s') ?></td></tr>
            <tr><td style="color:var(--text-muted);">memory_limit</td><td><?= ini_get('memory_limit') ?></td></tr>
            <tr><td style="color:var(--text-muted);">max_execution_time</td><td><?= ini_get('max_execution_time') ?>s</td></tr>
            <tr><td style="color:var(--text-muted);">upload_max_filesize</td><td><?= ini_get('upload_max_filesize') ?></td></tr>
            <tr><td style="color:var(--text-muted);">post_max_size</td><td><?= ini_get('post_max_size') ?></td></tr>
            <tr><td style="color:var(--text-muted);">allow_url_fopen</td><td><?= ini_get('allow_url_fopen') ? '✅' : '❌' ?></td></tr>
            <tr><td style="color:var(--text-muted);">cURL</td><td><?= function_exists('curl_version') ? '✅ ' . curl_version()['version'] : '❌' ?></td></tr>
            <tr><td style="color:var(--text-muted);">SITE_URL</td><td><?= SITE_URL ?></td></tr>
            <tr><td style="color:var(--text-muted);">ROOT_PATH</td><td style="font-size:0.75rem;"><?= ROOT_PATH ?></td></tr>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
