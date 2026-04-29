<?php
/**
 * CraftRadar — Тесты SQL-схемы (install/database.sql)
 * 
 * Проверяем, что SQL-файл корректен и содержит все необходимые таблицы.
 */

use PHPUnit\Framework\TestCase;

class DatabaseSchemaTest extends TestCase
{
    private string $sql;

    protected function setUp(): void
    {
        $path = __DIR__ . '/../../install/database.sql';
        $this->assertTrue(file_exists($path), 'database.sql должен существовать');
        $this->sql = file_get_contents($path);
    }

    // ==========================================
    // Проверка наличия всех таблиц
    // ==========================================

    public function testUsersTableExists(): void
    {
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS users', $this->sql);
    }

    public function testServersTableExists(): void
    {
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS servers', $this->sql);
    }

    public function testVotesTableExists(): void
    {
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS votes', $this->sql);
    }

    public function testServerStatsTableExists(): void
    {
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS server_stats', $this->sql);
    }

    public function testCategoriesTableExists(): void
    {
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS categories', $this->sql);
    }

    public function testReviewsTableExists(): void
    {
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS reviews', $this->sql);
    }

    public function testReportsTableExists(): void
    {
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS reports', $this->sql);
    }

    public function testAdminLogTableExists(): void
    {
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS admin_log', $this->sql);
    }

    public function testSettingsTableExists(): void
    {
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS settings', $this->sql);
    }

    public function testPagesTableExists(): void
    {
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS pages', $this->sql);
    }

    // ==========================================
    // Проверка ключевых полей
    // ==========================================

    public function testUsersHasPasswordHash(): void
    {
        $this->assertStringContainsString('password_hash', $this->sql);
    }

    public function testUsersHasRoleEnum(): void
    {
        $this->assertMatchesRegularExpression("/role\s+ENUM\('user',\s*'moderator',\s*'admin'\)/", $this->sql);
    }

    public function testServersHasStatusEnum(): void
    {
        $this->assertMatchesRegularExpression("/status\s+ENUM\('active',\s*'pending',\s*'rejected',\s*'banned'\)/", $this->sql);
    }

    public function testReviewsHasUniqueIndex(): void
    {
        $this->assertStringContainsString('UNIQUE INDEX idx_server_user (server_id, user_id)', $this->sql);
    }

    // ==========================================
    // Проверка начальных данных
    // ==========================================

    public function testDefaultCategoriesInserted(): void
    {
        $this->assertStringContainsString("INSERT INTO categories", $this->sql);
        $this->assertStringContainsString("'Анархия'", $this->sql);
        $this->assertStringContainsString("'Ванилла'", $this->sql);
        $this->assertStringContainsString("'PvP'", $this->sql);
    }

    public function testDefaultSettingsInserted(): void
    {
        $this->assertStringContainsString("INSERT INTO settings", $this->sql);
        $this->assertStringContainsString("'site_name'", $this->sql);
        $this->assertStringContainsString("'CraftRadar'", $this->sql);
    }

    public function testDefaultPagesInserted(): void
    {
        $this->assertStringContainsString("INSERT INTO pages", $this->sql);
        $this->assertStringContainsString("'about'", $this->sql);
        $this->assertStringContainsString("'rules'", $this->sql);
        $this->assertStringContainsString("'faq'", $this->sql);
    }

    // ==========================================
    // Проверка безопасности схемы
    // ==========================================

    public function testUsesInnoDB(): void
    {
        $this->assertStringContainsString('ENGINE=InnoDB', $this->sql);
    }

    public function testUsesUtf8mb4(): void
    {
        $this->assertStringContainsString('utf8mb4', $this->sql);
    }

    public function testHasForeignKeys(): void
    {
        $this->assertStringContainsString('FOREIGN KEY', $this->sql);
    }

    public function testHasIndexes(): void
    {
        $this->assertStringContainsString('INDEX', $this->sql);
    }
}
