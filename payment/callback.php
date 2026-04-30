<?php
/**
 * CraftRadar — OAuth callback от ЮMoney
 * 
 * ЮMoney перенаправляет сюда после авторизации с кодом.
 * Обмениваем код на access_token и сохраняем номер кошелька.
 * 
 * Используется только администратором для привязки кошелька.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/yoomoney.php';

requireAuth();

// Только админ может привязывать кошелёк
if (!isAdmin()) {
    setFlash('error', 'Доступ запрещён.');
    redirect(SITE_URL . '/');
}

$code = get('code');
$error = get('error');

if ($error) {
    setFlash('error', 'Ошибка авторизации ЮMoney: ' . e($error));
    redirect(SITE_URL . '/admin/settings.php');
}

if (!$code) {
    setFlash('error', 'Код авторизации не получен.');
    redirect(SITE_URL . '/admin/settings.php');
}

$yoomoney = new YooMoney();

// Обмениваем код на токен
$accessToken = $yoomoney->exchangeCodeForToken($code);

if (!$accessToken) {
    setFlash('error', 'Не удалось получить токен ЮMoney. Попробуйте снова.');
    redirect(SITE_URL . '/admin/settings.php');
}

// Получаем информацию о кошельке
$accountInfo = $yoomoney->getAccountInfo($accessToken);

if ($accountInfo && isset($accountInfo['account'])) {
    $wallet = $accountInfo['account'];

    // Сохраняем в настройки
    $db = getDB();
    $db->prepare("UPDATE settings SET `value` = ?, updated_at = ?, updated_by = ? WHERE `key` = 'yoomoney_wallet'")
        ->execute([$wallet, now(), currentUserId()]);

    paymentLog("OAuth SUCCESS: wallet={$wallet}, admin=" . currentUserId());
    setFlash('success', 'Кошелёк ЮMoney привязан: ' . $wallet);
} else {
    paymentLog("OAuth: token received but account-info failed: " . json_encode($accountInfo));
    setFlash('warning', 'Токен получен, но не удалось определить номер кошелька. Укажите его вручную в настройках.');
}

redirect(SITE_URL . '/admin/settings.php');
