<?php
/**
 * CraftRadar — Cron: Сброс месячных голосов
 * 
 * Расписание: 1-е число каждого месяца в 00:00
 * Хостинг:    wget -qO- "https://yourdomain.com/cron/reset_monthly.php?key=craftradar_cron_2026_secret" >/dev/null 2>&1
 * CLI:        php /path/to/CraftRadar/cron/reset_monthly.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// Авторизация
if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['key']) || $_GET['key'] !== CRON_SECRET_KEY) {
        http_response_code(403);
        die('Access denied');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

// Логирование
function cronLog(string $message): void
{
    $logDir = ROOT_PATH . 'storage/logs/';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $logFile = $logDir . 'cron_monthly_' . date('Y-m') . '.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
}

$db = getDB();

$stmt = $db->query('UPDATE servers SET votes_month = 0');
$affected = $stmt->rowCount();

cronLog("Месячные голоса сброшены. Затронуто серверов: {$affected}");

// Уведомление админам
try {
    require_once __DIR__ . '/../includes/notifications.php';
    $admins = $db->query("SELECT id FROM users WHERE role = 'admin'")->fetchAll();
    foreach ($admins as $admin) {
        createNotification($admin['id'], 'system',
            '🔄 Месячные голоса сброшены',
            "Затронуто серверов: {$affected}. Новый месяц начался!",
            SITE_URL . '/admin/'
        );
    }
} catch (\Exception $e) {}
