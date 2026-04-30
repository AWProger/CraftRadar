<?php
/**
 * CraftRadar — Система уведомлений
 */

/**
 * Создать уведомление
 */
function createNotification(int $userId, string $type, string $title, string $message = '', string $link = ''): void
{
    $db = getDB();
    $stmt = $db->prepare('INSERT INTO notifications (user_id, type, title, message, link, created_at) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $type, $title, $message, $link, now()]);
}

/**
 * Получить непрочитанные уведомления
 */
function getUnreadNotifications(int $userId, int $limit = 20): array
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Получить все уведомления
 */
function getAllNotifications(int $userId, int $limit = 50): array
{
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Количество непрочитанных
 */
function getUnreadCount(int $userId): int
{
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Пометить как прочитанное
 */
function markNotificationRead(int $notificationId, int $userId): void
{
    $db = getDB();
    $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')->execute([$notificationId, $userId]);
}

/**
 * Пометить все как прочитанные
 */
function markAllNotificationsRead(int $userId): void
{
    $db = getDB();
    $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')->execute([$userId]);
}

/**
 * Иконка по типу уведомления
 */
function notificationIcon(string $type): string
{
    return match($type) {
        'server_offline'   => '🔴',
        'server_approved'  => '✅',
        'server_rejected'  => '❌',
        'new_review'       => '💬',
        'payment_completed'=> '💰',
        'server_verified'  => '🔐',
        default            => '🔔',
    };
}
