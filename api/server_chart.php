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

$stmt = $db->prepare("SELECT id FROM servers WHERE id = ? AND status = 'active'");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    echo json_encode(['error' => 'Server not found']);
    exit;
}

$dateCol = isSQLite() ? "STRFTIME('%Y-%m-%d %H:00', recorded_at)" : "DATE_FORMAT(recorded_at, '%Y-%m-%d %H:00')";
$dateDayCol = isSQLite() ? "STRFTIME('%Y-%m-%d', recorded_at)" : "DATE_FORMAT(recorded_at, '%Y-%m-%d')";

switch ($period) {
    case '7d':
        $stmt = $db->prepare("
            SELECT {$dateCol} as time, ROUND(AVG(players_online)) as players
            FROM server_stats 
            WHERE server_id = ? AND recorded_at >= ?
            GROUP BY time ORDER BY time
        ");
        $stmt->execute([$id, dateAgo(7, 'day')]);
        break;
    case '30d':
        $stmt = $db->prepare("
            SELECT {$dateDayCol} as time, ROUND(AVG(players_online)) as players
            FROM server_stats 
            WHERE server_id = ? AND recorded_at >= ?
            GROUP BY time ORDER BY time
        ");
        $stmt->execute([$id, dateAgo(30, 'day')]);
        break;
    default:
        $stmt = $db->prepare("
            SELECT recorded_at as time, players_online as players
            FROM server_stats 
            WHERE server_id = ? AND recorded_at >= ?
            ORDER BY recorded_at
        ");
        $stmt->execute([$id, dateAgo(24, 'hour')]);
        break;
}

$data = $stmt->fetchAll();

echo json_encode([
    'period' => $period,
    'data' => array_map(function($d) {
        return ['time' => $d['time'], 'players' => (int)$d['players']];
    }, $data)
]);
