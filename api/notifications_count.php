<?php
/**
 * CraftRadar — API: Количество непрочитанных уведомлений
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit;
}

try {
    require_once __DIR__ . '/../includes/notifications.php';
    echo json_encode(['count' => getUnreadCount(currentUserId())]);
} catch (\Exception $e) {
    echo json_encode(['count' => 0]);
}
