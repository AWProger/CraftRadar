<?php
/**
 * CraftRadar — API: Топ серверов для бокового виджета
 * Возвращает JSON с топ-N серверов по голосам за месяц.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/cache.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$limit = min(max(getInt('limit', SIDEBAR_TOP_COUNT), 1), 50);
$sort = get('sort', 'votes');

$sortMap = [
    'votes'   => 'votes_month DESC, votes_total DESC',
    'online'  => 'players_online DESC',
    'rating'  => 'rating DESC',
    'new'     => 'created_at DESC',
];
$orderBy = $sortMap[$sort] ?? $sortMap['votes'];

$cacheKey = "top_servers_{$limit}_{$sort}";
$data = cacheRemember($cacheKey, SIDEBAR_ROTATE_SECONDS, function() use ($limit, $orderBy) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT id, name, ip, port, icon, motd, is_online, players_online, players_max,
               votes_month, rating, is_verified, is_promoted,
               (highlighted_until > NOW()) as is_highlighted
        FROM servers 
        WHERE status IN ('active', 'pending')
        ORDER BY is_promoted DESC, (highlighted_until > NOW()) DESC, {$orderBy}
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
});

$result = array_map(function($s) {
    return [
        'id'             => (int)$s['id'],
        'name'           => $s['name'],
        'ip'             => $s['ip'] . ':' . $s['port'],
        'icon'           => $s['icon'] ? SITE_URL . '/' . $s['icon'] : null,
        'motd'           => $s['motd'] ?: '',
        'is_online'      => (bool)$s['is_online'],
        'players_online' => (int)$s['players_online'],
        'players_max'    => (int)$s['players_max'],
        'votes_month'    => (int)$s['votes_month'],
        'rating'         => (float)$s['rating'],
        'is_verified'    => (bool)$s['is_verified'],
        'is_promoted'    => (bool)$s['is_promoted'],
        'is_highlighted' => (bool)$s['is_highlighted'],
        'url'            => SITE_URL . '/server.php?id=' . $s['id'],
    ];
}, $data);

echo json_encode(['servers' => $result, 'total' => count($result)]);
