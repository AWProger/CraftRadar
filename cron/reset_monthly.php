<?php
/**
 * CraftRadar — Cron: Сброс месячных голосов
 * Запуск: 0 0 1 * *  php /path/to/CraftRadar/cron/reset_monthly.php
 * 
 * Выполняется 1-го числа каждого месяца в 00:00.
 * Обнуляет votes_month у всех серверов.
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
    $logFile = $logDir . 'cron_monthly_' . date('Y-m') . '.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
}

$db = getDB();

$stmt = $db->query('UPDATE servers SET votes_month = 0');
$affected = $stmt->rowCount();

cronLog("Месячные голоса сброшены. Затронуто серверов: {$affected}");
