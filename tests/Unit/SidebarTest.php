<?php
/**
 * CraftRadar — Тесты бокового виджета топ-серверов
 */

use PHPUnit\Framework\TestCase;

class SidebarTest extends TestCase
{
    // ==========================================
    // Конфигурация
    // ==========================================

    public function testSidebarTopCountDefined(): void
    {
        $this->assertTrue(defined('SIDEBAR_TOP_COUNT'));
        $this->assertGreaterThan(0, SIDEBAR_TOP_COUNT);
    }

    public function testSidebarRotateSecondsDefined(): void
    {
        $this->assertTrue(defined('SIDEBAR_ROTATE_SECONDS'));
        $this->assertGreaterThan(0, SIDEBAR_ROTATE_SECONDS);
    }

    public function testSidebarSlideIntervalDefined(): void
    {
        $this->assertTrue(defined('SIDEBAR_SLIDE_INTERVAL'));
        $this->assertGreaterThan(0, SIDEBAR_SLIDE_INTERVAL);
    }

    // ==========================================
    // Файлы
    // ==========================================

    public function testSidebarComponentExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'includes/sidebar_top.php');
    }

    public function testTopServersApiExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'api/top_servers.php');
    }

    // ==========================================
    // Sidebar PHP содержит нужные элементы
    // ==========================================

    public function testSidebarHasCarousel(): void
    {
        $content = file_get_contents(ROOT_PATH . 'includes/sidebar_top.php');
        $this->assertStringContainsString('sidebarCarousel', $content);
        $this->assertStringContainsString('sidebarTrack', $content);
    }

    public function testSidebarHasControls(): void
    {
        $content = file_get_contents(ROOT_PATH . 'includes/sidebar_top.php');
        $this->assertStringContainsString('sidebarPrev', $content);
        $this->assertStringContainsString('sidebarNext', $content);
    }

    public function testSidebarShowsServerInfo(): void
    {
        $content = file_get_contents(ROOT_PATH . 'includes/sidebar_top.php');
        $this->assertStringContainsString('sidebar-server-name', $content);
        $this->assertStringContainsString('sidebar-server-motd', $content);
        $this->assertStringContainsString('sidebar-server-icon', $content);
        $this->assertStringContainsString('sidebar-server-stats', $content);
    }

    public function testSidebarShowsOnlineStatus(): void
    {
        $content = file_get_contents(ROOT_PATH . 'includes/sidebar_top.php');
        $this->assertStringContainsString('players_online', $content);
        $this->assertStringContainsString('sidebar-online-dot', $content);
    }

    public function testSidebarShowsVerifiedAndPromoted(): void
    {
        $content = file_get_contents(ROOT_PATH . 'includes/sidebar_top.php');
        $this->assertStringContainsString('is_verified', $content);
        $this->assertStringContainsString('is_promoted', $content);
        $this->assertStringContainsString('is_highlighted', $content);
    }

    public function testSidebarUsesConstants(): void
    {
        $content = file_get_contents(ROOT_PATH . 'includes/sidebar_top.php');
        $this->assertStringContainsString('SIDEBAR_TOP_COUNT', $content);
        $this->assertStringContainsString('SIDEBAR_ROTATE_SECONDS', $content);
    }

    public function testSidebarHasDots(): void
    {
        $content = file_get_contents(ROOT_PATH . 'includes/sidebar_top.php');
        $this->assertStringContainsString('sidebarDots', $content);
    }

    public function testSidebarHasAllServersLink(): void
    {
        $content = file_get_contents(ROOT_PATH . 'includes/sidebar_top.php');
        $this->assertStringContainsString('servers.php', $content);
        $this->assertStringContainsString('Все серверы', $content);
    }

    // ==========================================
    // API
    // ==========================================

    public function testApiUsesCache(): void
    {
        $content = file_get_contents(ROOT_PATH . 'api/top_servers.php');
        $this->assertStringContainsString('cacheRemember', $content);
    }

    public function testApiReturnsJson(): void
    {
        $content = file_get_contents(ROOT_PATH . 'api/top_servers.php');
        $this->assertStringContainsString('application/json', $content);
        $this->assertStringContainsString('json_encode', $content);
    }

    public function testApiUsesConstant(): void
    {
        $content = file_get_contents(ROOT_PATH . 'api/top_servers.php');
        $this->assertStringContainsString('SIDEBAR_TOP_COUNT', $content);
    }

    // ==========================================
    // JS карусель
    // ==========================================

    public function testJsHasCarouselCode(): void
    {
        $content = file_get_contents(ROOT_PATH . 'assets/js/app.js');
        $this->assertStringContainsString('sidebarCarousel', $content);
        $this->assertStringContainsString('goToPage', $content);
        $this->assertStringContainsString('autoSlideTimer', $content);
    }

    public function testJsHasAutoRefresh(): void
    {
        $content = file_get_contents(ROOT_PATH . 'assets/js/app.js');
        $this->assertStringContainsString('refreshSidebarData', $content);
        $this->assertStringContainsString('top_servers.php', $content);
    }

    public function testJsPausesOnHover(): void
    {
        $content = file_get_contents(ROOT_PATH . 'assets/js/app.js');
        $this->assertStringContainsString('mouseenter', $content);
        $this->assertStringContainsString('mouseleave', $content);
    }

    // ==========================================
    // CSS
    // ==========================================

    public function testCssHasSidebarStyles(): void
    {
        $css = file_get_contents(ROOT_PATH . 'assets/css/style.css');
        $this->assertStringContainsString('.sidebar-top', $css);
        $this->assertStringContainsString('.sidebar-server', $css);
        $this->assertStringContainsString('.sidebar-top-carousel', $css);
    }

    public function testCssHidesOnSmallScreens(): void
    {
        $css = file_get_contents(ROOT_PATH . 'assets/css/style.css');
        $this->assertStringContainsString('1200px', $css);
        $this->assertStringContainsString('display: none', $css);
    }

    // ==========================================
    // Footer подключает сайдбар
    // ==========================================

    public function testFooterIncludesSidebar(): void
    {
        $content = file_get_contents(ROOT_PATH . 'includes/footer.php');
        $this->assertStringContainsString('sidebar_top.php', $content);
    }

    public function testFooterPassesConfigToJs(): void
    {
        $content = file_get_contents(ROOT_PATH . 'includes/footer.php');
        $this->assertStringContainsString('siteUrl', $content);
        $this->assertStringContainsString('SIDEBAR_SLIDE_INTERVAL', $content);
    }
}
