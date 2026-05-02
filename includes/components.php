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
 * Бейдж статуса онлайн/оффлайн (только индикатор)
 */
function onlineBadge(array $server): string
{
    if ($server['is_online']) {
        return '<span class="badge badge-online">Онлайн</span>';
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
    $name = e($s['name']);
    $ip = e($s['ip'] . ':' . $s['port']);
    $verified = !empty($s['is_verified']) ? ' <span style="color:var(--success);font-size:0.75rem;" title="Владелец подтверждён">✓</span>' : '';
    $promoted = !empty($s['is_promoted']) ? '<span style="color:var(--warning);">⭐</span> ' : '';
    $pendingBadge = (!empty($s['status']) && $s['status'] === 'pending') ? ' <span class="badge badge-pending">Модерация</span>' : '';

    $html = '<a href="' . $url . '" class="' . $cardClass . '">';

    if ($rank > 0) {
        $html .= '<div class="server-rank">#' . $rank . '</div>';
    }

    $html .= $icon;

    // Инфо
    $html .= '<div class="server-card-info">';
    $html .= '<div class="server-card-name">' . $promoted . $name . $verified . '</div>';

    $metaParts = ['<span>' . $ip . '</span>'];
    if (!empty($s['version'])) $metaParts[] = '<span>v' . e($s['version']) . '</span>';
    if (!empty($s['game_mode'])) $metaParts[] = '<span>' . e($s['game_mode']) . '</span>';
    $metaParts[] = $pendingBadge;
    $html .= '<div class="server-card-meta">' . implode('', $metaParts) . '</div>';
    $html .= '</div>';

    // Статистика справа
    $html .= '<div class="server-card-stats">';

    // Онлайн игроков
    if ($s['is_online']) {
        $html .= '<div class="stat-item"><span class="stat-value">' . (int)$s['players_online'] . '/' . (int)$s['players_max'] . '</span><span class="stat-label">Игроков</span></div>';
    } else {
        $html .= '<div class="stat-item"><span class="stat-value" style="color:var(--danger);">—</span><span class="stat-label">Оффлайн</span></div>';
    }

    // Голоса (всегда total — не сбрасываются)
    $html .= '<div class="stat-item"><span class="stat-value">' . (int)($s['votes_total'] ?? 0) . '</span><span class="stat-label">Голосов</span></div>';

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


/**
 * Отображение одного отзыва
 */
function reviewItem(array $review, bool $showServer = false): string
{
    $html = '<div style="padding: 10px 0; border-bottom: 1px solid var(--border);">';
    $html .= '<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px;">';

    if ($showServer && !empty($review['server_name'])) {
        $html .= '<a href="' . SITE_URL . '/server.php?id=' . (int)$review['server_id'] . '">' . e($review['server_name']) . '</a>';
    } else {
        $html .= '<a href="' . SITE_URL . '/profile.php?id=' . (int)$review['user_id'] . '"><strong>' . e($review['username'] ?? 'Пользователь') . '</strong></a>';
    }

    $html .= '<span class="stars" style="font-size: 0.8rem;">' . str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']) . '</span>';
    $html .= '</div>';

    if (!empty($review['text'])) {
        $html .= '<p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 4px;">' . e(truncate($review['text'], 150)) . '</p>';
    }

    $html .= '<span style="color: var(--text-muted); font-size: 0.65rem;">' . formatDate($review['created_at']) . '</span>';
    $html .= '</div>';
    return $html;
}


/**
 * Навигация кабинета
 */
function dashboardNav(string $active = ''): string
{
    $links = [
        'profile' => ['url' => SITE_URL . '/dashboard/profile.php', 'icon' => '👤', 'label' => 'Профиль'],
        'servers' => ['url' => SITE_URL . '/dashboard/', 'icon' => '📡', 'label' => 'Серверы'],
        'points'  => ['url' => SITE_URL . '/dashboard/points.php', 'icon' => '💎', 'label' => 'Баллы'],
        'coins'   => ['url' => SITE_URL . '/dashboard/buy_coins.php', 'icon' => '💰', 'label' => 'Монеты'],
        'notif'   => ['url' => SITE_URL . '/dashboard/notifications.php', 'icon' => '🔔', 'label' => 'Уведомления'],
        'settings'=> ['url' => SITE_URL . '/dashboard/settings.php', 'icon' => '⚙️', 'label' => 'Настройки'],
        'tickets' => ['url' => SITE_URL . '/dashboard/tickets.php', 'icon' => '🎫', 'label' => 'Обращения'],
        'help'    => ['url' => SITE_URL . '/dashboard/help.php', 'icon' => '❓', 'label' => 'Помощь'],
    ];

    $html = '<div class="dashboard-tabs">';
    foreach ($links as $key => $link) {
        $cls = $key === $active ? 'active' : '';
        $html .= '<a href="' . $link['url'] . '" class="dashboard-tab ' . $cls . '">' . $link['icon'] . ' ' . $link['label'] . '</a>';
    }
    $html .= '</div>';
    return $html;
}

/**
 * Хлебные крошки
 */
function breadcrumbs(array $items): string
{
    $html = '<nav class="breadcrumbs">';
    $last = count($items) - 1;
    foreach ($items as $i => $item) {
        if ($i === $last) {
            $html .= '<span class="breadcrumb-current">' . e($item['label']) . '</span>';
        } else {
            $html .= '<a href="' . $item['url'] . '" class="breadcrumb-link">' . e($item['label']) . '</a>';
            $html .= '<span class="breadcrumb-sep">›</span>';
        }
    }
    $html .= '</nav>';
    return $html;
}
