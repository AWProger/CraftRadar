<?php
/**
 * CraftRadar — Тесты cron-файлов
 * 
 * Проверяем структуру, наличие защит и корректность файлов.
 */

use PHPUnit\Framework\TestCase;

class CronTest extends TestCase
{
    // ==========================================
    // Файлы существуют
    // ==========================================

    public function testPingServersFileExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'cron/ping_servers.php');
    }

    public function testResetMonthlyFileExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'cron/reset_monthly.php');
    }

    public function testCleanupStatsFileExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'cron/cleanup_stats.php');
    }

    public function testReadmeExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'cron/README.md');
    }

    // ==========================================
    // ping_servers.php — защиты
    // ==========================================

    public function testPingHasCliCheck(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/ping_servers.php');
        $this->assertStringContainsString('php_sapi_name', $content);
        $this->assertStringContainsString('CRON_SECRET_KEY', $content);
    }

    public function testPingHasLockFile(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/ping_servers.php');
        $this->assertStringContainsString('lock', $content);
        $this->assertStringContainsString('cron_ping.lock', $content);
    }

    public function testPingHasLogging(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/ping_servers.php');
        $this->assertStringContainsString('cronLog', $content);
        $this->assertStringContainsString('storage/logs/', $content);
    }

    public function testPingIncludesMinecraftPing(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/ping_servers.php');
        $this->assertStringContainsString('minecraft_ping.php', $content);
    }

    public function testPingUpdatesServerStats(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/ping_servers.php');
        $this->assertStringContainsString('server_stats', $content);
        $this->assertStringContainsString('INSERT INTO server_stats', $content);
    }

    public function testPingUpdatesServerFields(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/ping_servers.php');
        $this->assertStringContainsString('is_online', $content);
        $this->assertStringContainsString('players_online', $content);
        $this->assertStringContainsString('consecutive_fails', $content);
    }

    public function testPingSavesIcon(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/ping_servers.php');
        $this->assertStringContainsString('saveServerIconCron', $content);
        $this->assertStringContainsString('favicon', $content);
    }

    // ==========================================
    // reset_monthly.php — защиты
    // ==========================================

    public function testResetHasCliCheck(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/reset_monthly.php');
        $this->assertStringContainsString('php_sapi_name', $content);
        $this->assertStringContainsString('CRON_SECRET_KEY', $content);
    }

    public function testResetHasLogging(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/reset_monthly.php');
        $this->assertStringContainsString('cronLog', $content);
    }

    public function testResetUpdatesVotesMonth(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/reset_monthly.php');
        $this->assertStringContainsString('votes_month = 0', $content);
    }

    // ==========================================
    // cleanup_stats.php — защиты
    // ==========================================

    public function testCleanupHasCliCheck(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/cleanup_stats.php');
        $this->assertStringContainsString('php_sapi_name', $content);
        $this->assertStringContainsString('CRON_SECRET_KEY', $content);
    }

    public function testCleanupHasLogging(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/cleanup_stats.php');
        $this->assertStringContainsString('cronLog', $content);
    }

    public function testCleanupDeletesOldStats(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/cleanup_stats.php');
        $this->assertStringContainsString('DELETE FROM server_stats', $content);
        $this->assertStringContainsString('30 DAY', $content);
    }

    public function testCleanupCleansLoginAttempts(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/cleanup_stats.php');
        $this->assertStringContainsString('login_attempts', $content);
    }

    public function testCleanupCleansOldLogs(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/cleanup_stats.php');
        $this->assertStringContainsString('cron_*.log', $content);
    }

    // ==========================================
    // README.md — инструкция
    // ==========================================

    public function testReadmeContainsCrontabInstructions(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/README.md');
        $this->assertStringContainsString('*/10 * * * *', $content);
        $this->assertStringContainsString('0 0 1 * *', $content);
        $this->assertStringContainsString('0 3 * * *', $content);
    }

    public function testReadmeContainsAllThreeScripts(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/README.md');
        $this->assertStringContainsString('ping_servers.php', $content);
        $this->assertStringContainsString('reset_monthly.php', $content);
        $this->assertStringContainsString('cleanup_stats.php', $content);
    }

    public function testReadmeContainsManualRunInstructions(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/README.md');
        $this->assertStringContainsString('Ручной запуск', $content);
    }

    // ==========================================
    // Авторизация по ключу
    // ==========================================

    public function testPingHasSecretKeyAuth(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/ping_servers.php');
        $this->assertStringContainsString("_GET['key']", $content);
        $this->assertStringContainsString('403', $content);
        $this->assertStringContainsString('Access denied', $content);
    }

    public function testResetHasSecretKeyAuth(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/reset_monthly.php');
        $this->assertStringContainsString("_GET['key']", $content);
        $this->assertStringContainsString('403', $content);
    }

    public function testCleanupHasSecretKeyAuth(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/cleanup_stats.php');
        $this->assertStringContainsString("_GET['key']", $content);
        $this->assertStringContainsString('403', $content);
    }

    public function testReadmeContainsWgetCommands(): void
    {
        $content = file_get_contents(ROOT_PATH . 'cron/README.md');
        $this->assertStringContainsString('wget -qO-', $content);
        $this->assertStringContainsString('?key=', $content);
    }
}
