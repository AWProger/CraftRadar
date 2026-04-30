<?php
/**
 * CraftRadar — Интеграция с ЮMoney (YooMoney)
 * 
 * Используется протокол P2P-переводов (для кошелька).
 * Форма оплаты → ЮMoney → HTTP-уведомление на notify.php.
 * 
 * Документация: https://yoomoney.ru/docs/wallet/process-payments/incoming-transfers
 */

class YooMoney
{
    private string $wallet;
    private string $secret;

    public function __construct(string $wallet = '', string $secret = '')
    {
        $this->wallet = $wallet ?: YOOMONEY_WALLET;
        $this->secret = $secret ?: YOOMONEY_SECRET;
    }

    /**
     * Генерация данных для формы оплаты
     * 
     * Форма отправляется напрямую на https://yoomoney.ru/quickpay/confirm
     * 
     * @param float  $amount  Сумма в рублях
     * @param string $label   Уникальная метка платежа
     * @param string $comment Описание платежа
     * @return array Параметры для формы
     */
    public function createPaymentForm(float $amount, string $label, string $comment = ''): array
    {
        return [
            'action'          => 'https://yoomoney.ru/quickpay/confirm',
            'receiver'        => $this->wallet,
            'quickpay-form'   => 'shop',
            'targets'         => $comment ?: 'Продвижение сервера на CraftRadar',
            'paymentType'     => 'AC', // AC = банковская карта, PC = кошелёк ЮMoney
            'sum'             => number_format($amount, 2, '.', ''),
            'label'           => $label,
            'successURL'      => YOOMONEY_SUCCESS_URL . '?label=' . urlencode($label),
            'need-fio'        => 'false',
            'need-email'      => 'false',
            'need-phone'      => 'false',
            'need-address'    => 'false',
        ];
    }

    /**
     * Проверка HTTP-уведомления от ЮMoney
     * 
     * ЮMoney отправляет POST на notify URL с параметрами.
     * Проверяем SHA-1 хэш для подтверждения подлинности.
     * 
     * @param array $data POST-данные от ЮMoney
     * @return bool Подлинность уведомления
     */
    public function verifyNotification(array $data): bool
    {
        if (empty($this->secret)) {
            return false;
        }

        $requiredFields = [
            'notification_type', 'operation_id', 'amount', 'currency',
            'datetime', 'sender', 'codepro', 'label'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        // Формируем строку для хэша
        // notification_type&operation_id&amount&currency&datetime&sender&codepro&notification_secret&label
        $hashString = implode('&', [
            $data['notification_type'],
            $data['operation_id'],
            $data['amount'],
            $data['currency'],
            $data['datetime'],
            $data['sender'] ?? '',
            $data['codepro'],
            $this->secret,
            $data['label'],
        ]);

        $expectedHash = sha1($hashString);

        return isset($data['sha1_hash']) && hash_equals($expectedHash, $data['sha1_hash']);
    }

    /**
     * Извлечение данных из уведомления
     */
    public function parseNotification(array $data): array
    {
        return [
            'operation_id'  => $data['operation_id'] ?? '',
            'amount'        => (float)($data['withdraw_amount'] ?? $data['amount'] ?? 0),
            'currency'      => $data['currency'] ?? 'RUB',
            'label'         => $data['label'] ?? '',
            'datetime'      => $data['datetime'] ?? '',
            'sender'        => $data['sender'] ?? '',
            'codepro'       => $data['codepro'] ?? 'false',
            'unaccepted'    => ($data['unaccepted'] ?? 'false') === 'true',
        ];
    }
}

/**
 * Создать платёж в БД и получить данные для формы
 */
function createPayment(int $userId, int $serverId, string $type): array
{
    $prices = PROMOTE_PRICES;
    $daysMap = ['7d' => 7, '14d' => 14, '30d' => 30];

    if (!isset($prices[$type])) {
        return ['success' => false, 'error' => 'Неизвестный тариф.'];
    }

    $amount = $prices[$type];
    $days = $daysMap[$type];
    $label = 'cr_' . $userId . '_' . $serverId . '_' . time();

    $db = getDB();

    // Проверяем сервер
    $stmt = $db->prepare('SELECT id, name FROM servers WHERE id = ? AND user_id = ?');
    $stmt->execute([$serverId, $userId]);
    $server = $stmt->fetch();

    if (!$server) {
        return ['success' => false, 'error' => 'Сервер не найден.'];
    }

    // Создаём запись платежа
    $stmt = $db->prepare('
        INSERT INTO payments (user_id, server_id, amount, type, status, label, description, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $description = "Продвижение \"{$server['name']}\" на {$days} дней";
    $stmt->execute([
        $userId, $serverId, $amount, 'promote_' . $type, 'pending',
        $label, $description, now()
    ]);

    $paymentId = (int)$db->lastInsertId();

    // Формируем данные для формы ЮMoney
    $yoomoney = new YooMoney();
    $formData = $yoomoney->createPaymentForm($amount, $label, $description);

    return [
        'success'    => true,
        'payment_id' => $paymentId,
        'label'      => $label,
        'amount'     => $amount,
        'days'       => $days,
        'form'       => $formData,
    ];
}

/**
 * Обработать уведомление об оплате
 */
function processPaymentNotification(array $postData): bool
{
    $yoomoney = new YooMoney();

    // Проверяем подлинность
    if (!$yoomoney->verifyNotification($postData)) {
        paymentLog('INVALID HASH: ' . json_encode($postData));
        return false;
    }

    $notification = $yoomoney->parseNotification($postData);

    // Защита от codepro (защита кодом протекции)
    if ($notification['codepro'] !== 'false') {
        paymentLog('CODEPRO payment rejected: ' . $notification['label']);
        return false;
    }

    // Ищем платёж по метке
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM payments WHERE label = ? AND status = 'pending'");
    $stmt->execute([$notification['label']]);
    $payment = $stmt->fetch();

    if (!$payment) {
        paymentLog('Payment not found for label: ' . $notification['label']);
        return false;
    }

    // Проверяем сумму (с допуском на комиссию ЮMoney)
    if ($notification['amount'] < $payment['amount'] * 0.95) {
        paymentLog("Amount mismatch: expected {$payment['amount']}, got {$notification['amount']} for label {$notification['label']}");
        return false;
    }

    // Обновляем платёж
    $stmt = $db->prepare("
        UPDATE payments SET 
            status = 'completed', 
            yoomoney_operation_id = ?, 
            paid_at = ?
        WHERE id = ?
    ");
    $stmt->execute([$notification['operation_id'], now(), $payment['id']]);

    // Активируем продвижение сервера
    $daysMap = ['promote_7d' => 7, 'promote_14d' => 14, 'promote_30d' => 30];
    $days = $daysMap[$payment['type']] ?? 7;

    $stmt = $db->prepare('SELECT is_promoted, promoted_until FROM servers WHERE id = ?');
    $stmt->execute([$payment['server_id']]);
    $server = $stmt->fetch();

    if ($server) {
        // Если уже продвигается — добавляем дни к текущему сроку
        $baseDate = ($server['is_promoted'] && $server['promoted_until'] && strtotime($server['promoted_until']) > time())
            ? $server['promoted_until']
            : now();

        $promotedUntil = date('Y-m-d H:i:s', strtotime($baseDate . " +{$days} days"));

        $db->prepare('UPDATE servers SET is_promoted = 1, promoted_until = ? WHERE id = ?')
            ->execute([$promotedUntil, $payment['server_id']]);
    }

    paymentLog("SUCCESS: Payment #{$payment['id']}, label={$notification['label']}, amount={$notification['amount']}, server={$payment['server_id']}, days={$days}");

    return true;
}

/**
 * Логирование платежей
 */
function paymentLog(string $message): void
{
    $logDir = ROOT_PATH . 'storage/logs/';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $logFile = $logDir . 'payments_' . date('Y-m') . '.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
}
