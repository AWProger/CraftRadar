<?php
/**
 * CraftRadar — Файловый кэш (JSON)
 * 
 * Кэширует результаты запросов для главной и каталога.
 * Файлы хранятся в storage/cache/.
 */

define('CACHE_DIR', ROOT_PATH . 'storage/cache/');
define('CACHE_TTL', 300); // 5 минут по умолчанию

/**
 * Получить данные из кэша
 * @return mixed|null Данные или null если кэш устарел/отсутствует
 */
function cacheGet(string $key, int $ttl = CACHE_TTL): mixed
{
    $file = CACHE_DIR . md5($key) . '.json';

    if (!file_exists($file)) {
        return null;
    }

    // Проверяем TTL
    if (time() - filemtime($file) > $ttl) {
        @unlink($file);
        return null;
    }

    $data = file_get_contents($file);
    if ($data === false) {
        return null;
    }

    return json_decode($data, true);
}

/**
 * Сохранить данные в кэш
 */
function cacheSet(string $key, mixed $data): void
{
    if (!is_dir(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0755, true);
    }

    $file = CACHE_DIR . md5($key) . '.json';
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
}

/**
 * Удалить конкретный кэш
 */
function cacheDelete(string $key): void
{
    $file = CACHE_DIR . md5($key) . '.json';
    if (file_exists($file)) {
        @unlink($file);
    }
}

/**
 * Очистить весь кэш
 */
function cacheClear(): void
{
    if (!is_dir(CACHE_DIR)) return;

    $files = glob(CACHE_DIR . '*.json');
    foreach ($files as $file) {
        @unlink($file);
    }
}

/**
 * Получить данные из кэша или выполнить callback и закэшировать
 */
function cacheRemember(string $key, int $ttl, callable $callback): mixed
{
    $data = cacheGet($key, $ttl);
    if ($data !== null) {
        return $data;
    }

    $data = $callback();
    cacheSet($key, $data);
    return $data;
}
