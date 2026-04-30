<?php
/**
 * CraftRadar — Виджет-баннер сервера (HTML/JS для вставки на сайт)
 * 
 * Использование: <script src="https://craftradar.ru/api/widget.php?id=1"></script>
 * Или iframe:    <iframe src="https://craftradar.ru/api/widget.php?id=1&format=html" width="468" height="60"></iframe>
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/cache.php';

$id = getInt('id');
$format = get('format', 'js');

if (!$id) {
    header('Content-Type: text/plain');
    die('Missing server ID');
}

// Кэш 2 минуты
$server = cacheRemember("widget_{$id}", 120, function() use ($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id, name, ip, port, is_online, players_online, players_max, votes_month, icon, is_verified FROM servers WHERE id = ? AND status = 'active'");
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
});

if (!$server) {
    header('Content-Type: text/plain');
    die('Server not found');
}

$serverUrl = SITE_URL . '/server.php?id=' . $server['id'];
$statusText = $server['is_online'] ? $server['players_online'] . '/' . $server['players_max'] . ' онлайн' : 'Оффлайн';
$statusColor = $server['is_online'] ? '#3fb950' : '#f85149';
$verified = $server['is_verified'] ? ' ✓' : '';

if ($format === 'html') {
    // HTML виджет (для iframe)
    header('Content-Type: text/html; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    ?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#161b22;color:#e6edf3}
.widget{display:flex;align-items:center;gap:10px;padding:8px 12px;height:60px;text-decoration:none;color:#e6edf3;border:1px solid #30363d;border-radius:6px;transition:border-color .2s}
.widget:hover{border-color:#00ff80}
.w-icon{width:40px;height:40px;border-radius:4px;object-fit:cover;background:#0d1117;display:flex;align-items:center;justify-content:center;font-size:1.2rem}
.w-info{flex:1;min-width:0}
.w-name{font-weight:600;font-size:.85rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.w-meta{font-size:.7rem;color:#8b949e;display:flex;gap:8px;align-items:center}
.w-status{color:<?= $statusColor ?>}
.w-votes{font-size:.75rem;color:#8b949e;text-align:center}
.w-votes span{display:block;font-size:1rem;font-weight:700;color:#00ff80}
.w-brand{font-size:.55rem;color:#484f58;text-align:right}
</style></head>
<body>
<a href="<?= e($serverUrl) ?>" target="_blank" class="widget">
    <div class="w-icon">📡</div>
    <div class="w-info">
        <div class="w-name"><?= e($server['name']) ?><?= $verified ?></div>
        <div class="w-meta">
            <span><?= e($server['ip'] . ':' . $server['port']) ?></span>
            <span class="w-status">● <?= $statusText ?></span>
        </div>
    </div>
    <div class="w-votes"><span><?= $server['votes_month'] ?></span>голосов</div>
</a>
</body></html>
    <?php
    exit;
}

// JS виджет (вставка через <script>)
header('Content-Type: application/javascript; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$name = addslashes(e($server['name']) . $verified);
$ip = addslashes(e($server['ip'] . ':' . $server['port']));

echo <<<JS
(function(){
var d=document,c=d.currentScript,w=d.createElement('div');
w.innerHTML='<a href="{$serverUrl}" target="_blank" style="display:inline-flex;align-items:center;gap:10px;padding:8px 14px;background:#161b22;border:1px solid #30363d;border-radius:6px;color:#e6edf3;text-decoration:none;font-family:-apple-system,sans-serif;font-size:13px;transition:border-color .2s" onmouseover="this.style.borderColor=\'#00ff80\'" onmouseout="this.style.borderColor=\'#30363d\'">'
+'<span style="font-size:1.2rem">📡</span>'
+'<span><b>{$name}</b><br><span style="font-size:11px;color:#8b949e">{$ip} <span style="color:{$statusColor}">● {$statusText}</span></span></span>'
+'<span style="text-align:center;margin-left:8px"><span style="font-size:16px;font-weight:700;color:#00ff80">{$server['votes_month']}</span><br><span style="font-size:10px;color:#8b949e">голосов</span></span>'
+'</a>';
c.parentNode.insertBefore(w,c);
})();
JS;
