<?php
/**
 * CraftRadar — AJAX-поиск серверов
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$q = get('q');
if (mb_strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$db = getDB();
$stmt = $db->prepare("
    SELECT id, name, ip, port, icon, is_online, players_online, players_max, votes_month
    FROM servers 
    WHERE status = 'active' AND (name LIKE ? OR ip LIKE ?)
    ORDER BY votes_month DESC
    LIMIT 10
");
$stmt->execute(["%{$q}%", "%{$q}%"]);
$results = $stmt->fetchAll();

$output = [];
foreach ($results as $s) {
    $output[] = [
        'id' => $s['id'],
        'name' => $s['name'],
        'ip' => $s['ip'] . ':' . $s['port'],
        'icon' => $s['icon'] ? SITE_URL . '/' . $s['icon'] : null,
        'is_online' => (bool)$s['is_online'],
        'players_online' => $s['players_online'],
        'players_max' => $s['players_max'],
        'votes_month' => $s['votes_month'],
        'url' => SITE_URL . '/server.php?id=' . $s['id'],
    ];
}

echo json_encode(['results' => $output]);
