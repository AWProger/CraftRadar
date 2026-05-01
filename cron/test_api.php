<?php
/**
 * Тест пинга через API mcsrvstat.us
 */
require_once __DIR__ . '/../includes/config.php';

if (!isset($_GET['key']) || $_GET['key'] !== CRON_SECRET_KEY) {
    http_response_code(403);
    die('Access denied');
}

header('Content-Type: text/plain; charset=utf-8');

$host = 'mc.mcawp.ru';
$port = 25560;
$url = "https://api.mcsrvstat.us/2/{$host}:{$port}";

echo "Testing API: {$url}\n\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT      => 'CraftRadar/1.0',
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Code: {$httpCode}\n";
echo "Curl Error: " . ($curlError ?: 'none') . "\n\n";

if ($response) {
    $data = json_decode($response, true);
    echo "Online: " . ($data['online'] ? 'YES' : 'NO') . "\n";
    echo "Players: " . ($data['players']['online'] ?? 0) . "/" . ($data['players']['max'] ?? 0) . "\n";
    echo "Version: " . ($data['version'] ?? 'unknown') . "\n";
    echo "MOTD: " . implode(' ', $data['motd']['clean'] ?? []) . "\n";
    echo "Icon: " . (!empty($data['icon']) ? 'YES (' . strlen($data['icon']) . ' bytes)' : 'NO') . "\n";
} else {
    echo "No response!\n";
}

echo "\n--- Now testing pingMinecraftServer() ---\n\n";

require_once __DIR__ . '/../includes/minecraft_ping.php';

$result = pingMinecraftServer($host, $port, 5);
if ($result) {
    echo "pingMinecraftServer: ONLINE\n";
    echo "Players: {$result['players']}/{$result['max_players']}\n";
    echo "MOTD: {$result['motd']}\n";
    echo "Ping: {$result['ping_ms']}ms\n";
} else {
    echo "pingMinecraftServer: OFFLINE (returned false)\n";
}
