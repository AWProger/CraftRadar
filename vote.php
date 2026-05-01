<?php
/**
 * CraftRadar — Обработка голосования (AJAX)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/points.php';

header('Content-Type: application/json');

// Только POST
if (!isPost()) {
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается.']);
    exit;
}

// Проверка авторизации
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Необходимо авторизоваться.']);
    exit;
}

// Проверка CSRF
if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
    echo json_encode(['success' => false, 'error' => 'Ошибка безопасности.']);
    exit;
}

$db = getDB();
$userId = currentUserId();
$serverId = (int)post('server_id');

// Проверка бана
$user = currentUser();
if ($user && $user['is_banned']) {
    echo json_encode(['success' => false, 'error' => 'Ваш аккаунт заблокирован.']);
    exit;
}

// Проверка существования сервера
$stmt = $db->prepare("SELECT id, votes_total, votes_month FROM servers WHERE id = ? AND status IN ('active', 'pending')");
$stmt->execute([$serverId]);
$server = $stmt->fetch();

if (!$server) {
    echo json_encode(['success' => false, 'error' => 'Сервер не найден.']);
    exit;
}

// Проверка кулдауна (1 голос в 24 часа за каждый сервер)
$stmt = $db->prepare('
    SELECT voted_at FROM votes 
    WHERE server_id = ? AND user_id = ? 
    ORDER BY voted_at DESC LIMIT 1
');
$stmt->execute([$serverId, $userId]);
$lastVote = $stmt->fetchColumn();

if ($lastVote && (time() - strtotime($lastVote)) < VOTE_COOLDOWN * 3600) {
    $nextVote = strtotime($lastVote) + VOTE_COOLDOWN * 3600;
    echo json_encode([
        'success' => false,
        'error' => 'Вы уже голосовали. Следующий голос: ' . date('H:i', $nextVote)
    ]);
    exit;
}

// Записываем голос
$stmt = $db->prepare('INSERT INTO votes (server_id, user_id, ip_address, voted_at) VALUES (?, ?, ?, ?)');
$stmt->execute([$serverId, $userId, getUserIP(), now()]);

// Обновляем счётчики сервера
$stmt = $db->prepare('UPDATE servers SET votes_total = votes_total + 1, votes_month = votes_month + 1 WHERE id = ?');
$stmt->execute([$serverId]);

// Начисляем баллы за голосование
rewardVotePoints($userId);

echo json_encode([
    'success' => true,
    'votes_total' => $server['votes_total'] + 1,
    'votes_month' => $server['votes_month'] + 1
]);
