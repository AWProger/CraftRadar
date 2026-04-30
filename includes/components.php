<?php
/**
 * CraftRadar — Переиспользуемые компоненты (правило №6 — без дублирования)
 */

/**
 * Иконка сервера
 */
function serverIcon(array $server, string $size = '48px'): string
{
    if (!empty($server['icon'])) {
        return '<img src="' . SITE_URL . '/' . e($server['icon']) . '" alt="" class="server-card-icon" style="width:' . $size . ';height:' . $size . ';">';
    }
    return '<div class="server-card-icon server-card-icon-placeholder" style="width:' . $size . ';height:' . $size . ';">📡</div>';
}

/**
 * Бейдж статуса онлайн/оффлайн
 */
function onlineBadge(array $server, bool $showPlayers = false): string
{
    if ($server['is_online']) {
        $text = $showPlayers ? $server['players_online'] . '/' . $server['players_max'] : 'Онлайн';
        return '<span class="badge badge-online">' . $text . '</span>';
    }
    return '<span class="badge badge-offline">Оффлайн</span>';
}

/**
 * Карточка сервера для списков (главная, каталог)
 */
function serverCard(array $s, int $rank = 0): string
{
    $cardClass = 'server-card';
    if (!empty($s['is_promoted'])) $cardClass .= ' server-card-promoted';
    elseif (!empty($s['highlighted_until']) && strtotime($s['highlighted_until']) > time()) $cardClass .= ' server-card-highlighted';

    $url = SITE_URL . '/server.php?id=' . $s['id'];
    $icon = serverIcon($s);
    $badge = onlineBadge($s, true);
    $name = e($s['name']);
    $ip = e($s['ip'] . ':' . $s['port']);
    $verified = !empty($s['is_verified']) ? '<span style="color:var(--success);font-size:0.75rem;" title="Владелец подтверждён">✓</span>' : '';
    $promoted = !empty($s['is_promoted']) ? '<span style="color:var(--warning);">⭐</span>' : '';

    $html = '<a href="' . $url . '" class="' . $cardClass . '">';

    if ($rank > 0) {
        $html .= '<div class="server-rank">#' . $rank . '</div>';
    }

    $html .= $icon;
    $html .= '<div class="server-card-info">';
    $html .= '<div class="server-card-name">' . $promoted . $name . ' ' . $verified . '</div>';
    $html .= '<div class="server-card-meta"><span>' . $ip . '</span>' . $badge . '</div>';
    $html .= '</div>';

    $html .= '<div class="server-card-stats">';
    if (isset($s['players_online']) && $s['is_online']) {
        $html .= '<div class="stat-item"><span class="stat-value">' . $s['players_online'] . '</span><span class="stat-label">Игроков</span></div>';
    }
    $html .= '<div class="stat-item"><span class="stat-value">' . ($s['votes_month'] ?? 0) . '</span><span class="stat-label">Голосов</span></div>';
    $html .= '</div>';

    $html .= '</a>';
    return $html;
}

/**
 * Список серверов
 */
function serverList(array $servers, bool $showRank = false): string
{
    if (empty($servers)) {
        return '<div class="card" style="text-align:center;padding:40px;"><p style="color:var(--text-muted);">Серверов пока нет.</p></div>';
    }

    $html = '<div class="server-list">';
    foreach ($servers as $i => $s) {
        $html .= serverCard($s, $showRank ? $i + 1 : 0);
    }
    $html .= '</div>';
    return $html;
}
