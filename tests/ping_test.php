<?php
/**
 * Тестовый пинг сервера mc.mcawp.ru:25560
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/minecraft_ping.php';

$host = 'mc.mcawp.ru';
$port = 25560;

echo "Пингую {$host}:{$port}...\n";

$result = pingMinecraftServer($host, $port, 10);

if ($result) {
    echo "=== ОНЛАЙН ===\n";
    echo "Игроков:  {$result['players']}/{$result['max_players']}\n";
    echo "Версия:   {$result['version']}\n";
    echo "Протокол: {$result['protocol']}\n";
    echo "MOTD:     {$result['motd']}\n";
    echo "Пинг:     {$result['ping_ms']}ms\n";
    echo "Иконка:   " . (empty($result['favicon']) ? 'нет' : 'есть (' . strlen($result['favicon']) . ' байт)') . "\n";
    
    // Сохраним иконку если есть
    if (!empty($result['favicon'])) {
        if (preg_match('/^data:image\/png;base64,(.+)$/', $result['favicon'], $matches)) {
            $dir = __DIR__ . '/../assets/img/icons/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            file_put_contents($dir . 'test_mcawp.png', base64_decode($matches[1]));
            echo "Иконка сохранена: assets/img/icons/test_mcawp.png\n";
        }
    }
    
    echo "\n=== RAW DATA ===\n";
    print_r($result);
} else {
    echo "ОФФЛАЙН или не отвечает.\n";
}
