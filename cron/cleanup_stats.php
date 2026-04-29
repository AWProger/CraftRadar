<?php
/**
 * CraftRadar — Cron: Очистка старых записей статистики
 * Запуск: 0 3 * * * php /path/to/CraftRadar/cron/cleanup_stats.php
 * Удаляет записи server_stats старше 30 дней.
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();

$stmt = $db->query('DELETE FROM server_stats WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 30 DAY)');
$deleted = $stmt->rowCount();

echo "[" . date('Y-m-d H:i:s') . "] Очистка статистики: удалено {$deleted} записей старше 30 дней.\n";

// Также чистим старые файлы блокировки логинов
$loginDir = ROOT_PATH . 'storage/login_attempts/';
if (is_dir($loginDir)) {
    $files = glob($loginDir . '*.json');
    $cleaned = 0;
    foreach ($files as $file) {
        if (filemtime($file) < time() - 86400) { // старше 1 дня
            unlink($file);
            $cleaned++;
        }
    }
    echo "[" . date('Y-m-d H:i:s') . "] Очистка login_attempts: удалено {$cleaned} файлов.\n";
}
