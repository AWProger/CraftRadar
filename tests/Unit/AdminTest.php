<?php
/**
 * CraftRadar — Тесты админ-панели
 */

use PHPUnit\Framework\TestCase;

class AdminTest extends TestCase
{
    // ==========================================
    // Конфигурация
    // ==========================================

    public function testAdminPerPageDefined(): void
    {
        $this->assertTrue(defined('ADMIN_PER_PAGE'));
        $this->assertGreaterThan(0, ADMIN_PER_PAGE);
    }

    public function testStatsPeriodDaysDefined(): void
    {
        $this->assertTrue(defined('STATS_PERIOD_DAYS'));
        $this->assertGreaterThan(0, STATS_PERIOD_DAYS);
    }

    // ==========================================
    // Файлы админки существуют
    // ==========================================

    public function testAdminDashboardExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'admin/index.php');
    }

    public function testAdminServersExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'admin/servers.php');
        $this->assertFileExists(ROOT_PATH . 'admin/server_view.php');
    }

    public function testAdminUsersExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'admin/users.php');
        $this->assertFileExists(ROOT_PATH . 'admin/user_view.php');
    }

    public function testAdminReportsExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'admin/reports.php');
    }

    public function testAdminReviewsExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'admin/reviews.php');
    }

    public function testAdminCategoriesExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'admin/categories.php');
    }

    public function testAdminSettingsExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'admin/settings.php');
    }

    public function testAdminPagesExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'admin/pages.php');
        $this->assertFileExists(ROOT_PATH . 'admin/page_edit.php');
    }

    public function testAdminLogExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'admin/log.php');
    }

    public function testAdminPaymentsExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'admin/payments.php');
    }

    // ==========================================
    // Нет хардкода perPage = 50
    // ==========================================

    public function testNoHardcodedPerPage(): void
    {
        $files = ['admin/servers.php', 'admin/users.php', 'admin/reports.php',
                   'admin/reviews.php', 'admin/payments.php', 'admin/log.php'];

        foreach ($files as $file) {
            $content = file_get_contents(ROOT_PATH . $file);
            $this->assertStringNotContainsString('$perPage = 50', $content, "Хардкод perPage=50 в {$file}");
            $this->assertStringContainsString('ADMIN_PER_PAGE', $content, "Нет ADMIN_PER_PAGE в {$file}");
        }
    }

    // ==========================================
    // Дашборд — prepared statements
    // ==========================================

    public function testDashboardUsesPreparedStatements(): void
    {
        $content = file_get_contents(ROOT_PATH . 'admin/index.php');
        // Не должно быть прямой подстановки переменных в SQL
        $this->assertStringNotContainsString("'{$today}'", $content);
        $this->assertStringContainsString('$stmt->execute', $content);
    }

    // ==========================================
    // Дашборд — расширенная статистика
    // ==========================================

    public function testDashboardHasRevenueStats(): void
    {
        $content = file_get_contents(ROOT_PATH . 'admin/index.php');
        $this->assertStringContainsString('revenueToday', $content);
        $this->assertStringContainsString('revenueMonth', $content);
        $this->assertStringContainsString('revenueTotal', $content);
        $this->assertStringContainsString('avgCheck', $content);
    }

    public function testDashboardHasTopServers(): void
    {
        $content = file_get_contents(ROOT_PATH . 'admin/index.php');
        $this->assertStringContainsString('topServers', $content);
        $this->assertStringContainsString('Топ-5', $content);
    }

    public function testDashboardHasCategoryStats(): void
    {
        $content = file_get_contents(ROOT_PATH . 'admin/index.php');
        $this->assertStringContainsString('categoryStats', $content);
        $this->assertStringContainsString('категориям', $content);
    }

    public function testDashboardHasRatingDistribution(): void
    {
        $content = file_get_contents(ROOT_PATH . 'admin/index.php');
        $this->assertStringContainsString('ratingDistribution', $content);
        $this->assertStringContainsString('оценок', $content);
    }

    public function testDashboardHasVerifiedCount(): void
    {
        $content = file_get_contents(ROOT_PATH . 'admin/index.php');
        $this->assertStringContainsString('verifiedServers', $content);
    }

    public function testDashboardHasPendingPayments(): void
    {
        $content = file_get_contents(ROOT_PATH . 'admin/index.php');
        $this->assertStringContainsString('pendingPayments', $content);
    }

    public function testDashboardHasCharts(): void
    {
        $content = file_get_contents(ROOT_PATH . 'admin/index.php');
        $this->assertStringContainsString('chartRegistrations', $content);
        $this->assertStringContainsString('chartServers', $content);
        $this->assertStringContainsString('chartVotes', $content);
        $this->assertStringContainsString('chartRevenue', $content);
        $this->assertStringContainsString('Chart.js', $content);
    }

    public function testDashboardUsesStatsPeriodConstant(): void
    {
        $content = file_get_contents(ROOT_PATH . 'admin/index.php');
        $this->assertStringContainsString('STATS_PERIOD_DAYS', $content);
    }

    // ==========================================
    // admin.js — обработчики
    // ==========================================

    public function testAdminJsHasConfirmHandler(): void
    {
        $content = file_get_contents(ROOT_PATH . 'assets/js/admin.js');
        $this->assertStringContainsString('data-confirm', $content);
        $this->assertStringContainsString('confirm(', $content);
    }

    public function testAdminJsHasSelectAll(): void
    {
        $content = file_get_contents(ROOT_PATH . 'assets/js/admin.js');
        $this->assertStringContainsString('selectAll', $content);
        $this->assertStringContainsString('row-checkbox', $content);
    }

    public function testAdminJsHasAlertAutoHide(): void
    {
        $content = file_get_contents(ROOT_PATH . 'assets/js/admin.js');
        $this->assertStringContainsString('.alert', $content);
        $this->assertStringContainsString('opacity', $content);
    }

    // ==========================================
    // admin.css — Minecraft стиль
    // ==========================================

    public function testAdminCssHasPixelBorder(): void
    {
        $content = file_get_contents(ROOT_PATH . 'assets/css/admin.css');
        $this->assertStringContainsString('pixel-border', $content);
    }

    public function testAdminCssHasMinecraftFont(): void
    {
        $content = file_get_contents(ROOT_PATH . 'assets/css/admin.css');
        $this->assertStringContainsString('font-mc', $content);
    }

    public function testAdminCssHasShadows(): void
    {
        $content = file_get_contents(ROOT_PATH . 'assets/css/admin.css');
        $this->assertStringContainsString('box-shadow', $content);
    }

    // ==========================================
    // Безопасность
    // ==========================================

    public function testAdminAuthHasAdminLog(): void
    {
        $content = file_get_contents(ROOT_PATH . 'admin/includes/admin_auth.php');
        $this->assertStringContainsString('function adminLog', $content);
    }

    public function testAdminAuthChecksRole(): void
    {
        $content = file_get_contents(ROOT_PATH . 'admin/includes/admin_auth.php');
        $this->assertStringContainsString('isModerator', $content);
    }

    public function testAdminHeaderHasPaymentsLink(): void
    {
        $content = file_get_contents(ROOT_PATH . 'admin/includes/admin_header.php');
        $this->assertStringContainsString('payments.php', $content);
    }
}
