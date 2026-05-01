<?php
/**
 * CraftRadar — Реферальная программа
 * 
 * Пользователь получает уникальный реферальный код.
 * Приглашённый регистрируется по ссылке ?ref=CODE.
 * Оба получают бонусные алмазы.
 */

/**
 * Генерация реферального кода для пользователя
 */
function generateReferralCode(int $userId): string
{
    $db = getDB();
    $stmt = $db->prepare('SELECT referral_code FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $existing = $stmt->fetchColumn();
    if ($existing) return $existing;

    // Генерируем уникальный код
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) $code .= $chars[random_int(0, strlen($chars) - 1)];
        $check = $db->prepare('SELECT id FROM users WHERE referral_code = ?');
        $check->execute([$code]);
    } while ($check->fetch());

    $db->prepare('UPDATE users SET referral_code = ? WHERE id = ?')->execute([$code, $userId]);
    return $code;
}

/**
 * Получить реферальный код пользователя
 */
function getReferralCode(int $userId): string
{
    $db = getDB();
    $stmt = $db->prepare('SELECT referral_code FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $code = $stmt->fetchColumn();
    return $code ?: generateReferralCode($userId);
}

/**
 * Получить реферальную ссылку
 */
function getReferralLink(int $userId): string
{
    return SITE_URL . '/register.php?ref=' . getReferralCode($userId);
}

/**
 * Обработать реферальную регистрацию
 */
function processReferral(int $newUserId, string $refCode): void
{
    $db = getDB();

    // Находим реферера
    $stmt = $db->prepare('SELECT id FROM users WHERE referral_code = ? AND id != ?');
    $stmt->execute([$refCode, $newUserId]);
    $referrer = $stmt->fetch();

    if (!$referrer) return;

    $referrerId = $referrer['id'];

    // Привязываем
    $db->prepare('UPDATE users SET referred_by = ? WHERE id = ?')->execute([$referrerId, $newUserId]);
    $db->prepare('UPDATE users SET referral_count = referral_count + 1 WHERE id = ?')->execute([$referrerId]);

    // Награда рефереру
    require_once __DIR__ . '/points.php';
    require_once __DIR__ . '/notifications.php';

    if (REFERRAL_REWARD_REGISTER > 0) {
        addPoints($referrerId, REFERRAL_REWARD_REGISTER, 'referral_register', 'Реферал зарегистрировался');
        $db->prepare('INSERT INTO referral_rewards (referrer_id, referred_id, reward_type, points_reward, created_at) VALUES (?, ?, ?, ?, ?)')
            ->execute([$referrerId, $newUserId, 'registration', REFERRAL_REWARD_REGISTER, now()]);

        createNotification($referrerId, 'referral',
            '👥 Новый реферал!',
            'По вашей ссылке зарегистрировался пользователь. +' . REFERRAL_REWARD_REGISTER . ' 💎',
            SITE_URL . '/dashboard/profile.php'
        );
    }

    // Бонус приглашённому
    if (REFERRAL_REWARD_REFERRED > 0) {
        addPoints($newUserId, REFERRAL_REWARD_REFERRED, 'referral_bonus', 'Бонус за регистрацию по реферальной ссылке');
    }
}

/**
 * Статистика рефералов
 */
function getReferralStats(int $userId): array
{
    $db = getDB();
    $stmt = $db->prepare('SELECT referral_count FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $count = (int)$stmt->fetchColumn();

    $stmt = $db->prepare('SELECT SUM(points_reward) FROM referral_rewards WHERE referrer_id = ?');
    $stmt->execute([$userId]);
    $totalReward = (int)$stmt->fetchColumn();

    return ['count' => $count, 'total_reward' => $totalReward];
}
