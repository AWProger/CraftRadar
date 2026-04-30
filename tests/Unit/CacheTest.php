<?php
/**
 * CraftRadar — Тесты файлового кэша (includes/cache.php)
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/cache.php';

class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        // Чистим кэш перед каждым тестом
        cacheClear();
    }

    protected function tearDown(): void
    {
        cacheClear();
    }

    // ==========================================
    // cacheSet / cacheGet
    // ==========================================

    public function testCacheSetAndGet(): void
    {
        cacheSet('test_key', ['foo' => 'bar']);
        $result = cacheGet('test_key');
        $this->assertSame(['foo' => 'bar'], $result);
    }

    public function testCacheGetReturnsNullForMissing(): void
    {
        $this->assertNull(cacheGet('nonexistent_key'));
    }

    public function testCacheSetOverwrites(): void
    {
        cacheSet('key', 'first');
        cacheSet('key', 'second');
        $this->assertSame('second', cacheGet('key'));
    }

    public function testCacheStoresArray(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Server 1'],
            ['id' => 2, 'name' => 'Server 2'],
        ];
        cacheSet('servers', $data);
        $this->assertSame($data, cacheGet('servers'));
    }

    public function testCacheStoresInteger(): void
    {
        cacheSet('count', 42);
        $this->assertSame(42, cacheGet('count'));
    }

    public function testCacheStoresString(): void
    {
        cacheSet('name', 'CraftRadar');
        $this->assertSame('CraftRadar', cacheGet('name'));
    }

    public function testCacheStoresUnicode(): void
    {
        cacheSet('text', 'Привет мир 🎮');
        $this->assertSame('Привет мир 🎮', cacheGet('text'));
    }

    // ==========================================
    // TTL (время жизни)
    // ==========================================

    public function testCacheRespectsTTL(): void
    {
        cacheSet('ttl_test', 'data');
        // С TTL 999999 — должен быть доступен
        $this->assertSame('data', cacheGet('ttl_test', 999999));
    }

    public function testCacheExpiredReturnsNull(): void
    {
        cacheSet('expired', 'data');
        // TTL = 0 — сразу устарел
        $this->assertNull(cacheGet('expired', 0));
    }

    // ==========================================
    // cacheDelete
    // ==========================================

    public function testCacheDelete(): void
    {
        cacheSet('to_delete', 'data');
        cacheDelete('to_delete');
        $this->assertNull(cacheGet('to_delete'));
    }

    public function testCacheDeleteNonExistent(): void
    {
        // Не должно бросать ошибку
        cacheDelete('nonexistent');
        $this->assertNull(cacheGet('nonexistent'));
    }

    // ==========================================
    // cacheClear
    // ==========================================

    public function testCacheClear(): void
    {
        cacheSet('key1', 'a');
        cacheSet('key2', 'b');
        cacheSet('key3', 'c');
        cacheClear();
        $this->assertNull(cacheGet('key1'));
        $this->assertNull(cacheGet('key2'));
        $this->assertNull(cacheGet('key3'));
    }

    // ==========================================
    // cacheRemember
    // ==========================================

    public function testCacheRememberCallsCallbackOnMiss(): void
    {
        $called = false;
        $result = cacheRemember('remember_test', 300, function() use (&$called) {
            $called = true;
            return 'computed_value';
        });

        $this->assertTrue($called);
        $this->assertSame('computed_value', $result);
    }

    public function testCacheRememberReturnsCachedOnHit(): void
    {
        cacheSet('remember_hit', 'cached_value');

        $called = false;
        $result = cacheRemember('remember_hit', 300, function() use (&$called) {
            $called = true;
            return 'new_value';
        });

        $this->assertFalse($called);
        $this->assertSame('cached_value', $result);
    }

    public function testCacheRememberStoresResult(): void
    {
        cacheRemember('stored', 300, function() {
            return ['data' => true];
        });

        $this->assertSame(['data' => true], cacheGet('stored'));
    }

    // ==========================================
    // Директория кэша
    // ==========================================

    public function testCacheDirConstantDefined(): void
    {
        $this->assertTrue(defined('CACHE_DIR'));
    }

    public function testCacheTtlConstantDefined(): void
    {
        $this->assertTrue(defined('CACHE_TTL'));
        $this->assertSame(300, CACHE_TTL);
    }
}
