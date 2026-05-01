<?php
/**
 * CraftRadar — Подача жалобы (AJAX)
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

if (!checkRateLimit('report', 3)) {
    echo json_encode(['success' => false, 'error' => 'Слишком много жалоб. Подождите минуту.']);
    exit;
}

if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
    echo json_encode(['success' => false, 'error' => 'Ошибка безопасности.']);
    exit;
}

$db = getDB();
$userId = currentUserId();
$targetType = post('target_type');
$targetId = (int)post('target_id');
$reason = post('reason');
$description = post('description');

// Валидация типа
$allowedTypes = ['server', 'review', 'user'];
if (!in_array($targetType, $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Некорректный тип жалобы.']);
    exit;
}

// Валидация причины
$allowedReasons = ['outdated', 'fraud', 'cheating', 'rules', 'spam', 'insult', 'false_info', 'multiaccount'];
if (!in_array($reason, $allowedReasons)) {
    echo json_encode(['success' => false, 'error' => 'Выберите причину жалобы.']);
    exit;
}

if (empty($description) || mb_strlen($description) < 10) {
    echo json_encode(['success' => false, 'error' => 'Описание должно быть не менее 10 символов.']);
    exit;
}

// Проверка существования объекта
$exists = false;
switch ($targetType) {
    case 'server':
        $stmt = $db->prepare('SELECT id FROM servers WHERE id = ?');
        $stmt->execute([$targetId]);
        $exists = (bool)$stmt->fetch();
        break;
    case 'review':
        $stmt = $db->prepare('SELECT id FROM reviews WHERE id = ?');
        $stmt->execute([$targetId]);
        $exists = (bool)$stmt->fetch();
        break;
    case 'user':
        $stmt = $db->prepare('SELECT id FROM users WHERE id = ?');
        $stmt->execute([$targetId]);
        $exists = (bool)$stmt->fetch();
        break;
}

if (!$exists) {
    echo json_encode(['success' => false, 'error' => 'Объект жалобы не найден.']);
    exit;
}

// Проверка: нельзя жаловаться на себя
if ($targetType === 'user' && $targetId == $userId) {
    echo json_encode(['success' => false, 'error' => 'Нельзя пожаловаться на себя.']);
    exit;
}

// Проверка дубликата (не более 1 жалобы на один объект от одного пользователя)
$stmt = $db->prepare('SELECT id FROM reports WHERE reporter_id = ? AND target_type = ? AND target_id = ? AND status IN (?, ?)');
$stmt->execute([$userId, $targetType, $targetId, 'new', 'in_progress']);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Вы уже подали жалобу на этот объект.']);
    exit;
}

// Создаём жалобу
$stmt = $db->prepare('INSERT INTO reports (reporter_id, target_type, target_id, reason, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
$stmt->execute([$userId, $targetType, $targetId, $reason, $description, 'new', now()]);

echo json_encode(['success' => true]);
