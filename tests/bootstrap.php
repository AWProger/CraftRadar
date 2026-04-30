<?php
/**
 * CraftRadar — Bootstrap для тестов
 * 
 * Загружает конфигурацию и функции.
 * getDB() переопределён чтобы не подключаться к реальной БД.
 */

define('TESTING', true);

// Подключаем конфиг (запустит сессию)
require_once __DIR__ . '/../includes/config.php';

// Переопределяем getDB() ДО подключения db.php
// Возвращаем заглушку чтобы тесты не падали на подключении к БД
function getDB(): PDO
{
    static $mock = null;
    if ($mock === null) {
        // SQLite in-memory как заглушка
        $mock = new PDO('sqlite::memory:');
        $mock->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    return $mock;
}

require_once __DIR__ . '/../includes/functions.php';

// Autoload composer
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
