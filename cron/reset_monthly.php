<?php
/**
 * CraftRadar — Cron: Сброс месячных голосов
 * Запуск: 0 0 1 * * php /path/to/CraftRadar/cron/reset_monthly.php
 */

if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$db = getDB();

$stmt = $db->query('UPDATE servers SET votes_month = 0');
$affected = $stmt->rowCount();

echo "[" . date('Y-m-d H:i:s') . "] Месячные голоса сброшены. Затронуто серверов: {$affected}\n";
