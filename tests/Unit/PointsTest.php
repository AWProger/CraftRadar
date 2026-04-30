<?php
/**
 * CraftRadar — Тесты системы баллов
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/points.php';

class PointsTest extends TestCase
{
    // ==========================================
    // Файлы и функции
    // ==========================================

    public function testPointsFileExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'includes/points.php');
    }

    public function testHighlightPageExists(): void
    {
        $this->assertFileExists(ROOT_PATH . 'dashboard/highlight.php');
    }

    public function testFunctionsExist(): void
    {
        $this->assertTrue(function_exists('getUserPoints'));
        $this->assertTrue(function_exists('addPoints'));
        $this->assertTrue(function_exists('rewardVotePoints'));
        $this->assertTrue(function_exists('highlightServer'));
        $this->assertTrue(function_exists('isHighlighted'));
        $this->assertTrue(function_exists('getPointHistory'));
    }

    // ==========================================
    // isHighlighted()
    // ==========================================

    public function testIsHighlightedReturnsFalseForNull(): void
    {
        $this->assertFalse(isHighlighted(['highlighted_until' => null]));
    }

    public function testIsHighlightedReturnsFalseForPast(): void
    {
        $this->assertFalse(isHighlighted(['highlighted_until' => '2020-01-01 00:00:00']));
    }

    public function testIsHighlightedReturnsTrueForFuture(): void
    {
        $this->assertTrue(isHighlighted(['highlighted_until' => '2099-01-01 00:00:00']));
    }

    // ==========================================
    // БД
    // ==========================================

    public function testPointTransactionsTableInSQL(): void
    {
        $sql = file_get_contents(ROOT_PATH . 'install/database.sql');
        $this->assertStringContainsString('point_transactions', $sql);
        $this->assertStringContainsString('vote_reward', $sql);
        $this->assertStringContainsString('highlight_spend', $sql);
    }

    public function testHighlightedUntilFieldInSQL(): void
    {
        $sql = file_get_contents(ROOT_PATH . 'install/database.sql');
        $this->assertStringContainsString('highlighted_until', $sql);
    }

    public function testPointsSettingsInSQL(): void
    {
        $sql = file_get_contents(ROOT_PATH . 'install/database.sql');
        $this->assertStringContainsString('points_per_vote', $sql);
        $this->assertStringContainsString('highlight_cost_1h', $sql);
        $this->assertStringContainsString('highlight_cost_6h', $sql);
        $this->assertStringContainsString('highlight_cost_24h', $sql);
    }

    // ==========================================
    // Интеграция с голосованием
    // ==========================================

    public function testVotePhpIncludesPoints(): void
    {
        $content = file_get_contents(ROOT_PATH . 'vote.php');
        $this->assertStringContainsString('rewardVotePoints', $content);
        $this->assertStringContainsString('points.php', $content);
    }

    // ==========================================
    // Баллы в header
    // ==========================================

    public function testHeaderShowsPoints(): void
    {
        $content = file_get_contents(ROOT_PATH . 'includes/header.php');
        $this->assertStringContainsString('points-display', $content);
        $this->assertStringContainsString('getUserPoints', $content);
    }

    // ==========================================
    // Каталог — highlighted серверы
    // ==========================================

    public function testCatalogHasHighlightedClass(): void
    {
        // Карточки теперь рендерятся через components.php
        $content = file_get_contents(ROOT_PATH . 'includes/components.php');
        $this->assertStringContainsString('server-card-highlighted', $content);
        $this->assertStringContainsString('highlighted_until', $content);
    }

    public function testCatalogSortsHighlightedFirst(): void
    {
        $content = file_get_contents(ROOT_PATH . 'servers.php');
        $this->assertStringContainsString('highlighted_until > NOW()', $content);
    }

    // ==========================================
    // CSS — Minecraft стиль
    // ==========================================

    public function testCssHasMinecraftFont(): void
    {
        $css = file_get_contents(ROOT_PATH . 'assets/css/style.css');
        $this->assertStringContainsString('Press Start 2P', $css);
    }

    public function testCssHasBlockyStyle(): void
    {
        $css = file_get_contents(ROOT_PATH . 'assets/css/style.css');
        $this->assertStringContainsString('--radius: 0px', $css);
        $this->assertStringContainsString('pixel-border', $css);
    }

    public function testCssHasMinecraftColors(): void
    {
        $css = file_get_contents(ROOT_PATH . 'assets/css/style.css');
        $this->assertStringContainsString('--gold', $css);
        $this->assertStringContainsString('--diamond', $css);
        $this->assertStringContainsString('--emerald', $css);
        $this->assertStringContainsString('--redstone', $css);
    }

    public function testCssHasHighlightedCard(): void
    {
        $css = file_get_contents(ROOT_PATH . 'assets/css/style.css');
        $this->assertStringContainsString('.server-card-highlighted', $css);
        $this->assertStringContainsString('.server-card-promoted', $css);
    }

    public function testCssHasPointsDisplay(): void
    {
        $css = file_get_contents(ROOT_PATH . 'assets/css/style.css');
        $this->assertStringContainsString('.points-display', $css);
    }
}
