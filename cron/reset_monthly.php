<?php
/**
 * CraftRadar — Cron: Ежемесячные задачи
 * 
 * ВНИМАНИЕ: Голоса больше НЕ сбрасываются! Они копятся навсегда.
 * Этот крон теперь выполняет только вспомогательные задачи.
 * 
 * Расписание: 1-е число каждого месяца в 00:00
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['key']) || $_GET['key'] !== CRON_SECRET_KEY) {
        http_response_code(403);
        die('Access denied');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

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

// Голоса НЕ сбрасываются — они копятся навсегда
cronLog("Голоса не сбрасываются (новая логика). Месячный крон выполнен.");

// Начисление монет за голоса (каждые 100 голосов = +1 монета)
// Это уже обрабатывается в vote.php при каждом голосе
cronLog("Бонусные монеты начисляются автоматически при голосовании.");
