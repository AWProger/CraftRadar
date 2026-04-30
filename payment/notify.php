<?php
/**
 * CraftRadar — Обработчик уведомлений ЮMoney (webhook)
 * 
 * ЮMoney отправляет POST-запрос на этот URL после успешной оплаты.
 * URL настраивается в личном кабинете ЮMoney → Настройки → HTTP-уведомления.
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/yoomoney.php';

// Только POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Логируем входящие данные
paymentLog('NOTIFY RECEIVED: ' . json_encode($_POST));

// Обрабатываем уведомление
$result = processPaymentNotification($_POST);

if ($result) {
    http_response_code(200);
    echo 'OK';
} else {
    http_response_code(200); // ЮMoney ожидает 200 даже при ошибке
    echo 'ERROR';
}
