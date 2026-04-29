<?php
/**
 * CraftRadar — Тесты функций авторизации (includes/auth.php)
 * 
 * Тестируем функции, не требующие подключения к БД.
 * Интеграционные тесты с БД — в tests/Integration/.
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../includes/auth.php';

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        // Очищаем сессию перед каждым тестом
        $_SESSION = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test Agent';
    }

    // ==========================================
    // isLoggedIn()
    // ==========================================

    public function testIsLoggedInReturnsFalseWhenNoSession(): void
    {
        $this->assertFalse(isLoggedIn());
    }

    public function testIsLoggedInReturnsTrueWithValidSession(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'testuser';
        $_SESSION['role'] = 'user';
        $_SESSION['user_ip'] = '127.0.0.1';
        $_SESSION['user_agent'] = 'PHPUnit Test Agent';

        $this->assertTrue(isLoggedIn());
    }

    public function testIsLoggedInReturnsFalseWithIPMismatch(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_ip'] = '10.0.0.1'; // Другой IP
        $_SESSION['user_agent'] = 'PHPUnit Test Agent';

        $this->assertFalse(isLoggedIn());
    }

    public function testIsLoggedInReturnsFalseWithUAMismatch(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['user_ip'] = '127.0.0.1';
        $_SESSION['user_agent'] = 'Different Agent'; // Другой UA

        $this->assertFalse(isLoggedIn());
    }

    // ==========================================
    // currentUserId()
    // ==========================================

    public function testCurrentUserIdReturnsZeroWhenNotLoggedIn(): void
    {
        $this->assertSame(0, currentUserId());
    }

    public function testCurrentUserIdReturnsIdWhenLoggedIn(): void
    {
        $_SESSION['user_id'] = 42;
        $this->assertSame(42, currentUserId());
    }

    // ==========================================
    // currentUserRole()
    // ==========================================

    public function testCurrentUserRoleReturnsUserByDefault(): void
    {
        $this->assertSame('user', currentUserRole());
    }

    public function testCurrentUserRoleReturnsSessionRole(): void
    {
        $_SESSION['role'] = 'admin';
        $this->assertSame('admin', currentUserRole());
    }

    // ==========================================
    // isAdmin() / isModerator()
    // ==========================================

    public function testIsAdminReturnsFalseWhenNotLoggedIn(): void
    {
        $this->assertFalse(isAdmin());
    }

    public function testIsAdminReturnsTrueForAdmin(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'admin';
        $_SESSION['user_ip'] = '127.0.0.1';
        $_SESSION['user_agent'] = 'PHPUnit Test Agent';

        $this->assertTrue(isAdmin());
    }

    public function testIsAdminReturnsFalseForModerator(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'moderator';
        $_SESSION['user_ip'] = '127.0.0.1';
        $_SESSION['user_agent'] = 'PHPUnit Test Agent';

        $this->assertFalse(isAdmin());
    }

    public function testIsModeratorReturnsTrueForModerator(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'moderator';
        $_SESSION['user_ip'] = '127.0.0.1';
        $_SESSION['user_agent'] = 'PHPUnit Test Agent';

        $this->assertTrue(isModerator());
    }

    public function testIsModeratorReturnsTrueForAdmin(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'admin';
        $_SESSION['user_ip'] = '127.0.0.1';
        $_SESSION['user_agent'] = 'PHPUnit Test Agent';

        $this->assertTrue(isModerator());
    }

    public function testIsModeratorReturnsFalseForUser(): void
    {
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'user';
        $_SESSION['user_ip'] = '127.0.0.1';
        $_SESSION['user_agent'] = 'PHPUnit Test Agent';

        $this->assertFalse(isModerator());
    }

    // ==========================================
    // Валидация пароля (через registerUser — проверяем ошибки без БД)
    // ==========================================

    public function testRegisterEmptyFieldsReturnsError(): void
    {
        // registerUser требует БД, но пустые поля проверяются до обращения к БД
        $result = registerUser('', '', '', '');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Заполните', $result['error']);
    }

    public function testRegisterShortUsernameReturnsError(): void
    {
        $result = registerUser('ab', 'test@test.com', 'password123', 'password123');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('от 3 до 50', $result['error']);
    }

    public function testRegisterInvalidUsernameCharsReturnsError(): void
    {
        $result = registerUser('user name!', 'test@test.com', 'password123', 'password123');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('латинские буквы', $result['error']);
    }

    public function testRegisterInvalidEmailReturnsError(): void
    {
        $result = registerUser('testuser', 'not-an-email', 'password123', 'password123');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('email', $result['error']);
    }

    public function testRegisterShortPasswordReturnsError(): void
    {
        $result = registerUser('testuser', 'test@test.com', '123', '123');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('не менее', $result['error']);
    }

    public function testRegisterPasswordMismatchReturnsError(): void
    {
        $result = registerUser('testuser', 'test@test.com', 'password123', 'different456');
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('не совпадают', $result['error']);
    }
}
