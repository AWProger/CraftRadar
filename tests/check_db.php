<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$db = getDB();

$users = $db->query('SELECT id, username, role FROM users')->fetchAll();
echo 'Users: ' . json_encode($users, JSON_UNESCAPED_UNICODE) . PHP_EOL;

$servers = $db->query('SELECT id, name, ip, port, status FROM servers')->fetchAll();
echo 'Servers: ' . json_encode($servers, JSON_UNESCAPED_UNICODE) . PHP_EOL;

$pages = $db->query('SELECT slug, title FROM pages')->fetchAll();
echo 'Pages: ' . json_encode($pages, JSON_UNESCAPED_UNICODE) . PHP_EOL;

$hash = $db->query("SELECT password_hash FROM users WHERE id = 1")->fetchColumn();
echo 'Password verify (admin123): ' . (password_verify('admin123', $hash) ? 'OK' : 'FAIL (' . $hash . ')') . PHP_EOL;

echo 'Driver: ' . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL;
