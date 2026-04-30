<?php
/**
 * CraftRadar — Тесты UX-улучшений (Этап 9)
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/notifications.php';

class UxImprovementsTest extends TestCase
{
    // ==========================================
    // Уведомления
    // ==========================================

    public function testNotificationsFileExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'includes/notifications.php');
    }

    public function testNotificationsPageExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'dashboard/notifications.php');
    }

    public function testNotificationsTableInSQL(): void
    {
        $sql = file_get_contents(ROOT_PATH . 'install/database.sql');
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS notifications', $sql);
        $this->assertStringContainsString('is_read', $sql);
        $this->assertStringContainsString('type VARCHAR(50)', $sql);
    }

    public function testNotificationFunctionsExist(): void
    {
        $this->assertTrue(function_exists('createNotification'));
        $this->assertTrue(function_exists('getUnreadNotifications'));
        $this->assertTrue(function_exists('getAllNotifications'));
        $this->assertTrue(function_exists('getUnreadCount'));
        $this->assertTrue(function_exists('markNotificationRead'));
        $this->assertTrue(function_exists('markAllNotificationsRead'));
        $this->assertTrue(function_exists('notificationIcon'));
    }

    public function testNotificationIconReturnsEmoji(): void
    {
        $this->assertSame('🔴', notificationIcon('server_offline'));
        $this->assertSame('✅', notificationIcon('server_approved'));
        $this->assertSame('❌', notificationIcon('server_rejected'));
        $this->assertSame('💬', notificationIcon('new_review'));
        $this->assertSame('💰', notificationIcon('payment_completed'));
        $this->assertSame('🔔', notificationIcon('unknown_type'));
    }

    public function testHeaderHasNotificationBell(): void
    {
        $content = file_get_contents(ROOT_PATH . 'includes/header.php');
        $this->assertStringContainsString('notifications.php', $content);
        $this->assertStringContainsString('header-notif', $content);
        $this->assertStringContainsString('getUnreadCount', $content);
    }

    // ==========================================
    // Живой поиск
    // ==========================================

    public function testSearchInputHasLiveSearchAttribute(): void
    {
        $content = file_get_contents(ROOT_PATH . 'index.php');
        $this->assertStringContainsString('data-live-search', $content);
    }

    public function testAppJsHasLiveSearch(): void
    {
        $content = file_get_contents(ROOT_PATH . 'assets/js/app.js');
        $this->assertStringContainsString('data-live-search', $content);
        $this->assertStringContainsString('search-dropdown', $content);
        $this->assertStringContainsString('fetch(', $content);
    }

    public function testCssHasSearchDropdown(): void
    {
        $content = file_get_contents(ROOT_PATH . 'assets/css/style.css');
        $this->assertStringContainsString('.search-dropdown', $content);
        $this->assertStringContainsString('.search-dropdown-item', $content);
    }

    // ==========================================
    // Виджеты/баннеры
    // ==========================================

    public function testWidgetApiExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'api/widget.php');
    }

    public function testWidgetSupportsJsFormat(): void
    {
        $content = file_get_contents(ROOT_PATH . 'api/widget.php');
        $this->assertStringContainsString('application/javascript', $content);
    }

    public function testWidgetSupportsHtmlFormat(): void
    {
        $content = file_get_contents(ROOT_PATH . 'api/widget.php');
        $this->assertStringContainsString('text/html', $content);
        $this->assertStringContainsString('format', $content);
    }

    public function testServerPageHasWidgetBlock(): void
    {
        $content = file_get_contents(ROOT_PATH . 'server.php');
        $this->assertStringContainsString('widget.php', $content);
        $this->assertStringContainsString('Виджет для сайта', $content);
    }

    // ==========================================
    // Переключение графиков
    // ==========================================

    public function testChartApiExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'api/server_chart.php');
    }

    public function testChartApiSupportsPeriods(): void
    {
        $content = file_get_contents(ROOT_PATH . 'api/server_chart.php');
        $this->assertStringContainsString("'24h'", $content);
        $this->assertStringContainsString("'7d'", $content);
        $this->assertStringContainsString("'30d'", $content);
    }

    public function testServerPageHasChartTabs(): void
    {
        $content = file_get_contents(ROOT_PATH . 'server.php');
        $this->assertStringContainsString('chart-tab', $content);
        $this->assertStringContainsString('data-period', $content);
        $this->assertStringContainsString('loadChart', $content);
    }

    public function testCssHasChartTabs(): void
    {
        $content = file_get_contents(ROOT_PATH . 'assets/css/style.css');
        $this->assertStringContainsString('.chart-tab', $content);
    }
}
