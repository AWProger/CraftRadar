<?php
/**
 * CraftRadar — Тесты класса MinecraftPing (includes/minecraft_ping.php)
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/minecraft_ping.php';

class MinecraftPingTest extends TestCase
{
    // ==========================================
    // Создание экземпляра
    // ==========================================

    public function testCanCreateInstance(): void
    {
        $ping = new MinecraftPing('play.example.com', 25565, 5);
        $this->assertInstanceOf(MinecraftPing::class, $ping);
    }

    public function testCanCreateWithDefaultPort(): void
    {
        $ping = new MinecraftPing('play.example.com');
        $this->assertInstanceOf(MinecraftPing::class, $ping);
    }

    // ==========================================
    // Пинг несуществующего сервера
    // ==========================================

    public function testPingNonExistentServerReturnsFalse(): void
    {
        $ping = new MinecraftPing('192.0.2.1', 25565, 2); // TEST-NET, не маршрутизируется
        $result = $ping->ping();
        $this->assertFalse($result);
    }

    public function testPingInvalidHostReturnsFalse(): void
    {
        $ping = new MinecraftPing('this-host-does-not-exist-craftradar.invalid', 25565, 2);
        $result = $ping->ping();
        $this->assertFalse($result);
    }

    // ==========================================
    // Функция-обёртка pingMinecraftServer()
    // ==========================================

    public function testPingMinecraftServerFunctionExists(): void
    {
        $this->assertTrue(function_exists('pingMinecraftServer'));
    }

    public function testPingMinecraftServerReturnsFalseForInvalid(): void
    {
        $result = pingMinecraftServer('192.0.2.1', 25565, 2);
        $this->assertFalse($result);
    }

    // ==========================================
    // Парсинг MOTD (через рефлексию)
    // ==========================================

    public function testParseMotdString(): void
    {
        $ping = new MinecraftPing('localhost');
        $method = new ReflectionMethod(MinecraftPing::class, 'parseMotd');
        $method->setAccessible(true);

        $result = $method->invoke($ping, '§aHello §bWorld');
        $this->assertSame('Hello World', $result);
    }

    public function testParseMotdArray(): void
    {
        $ping = new MinecraftPing('localhost');
        $method = new ReflectionMethod(MinecraftPing::class, 'parseMotd');
        $method->setAccessible(true);

        $result = $method->invoke($ping, [
            'text' => 'Hello ',
            'extra' => [
                ['text' => 'World']
            ]
        ]);
        $this->assertSame('Hello World', $result);
    }

    public function testParseMotdEmptyArray(): void
    {
        $ping = new MinecraftPing('localhost');
        $method = new ReflectionMethod(MinecraftPing::class, 'parseMotd');
        $method->setAccessible(true);

        $result = $method->invoke($ping, []);
        $this->assertSame('', $result);
    }

    public function testParseMotdNull(): void
    {
        $ping = new MinecraftPing('localhost');
        $method = new ReflectionMethod(MinecraftPing::class, 'parseMotd');
        $method->setAccessible(true);

        $result = $method->invoke($ping, null);
        $this->assertSame('', $result);
    }

    // ==========================================
    // Удаление цветовых кодов
    // ==========================================

    public function testStripMinecraftColors(): void
    {
        $ping = new MinecraftPing('localhost');
        $method = new ReflectionMethod(MinecraftPing::class, 'stripMinecraftColors');
        $method->setAccessible(true);

        $this->assertSame('Hello World', $method->invoke($ping, '§aHello §cWorld'));
        $this->assertSame('Bold Italic', $method->invoke($ping, '§lBold §oItalic'));
        $this->assertSame('Reset', $method->invoke($ping, '§rReset'));
        $this->assertSame('Plain text', $method->invoke($ping, 'Plain text'));
    }

    // ==========================================
    // VarInt упаковка (через рефлексию)
    // ==========================================

    public function testPackVarIntZero(): void
    {
        $ping = new MinecraftPing('localhost');
        $method = new ReflectionMethod(MinecraftPing::class, 'packVarInt');
        $method->setAccessible(true);

        $result = $method->invoke($ping, 0);
        $this->assertSame("\x00", $result);
    }

    public function testPackVarIntSmall(): void
    {
        $ping = new MinecraftPing('localhost');
        $method = new ReflectionMethod(MinecraftPing::class, 'packVarInt');
        $method->setAccessible(true);

        $result = $method->invoke($ping, 1);
        $this->assertSame("\x01", $result);
    }

    public function testPackVarInt127(): void
    {
        $ping = new MinecraftPing('localhost');
        $method = new ReflectionMethod(MinecraftPing::class, 'packVarInt');
        $method->setAccessible(true);

        $result = $method->invoke($ping, 127);
        $this->assertSame("\x7F", $result);
    }

    public function testPackVarInt128(): void
    {
        $ping = new MinecraftPing('localhost');
        $method = new ReflectionMethod(MinecraftPing::class, 'packVarInt');
        $method->setAccessible(true);

        $result = $method->invoke($ping, 128);
        $this->assertSame("\x80\x01", $result);
    }

    public function testPackVarIntNegativeOne(): void
    {
        $ping = new MinecraftPing('localhost');
        $method = new ReflectionMethod(MinecraftPing::class, 'packVarInt');
        $method->setAccessible(true);

        $result = $method->invoke($ping, -1);
        $this->assertSame("\xFF\xFF\xFF\xFF\x0F", $result);
    }
}
