<?php
/**
 * CraftRadar — Система баллов
 * 
 * Баллы начисляются за голосование, тратятся на выделение сервера.
 */

/**
 * Получить баланс баллов пользователя
 */
function getUserPoints(int $userId): int
{
    $db = getDB();
    $stmt = $db->prepare('SELECT points FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Начислить/списать баллы
 */
function addPoints(int $userId, int $amount, string $type, string $description = '', ?int $serverId = null): bool
{
    $db = getDB();

    // Проверяем что хватает баллов при списании
    if ($amount < 0) {
        $current = getUserPoints($userId);
        if ($current + $amount < 0) {
            return false;
        }
    }

    // Обновляем баланс
    $db->prepare('UPDATE users SET points = points + ? WHERE id = ?')->execute([$amount, $userId]);

    // Записываем транзакцию
    $db->prepare('INSERT INTO point_transactions (user_id, amount, type, description, server_id, created_at) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$userId, $amount, $type, $description, $serverId, now()]);

    return true;
}

/**
 * Начислить баллы за голосование
 */
function rewardVotePoints(int $userId): void
{
    addPoints($userId, POINTS_PER_VOTE, 'vote_reward', 'Бонус за голосование');
}

/**
 * Выделить сервер за баллы
 */
function highlightServer(int $userId, int $serverId, string $duration): array
{
    $costs = HIGHLIGHT_COSTS;

    if (!isset($costs[$duration])) {
        return ['success' => false, 'error' => 'Неизвестная длительность.'];
    }

    $cost = $costs[$duration];
    $currentPoints = getUserPoints($userId);

    if ($currentPoints < $cost['points']) {
        return ['success' => false, 'error' => "Недостаточно баллов. Нужно {$cost['points']}, у вас {$currentPoints}."];
    }

    $db = getDB();

    // Проверяем сервер
    $stmt = $db->prepare("SELECT id, name, user_id, highlighted_until FROM servers WHERE id = ? AND status = 'active'");
    $stmt->execute([$serverId]);
    $server = $stmt->fetch();

    if (!$server) {
        return ['success' => false, 'error' => 'Сервер не найден.'];
    }

    if ($server['user_id'] != $userId) {
        return ['success' => false, 'error' => 'Это не ваш сервер.'];
    }

    // Списываем баллы
    $result = addPoints($userId, -$cost['points'], 'highlight_spend',
        "Выделение \"{$server['name']}\" на {$cost['hours']}ч", $serverId);

    if (!$result) {
        return ['success' => false, 'error' => 'Не удалось списать баллы.'];
    }

    // Активируем выделение (добавляем к текущему если уже выделен)
    $baseTime = ($server['highlighted_until'] && strtotime($server['highlighted_until']) > time())
        ? $server['highlighted_until']
        : now();

    $until = date('Y-m-d H:i:s', strtotime($baseTime . " +{$cost['hours']} hours"));

    $db->prepare('UPDATE servers SET highlighted_until = ? WHERE id = ?')->execute([$until, $serverId]);

    return [
        'success' => true,
        'until' => $until,
        'points_spent' => $cost['points'],
        'points_remaining' => getUserPoints($userId),
    ];
}

/**
 * Проверить, выделен ли сервер
 */
function isHighlighted(array $server): bool
{
    return !empty($server['highlighted_until']) && strtotime($server['highlighted_until']) > time();
}

/**
 * История транзакций баллов
 */
function getPointHistory(int $userId, int $limit = 30): array
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM point_transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
