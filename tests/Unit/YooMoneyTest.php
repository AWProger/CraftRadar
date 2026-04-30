<?php
/**
 * CraftRadar — Тесты интеграции ЮMoney (includes/yoomoney.php)
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/yoomoney.php';

class YooMoneyTest extends TestCase
{
    // ==========================================
    // Создание экземпляра
    // ==========================================

    public function testCanCreateInstance(): void
    {
        $ym = new YooMoney('4100000000000', 'test_secret');
        $this->assertInstanceOf(YooMoney::class, $ym);
    }

    // ==========================================
    // createPaymentForm()
    // ==========================================

    public function testCreatePaymentFormReturnsArray(): void
    {
        $ym = new YooMoney('4100000000000', 'secret');
        $form = $ym->createPaymentForm(99.00, 'test_label', 'Test payment');

        $this->assertIsArray($form);
        $this->assertArrayHasKey('action', $form);
        $this->assertArrayHasKey('receiver', $form);
        $this->assertArrayHasKey('sum', $form);
        $this->assertArrayHasKey('label', $form);
    }

    public function testCreatePaymentFormHasCorrectAction(): void
    {
        $ym = new YooMoney('4100000000000', 'secret');
        $form = $ym->createPaymentForm(99.00, 'label');

        $this->assertSame('https://yoomoney.ru/quickpay/confirm', $form['action']);
    }

    public function testCreatePaymentFormHasCorrectReceiver(): void
    {
        $ym = new YooMoney('4100000000000', 'secret');
        $form = $ym->createPaymentForm(99.00, 'label');

        $this->assertSame('4100000000000', $form['receiver']);
    }

    public function testCreatePaymentFormHasCorrectAmount(): void
    {
        $ym = new YooMoney('4100000000000', 'secret');
        $form = $ym->createPaymentForm(299.50, 'label');

        $this->assertSame('299.50', $form['sum']);
    }

    public function testCreatePaymentFormHasLabel(): void
    {
        $ym = new YooMoney('4100000000000', 'secret');
        $form = $ym->createPaymentForm(99.00, 'my_unique_label');

        $this->assertSame('my_unique_label', $form['label']);
    }

    public function testCreatePaymentFormHasPaymentType(): void
    {
        $ym = new YooMoney('4100000000000', 'secret');
        $form = $ym->createPaymentForm(99.00, 'label');

        $this->assertSame('AC', $form['paymentType']);
    }

    // ==========================================
    // verifyNotification()
    // ==========================================

    public function testVerifyNotificationWithValidHash(): void
    {
        $secret = 'test_secret_key';
        $ym = new YooMoney('4100000000000', $secret);

        $data = [
            'notification_type' => 'p2p-incoming',
            'operation_id'      => '123456',
            'amount'            => '99.00',
            'currency'          => '643',
            'datetime'          => '2026-04-30T12:00:00.000+03:00',
            'sender'            => '41001234567890',
            'codepro'           => 'false',
            'label'             => 'cr_1_1_1234567890',
        ];

        // Генерируем правильный хэш
        $hashString = implode('&', [
            $data['notification_type'], $data['operation_id'], $data['amount'],
            $data['currency'], $data['datetime'], $data['sender'],
            $data['codepro'], $secret, $data['label'],
        ]);
        $data['sha1_hash'] = sha1($hashString);

        $this->assertTrue($ym->verifyNotification($data));
    }

    public function testVerifyNotificationWithInvalidHash(): void
    {
        $ym = new YooMoney('4100000000000', 'test_secret');

        $data = [
            'notification_type' => 'p2p-incoming',
            'operation_id'      => '123456',
            'amount'            => '99.00',
            'currency'          => '643',
            'datetime'          => '2026-04-30T12:00:00.000+03:00',
            'sender'            => '41001234567890',
            'codepro'           => 'false',
            'label'             => 'cr_1_1_1234567890',
            'sha1_hash'         => 'invalid_hash_value',
        ];

        $this->assertFalse($ym->verifyNotification($data));
    }

    public function testVerifyNotificationWithMissingFields(): void
    {
        $ym = new YooMoney('4100000000000', 'test_secret');

        $data = [
            'notification_type' => 'p2p-incoming',
            // Missing required fields
        ];

        $this->assertFalse($ym->verifyNotification($data));
    }

    public function testVerifyNotificationWithEmptySecret(): void
    {
        $ym = new YooMoney('4100000000000', '');

        $data = [
            'notification_type' => 'p2p-incoming',
            'operation_id' => '123', 'amount' => '99', 'currency' => '643',
            'datetime' => '2026-01-01', 'sender' => '', 'codepro' => 'false',
            'label' => 'test', 'sha1_hash' => 'abc',
        ];

        $this->assertFalse($ym->verifyNotification($data));
    }

    // ==========================================
    // parseNotification()
    // ==========================================

    public function testParseNotificationExtractsData(): void
    {
        $ym = new YooMoney('4100000000000', 'secret');

        $data = [
            'operation_id'    => 'op_123',
            'amount'          => '99.00',
            'withdraw_amount' => '99.00',
            'currency'        => '643',
            'label'           => 'cr_1_1_1234567890',
            'datetime'        => '2026-04-30T12:00:00',
            'sender'          => '41001234567890',
            'codepro'         => 'false',
            'unaccepted'      => 'false',
        ];

        $result = $ym->parseNotification($data);

        $this->assertSame('op_123', $result['operation_id']);
        $this->assertSame(99.0, $result['amount']);
        $this->assertSame('cr_1_1_1234567890', $result['label']);
        $this->assertSame('false', $result['codepro']);
        $this->assertFalse($result['unaccepted']);
    }

    // ==========================================
    // Конфигурация
    // ==========================================

    public function testYooMoneyConstantsDefined(): void
    {
        $this->assertTrue(defined('YOOMONEY_WALLET'));
        $this->assertTrue(defined('YOOMONEY_SECRET'));
        $this->assertTrue(defined('YOOMONEY_NOTIFY_URL'));
        $this->assertTrue(defined('YOOMONEY_SUCCESS_URL'));
        $this->assertTrue(defined('YOOMONEY_FAIL_URL'));
    }

    public function testPromotePricesDefined(): void
    {
        $this->assertTrue(defined('PROMOTE_PRICES'));
        $this->assertIsArray(PROMOTE_PRICES);
        $this->assertArrayHasKey('7d', PROMOTE_PRICES);
        $this->assertArrayHasKey('14d', PROMOTE_PRICES);
        $this->assertArrayHasKey('30d', PROMOTE_PRICES);
    }

    public function testPromotePricesArePositive(): void
    {
        foreach (PROMOTE_PRICES as $key => $price) {
            $this->assertGreaterThan(0, $price, "Price for {$key} should be positive");
        }
    }

    public function testPromotePricesAscending(): void
    {
        $prices = PROMOTE_PRICES;
        $this->assertLessThan($prices['14d'], $prices['7d']);
        $this->assertLessThan($prices['30d'], $prices['14d']);
    }

    // ==========================================
    // Файлы платёжной системы
    // ==========================================

    public function testPaymentNotifyExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'payment/notify.php');
    }

    public function testPaymentSuccessExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'payment/success.php');
    }

    public function testPaymentFailExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'payment/fail.php');
    }

    public function testDashboardPromoteExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'dashboard/promote.php');
    }

    public function testAdminPaymentsExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'admin/payments.php');
    }

    // ==========================================
    // Таблица payments в SQL
    // ==========================================

    public function testPaymentsTableInSQL(): void
    {
        $sql = file_get_contents(ROOT_PATH . 'install/database.sql');
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS payments', $sql);
    }

    public function testPaymentsHasYooMoneyFields(): void
    {
        $sql = file_get_contents(ROOT_PATH . 'install/database.sql');
        $this->assertStringContainsString('yoomoney_operation_id', $sql);
        $this->assertStringContainsString('label', $sql);
    }

    public function testPaymentsHasStatusEnum(): void
    {
        $sql = file_get_contents(ROOT_PATH . 'install/database.sql');
        $this->assertStringContainsString("'pending', 'completed', 'failed', 'refunded'", $sql);
    }
}
