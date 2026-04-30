<?php
/**
 * CraftRadar — Тесты страниц для подключения платежей ЮMoney
 * 
 * Проверяем наличие обязательных страниц и контента для прохождения модерации.
 */

use PHPUnit\Framework\TestCase;

class PaymentPagesTest extends TestCase
{
    private string $sql;

    protected function setUp(): void
    {
        $this->sql = file_get_contents(ROOT_PATH . 'install/database.sql');
    }

    // ==========================================
    // Обязательные страницы в БД
    // ==========================================

    public function testOfferPageExists(): void
    {
        $this->assertStringContainsString("'offer'", $this->sql);
        $this->assertStringContainsString('Публичная оферта', $this->sql);
    }

    public function testContactsPageExists(): void
    {
        $this->assertStringContainsString("'contacts'", $this->sql);
        $this->assertStringContainsString('Контактная информация', $this->sql);
    }

    public function testServicesPageExists(): void
    {
        $this->assertStringContainsString("'services'", $this->sql);
        $this->assertStringContainsString('Платные услуги', $this->sql);
    }

    // ==========================================
    // Оферта содержит обязательные разделы
    // ==========================================

    public function testOfferHasServiceDescription(): void
    {
        $this->assertStringContainsString('Описание услуги', $this->sql);
    }

    public function testOfferHasPrices(): void
    {
        $this->assertStringContainsString('99 руб', $this->sql);
        $this->assertStringContainsString('179 руб', $this->sql);
        $this->assertStringContainsString('299 руб', $this->sql);
    }

    public function testOfferHasDeliveryInfo(): void
    {
        $this->assertStringContainsString('активируется автоматически', $this->sql);
    }

    public function testOfferHasRefundPolicy(): void
    {
        $this->assertStringContainsString('Возврат средств', $this->sql);
    }

    // ==========================================
    // Контакты содержат обязательные данные
    // ==========================================

    public function testContactsHasEmail(): void
    {
        $this->assertStringContainsString('admin@craftradar.ru', $this->sql);
    }

    public function testContactsHasInnPlaceholder(): void
    {
        $this->assertStringContainsString('ИНН', $this->sql);
    }

    public function testContactsHasFioPlaceholder(): void
    {
        $this->assertStringContainsString('ФИО', $this->sql);
    }

    public function testContactsHasSelfEmployedStatus(): void
    {
        $this->assertStringContainsString('Самозанятый', $this->sql);
    }

    // ==========================================
    // Услуги содержат описание товаров
    // ==========================================

    public function testServicesHasProductDescription(): void
    {
        $this->assertStringContainsString('Закрепление в топе', $this->sql);
    }

    public function testServicesHasPriceTable(): void
    {
        $this->assertStringContainsString('7 дней', $this->sql);
        $this->assertStringContainsString('14 дней', $this->sql);
        $this->assertStringContainsString('30 дней', $this->sql);
    }

    public function testServicesHasPaymentMethods(): void
    {
        $this->assertStringContainsString('Способы оплаты', $this->sql);
        $this->assertStringContainsString('банковская карта', $this->sql);
    }

    public function testServicesHasHowToOrder(): void
    {
        $this->assertStringContainsString('Как получить услугу', $this->sql);
    }

    // ==========================================
    // Footer содержит ссылки
    // ==========================================

    public function testFooterHasOfferLink(): void
    {
        $footer = file_get_contents(ROOT_PATH . 'includes/footer.php');
        $this->assertStringContainsString('slug=offer', $footer);
        $this->assertStringContainsString('Оферта', $footer);
    }

    public function testFooterHasContactsLink(): void
    {
        $footer = file_get_contents(ROOT_PATH . 'includes/footer.php');
        $this->assertStringContainsString('slug=contacts', $footer);
        $this->assertStringContainsString('Контакты', $footer);
    }

    public function testFooterHasServicesLink(): void
    {
        $footer = file_get_contents(ROOT_PATH . 'includes/footer.php');
        $this->assertStringContainsString('slug=services', $footer);
        $this->assertStringContainsString('Услуги', $footer);
    }

    // ==========================================
    // Promote страница содержит обязательную информацию
    // ==========================================

    public function testPromoteHasOfferLink(): void
    {
        $content = file_get_contents(ROOT_PATH . 'dashboard/promote.php');
        $this->assertStringContainsString('slug=offer', $content);
        $this->assertStringContainsString('офертой', $content);
    }

    public function testPromoteHasPaymentMethods(): void
    {
        $content = file_get_contents(ROOT_PATH . 'dashboard/promote.php');
        $this->assertStringContainsString('банковской картой', $content);
        $this->assertStringContainsString('ЮMoney', $content);
    }

    public function testPromoteHasHowItWorks(): void
    {
        $content = file_get_contents(ROOT_PATH . 'dashboard/promote.php');
        $this->assertStringContainsString('Как это работает', $content);
    }

    public function testPromoteHasContactsLink(): void
    {
        $content = file_get_contents(ROOT_PATH . 'dashboard/promote.php');
        $this->assertStringContainsString('slug=contacts', $content);
    }
}
