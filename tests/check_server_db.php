<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
$db = getDB();
$s = $db->query('SELECT is_online, players_online, players_max, motd, icon FROM servers WHERE id = 1')->fetch();
print_r($s);
