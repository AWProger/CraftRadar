<?php
/**
 * CraftRadar — Добавление отзыва (AJAX)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

header('Content-Type: application/json');

if (!isPost()) {
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается.']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Необходимо авторизоваться.']);
    exit;
}

if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
    echo json_encode(['success' => false, 'error' => 'Ошибка безопасности.']);
    exit;
}

$db = getDB();
$userId = currentUserId();
$serverId = (int)post('server_id');
$rating = (int)post('rating');
$text = post('text');

// Валидация рейтинга
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Оценка должна быть от 1 до 5.']);
    exit;
}

// Валидация текста
if (mb_strlen($text) < REVIEW_MIN_LENGTH) {
    echo json_encode(['success' => false, 'error' => 'Отзыв должен быть не менее ' . REVIEW_MIN_LENGTH . ' символов.']);
    exit;
}

if (mb_strlen($text) > REVIEW_MAX_LENGTH) {
    echo json_encode(['success' => false, 'error' => 'Отзыв не должен превышать ' . REVIEW_MAX_LENGTH . ' символов.']);
    exit;
}

// Проверка существования сервера
$stmt = $db->prepare("SELECT id, user_id FROM servers WHERE id = ? AND status IN ('active', 'pending')");
$stmt->execute([$serverId]);
$server = $stmt->fetch();

if (!$server) {
    echo json_encode(['success' => false, 'error' => 'Сервер не найден.']);
    exit;
}

// Владелец не может оставить отзыв на свой сервер
if ($server['user_id'] == $userId) {
    echo json_encode(['success' => false, 'error' => 'Нельзя оставить отзыв на свой сервер.']);
    exit;
}

// Проверка: один отзыв на сервер от пользователя
$stmt = $db->prepare('SELECT id FROM reviews WHERE server_id = ? AND user_id = ?');
$stmt->execute([$serverId, $userId]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Вы уже оставили отзыв на этот сервер.']);
    exit;
}

// Добавляем отзыв
$stmt = $db->prepare('INSERT INTO reviews (server_id, user_id, rating, text, status, created_at) VALUES (?, ?, ?, ?, ?, ?)');
$stmt->execute([$serverId, $userId, $rating, $text, 'active', now()]);

// Пересчитываем средний рейтинг и количество отзывов
$stmt = $db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as cnt FROM reviews WHERE server_id = ? AND status = 'active'");
$stmt->execute([$serverId]);
$stats = $stmt->fetch();

$stmt = $db->prepare('UPDATE servers SET rating = ?, reviews_count = ? WHERE id = ?');
$stmt->execute([
    round($stats['avg_rating'], 2),
    $stats['cnt'],
    $serverId
]);

echo json_encode(['success' => true]);
