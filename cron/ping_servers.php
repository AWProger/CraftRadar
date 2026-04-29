<?php
/**
 * CraftRadar — Cron: Пинг всех активных серверов
 * Запуск: */10 * * * * php /path/to/CraftRadar/cron/ping_servers.php
 */

// Запуск только из CLI
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('CLI only');
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/minecraft_ping.php';

// Защита от двойного запуска
$lockFile = ROOT_PATH . 'storage/cron_ping.lock';
$lockDir = dirname($lockFile);
if (!is_dir($lockDir)) mkdir($lockDir, 0755, true);

if (file_exists($lockFile)) {
    $lockTime = (int)file_get_contents($lockFile);
    if (time() - $lockTime < 600) { // 10 минут
        cronLog("SKIP: предыдущий запуск ещё не завершён (lock: " . date('H:i:s', $lockTime) . ")");
        exit(0);
    }
}
file_put_contents($lockFile, time());
register_shutdown_function(function() use ($lockFile) { @unlink($lockFile); });

// Логирование в файл
function cronLog(string $message): void
{
    $logDir = ROOT_PATH . 'storage/logs/';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $logFile = $logDir . 'cron_ping_' . date('Y-m-d') . '.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
}

$db = getDB();

// Выбираем все активные серверы
$servers = $db->query("SELECT id, ip, port, consecutive_fails FROM servers WHERE status = 'active'")->fetchAll();

$total = count($servers);
$online = 0;
$offline = 0;

cronLog("Пинг серверов: {$total} шт.");

foreach ($servers as $server) {
    $result = pingMinecraftServer($server['ip'], $server['port'], PING_TIMEOUT);

    if ($result) {
        $online++;

        // Обновляем данные сервера
        $stmt = $db->prepare('
            UPDATE servers SET 
                is_online = 1,
                players_online = ?,
                players_max = ?,
                motd = ?,
                last_ping = ?,
                consecutive_fails = 0
            WHERE id = ?
        ');
        $stmt->execute([
            $result['players'],
            $result['max_players'],
            mb_substr($result['motd'], 0, 255),
            now(),
            $server['id']
        ]);

        // Сохраняем иконку если обновилась
        if (!empty($result['favicon'])) {
            $iconPath = saveServerIconCron($result['favicon'], $server['id']);
            if ($iconPath) {
                $db->prepare('UPDATE servers SET icon = ? WHERE id = ?')->execute([$iconPath, $server['id']]);
            }
        }

        // Записываем статистику
        $stmt = $db->prepare('
            INSERT INTO server_stats (server_id, players_online, is_online, ping_ms, recorded_at)
            VALUES (?, ?, 1, ?, ?)
        ');
        $stmt->execute([$server['id'], $result['players'], $result['ping_ms'], now()]);

        cronLog("  ✓ #{$server['id']} {$server['ip']}:{$server['port']} — {$result['players']}/{$result['max_players']} ({$result['ping_ms']}ms)");

    } else {
        $offline++;
        $fails = $server['consecutive_fails'] + 1;

        // Обновляем сервер
        $updateFields = 'consecutive_fails = ?';
        $updateParams = [$fails];

        if ($fails >= MAX_CONSECUTIVE_FAILS) {
            $updateFields .= ', is_online = 0, players_online = 0';
        }

        $stmt = $db->prepare("UPDATE servers SET {$updateFields} WHERE id = ?");
        $updateParams[] = $server['id'];
        $stmt->execute($updateParams);

        // Записываем статистику (оффлайн)
        $stmt = $db->prepare('
            INSERT INTO server_stats (server_id, players_online, is_online, ping_ms, recorded_at)
            VALUES (?, 0, 0, NULL, ?)
        ');
        $stmt->execute([$server['id'], now()]);

        cronLog("  ✗ #{$server['id']} {$server['ip']}:{$server['port']} — OFFLINE (fails: {$fails})");
    }
}

cronLog("Готово. Онлайн: {$online}, Оффлайн: {$offline}");

/**
 * Сохранение иконки сервера (для cron)
 */
function saveServerIconCron(string $favicon, int $serverId): ?string
{
    if (!preg_match('/^data:image\/png;base64,(.+)$/', $favicon, $matches)) {
        return null;
    }

    $imageData = base64_decode($matches[1]);
    if (!$imageData) return null;

    $dir = ROOT_PATH . 'assets/img/icons/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = 'server_' . $serverId . '.png';
    $path = $dir . $filename;

    if (file_put_contents($path, $imageData)) {
        return 'assets/img/icons/' . $filename;
    }

    return null;
}
