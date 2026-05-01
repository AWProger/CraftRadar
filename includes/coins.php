<?php
/**
 * CraftRadar — Система монет (платная валюта)
 * 
 * 💰 Монеты покупаются за реальные деньги через ЮMoney.
 * 1 монета = 1 рубль.
 * Тратятся на продвижение серверов (⭐ золотая рамка, топ каталога).
 */

/**
 * Получить баланс монет
 */
function getUserCoins(int $userId): int
{
    $db = getDB();
    $stmt = $db->prepare('SELECT coins FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Начислить монеты (после оплаты)
 */
function addCoins(int $userId, int $amount, string $description = ''): bool
{
    if ($amount <= 0) return false;

    $db = getDB();
    $db->prepare('UPDATE users SET coins = coins + ? WHERE id = ?')->execute([$amount, $userId]);

    // Логируем транзакцию
    $db->prepare('INSERT INTO point_transactions (user_id, amount, type, description, created_at) VALUES (?, ?, ?, ?, ?)')
        ->execute([$userId, $amount, 'coins_purchase', $description ?: 'Пополнение монет', now()]);

    return true;
}

/**
 * Списать монеты
 */
function spendCoins(int $userId, int $amount, string $description = '', ?int $serverId = null): bool
{
    $current = getUserCoins($userId);
    if ($current < $amount) return false;

    $db = getDB();
    $db->prepare('UPDATE users SET coins = coins - ? WHERE id = ?')->execute([$amount, $userId]);

    $db->prepare('INSERT INTO point_transactions (user_id, amount, type, description, server_id, created_at) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([$userId, -$amount, 'coins_spend', $description, $serverId, now()]);

    return true;
}

/**
 * Продвинуть сервер за монеты
 */
function promoteServerWithCoins(int $userId, int $serverId, string $duration): array
{
    $costs = PROMOTE_COIN_COSTS;
    $daysMap = ['7d' => 7, '14d' => 14, '30d' => 30];

    if (!isset($costs[$duration])) {
        return ['success' => false, 'error' => 'Неизвестный тариф.'];
    }

    $cost = $costs[$duration];
    $days = $daysMap[$duration];
    $currentCoins = getUserCoins($userId);

    if ($currentCoins < $cost) {
        return ['success' => false, 'error' => "Недостаточно монет. Нужно {$cost} 💰, у вас {$currentCoins}."];
    }

    $db = getDB();

    $stmt = $db->prepare("SELECT id, name, user_id, is_promoted, promoted_until FROM servers WHERE id = ? AND status IN ('active', 'pending')");
    $stmt->execute([$serverId]);
    $server = $stmt->fetch();

    if (!$server || $server['user_id'] != $userId) {
        return ['success' => false, 'error' => 'Сервер не найден.'];
    }

    // Списываем монеты
    if (!spendCoins($userId, $cost, "Продвижение \"{$server['name']}\" на {$days} дней", $serverId)) {
        return ['success' => false, 'error' => 'Не удалось списать монеты.'];
    }

    // Активируем продвижение
    $baseDate = ($server['is_promoted'] && $server['promoted_until'] && strtotime($server['promoted_until']) > time())
        ? $server['promoted_until'] : now();
    $until = date('Y-m-d H:i:s', strtotime($baseDate . " +{$days} days"));

    $db->prepare('UPDATE servers SET is_promoted = 1, promoted_until = ? WHERE id = ?')->execute([$until, $serverId]);

    return [
        'success' => true,
        'until' => $until,
        'coins_spent' => $cost,
        'coins_remaining' => getUserCoins($userId),
    ];
}
