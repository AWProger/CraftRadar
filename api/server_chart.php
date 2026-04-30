<?php
/**
 * CraftRadar — API: Данные графика онлайна сервера
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$id = getInt('id');
$period = get('period', '24h');

if (!$id) {
    echo json_encode(['error' => 'Missing server ID']);
    exit;
}

$db = getDB();

// Проверяем сервер
$stmt = $db->prepare("SELECT id FROM servers WHERE id = ? AND status = 'active'");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Server not found']);
    exit;
}

switch ($period) {
    case '7d':
        $stmt = $db->prepare('
            SELECT DATE_FORMAT(recorded_at, "%Y-%m-%d %H:00") as time,
                   ROUND(AVG(players_online)) as players
            FROM server_stats 
            WHERE server_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY time ORDER BY time
        ');
        break;
    case '30d':
        $stmt = $db->prepare('
            SELECT DATE_FORMAT(recorded_at, "%Y-%m-%d") as time,
                   ROUND(AVG(players_online)) as players
            FROM server_stats 
            WHERE server_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY time ORDER BY time
        ');
        break;
    default: // 24h
        $stmt = $db->prepare('
            SELECT recorded_at as time, players_online as players
            FROM server_stats 
            WHERE server_id = ? AND recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY recorded_at
        ');
        break;
}

$stmt->execute([$id]);
$data = $stmt->fetchAll();

echo json_encode([
    'period' => $period,
    'data' => array_map(function($d) {
        return ['time' => $d['time'], 'players' => (int)$d['players']];
    }, $data)
]);
