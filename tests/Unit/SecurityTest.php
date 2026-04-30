<?php
/**
 * CraftRadar — Тесты безопасности
 * 
 * Проверяем .htaccess, конфигурацию, заголовки.
 */

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    // ==========================================
    // .htaccess существует и содержит защиты
    // ==========================================

    public function testHtaccessExists(): void
    {
        $this->assertFileExists(ROOT_PATH . '.htaccess');
    }

    public function testHtaccessDeniesStorageAccess(): void
    {
        $content = file_get_contents(ROOT_PATH . '.htaccess');
        $this->assertStringContainsString('storage/', $content);
    }

    public function testHtaccessDeniesIncludesAccess(): void
    {
        $content = file_get_contents(ROOT_PATH . '.htaccess');
        $this->assertStringContainsString('includes/', $content);
    }

    public function testHtaccessDeniesTestsAccess(): void
    {
        $content = file_get_contents(ROOT_PATH . '.htaccess');
        $this->assertStringContainsString('tests/', $content);
    }

    public function testHtaccessDeniesVendorAccess(): void
    {
        $content = file_get_contents(ROOT_PATH . '.htaccess');
        $this->assertStringContainsString('vendor/', $content);
    }

    public function testHtaccessDeniesInstallAccess(): void
    {
        $content = file_get_contents(ROOT_PATH . '.htaccess');
        $this->assertStringContainsString('install/', $content);
    }

    public function testHtaccessDeniesKiroAccess(): void
    {
        $content = file_get_contents(ROOT_PATH . '.htaccess');
        $this->assertStringContainsString('.kiro/', $content);
    }

    public function testHtaccessDisablesDirectoryListing(): void
    {
        $content = file_get_contents(ROOT_PATH . '.htaccess');
        $this->assertStringContainsString('-Indexes', $content);
    }

    public function testHtaccessHasSecurityHeaders(): void
    {
        $content = file_get_contents(ROOT_PATH . '.htaccess');
        $this->assertStringContainsString('X-Content-Type-Options', $content);
        $this->assertStringContainsString('X-Frame-Options', $content);
        $this->assertStringContainsString('X-XSS-Protection', $content);
    }

    public function testHtaccessForcesHttps(): void
    {
        $content = file_get_contents(ROOT_PATH . '.htaccess');
        $this->assertStringContainsString('HTTPS', $content);
        $this->assertStringContainsString('R=301', $content);
    }

    public function testHtaccessHasGzipCompression(): void
    {
        $content = file_get_contents(ROOT_PATH . '.htaccess');
        $this->assertStringContainsString('mod_deflate', $content);
    }

    public function testHtaccessHasStaticCaching(): void
    {
        $content = file_get_contents(ROOT_PATH . '.htaccess');
        $this->assertStringContainsString('mod_expires', $content);
    }

    public function testHtaccessDeniesEnvFiles(): void
    {
        $content = file_get_contents(ROOT_PATH . '.htaccess');
        $this->assertStringContainsString('.env', $content);
    }

    public function testHtaccessHas404Page(): void
    {
        $content = file_get_contents(ROOT_PATH . '.htaccess');
        $this->assertStringContainsString('ErrorDocument 404', $content);
        $this->assertStringContainsString('404.php', $content);
    }

    // ==========================================
    // 404 страница
    // ==========================================

    public function test404PageExists(): void
    {
        $this->assertFileExists(ROOT_PATH . '404.php');
    }

    public function test404PageSetsResponseCode(): void
    {
        $content = file_get_contents(ROOT_PATH . '404.php');
        $this->assertStringContainsString('http_response_code(404)', $content);
    }

    public function test404PageHasLinks(): void
    {
        $content = file_get_contents(ROOT_PATH . '404.php');
        $this->assertStringContainsString('На главную', $content);
        $this->assertStringContainsString('servers.php', $content);
    }

    // ==========================================
    // Конфигурация безопасности
    // ==========================================

    public function testBcryptCostDefined(): void
    {
        $this->assertGreaterThanOrEqual(10, BCRYPT_COST);
    }

    public function testCronSecretKeyDefined(): void
    {
        $this->assertTrue(defined('CRON_SECRET_KEY'));
        $this->assertNotEmpty(CRON_SECRET_KEY);
    }

    public function testCsrfTokenNameDefined(): void
    {
        $this->assertTrue(defined('CSRF_TOKEN_NAME'));
        $this->assertNotEmpty(CSRF_TOKEN_NAME);
    }

    // ==========================================
    // OpenGraph в header
    // ==========================================

    public function testHeaderHasOpenGraph(): void
    {
        $content = file_get_contents(ROOT_PATH . 'includes/header.php');
        $this->assertStringContainsString('og:title', $content);
        $this->assertStringContainsString('og:description', $content);
        $this->assertStringContainsString('og:url', $content);
        $this->assertStringContainsString('og:site_name', $content);
    }

    public function testHeaderHasCanonical(): void
    {
        $content = file_get_contents(ROOT_PATH . 'includes/header.php');
        $this->assertStringContainsString('rel="canonical"', $content);
    }
}
