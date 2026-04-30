<?php
/**
 * CraftRadar — Тесты конфигурации (includes/config.php)
 */

use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    // ==========================================
    // Проверка определения констант
    // ==========================================

    public function testDatabaseConstantsDefined(): void
    {
        $this->assertTrue(defined('DB_HOST'));
        $this->assertTrue(defined('DB_NAME'));
        $this->assertTrue(defined('DB_USER'));
        $this->assertTrue(defined('DB_PASS'));
        $this->assertTrue(defined('DB_CHARSET'));
    }

    public function testPathConstantsDefined(): void
    {
        $this->assertTrue(defined('ROOT_PATH'));
        $this->assertTrue(defined('INCLUDES_PATH'));
        $this->assertTrue(defined('ASSETS_PATH'));
        $this->assertTrue(defined('UPLOADS_PATH'));
    }

    public function testSiteConstantsDefined(): void
    {
        $this->assertTrue(defined('SITE_URL'));
        $this->assertTrue(defined('SITE_NAME'));
        $this->assertSame('CraftRadar', SITE_NAME);
    }

    public function testSecurityConstantsDefined(): void
    {
        $this->assertTrue(defined('BCRYPT_COST'));
        $this->assertTrue(defined('MAX_LOGIN_ATTEMPTS'));
        $this->assertTrue(defined('LOGIN_BLOCK_TIME'));
        $this->assertTrue(defined('MIN_PASSWORD_LENGTH'));
        $this->assertTrue(defined('CSRF_TOKEN_NAME'));
    }

    public function testServerConstantsDefined(): void
    {
        $this->assertTrue(defined('SERVERS_PER_PAGE'));
        $this->assertTrue(defined('SERVERS_LIMIT_PER_USER'));
        $this->assertTrue(defined('PING_TIMEOUT'));
        $this->assertTrue(defined('MAX_CONSECUTIVE_FAILS'));
    }

    public function testVoteConstantsDefined(): void
    {
        $this->assertTrue(defined('VOTE_COOLDOWN'));
        $this->assertSame(24, VOTE_COOLDOWN);
    }

    public function testReviewConstantsDefined(): void
    {
        $this->assertTrue(defined('REVIEW_MIN_LENGTH'));
        $this->assertTrue(defined('REVIEW_MAX_LENGTH'));
        $this->assertGreaterThan(0, REVIEW_MIN_LENGTH);
        $this->assertGreaterThan(REVIEW_MIN_LENGTH, REVIEW_MAX_LENGTH);
    }

    public function testUploadConstantsDefined(): void
    {
        $this->assertTrue(defined('MAX_ICON_SIZE'));
        $this->assertTrue(defined('MAX_BANNER_SIZE'));
        $this->assertTrue(defined('ALLOWED_IMAGE_TYPES'));
        $this->assertIsArray(ALLOWED_IMAGE_TYPES);
        $this->assertContains('image/png', ALLOWED_IMAGE_TYPES);
        $this->assertContains('image/jpeg', ALLOWED_IMAGE_TYPES);
    }

    // ==========================================
    // Проверка значений
    // ==========================================

    public function testBcryptCostIsReasonable(): void
    {
        $this->assertGreaterThanOrEqual(10, BCRYPT_COST);
        $this->assertLessThanOrEqual(15, BCRYPT_COST);
    }

    public function testMinPasswordLengthIsReasonable(): void
    {
        $this->assertGreaterThanOrEqual(4, MIN_PASSWORD_LENGTH);
    }

    public function testServersPerPageIsPositive(): void
    {
        $this->assertGreaterThan(0, SERVERS_PER_PAGE);
    }

    public function testRootPathEndsWithSlash(): void
    {
        $this->assertStringEndsWith('/', ROOT_PATH);
    }

    public function testTimezoneIsSet(): void
    {
        $this->assertNotEmpty(date_default_timezone_get());
    }

    public function testSessionIsStarted(): void
    {
        // В CLI сессия может быть активна или нет — проверяем что session_start() вызывается в конфиге
        $content = file_get_contents(ROOT_PATH . 'includes/config.php');
        $this->assertStringContainsString('session_start()', $content);
    }
}
