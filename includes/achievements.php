<?php
/**
 * CraftRadar — Система достижений и избранного
 */

// ==========================================
// Избранное
// ==========================================

function addFavorite(int $userId, int $serverId): bool
{
    $db = getDB();
    try {
        $db->prepare('INSERT INTO favorites (user_id, server_id, created_at) VALUES (?, ?, ?)')->execute([$userId, $serverId, now()]);
        checkAchievement($userId, 'favorite_count');
        return true;
    } catch (\Exception $e) {
        return false; // Уже в избранном
    }
}

function removeFavorite(int $userId, int $serverId): void
{
    $db = getDB();
    $db->prepare('DELETE FROM favorites WHERE user_id = ? AND server_id = ?')->execute([$userId, $serverId]);
}

function isFavorite(int $userId, int $serverId): bool
{
    $db = getDB();
    $stmt = $db->prepare('SELECT id FROM favorites WHERE user_id = ? AND server_id = ?');
    $stmt->execute([$userId, $serverId]);
    return (bool)$stmt->fetch();
}

function getUserFavorites(int $userId): array
{
    $db = getDB();
    $stmt = $db->prepare("
        SELECT s.* FROM favorites f 
        JOIN servers s ON f.server_id = s.id 
        WHERE f.user_id = ? AND s.status IN ('active', 'pending')
        ORDER BY f.created_at DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function getFavoriteCount(int $userId): int
{
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ?');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

// ==========================================
// Достижения
// ==========================================

function getUserAchievements(int $userId): array
{
    $db = getDB();
    $stmt = $db->prepare("
        SELECT a.*, ua.earned_at 
        FROM achievements a 
        LEFT JOIN user_achievements ua ON a.slug = ua.achievement_slug AND ua.user_id = ?
        ORDER BY a.sort_order
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function hasAchievement(int $userId, string $slug): bool
{
    $db = getDB();
    $stmt = $db->prepare('SELECT id FROM user_achievements WHERE user_id = ? AND achievement_slug = ?');
    $stmt->execute([$userId, $slug]);
    return (bool)$stmt->fetch();
}

function grantAchievement(int $userId, string $slug): bool
{
    if (hasAchievement($userId, $slug)) return false;

    $db = getDB();
    try {
        $db->prepare('INSERT INTO user_achievements (user_id, achievement_slug, earned_at) VALUES (?, ?, ?)')
            ->execute([$userId, $slug, now()]);

        // Начисляем баллы за достижение
        $stmt = $db->prepare('SELECT name, points_reward, icon FROM achievements WHERE slug = ?');
        $stmt->execute([$slug]);
        $achievement = $stmt->fetch();

        if ($achievement && $achievement['points_reward'] > 0) {
            require_once __DIR__ . '/points.php';
            addPoints($userId, $achievement['points_reward'], 'achievement', 
                'Достижение: ' . $achievement['icon'] . ' ' . $achievement['name']);
        }

        // Уведомление
        if ($achievement) {
            require_once __DIR__ . '/notifications.php';
            createNotification($userId, 'achievement',
                $achievement['icon'] . ' Достижение: ' . $achievement['name'],
                $achievement['description'] . ($achievement['points_reward'] > 0 ? ' (+' . $achievement['points_reward'] . ' 💎)' : ''),
                SITE_URL . '/dashboard/profile.php'
            );
        }

        return true;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * Проверка и выдача достижений по событию
 */
function checkAchievement(int $userId, string $event): void
{
    $db = getDB();

    switch ($event) {
        case 'vote':
            $count = (int)$db->prepare('SELECT COUNT(*) FROM votes WHERE user_id = ?')->execute([$userId]) ? $db->prepare('SELECT COUNT(*) FROM votes WHERE user_id = ?') : null;
            $stmt = $db->prepare('SELECT COUNT(*) FROM votes WHERE user_id = ?');
            $stmt->execute([$userId]);
            $count = (int)$stmt->fetchColumn();
            if ($count >= 1) grantAchievement($userId, 'first_vote');
            if ($count >= 10) grantAchievement($userId, 'voter_10');
            if ($count >= 50) grantAchievement($userId, 'voter_50');
            break;

        case 'review':
            $stmt = $db->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = ?');
            $stmt->execute([$userId]);
            $count = (int)$stmt->fetchColumn();
            if ($count >= 1) grantAchievement($userId, 'first_review');
            break;

        case 'server_add':
            $stmt = $db->prepare('SELECT COUNT(*) FROM servers WHERE user_id = ?');
            $stmt->execute([$userId]);
            if ((int)$stmt->fetchColumn() >= 1) grantAchievement($userId, 'first_server');
            break;

        case 'verify':
            grantAchievement($userId, 'verified_owner');
            break;

        case 'favorite_count':
            if (getFavoriteCount($userId) >= 5) grantAchievement($userId, 'favorite_5');
            break;

        case 'daily_visit':
            $user = $db->prepare('SELECT daily_streak FROM users WHERE id = ?');
            $user->execute([$userId]);
            $streak = (int)$user->fetchColumn();
            if ($streak >= 3) grantAchievement($userId, 'daily_3');
            if ($streak >= 7) grantAchievement($userId, 'daily_7');
            if ($streak >= 30) grantAchievement($userId, 'daily_30');
            break;
    }
}

/**
 * Обновить ежедневный визит (вызывается при каждом входе)
 */
function trackDailyVisit(int $userId): void
{
    $db = getDB();
    $today = date('Y-m-d');

    $stmt = $db->prepare('SELECT last_daily_visit, daily_streak FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) return;

    $lastVisit = $user['last_daily_visit'];

    if ($lastVisit === $today) return; // Уже заходил сегодня

    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $newStreak = ($lastVisit === $yesterday) ? $user['daily_streak'] + 1 : 1;

    $db->prepare('UPDATE users SET last_daily_visit = ?, daily_streak = ? WHERE id = ?')
        ->execute([$today, $newStreak, $userId]);

    checkAchievement($userId, 'daily_visit');
}

/**
 * Получить Minecraft ник пользователя
 */
function getUserMinecraftNick(int $userId): string
{
    $db = getDB();
    $stmt = $db->prepare('SELECT minecraft_nick FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetchColumn() ?: '';
}

/**
 * Сохранить Minecraft ник
 */
function setUserMinecraftNick(int $userId, string $nick): void
{
    $db = getDB();
    $db->prepare('UPDATE users SET minecraft_nick = ? WHERE id = ?')->execute([$nick ?: null, $userId]);
}
