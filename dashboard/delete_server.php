<?php
/**
 * CraftRadar — Удаление сервера
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();

if (!isPost()) {
    redirect(SITE_URL . '/dashboard/');
}

if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
    setFlash('error', 'Ошибка безопасности.');
    redirect(SITE_URL . '/dashboard/');
}

$db = getDB();
$userId = currentUserId();
$id = (int)post('id');

// Проверяем, что сервер принадлежит пользователю
$stmt = $db->prepare('SELECT id, icon, banner FROM servers WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $userId]);
$server = $stmt->fetch();

if (!$server) {
    setFlash('error', 'Сервер не найден.');
    redirect(SITE_URL . '/dashboard/');
}

// Удаляем файлы
if ($server['icon'] && file_exists(ROOT_PATH . $server['icon'])) {
    unlink(ROOT_PATH . $server['icon']);
}
if ($server['banner'] && file_exists(ROOT_PATH . $server['banner'])) {
    unlink(ROOT_PATH . $server['banner']);
}

// Удаляем сервер (каскадно удалятся votes, server_stats, reviews)
$stmt = $db->prepare('DELETE FROM servers WHERE id = ?');
$stmt->execute([$id]);

setFlash('success', 'Сервер удалён.');
redirect(SITE_URL . '/dashboard/');
