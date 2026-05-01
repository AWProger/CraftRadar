<?php
/**
 * CraftRadar — Cron: Пинг всех активных серверов
 * 
 * Расписание: каждые 10 минут
 * Хостинг:    wget -qO- "https://yourdomain.com/cron/ping_servers.php?key=craftradar_cron_2026_secret" >/dev/null 2>&1
 * CLI:        php /path/to/CraftRadar/cron/ping_servers.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/minecraft_ping.php';
require_once __DIR__ . '/../includes/notifications.php';

// Авторизация: CLI пропускаем, HTTP — проверяем ключ
if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['key']) || $_GET['key'] !== CRON_SECRET_KEY) {
        http_response_code(403);
        die('Access denied');
    }
    // Отключаем вывод для HTTP — отдаём 200 OK сразу
    header('Content-Type: text/plain; charset=utf-8');
}

// Защита от двойного запуска
$lockFile = ROOT_PATH . 'storage/cron_ping.lock';
$lockDir = dirname($lockFile);
if (!is_dir($lockDir)) mkdir($lockDir, 0755, true);

if (file_exists($lockFile)) {
    $lockTime = (int)file_get_contents($lockFile);
    if (time() - $lockTime < 600) {
        cronLog("SKIP: предыдущий запуск ещё не завершён (lock: " . date('H:i:s', $lockTime) . ")");
        exit(0);
    }
}
file_put_contents($lockFile, time());
register_shutdown_function(function() use ($lockFile) { @unlink($lockFile); });

// Логирование
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
$servers = $db->query("SELECT id, ip, port, consecutive_fails, is_online, user_id, name FROM servers WHERE status IN ('active', 'pending')")->fetchAll();

$total = count($servers);
$online = 0;
$offline = 0;

cronLog("Пинг серверов: {$total} шт.");

// Диагностика (если передан debug)
if (isset($_GET['debug'])) {
    echo "PHP version: " . PHP_VERSION . "\n";
    echo "fsockopen: " . (function_exists('fsockopen') ? 'YES' : 'NO') . "\n";
    echo "allow_url_fopen: " . ini_get('allow_url_fopen') . "\n";
    
    foreach ($servers as $s) {
        echo "\nTesting {$s['ip']}:{$s['port']}...\n";
        $errno = 0; $errstr = '';
        $sock = @fsockopen($s['ip'], $s['port'], $errno, $errstr, 5);
        if ($sock) {
            echo "  TCP connect: OK\n";
            fclose($sock);
        } else {
            echo "  TCP connect: FAIL - {$errstr} ({$errno})\n";
        }
        
        // DNS resolve
        $ip = gethostbyname($s['ip']);
        echo "  DNS: {$s['ip']} -> {$ip}\n";
    }
    echo "\n";
}

foreach ($servers as $server) {
    $result = pingMinecraftServer($server['ip'], $server['port'], PING_TIMEOUT);

    if ($result) {
        $online++;

        $stmt = $db->prepare('
            UPDATE servers SET 
                is_online = 1, players_online = ?, players_max = ?,
                motd = ?, last_ping = ?, consecutive_fails = 0
            WHERE id = ?
        ');
        $stmt->execute([
            $result['players'], $result['max_players'],
            mb_substr($result['motd'], 0, 255), now(), $server['id']
        ]);

        // Уведомление — сервер вернулся онлайн (если был оффлайн)
        if ($server['is_online'] == 0 && $server['user_id']) {
            createNotification(
                $server['user_id'], 'server_approved',
                '🟢 Сервер «' . $server['name'] . '» снова онлайн!',
                'Игроков: ' . $result['players'] . '/' . $result['max_players'],
                SITE_URL . '/server.php?id=' . $server['id']
            );
        }

        // Иконка
        if (!empty($result['favicon'])) {
            $iconPath = saveServerIconCron($result['favicon'], $server['id']);
            if ($iconPath) {
                $db->prepare('UPDATE servers SET icon = ? WHERE id = ?')->execute([$iconPath, $server['id']]);
            }
        }

        // Статистика
        $db->prepare('INSERT INTO server_stats (server_id, players_online, is_online, ping_ms, recorded_at) VALUES (?, ?, 1, ?, ?)')
            ->execute([$server['id'], $result['players'], $result['ping_ms'], now()]);

        cronLog("  ✓ #{$server['id']} {$server['ip']}:{$server['port']} — {$result['players']}/{$result['max_players']} ({$result['ping_ms']}ms)");

    } else {
        $offline++;
        $fails = $server['consecutive_fails'] + 1;

        $updateFields = 'consecutive_fails = ?';
        $updateParams = [$fails];
        if ($fails >= MAX_CONSECUTIVE_FAILS) {
            $updateFields .= ', is_online = 0, players_online = 0';
            // Уведомление владельцу — сервер ушёл в оффлайн (только при первом переходе)
            if ($server['is_online'] == 1 && $server['user_id']) {
                createNotification(
                    $server['user_id'], 'server_offline',
                    '🔴 Сервер «' . $server['name'] . '» оффлайн',
                    'Сервер не отвечает после ' . MAX_CONSECUTIVE_FAILS . ' попыток пинга.',
                    SITE_URL . '/dashboard/stats.php?id=' . $server['id']
                );

                // Уведомляем пользователей с этим сервером в избранном
                try {
                    $favUsers = $db->prepare('SELECT user_id FROM favorites WHERE server_id = ?');
                    $favUsers->execute([$server['id']]);
                    foreach ($favUsers->fetchAll() as $fu) {
                        if ($fu['user_id'] != $server['user_id']) {
                            createNotification($fu['user_id'], 'server_offline',
                                '🔴 «' . $server['name'] . '» ушёл в оффлайн',
                                'Сервер из вашего избранного не отвечает.',
                                SITE_URL . '/server.php?id=' . $server['id']);
                        }
                    }
                } catch (\Exception $e) {}
            }
        }

        $db->prepare("UPDATE servers SET {$updateFields} WHERE id = ?")->execute(array_merge($updateParams, [$server['id']]));
        $db->prepare('INSERT INTO server_stats (server_id, players_online, is_online, ping_ms, recorded_at) VALUES (?, 0, 0, NULL, ?)')
            ->execute([$server['id'], now()]);

        cronLog("  ✗ #{$server['id']} {$server['ip']}:{$server['port']} — OFFLINE (fails: {$fails})");
    }
}

cronLog("Готово. Онлайн: {$online}, Оффлайн: {$offline}");

/**
 * Сохранение иконки сервера
 */
function saveServerIconCron(string $favicon, int $serverId): ?string
{
    if (!preg_match('/^data:image\/png;base64,(.+)$/', $favicon, $matches)) return null;
    $imageData = base64_decode($matches[1]);
    if (!$imageData) return null;

    $dir = ROOT_PATH . 'assets/img/icons/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = 'server_' . $serverId . '.png';
    if (file_put_contents($dir . $filename, $imageData)) {
        return 'assets/img/icons/' . $filename;
    }
    return null;
}
