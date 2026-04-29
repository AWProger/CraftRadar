<?php
/**
 * CraftRadar — Тесты структуры файлов проекта
 * 
 * Проверяем, что все необходимые файлы существуют.
 */

use PHPUnit\Framework\TestCase;

class FileStructureTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = ROOT_PATH;
    }

    // ==========================================
    // Includes
    // ==========================================

    public function testConfigExists(): void
    {
        $this->assertFileExists($this->root . 'includes/config.php');
    }

    public function testDbExists(): void
    {
        $this->assertFileExists($this->root . 'includes/db.php');
    }

    public function testFunctionsExists(): void
    {
        $this->assertFileExists($this->root . 'includes/functions.php');
    }

    public function testAuthExists(): void
    {
        $this->assertFileExists($this->root . 'includes/auth.php');
    }

    public function testMinecraftPingExists(): void
    {
        $this->assertFileExists($this->root . 'includes/minecraft_ping.php');
    }

    public function testHeaderExists(): void
    {
        $this->assertFileExists($this->root . 'includes/header.php');
    }

    public function testFooterExists(): void
    {
        $this->assertFileExists($this->root . 'includes/footer.php');
    }

    // ==========================================
    // Публичные страницы
    // ==========================================

    public function testIndexExists(): void
    {
        $this->assertFileExists($this->root . 'index.php');
    }

    public function testServersExists(): void
    {
        $this->assertFileExists($this->root . 'servers.php');
    }

    public function testServerExists(): void
    {
        $this->assertFileExists($this->root . 'server.php');
    }

    public function testLoginExists(): void
    {
        $this->assertFileExists($this->root . 'login.php');
    }

    public function testRegisterExists(): void
    {
        $this->assertFileExists($this->root . 'register.php');
    }

    public function testLogoutExists(): void
    {
        $this->assertFileExists($this->root . 'logout.php');
    }

    public function testVoteExists(): void
    {
        $this->assertFileExists($this->root . 'vote.php');
    }

    public function testReviewExists(): void
    {
        $this->assertFileExists($this->root . 'review.php');
    }

    public function testReportExists(): void
    {
        $this->assertFileExists($this->root . 'report.php');
    }

    public function testPageExists(): void
    {
        $this->assertFileExists($this->root . 'page.php');
    }

    // ==========================================
    // Dashboard
    // ==========================================

    public function testDashboardIndexExists(): void
    {
        $this->assertFileExists($this->root . 'dashboard/index.php');
    }

    public function testDashboardAddExists(): void
    {
        $this->assertFileExists($this->root . 'dashboard/add.php');
    }

    public function testDashboardEditExists(): void
    {
        $this->assertFileExists($this->root . 'dashboard/edit.php');
    }

    public function testDashboardStatsExists(): void
    {
        $this->assertFileExists($this->root . 'dashboard/stats.php');
    }

    public function testDashboardSettingsExists(): void
    {
        $this->assertFileExists($this->root . 'dashboard/settings.php');
    }

    // ==========================================
    // Admin
    // ==========================================

    public function testAdminIndexExists(): void
    {
        $this->assertFileExists($this->root . 'admin/index.php');
    }

    public function testAdminServersExists(): void
    {
        $this->assertFileExists($this->root . 'admin/servers.php');
    }

    public function testAdminUsersExists(): void
    {
        $this->assertFileExists($this->root . 'admin/users.php');
    }

    public function testAdminReportsExists(): void
    {
        $this->assertFileExists($this->root . 'admin/reports.php');
    }

    public function testAdminReviewsExists(): void
    {
        $this->assertFileExists($this->root . 'admin/reviews.php');
    }

    public function testAdminCategoriesExists(): void
    {
        $this->assertFileExists($this->root . 'admin/categories.php');
    }

    public function testAdminSettingsExists(): void
    {
        $this->assertFileExists($this->root . 'admin/settings.php');
    }

    public function testAdminPagesExists(): void
    {
        $this->assertFileExists($this->root . 'admin/pages.php');
    }

    public function testAdminLogExists(): void
    {
        $this->assertFileExists($this->root . 'admin/log.php');
    }

    // ==========================================
    // Cron
    // ==========================================

    public function testCronPingExists(): void
    {
        $this->assertFileExists($this->root . 'cron/ping_servers.php');
    }

    public function testCronResetMonthlyExists(): void
    {
        $this->assertFileExists($this->root . 'cron/reset_monthly.php');
    }

    public function testCronCleanupExists(): void
    {
        $this->assertFileExists($this->root . 'cron/cleanup_stats.php');
    }

    // ==========================================
    // API
    // ==========================================

    public function testApiSearchExists(): void
    {
        $this->assertFileExists($this->root . 'api/search.php');
    }

    public function testApiServerStatusExists(): void
    {
        $this->assertFileExists($this->root . 'api/server_status.php');
    }

    // ==========================================
    // Assets
    // ==========================================

    public function testStyleCssExists(): void
    {
        $this->assertFileExists($this->root . 'assets/css/style.css');
    }

    public function testAdminCssExists(): void
    {
        $this->assertFileExists($this->root . 'assets/css/admin.css');
    }

    public function testAppJsExists(): void
    {
        $this->assertFileExists($this->root . 'assets/js/app.js');
    }

    public function testAdminJsExists(): void
    {
        $this->assertFileExists($this->root . 'assets/js/admin.js');
    }

    // ==========================================
    // Install
    // ==========================================

    public function testDatabaseSqlExists(): void
    {
        $this->assertFileExists($this->root . 'install/database.sql');
    }

    // ==========================================
    // Directories
    // ==========================================

    public function testIconsDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->root . 'assets/img/icons');
    }

    public function testBannersDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->root . 'assets/img/banners');
    }
}
