<?php
/**
 * CraftRadar — Тесты вспомогательных функций (includes/functions.php)
 */

use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    // ==========================================
    // e() — экранирование XSS
    // ==========================================

    public function testEscapeHtmlTags(): void
    {
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', e('<script>alert(1)</script>'));
    }

    public function testEscapeQuotes(): void
    {
        $this->assertSame('&quot;hello&quot; &amp; &#039;world&#039;', e('"hello" & \'world\''));
    }

    public function testEscapeEmptyString(): void
    {
        $this->assertSame('', e(''));
    }

    public function testEscapePlainText(): void
    {
        $this->assertSame('Hello World', e('Hello World'));
    }

    // ==========================================
    // truncate() — обрезка текста
    // ==========================================

    public function testTruncateShortText(): void
    {
        $this->assertSame('Hello', truncate('Hello', 10));
    }

    public function testTruncateLongText(): void
    {
        $result = truncate('Hello World, this is a long text', 10);
        $this->assertSame('Hello Worl...', $result);
    }

    public function testTruncateExactLength(): void
    {
        $this->assertSame('Hello', truncate('Hello', 5));
    }

    // ==========================================
    // now() — текущая дата
    // ==========================================

    public function testNowReturnsValidDateFormat(): void
    {
        $result = now();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);
    }

    // ==========================================
    // formatDate() — форматирование даты
    // ==========================================

    public function testFormatDate(): void
    {
        $result = formatDate('2026-04-30 14:30:00');
        $this->assertSame('30.04.2026 14:30', $result);
    }

    // ==========================================
    // isValidServerIP() — валидация IP серверов
    // ==========================================

    public function testValidPublicIP(): void
    {
        $this->assertTrue(isValidServerIP('8.8.8.8'));
    }

    public function testValidDomain(): void
    {
        $this->assertTrue(isValidServerIP('play.example.com'));
    }

    public function testValidSubdomain(): void
    {
        $this->assertTrue(isValidServerIP('mc.server.example.com'));
    }

    public function testInvalidLocalhost(): void
    {
        $this->assertFalse(isValidServerIP('localhost'));
    }

    public function testInvalidLoopback(): void
    {
        $this->assertFalse(isValidServerIP('127.0.0.1'));
    }

    public function testInvalidIPv6Loopback(): void
    {
        $this->assertFalse(isValidServerIP('::1'));
    }

    public function testInvalidZeroIP(): void
    {
        $this->assertFalse(isValidServerIP('0.0.0.0'));
    }

    public function testInvalidPrivateIP192(): void
    {
        $this->assertFalse(isValidServerIP('192.168.1.1'));
    }

    public function testInvalidPrivateIP10(): void
    {
        $this->assertFalse(isValidServerIP('10.0.0.1'));
    }

    public function testInvalidPrivateIP172(): void
    {
        $this->assertFalse(isValidServerIP('172.16.0.1'));
    }

    // ==========================================
    // isValidPort() — валидация порта
    // ==========================================

    public function testValidDefaultPort(): void
    {
        $this->assertTrue(isValidPort(25565));
    }

    public function testValidMinPort(): void
    {
        $this->assertTrue(isValidPort(1));
    }

    public function testValidMaxPort(): void
    {
        $this->assertTrue(isValidPort(65535));
    }

    public function testInvalidZeroPort(): void
    {
        $this->assertFalse(isValidPort(0));
    }

    public function testInvalidNegativePort(): void
    {
        $this->assertFalse(isValidPort(-1));
    }

    public function testInvalidTooHighPort(): void
    {
        $this->assertFalse(isValidPort(65536));
    }

    // ==========================================
    // CSRF — генерация и проверка токенов
    // ==========================================

    public function testCsrfTokenGeneration(): void
    {
        $token = generateCsrfToken();
        $this->assertNotEmpty($token);
        $this->assertSame(64, strlen($token)); // 32 bytes = 64 hex chars
    }

    public function testCsrfTokenConsistency(): void
    {
        $token1 = generateCsrfToken();
        $token2 = generateCsrfToken();
        $this->assertSame($token1, $token2); // Один и тот же в рамках сессии
    }

    public function testCsrfTokenVerificationValid(): void
    {
        $token = generateCsrfToken();
        $this->assertTrue(verifyCsrfToken($token));
    }

    public function testCsrfTokenVerificationInvalid(): void
    {
        generateCsrfToken();
        $this->assertFalse(verifyCsrfToken('invalid_token'));
    }

    public function testCsrfTokenVerificationEmpty(): void
    {
        $this->assertFalse(verifyCsrfToken(''));
    }

    // ==========================================
    // csrfField() — HTML поле
    // ==========================================

    public function testCsrfFieldContainsInput(): void
    {
        $field = csrfField();
        $this->assertStringContainsString('<input type="hidden"', $field);
        $this->assertStringContainsString('name="' . CSRF_TOKEN_NAME . '"', $field);
    }

    // ==========================================
    // Flash-сообщения
    // ==========================================

    public function testSetAndGetFlash(): void
    {
        setFlash('success', 'Test message');
        $flash = getFlash();
        $this->assertSame('success', $flash['type']);
        $this->assertSame('Test message', $flash['message']);
    }

    public function testFlashClearedAfterGet(): void
    {
        setFlash('error', 'Error message');
        getFlash();
        $this->assertNull(getFlash());
    }

    public function testGetFlashWhenEmpty(): void
    {
        unset($_SESSION['flash']);
        $this->assertNull(getFlash());
    }

    public function testShowFlashReturnsHtml(): void
    {
        setFlash('success', 'OK');
        $html = showFlash();
        $this->assertStringContainsString('alert-success', $html);
        $this->assertStringContainsString('OK', $html);
    }

    public function testShowFlashReturnsEmptyWhenNoFlash(): void
    {
        unset($_SESSION['flash']);
        $this->assertSame('', showFlash());
    }

    // ==========================================
    // isPost() — проверка метода
    // ==========================================

    public function testIsPostReturnsFalseForGet(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->assertFalse(isPost());
    }

    public function testIsPostReturnsTrueForPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertTrue(isPost());
    }

    // ==========================================
    // post() / get() / getInt() — получение параметров
    // ==========================================

    public function testPostReturnsValue(): void
    {
        $_POST['test_key'] = '  hello  ';
        $this->assertSame('hello', post('test_key'));
    }

    public function testPostReturnsDefault(): void
    {
        unset($_POST['missing']);
        $this->assertSame('default', post('missing', 'default'));
    }

    public function testGetReturnsValue(): void
    {
        $_GET['q'] = ' search ';
        $this->assertSame('search', get('q'));
    }

    public function testGetIntReturnsInt(): void
    {
        $_GET['page'] = '5';
        $this->assertSame(5, getInt('page'));
    }

    public function testGetIntReturnsDefaultForMissing(): void
    {
        unset($_GET['missing']);
        $this->assertSame(1, getInt('missing', 1));
    }

    public function testGetIntReturnsZeroForNonNumeric(): void
    {
        $_GET['page'] = 'abc';
        $this->assertSame(0, getInt('page'));
    }

    // ==========================================
    // paginate() — пагинация
    // ==========================================

    public function testPaginateReturnsEmptyForSinglePage(): void
    {
        $this->assertSame('', paginate(10, 20, 1, '/servers?x=1'));
    }

    public function testPaginateReturnsHtmlForMultiplePages(): void
    {
        $html = paginate(100, 20, 1, '/servers?x=1');
        $this->assertStringContainsString('pagination', $html);
        $this->assertStringContainsString('page=2', $html);
    }

    public function testPaginateHighlightsCurrentPage(): void
    {
        $html = paginate(100, 20, 3, '/servers?x=1');
        $this->assertStringContainsString('active', $html);
    }
}
