<?php
/**
 * Пинг локального сервера и обновление БД
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/minecraft_ping.php';

// Пингуем по локальному IP
$host = '192.168.0.11';
$port = 25560;

echo "Пингую {$host}:{$port}...\n";
$result = pingMinecraftServer($host, $port, 10);

if ($result) {
    echo "ОНЛАЙН!\n";
    echo "Игроков: {$result['players']}/{$result['max_players']}\n";
    echo "Версия: {$result['version']}\n";
    echo "MOTD: {$result['motd']}\n";
    echo "Пинг: {$result['ping_ms']}ms\n";
    echo "Иконка: " . (!empty($result['favicon']) ? 'есть' : 'нет') . "\n";

    // Обновляем БД
    $db = getDB();
    $now = date('Y-m-d H:i:s');

    $stmt = $db->prepare('UPDATE servers SET 
        is_online = 1, players_online = ?, players_max = ?, 
        motd = ?, last_ping = ?, consecutive_fails = 0
        WHERE id = 1');
    $stmt->execute([$result['players'], $result['max_players'], mb_substr($result['motd'], 0, 255), $now]);

    // Сохраняем иконку
    if (!empty($result['favicon']) && preg_match('/^data:image\/png;base64,(.+)$/', $result['favicon'], $matches)) {
        $dir = __DIR__ . '/../assets/img/icons/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents($dir . 'server_1.png', base64_decode($matches[1]));
        $db->prepare('UPDATE servers SET icon = ? WHERE id = 1')->execute(['assets/img/icons/server_1.png']);
        echo "Иконка сохранена\n";
    }

    // Записываем статистику
    $db->prepare('INSERT INTO server_stats (server_id, players_online, is_online, ping_ms, recorded_at) VALUES (1, ?, 1, ?, ?)')
        ->execute([$result['players'], $result['ping_ms'], $now]);

    echo "\nБД обновлена! Обнови страницу в браузере.\n";
} else {
    echo "Сервер не отвечает.\n";
}
