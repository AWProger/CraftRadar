<?php
/**
 * CraftRadar — Боковой виджет: Топ серверов (карусель)
 * 
 * Подключается в layout. Показывает топ-20 серверов с автопрокруткой.
 * Данные загружаются через API и обновляются периодически.
 */

require_once __DIR__ . '/cache.php';

// Начальные данные (серверная отрисовка для SEO)
$sidebarServers = cacheRemember('sidebar_top_initial', SIDEBAR_ROTATE_SECONDS, function() {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT id, name, ip, port, icon, motd, is_online, players_online, players_max,
               votes_month, is_verified, is_promoted,
               (highlighted_until > NOW()) as is_highlighted
        FROM servers WHERE status IN ('active', 'pending')
        ORDER BY is_promoted DESC, (highlighted_until > NOW()) DESC, votes_month DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, SIDEBAR_TOP_COUNT, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
});
?>

<aside class="sidebar-top" id="sidebarTop">
    <div class="sidebar-top-header">
        <h3 class="sidebar-top-title">🏆 Топ серверов</h3>
        <div class="sidebar-top-controls">
            <button class="sidebar-btn" id="sidebarPrev" aria-label="Назад">◀</button>
            <button class="sidebar-btn" id="sidebarNext" aria-label="Вперёд">▶</button>
        </div>
    </div>

    <div class="sidebar-top-carousel" id="sidebarCarousel">
        <div class="sidebar-top-track" id="sidebarTrack">
            <?php foreach ($sidebarServers as $i => $srv): ?>
                <?php
                $cardClass = 'sidebar-server';
                if ($srv['is_promoted']) $cardClass .= ' sidebar-server-promoted';
                elseif ($srv['is_highlighted']) $cardClass .= ' sidebar-server-highlighted';
                ?>
                <a href="<?= SITE_URL ?>/server.php?id=<?= $srv['id'] ?>" class="<?= $cardClass ?>" data-index="<?= $i ?>">
                    <div class="sidebar-server-rank">#<?= $i + 1 ?></div>
                    <div class="sidebar-server-icon-wrap">
                        <?php if ($srv['icon']): ?>
                            <img src="<?= SITE_URL . '/' . e($srv['icon']) ?>" alt="" class="sidebar-server-icon">
                        <?php else: ?>
                            <div class="sidebar-server-icon sidebar-server-icon-placeholder">📡</div>
                        <?php endif; ?>
                        <?php if ($srv['is_online']): ?>
                            <span class="sidebar-online-dot"></span>
                        <?php endif; ?>
                    </div>
                    <div class="sidebar-server-info">
                        <div class="sidebar-server-name">
                            <?php if ($srv['is_promoted']): ?>⭐<?php endif; ?>
                            <?php if ($srv['is_verified']): ?>✓<?php endif; ?>
                            <?= e(truncate($srv['name'], 22)) ?>
                        </div>
                        <div class="sidebar-server-motd"><?= e(truncate($srv['motd'] ?: $srv['ip'] . ':' . $srv['port'], 35)) ?></div>
                        <div class="sidebar-server-stats">
                            <?php if ($srv['is_online']): ?>
                                <span class="sidebar-players">👥 <?= $srv['players_online'] ?>/<?= $srv['players_max'] ?></span>
                            <?php else: ?>
                                <span class="sidebar-offline">● Оффлайн</span>
                            <?php endif; ?>
                            <span class="sidebar-votes">👍 <?= $srv['votes_month'] ?></span>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>

            <?php if (empty($sidebarServers)): ?>
                <div class="sidebar-empty">
                    <p>Серверов пока нет</p>
                    <a href="<?= SITE_URL ?>/dashboard/add.php" class="btn btn-sm btn-primary">Добавить</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="sidebar-top-footer">
        <div class="sidebar-dots" id="sidebarDots"></div>
        <a href="<?= SITE_URL ?>/servers.php" class="sidebar-all-link">Все серверы →</a>
    </div>
</aside>
