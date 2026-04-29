<?php
/**
 * CraftRadar — Cron: Очистка старых записей
 * Запуск: 0 3 * * *  php /path/to/CraftRadar/cron/cleanup_stats.php
 * 
 * Выполняется ежедневно в 03:00.
 * Удаляет записи server_stats старше 30 дней.
 * Чистит старые файлы блокировки логинов.
 * Чистит старые cron-логи (старше 30 дней).
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

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
$stmt = $db->query('DELETE FROM server_stats WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
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
