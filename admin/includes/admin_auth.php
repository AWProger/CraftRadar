<?php
/**
 * CraftRadar — Проверка прав доступа в админке
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Проверяем авторизацию и роль
if (!isLoggedIn()) {
    setFlash('error', 'Необходимо авторизоваться.');
    redirect(SITE_URL . '/login.php');
}

if (!isModerator()) {
    setFlash('error', 'Доступ запрещён.');
    redirect(SITE_URL . '/');
}

/**
 * Логирование действий администрации
 */
function adminLog(string $action, string $targetType, int $targetId, ?string $details = null): void
{
    $db = getDB();
    $stmt = $db->prepare('
        INSERT INTO admin_log (admin_id, action, target_type, target_id, details, ip_address, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        currentUserId(), $action, $targetType, $targetId,
        $details, getUserIP(), now()
    ]);
}

/**
 * Rate-limiting для админ-действий (макс 30 действий в минуту)
 */
function checkAdminRateLimit(): bool
{
    return checkRateLimit('admin_action', 30);
}
