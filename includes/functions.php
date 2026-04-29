<?php
/**
 * CraftRadar — Общие вспомогательные функции
 */

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
