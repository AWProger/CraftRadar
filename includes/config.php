<?php
/**
 * CraftRadar — Конфигурация
 */

// Режим разработки (true = показывать ошибки)
define('DEBUG', true);

// Настройки базы данных
define('DB_HOST', 'localhost');
define('DB_NAME', 'craftradar');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Пути
define('ROOT_PATH', dirname(__DIR__) . '/');
define('INCLUDES_PATH', ROOT_PATH . 'includes/');
define('ASSETS_PATH', ROOT_PATH . 'assets/');
define('UPLOADS_PATH', ROOT_PATH . 'assets/img/');

// URL сайта (без слеша в конце)
define('SITE_URL', 'https://craftradar.ru');
define('SITE_NAME', 'CraftRadar');

// Сессии
define('SESSION_LIFETIME', 86400 * 7); // 7 дней
define('SESSION_NAME', 'craftradar_sid');

// Серверы
define('SERVERS_PER_PAGE', 20);
define('SERVERS_LIMIT_PER_USER', 5);
define('PING_TIMEOUT', 5); // секунд
define('PING_INTERVAL', 10); // минут
define('MAX_CONSECUTIVE_FAILS', 3);

// Голосование
define('VOTE_COOLDOWN', 24); // часов

// Отзывы
define('REVIEW_MIN_LENGTH', 10);
define('REVIEW_MAX_LENGTH', 1000);

// Описание сервера
define('DESCRIPTION_MAX_LENGTH', 2000);

// Загрузка файлов
define('MAX_ICON_SIZE', 64 * 1024);       // 64KB
define('MAX_BANNER_SIZE', 500 * 1024);     // 500KB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// Безопасность
define('BCRYPT_COST', 12);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_BLOCK_TIME', 15 * 60); // 15 минут
define('MIN_PASSWORD_LENGTH', 6);

// CSRF
define('CSRF_TOKEN_NAME', 'csrf_token');

// Cron — секретный ключ для запуска через HTTP (wget)
define('CRON_SECRET_KEY', 'craftradar_cron_2026_secret');

// Настройки ошибок
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Часовой пояс
date_default_timezone_set('Europe/Moscow');

// Запуск сессии
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_name(SESSION_NAME);
    session_start();
}
