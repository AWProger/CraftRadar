<?php
/**
 * CraftRadar — Cron: Проверка обратных ссылок на сайтах серверов
 * 
 * Если на сайте сервера есть ссылка на CraftRadar (без nofollow/noopener/noreferrer):
 * → сервер получает +100 бонусных монет (пока ссылка есть)
 * 
 * Расписание: раз в сутки
 * wget -qO- "https://craftradar.ru/cron/check_backlinks.php?key=craftradar_cron_2026_secret"
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['key']) || $_GET['key'] !== CRON_SECRET_KEY) {
        http_response_code(403);
        die('Access denied');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

function cronLog(string $msg): void
{
    $logDir = ROOT_PATH . 'storage/logs/';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    $logFile = $logDir . 'cron_backlinks_' . date('Y-m-d') . '.log';
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n";
    file_put_contents($logFile, $line, FILE_APPEND);
    echo $line;
}

define('BACKLINK_BONUS', 100); // монет за ссылку
$monitoringDomain = parse_url(SITE_URL, PHP_URL_HOST); // craftradar.ru

$db = getDB();

// Серверы с указанным сайтом
$servers = $db->query("
    SELECT s.id, s.name, s.website, s.user_id, s.has_backlink 
    FROM servers s 
    WHERE s.status IN ('active', 'pending') AND s.website IS NOT NULL AND s.website != ''
")->fetchAll();

cronLog("Проверка обратных ссылок: " . count($servers) . " серверов с сайтами");

$found = 0;
$lost = 0;

foreach ($servers as $srv) {
    $url = $srv['website'];
    $hasLink = false;

    try {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => 'CraftRadar-Bot/1.0 (+https://craftradar.ru)',
        ]);
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 && $html) {
            // Ищем ссылки на наш домен
            if (preg_match_all('/<a\s[^>]*href=["\']([^"\']*' . preg_quote($monitoringDomain, '/') . '[^"\']*)["\'][^>]*>/i', $html, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $tag = $match[0];
                    // Проверяем что нет nofollow, noopener, noreferrer
                    if (preg_match('/rel\s*=\s*["\'][^"\']*(?:nofollow|noopener|noreferrer)/i', $tag)) {
                        continue; // Ссылка с запрещёнными атрибутами
                    }
                    $hasLink = true;
                    break;
                }
            }
        }
    } catch (\Exception $e) {
        cronLog("  ✗ #{$srv['id']} {$srv['name']} — ошибка: " . $e->getMessage());
        continue;
    }

    $hadBacklink = (int)($srv['has_backlink'] ?? 0);

    if ($hasLink && !$hadBacklink) {
        // Ссылка появилась — начисляем бонус
        $db->prepare('UPDATE servers SET has_backlink = 1, backlink_checked_at = ? WHERE id = ?')->execute([now(), $srv['id']]);
        try {
            require_once INCLUDES_PATH . 'coins.php';
            require_once INCLUDES_PATH . 'notifications.php';
            addCoins($srv['user_id'], BACKLINK_BONUS, 'Бонус за ссылку на CraftRadar с сайта сервера');
            createNotification($srv['user_id'], 'backlink_bonus',
                '⭐ +' . BACKLINK_BONUS . ' монет за ссылку!',
                'На сайте вашего сервера найдена ссылка на CraftRadar. Вам начислено ' . BACKLINK_BONUS . ' бонусных монет!',
                SITE_URL . '/server.php?id=' . $srv['id']);
        } catch (\Exception $e) {}
        $found++;
        cronLog("  ✓ #{$srv['id']} {$srv['name']} — ссылка НАЙДЕНА, +{BACKLINK_BONUS} монет");
    } elseif (!$hasLink && $hadBacklink) {
        // Ссылка пропала — забираем бонус
        $db->prepare('UPDATE servers SET has_backlink = 0, backlink_checked_at = ? WHERE id = ?')->execute([now(), $srv['id']]);
        try {
            require_once INCLUDES_PATH . 'coins.php';
            require_once INCLUDES_PATH . 'notifications.php';
            spendCoins($srv['user_id'], BACKLINK_BONUS, 'Ссылка на CraftRadar удалена с сайта сервера');
            createNotification($srv['user_id'], 'backlink_lost',
                '⚠️ Ссылка на CraftRadar удалена',
                'На сайте вашего сервера больше нет ссылки на CraftRadar. Бонус ' . BACKLINK_BONUS . ' монет снят.',
                SITE_URL . '/dashboard/');
        } catch (\Exception $e) {}
        $lost++;
        cronLog("  ✗ #{$srv['id']} {$srv['name']} — ссылка ПРОПАЛА, -{BACKLINK_BONUS} монет");
    } elseif ($hasLink) {
        $db->prepare('UPDATE servers SET backlink_checked_at = ? WHERE id = ?')->execute([now(), $srv['id']]);
        cronLog("  ✓ #{$srv['id']} {$srv['name']} — ссылка на месте");
    } else {
        $db->prepare('UPDATE servers SET backlink_checked_at = ? WHERE id = ?')->execute([now(), $srv['id']]);
        cronLog("  — #{$srv['id']} {$srv['name']} — ссылки нет");
    }

    usleep(500000); // 0.5 сек между запросами
}

cronLog("Готово. Найдено новых: {$found}, потеряно: {$lost}");
