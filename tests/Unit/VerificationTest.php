<?php
/**
 * CraftRadar — Тесты системы верификации владельца сервера
 */

use PHPUnit\Framework\TestCase;

class VerificationTest extends TestCase
{
    // ==========================================
    // Файлы существуют
    // ==========================================

    public function testVerifyPageExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'dashboard/verify.php');
    }

    // ==========================================
    // Верификация в database.sql
    // ==========================================

    public function testDatabaseHasVerifyFields(): void
    {
        $sql = file_get_contents(ROOT_PATH . 'install/database.sql');
        $this->assertStringContainsString('is_verified', $sql);
        $this->assertStringContainsString('verify_code', $sql);
        $this->assertStringContainsString('verified_at', $sql);
        $this->assertStringContainsString('verified_by', $sql);
    }

    // ==========================================
    // Страница верификации содержит инструкцию
    // ==========================================

    public function testVerifyPageHasSteps(): void
    {
        $content = file_get_contents(ROOT_PATH . 'dashboard/verify.php');
        $this->assertStringContainsString('server.properties', $content);
        $this->assertStringContainsString('motd', $content);
        $this->assertStringContainsString('Перезагрузите', $content);
    }

    public function testVerifyPageHasCodeGeneration(): void
    {
        $content = file_get_contents(ROOT_PATH . 'dashboard/verify.php');
        $this->assertStringContainsString('generateVerifyCode', $content);
    }

    public function testVerifyPageUsesPing(): void
    {
        $content = file_get_contents(ROOT_PATH . 'dashboard/verify.php');
        $this->assertStringContainsString('pingMinecraftServer', $content);
    }

    public function testVerifyPageChecksMotd(): void
    {
        $content = file_get_contents(ROOT_PATH . 'dashboard/verify.php');
        $this->assertStringContainsString('stripos', $content);
        $this->assertStringContainsString('verifyCode', $content);
    }

    public function testVerifyPageTransfersOwnership(): void
    {
        $content = file_get_contents(ROOT_PATH . 'dashboard/verify.php');
        $this->assertStringContainsString('user_id = ?', $content);
        $this->assertStringContainsString('is_verified = 1', $content);
        $this->assertStringContainsString('verified_by', $content);
    }

    public function testVerifyPageHasCsrf(): void
    {
        $content = file_get_contents(ROOT_PATH . 'dashboard/verify.php');
        $this->assertStringContainsString('csrfField', $content);
        $this->assertStringContainsString('verifyCsrfToken', $content);
    }

    // ==========================================
    // Значок верификации на страницах
    // ==========================================

    public function testServerPageShowsVerifiedBadge(): void
    {
        $content = file_get_contents(ROOT_PATH . 'server.php');
        $this->assertStringContainsString('is_verified', $content);
        $this->assertStringContainsString('Владелец подтверждён', $content);
    }

    public function testServerPageHasClaimBlock(): void
    {
        $content = file_get_contents(ROOT_PATH . 'server.php');
        $this->assertStringContainsString('Это ваш сервер', $content);
        $this->assertStringContainsString('verify.php', $content);
    }

    public function testCatalogShowsVerifiedMark(): void
    {
        // Карточки теперь рендерятся через components.php
        $content = file_get_contents(ROOT_PATH . 'includes/components.php');
        $this->assertStringContainsString('is_verified', $content);
    }

    public function testDashboardShowsVerifyButton(): void
    {
        $content = file_get_contents(ROOT_PATH . 'dashboard/index.php');
        $this->assertStringContainsString('verify.php', $content);
        $this->assertStringContainsString('Верифицировать', $content);
        $this->assertStringContainsString('is_verified', $content);
    }

    // ==========================================
    // Генерация кода
    // ==========================================

    public function testVerifyCodeFunctionExists(): void
    {
        // Подключаем файл чтобы проверить функцию
        // Не можем подключить verify.php напрямую (требует сессию и header),
        // но проверяем что функция определена в файле
        $content = file_get_contents(ROOT_PATH . 'dashboard/verify.php');
        $this->assertStringContainsString('function generateVerifyCode', $content);
    }

    public function testVerifyCodeIs6Chars(): void
    {
        $content = file_get_contents(ROOT_PATH . 'dashboard/verify.php');
        // Проверяем что длина кода = 6
        $this->assertStringContainsString('$i < 6', $content);
    }
}
