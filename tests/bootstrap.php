<?php
/**
 * CraftRadar — Bootstrap для тестов
 * 
 * Загружает конфигурацию и функции без запуска сессии и подключения к БД.
 */

// Определяем константы для тестового окружения
define('TESTING', true);

// Подключаем конфиг (сессия запустится, но это ок для тестов)
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Autoload composer (если есть)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
