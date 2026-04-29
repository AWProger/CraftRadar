<?php
/**
 * CraftRadar — JSON API: Статус сервера (для виджетов)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$id = getInt('id');

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing server ID']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("
    SELECT id, name, ip, port, is_online, players_online, players_max, 
           motd, version, votes_month, votes_total, rating, reviews_count, last_ping
    FROM servers 
    WHERE id = ? AND status = 'active'
");
$stmt->execute([$id]);
$server = $stmt->fetch();

if (!$server) {
    http_response_code(404);
    echo json_encode(['error' => 'Server not found']);
    exit;
}

echo json_encode([
    'id' => (int)$server['id'],
    'name' => $server['name'],
    'ip' => $server['ip'],
    'port' => (int)$server['port'],
    'is_online' => (bool)$server['is_online'],
    'players_online' => (int)$server['players_online'],
    'players_max' => (int)$server['players_max'],
    'motd' => $server['motd'],
    'version' => $server['version'],
    'votes_month' => (int)$server['votes_month'],
    'votes_total' => (int)$server['votes_total'],
    'rating' => (float)$server['rating'],
    'reviews_count' => (int)$server['reviews_count'],
    'last_ping' => $server['last_ping'],
]);
