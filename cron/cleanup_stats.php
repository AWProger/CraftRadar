<?php
/**
 * CraftRadar — Cron: Очистка старых записей
 * 
 * Расписание: ежедневно в 03:00
 * Хостинг:    wget -qO- "https://yourdomain.com/cron/cleanup_stats.php?key=craftradar_cron_2026_secret" >/dev/null 2>&1
 * CLI:        php /path/to/CraftRadar/cron/cleanup_stats.php
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
    $logFile = $logDir . 'cron_cleanup_' . date('Y-m-d') . '.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
}

$db = getDB();

// 1. Очистка старой статистики пингов
$stmt = $db->prepare('DELETE FROM server_stats WHERE recorded_at < ?');
$stmt->execute([dateAgo(30, 'day')]);
$deleted = $stmt->rowCount();
cronLog("Очистка server_stats: удалено {$deleted} записей старше 30 дней");

// 2. Очистка файлов блокировки логинов
$loginDir = ROOT_PATH . 'storage/login_attempts/';
if (is_dir($loginDir)) {
    $files = glob($loginDir . '*.json');
    $cleaned = 0;
    foreach ($files as $file) {
        if (filemtime($file) < time() - 86400) {
            unlink($file);
            $cleaned++;
        }
    }
    cronLog("Очистка login_attempts: удалено {$cleaned} файлов");
}

// 3. Очистка старых cron-логов (старше 30 дней)
$logDir = ROOT_PATH . 'storage/logs/';
if (is_dir($logDir)) {
    $logFiles = glob($logDir . 'cron_*.log');
    $cleanedLogs = 0;
    foreach ($logFiles as $file) {
        if (filemtime($file) < time() - 30 * 86400) {
            unlink($file);
            $cleanedLogs++;
        }
    }
    if ($cleanedLogs > 0) {
        cronLog("Очистка cron-логов: удалено {$cleanedLogs} файлов");
    }
}

cronLog("Очистка завершена");
