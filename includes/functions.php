<?php
/**
 * CraftRadar — Общие вспомогательные функции
 */

/**
 * Проверка: используется ли SQLite (для совместимости SQL-запросов)
 */
function isSQLite(): bool
{
    static $result = null;
    if ($result === null) {
        try {
            $result = getDB()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        } catch (\Exception $e) {
            $result = false;
        }
    }
    return $result;
}

/**
 * SQL-выражение для текущей даты (совместимо с MySQL и SQLite)
 */
function sqlCurDate(): string
{
    return isSQLite() ? "DATE('now')" : 'CURDATE()';
}

/**
 * SQL-выражение для NOW() (совместимо с MySQL и SQLite)
 */
function sqlNow(): string
{
    return isSQLite() ? "DATETIME('now')" : 'NOW()';
}

/**
 * SQL-выражение для DATE_SUB (совместимо с MySQL и SQLite)
 * Возвращает строку условия: column >= дата_в_прошлом
 */
function sqlDateSub(string $column, int $value, string $unit = 'DAY'): string
{
    if (isSQLite()) {
        $phpDate = date('Y-m-d H:i:s', strtotime("-{$value} {$unit}"));
        return "{$column} >= '{$phpDate}'";
    }
    return "{$column} >= DATE_SUB(NOW(), INTERVAL {$value} {$unit})";
}

/**
 * Дата N единиц назад (для подстановки в SQL через prepared statements)
 */
function dateAgo(int $value, string $unit = 'day'): string
{
    return date('Y-m-d H:i:s', strtotime("-{$value} {$unit}"));
}

/**
 * SQL-выражение для DATE_FORMAT (совместимо с MySQL и SQLite)
 */
function sqlDateFormat(string $column, string $mysqlFormat): string
{
    if (isSQLite()) {
        // Конвертируем MySQL формат в SQLite strftime
        $sqliteFormat = str_replace(
            ['%Y', '%m', '%d', '%H', '%i', '%s'],
            ['%Y', '%m', '%d', '%H', '%M', '%S'],
            $mysqlFormat
        );
        return "STRFTIME('{$sqliteFormat}', {$column})";
    }
    return "DATE_FORMAT({$column}, '{$mysqlFormat}')";
}

/**
 * Экранирование вывода (защита от XSS)
 */
function e(string $str): string
{
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Редирект
 */
function redirect(string $url): void
{
    // Очищаем буфер вывода если есть (чтобы header() сработал)
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Генерация CSRF-токена
 */
function generateCsrfToken(): string
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Проверка CSRF-токена
 */
function verifyCsrfToken(string $token): bool
{
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Скрытое поле CSRF для форм
 */
function csrfField(): string
{
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e(generateCsrfToken()) . '">';
}

/**
 * Установить flash-сообщение
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Получить и удалить flash-сообщение
 */
function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Показать flash-сообщение (HTML)
 */
function showFlash(): string
{
    $flash = getFlash();
    if (!$flash) return '';

    $type = e($flash['type']);
    $message = e($flash['message']);
    return "<div class=\"alert alert-{$type}\">{$message}</div>";
}

/**
 * Проверка метода запроса
 */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Получить значение из POST с очисткой
 */
function post(string $key, string $default = ''): string
{
    return trim($_POST[$key] ?? $default);
}

/**
 * Получить значение из GET с очисткой
 */
function get(string $key, string $default = ''): string
{
    return trim($_GET[$key] ?? $default);
}

/**
 * Получить int из GET
 */
function getInt(string $key, int $default = 0): int
{
    return (int)($_GET[$key] ?? $default);
}

/**
 * Текущая дата-время для БД
 */
function now(): string
{
    return date('Y-m-d H:i:s');
}

/**
 * Форматирование даты для отображения
 */
function formatDate(string $date): string
{
    return date('d.m.Y H:i', strtotime($date));
}

/**
 * Обрезка текста
 */
function truncate(string $text, int $length = 100): string
{
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '...';
}

/**
 * Получить IP пользователя
 */
function getUserIP(): string
{
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Генерация пагинации
 */
function paginate(int $total, int $perPage, int $currentPage, string $baseUrl): string
{
    $totalPages = (int)ceil($total / $perPage);
    if ($totalPages <= 1) return '';

    $html = '<div class="pagination">';

    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($currentPage - 1) . '" class="page-link">&laquo;</a>';
    }

    $start = max(1, $currentPage - 3);
    $end = min($totalPages, $currentPage + 3);

    if ($start > 1) {
        $html .= '<a href="' . $baseUrl . '&page=1" class="page-link">1</a>';
        if ($start > 2) $html .= '<span class="page-dots">...</span>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $html .= '<a href="' . $baseUrl . '&page=' . $i . '" class="page-link' . $active . '">' . $i . '</a>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<span class="page-dots">...</span>';
        $html .= '<a href="' . $baseUrl . '&page=' . $totalPages . '" class="page-link">' . $totalPages . '</a>';
    }

    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($currentPage + 1) . '" class="page-link">&raquo;</a>';
    }

    $html .= '</div>';
    return $html;
}

/**
 * Валидация IP-адреса сервера (запрет localhost и приватных сетей)
 */
function isValidServerIP(string $ip): bool
{
    // Запрет localhost
    if (in_array($ip, ['localhost', '127.0.0.1', '::1', '0.0.0.0'])) {
        return false;
    }

    // Если это домен — разрешаем
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return preg_match('/^[a-zA-Z0-9][a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,}$/', $ip) === 1;
    }

    // Запрет приватных и зарезервированных IP
    return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

/**
 * Валидация порта
 */
function isValidPort(int $port): bool
{
    return $port >= 1 && $port <= 65535;
}


/**
 * Простой rate-limiter по IP (файловый)
 * @return bool true если лимит НЕ превышен
 */
function checkRateLimit(string $action, int $maxPerMinute = 10): bool
{
    $ip = getUserIP();
    $dir = ROOT_PATH . 'storage/ratelimit/';
    if (!is_dir($dir)) @mkdir($dir, 0755, true);

    $file = $dir . md5($action . '_' . $ip) . '.json';
    $now = time();

    $data = [];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?: [];
    }

    // Убираем записи старше минуты
    $data = array_filter($data, function($t) use ($now) { return ($now - $t) < 60; });

    if (count($data) >= $maxPerMinute) {
        return false; // Лимит превышен
    }

    $data[] = $now;
    file_put_contents($file, json_encode(array_values($data)));
    return true;
}
