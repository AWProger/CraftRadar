<?php
/**
 * CraftRadar — Функции авторизации
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Регистрация пользователя
 * @return array ['success' => bool, 'error' => string|null]
 */
function registerUser(string $username, string $email, string $password, string $passwordConfirm): array
{
    $db = getDB();

    // Валидация
    if (empty($username) || empty($email) || empty($password)) {
        return ['success' => false, 'error' => 'Заполните все поля.'];
    }

    if (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
        return ['success' => false, 'error' => 'Логин должен быть от 3 до 50 символов.'];
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return ['success' => false, 'error' => 'Логин может содержать только латинские буквы, цифры и _.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Некорректный email.'];
    }

    if (mb_strlen($password) < MIN_PASSWORD_LENGTH) {
        return ['success' => false, 'error' => 'Пароль должен быть не менее ' . MIN_PASSWORD_LENGTH . ' символов.'];
    }

    if ($password !== $passwordConfirm) {
        return ['success' => false, 'error' => 'Пароли не совпадают.'];
    }

    // Проверка уникальности
    $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Пользователь с таким логином или email уже существует.'];
    }

    // Создание пользователя
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
    $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, created_at, last_ip) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$username, $email, $hash, now(), getUserIP()]);

    return ['success' => true, 'error' => null];
}

/**
 * Авторизация пользователя
 * @return array ['success' => bool, 'error' => string|null]
 */
function loginUser(string $login, string $password): array
{
    $db = getDB();

    if (empty($login) || empty($password)) {
        return ['success' => false, 'error' => 'Заполните все поля.'];
    }

    // Проверка блокировки по IP (брутфорс)
    $ip = getUserIP();
    if (isLoginBlocked($ip)) {
        return ['success' => false, 'error' => 'Слишком много попыток входа. Попробуйте через 15 минут.'];
    }

    // Поиск пользователя по логину или email
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ? OR email = ?');
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        recordFailedLogin($ip);
        return ['success' => false, 'error' => 'Неверный логин или пароль.'];
    }

    // Проверка бана
    if ($user['is_banned']) {
        if ($user['ban_until'] && strtotime($user['ban_until']) < time()) {
            // Бан истёк — снимаем
            $stmt = $db->prepare('UPDATE users SET is_banned = 0, ban_reason = NULL, ban_until = NULL, banned_by = NULL WHERE id = ?');
            $stmt->execute([$user['id']]);
        } else {
            $until = $user['ban_until'] ? ' до ' . formatDate($user['ban_until']) : ' навсегда';
            $reason = $user['ban_reason'] ? ': ' . $user['ban_reason'] : '';
            return ['success' => false, 'error' => 'Аккаунт заблокирован' . $until . $reason];
        }
    }

    // Успешный вход — создаём сессию
    clearFailedLogins($ip);
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['user_ip'] = $ip;
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';

    // Обновляем last_login
    $stmt = $db->prepare('UPDATE users SET last_login = ?, last_ip = ? WHERE id = ?');
    $stmt->execute([now(), $ip, $user['id']]);

    // Логируем вход
    try {
        $db->prepare('INSERT INTO admin_log (admin_id, action, target_type, target_id, details, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$user['id'], 'login', 'user', $user['id'], json_encode(['user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '']), $ip, now()]);
    } catch (\Exception $e) {} // Не блокируем вход если лог не записался

    return ['success' => true, 'error' => null];
}

/**
 * Выход
 */
function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Проверка авторизации
 */
function isLoggedIn(): bool
{
    if (empty($_SESSION['user_id'])) {
        return false;
    }

    // Проверка привязки к User-Agent (IP не проверяем — на хостингах с прокси он может меняться)
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $ua) {
        logoutUser();
        return false;
    }

    return true;
}

/**
 * Получить текущего пользователя
 */
function currentUser(): ?array
{
    if (!isLoggedIn()) return null;

    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

/**
 * Получить ID текущего пользователя
 */
function currentUserId(): int
{
    return $_SESSION['user_id'] ?? 0;
}

/**
 * Получить роль текущего пользователя
 */
function currentUserRole(): string
{
    // Всегда читаем роль из БД чтобы изменения применялись без перелогина
    if (!empty($_SESSION['user_id'])) {
        try {
            $db = getDB();
            $stmt = $db->prepare('SELECT role FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $role = $stmt->fetchColumn();
            if ($role) {
                $_SESSION['role'] = $role;
                return $role;
            }
        } catch (\Exception $e) {}
    }
    return $_SESSION['role'] ?? 'user';
}

/**
 * Проверка роли
 */
function isAdmin(): bool
{
    return isLoggedIn() && currentUserRole() === 'admin';
}

function isModerator(): bool
{
    return isLoggedIn() && in_array(currentUserRole(), ['moderator', 'admin']);
}

/**
 * Требовать авторизацию (редирект если не авторизован)
 */
function requireAuth(): void
{
    if (!isLoggedIn()) {
        setFlash('error', 'Необходимо авторизоваться.');
        redirect(SITE_URL . '/login.php');
    }
}

/**
 * Требовать роль админа
 */
function requireAdmin(): void
{
    requireAuth();
    if (!isAdmin()) {
        setFlash('error', 'Доступ запрещён.');
        redirect(SITE_URL . '/');
    }
}

/**
 * Требовать роль модератора или админа
 */
function requireModerator(): void
{
    requireAuth();
    if (!isModerator()) {
        setFlash('error', 'Доступ запрещён.');
        redirect(SITE_URL . '/');
    }
}

// ============================================
// Защита от брутфорса (файловая, без БД)
// ============================================

function getLoginAttemptsFile(): string
{
    $dir = ROOT_PATH . 'storage/login_attempts/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir . md5(getUserIP()) . '.json';
}

function isLoginBlocked(string $ip): bool
{
    $file = getLoginAttemptsFile();
    if (!file_exists($file)) return false;

    $data = json_decode(file_get_contents($file), true);
    if (!$data) return false;

    if ($data['count'] >= MAX_LOGIN_ATTEMPTS) {
        if (time() - $data['last_attempt'] < LOGIN_BLOCK_TIME) {
            return true;
        }
        // Блокировка истекла
        unlink($file);
    }

    return false;
}

function recordFailedLogin(string $ip): void
{
    $file = getLoginAttemptsFile();
    $data = ['count' => 0, 'last_attempt' => 0];

    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?: $data;
    }

    $data['count']++;
    $data['last_attempt'] = time();

    file_put_contents($file, json_encode($data));
}

function clearFailedLogins(string $ip): void
{
    $file = getLoginAttemptsFile();
    if (file_exists($file)) {
        unlink($file);
    }
}
