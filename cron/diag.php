<?php
/**
 * Диагностика сетевых подключений хостинга
 */

require_once __DIR__ . '/../includes/config.php';

// Проверка ключа
if (!isset($_GET['key']) || $_GET['key'] !== CRON_SECRET_KEY) {
    http_response_code(403);
    die('Access denied');
}

header('Content-Type: text/plain; charset=utf-8');

echo "=== CraftRadar Network Diagnostics ===\n\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "OS: " . PHP_OS . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n";
echo "fsockopen: " . (function_exists('fsockopen') ? 'YES' : 'DISABLED') . "\n";
echo "stream_socket_client: " . (function_exists('stream_socket_client') ? 'YES' : 'DISABLED') . "\n";
echo "allow_url_fopen: " . ini_get('allow_url_fopen') . "\n";
echo "default_socket_timeout: " . ini_get('default_socket_timeout') . "\n\n";

// Disabled functions
$disabled = ini_get('disable_functions');
echo "Disabled functions: " . ($disabled ?: 'none') . "\n\n";

// Test DNS
$host = 'mc.mcawp.ru';
$port = 25560;

echo "=== DNS Test ===\n";
$ip = gethostbyname($host);
echo "{$host} -> {$ip}\n";
if ($ip === $host) {
    echo "WARNING: DNS resolution failed!\n";
}
echo "\n";

// Test TCP connection
echo "=== TCP Connection Test: {$host}:{$port} ===\n";
$start = microtime(true);
$errno = 0;
$errstr = '';
$sock = @fsockopen($host, $port, $errno, $errstr, 5);
$elapsed = round((microtime(true) - $start) * 1000);

if ($sock) {
    echo "RESULT: SUCCESS ({$elapsed}ms)\n";
    fclose($sock);
} else {
    echo "RESULT: FAILED ({$elapsed}ms)\n";
    echo "Error: {$errstr} (code: {$errno})\n";
}
echo "\n";

// Test TCP to IP directly
echo "=== TCP Connection Test: {$ip}:{$port} (direct IP) ===\n";
$start = microtime(true);
$sock2 = @fsockopen($ip, $port, $errno2, $errstr2, 5);
$elapsed2 = round((microtime(true) - $start) * 1000);

if ($sock2) {
    echo "RESULT: SUCCESS ({$elapsed2}ms)\n";
    fclose($sock2);
} else {
    echo "RESULT: FAILED ({$elapsed2}ms)\n";
    echo "Error: {$errstr2} (code: {$errno2})\n";
}
echo "\n";

// Test standard ports
echo "=== TCP Test: google.com:80 (control) ===\n";
$sock3 = @fsockopen('google.com', 80, $e3, $es3, 5);
echo $sock3 ? "SUCCESS\n" : "FAILED: {$es3}\n";
if ($sock3) fclose($sock3);

echo "\n=== TCP Test: google.com:443 (control) ===\n";
$sock4 = @fsockopen('google.com', 443, $e4, $es4, 5);
echo $sock4 ? "SUCCESS\n" : "FAILED: {$es4}\n";
if ($sock4) fclose($sock4);

echo "\nDone.\n";
