<?php
/**
 * Проверка всех страниц сайта на ошибки
 */

$baseUrl = 'http://localhost:8080';

$pages = [
    '/' => 'Главная',
    '/servers.php' => 'Каталог',
    '/server.php?id=1' => 'Страница сервера',
    '/login.php' => 'Вход',
    '/register.php' => 'Регистрация',
    '/page.php?slug=about' => 'О проекте',
    '/page.php?slug=rules' => 'Правила',
    '/page.php?slug=faq' => 'FAQ',
    '/page.php?slug=services' => 'Услуги',
    '/page.php?slug=offer' => 'Оферта',
    '/page.php?slug=contacts' => 'Контакты',
    '/404.php' => '404',
    '/api/search.php?q=mc' => 'API поиск',
    '/api/server_status.php?id=1' => 'API статус',
    '/api/top_servers.php' => 'API топ серверов',
    '/api/server_chart.php?id=1&period=24h' => 'API график',
];

echo "=== Проверка страниц ===\n\n";

$errors = 0;
$ok = 0;

foreach ($pages as $path => $name) {
    $url = $baseUrl . $path;
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true,
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "❌ {$name} ({$path}) — НЕ ОТВЕЧАЕТ\n";
        $errors++;
        continue;
    }
    
    // Проверяем HTTP код
    $httpCode = 200;
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('/HTTP\/\d\.\d\s+(\d+)/', $header, $m)) {
                $httpCode = (int)$m[1];
            }
        }
    }
    
    // Проверяем на PHP ошибки
    $hasError = false;
    $errorTypes = ['Fatal error', 'Warning:', 'Notice:', 'Parse error', 'Deprecated:'];
    foreach ($errorTypes as $type) {
        if (stripos($response, $type) !== false && stripos($response, 'display_errors') === false) {
            $hasError = true;
            // Извлекаем текст ошибки
            if (preg_match('/' . preg_quote($type) . '.*?(?:in |$)/s', $response, $m)) {
                echo "⚠️  {$name} ({$path}) — HTTP {$httpCode} — " . trim(strip_tags(substr($m[0], 0, 150))) . "\n";
            } else {
                echo "⚠️  {$name} ({$path}) — HTTP {$httpCode} — содержит {$type}\n";
            }
            $errors++;
            break;
        }
    }
    
    if (!$hasError) {
        $size = strlen($response);
        echo "✅ {$name} ({$path}) — HTTP {$httpCode} — {$size} байт\n";
        $ok++;
    }
}

echo "\n=== Итого: {$ok} OK, {$errors} ошибок из " . count($pages) . " страниц ===\n";
