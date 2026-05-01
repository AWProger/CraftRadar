<?php
/**
 * CraftRadar — API: Добавить/убрать из избранного (AJAX)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/achievements.php';

header('Content-Type: application/json');

if (!isPost() || !isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Необходимо авторизоваться.']);
    exit;
}

if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
    echo json_encode(['success' => false, 'error' => 'Ошибка безопасности.']);
    exit;
}

$serverId = (int)post('server_id');
$action = post('action'); // add или remove

$userId = currentUserId();

if ($action === 'add') {
    $result = addFavorite($userId, $serverId);
    echo json_encode(['success' => true, 'is_favorite' => true]);
} elseif ($action === 'remove') {
    removeFavorite($userId, $serverId);
    echo json_encode(['success' => true, 'is_favorite' => false]);
} else {
    // Toggle
    if (isFavorite($userId, $serverId)) {
        removeFavorite($userId, $serverId);
        echo json_encode(['success' => true, 'is_favorite' => false]);
    } else {
        addFavorite($userId, $serverId);
        echo json_encode(['success' => true, 'is_favorite' => true]);
    }
}
