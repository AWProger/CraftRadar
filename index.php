<?php
/**
 * CraftRadar — Главная страница
 */

$pageTitle = 'Мониторинг серверов Minecraft';
$pageDescription = 'CraftRadar — каталог серверов Minecraft с рейтингом, статистикой и голосованием.';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/cache.php';

$db = getDB();

// Топ-10 по голосам за месяц (кэш 5 мин)
$topVotes = cacheRemember('home_top_votes', 300, function() use ($db) {
    return $db->query("
        SELECT id, name, ip, port, icon, is_online, players_online, players_max, votes_month, rating, is_verified
        FROM servers WHERE status = 'active' 
        ORDER BY is_promoted DESC, votes_month DESC, votes_total DESC 
        LIMIT 10
    ")->fetchAll();
});

// Топ-10 по онлайну (кэш 2 мин — обновляется чаще)
$topOnline = cacheRemember('home_top_online', 120, function() use ($db) {
    return $db->query("
        SELECT id, name, ip, port, icon, is_online, players_online, players_max, votes_month, is_verified
        FROM servers WHERE status = 'active' AND is_online = 1
        ORDER BY players_online DESC 
        LIMIT 10
    ")->fetchAll();
});

// Новые серверы
$newServers = cacheRemember('home_new_servers', 300, function() use ($db) {
    return $db->query("
    SELECT id, name, ip, port, icon, is_online, players_online, votes_month, is_verified, created_at
    FROM servers WHERE status = 'active' 
    ORDER BY created_at DESC 
    LIMIT 10
")->fetchAll();
});

// Общая статистика (кэш 2 мин)
$stats = cacheRemember('home_stats', 120, function() use ($db) {
    return $db->query("
        SELECT 
            COUNT(*) as total_servers,
            SUM(CASE WHEN is_online = 1 THEN players_online ELSE 0 END) as total_players,
            SUM(CASE WHEN is_online = 1 THEN 1 ELSE 0 END) as online_servers
        FROM servers WHERE status = 'active'
    ")->fetch();
});

$votesToday = cacheRemember('home_votes_today', 120, function() use ($db) {
    return (int)$db->query("SELECT COUNT(*) FROM votes WHERE DATE(voted_at) = CURDATE()")->fetchColumn();
});
?>

<!-- Hero -->
<section class="hero">
    <h1>📡 <?= SITE_NAME ?></h1>
    <p class="hero-subtitle">Мониторинг серверов Minecraft — рейтинг, статистика, голосование</p>

    <form class="search-form" action="<?= SITE_URL ?>/servers.php" method="GET">
        <input type="text" name="q" placeholder="Поиск сервера по названию или IP..." class="search-input" data-live-search="<?= SITE_URL ?>/api/search.php" autocomplete="off">
        <button type="submit" class="btn btn-primary">Найти</button>
    </form>
</section>

<!-- Статистика -->
<section class="home-stats">
    <div class="home-stat-item">
        <div class="home-stat-value"><?= (int)$stats['total_servers'] ?></div>
        <div class="home-stat-label">Серверов</div>
    </div>
    <div class="home-stat-item">
        <div class="home-stat-value"><?= (int)$stats['total_players'] ?></div>
        <div class="home-stat-label">Игроков онлайн</div>
    </div>
    <div class="home-stat-item">
        <div class="home-stat-value"><?= (int)$stats['online_servers'] ?></div>
        <div class="home-stat-label">Серверов онлайн</div>
    </div>
    <div class="home-stat-item">
        <div class="home-stat-value"><?= (int)$votesToday ?></div>
        <div class="home-stat-label">Голосов сегодня</div>
    </div>
</section>

<!-- Топ по голосам -->
<?php if (!empty($topVotes)): ?>
<section class="section">
    <div class="section-header">
        <h2 class="section-title">🏆 Топ по голосам</h2>
        <a href="<?= SITE_URL ?>/servers.php?sort=votes" class="btn btn-ghost btn-sm">Все серверы →</a>
    </div>
    <div class="server-list">
        <?php foreach ($topVotes as $i => $s): ?>
            <a href="<?= SITE_URL ?>/server.php?id=<?= $s['id'] ?>" class="server-card">
                <div class="server-rank">#<?= $i + 1 ?></div>
                <?php if ($s['icon']): ?>
                    <img src="<?= SITE_URL . '/' . e($s['icon']) ?>" alt="" class="server-card-icon">
                <?php else: ?>
                    <div class="server-card-icon" style="display:flex;align-items:center;justify-content:center;background:var(--bg);font-size:1.5rem;">📡</div>
                <?php endif; ?>
                <div class="server-card-info">
                    <div class="server-card-name"><?= e($s['name']) ?></div>
                    <div class="server-card-meta">
                        <span><?= e($s['ip'] . ':' . $s['port']) ?></span>
                        <?php if ($s['is_online']): ?>
                            <span class="badge badge-online"><?= $s['players_online'] ?>/<?= $s['players_max'] ?></span>
                        <?php else: ?>
                            <span class="badge badge-offline">Оффлайн</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="server-card-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?= $s['votes_month'] ?></span>
                        <span class="stat-label">Голосов</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Топ по онлайну -->
<?php if (!empty($topOnline)): ?>
<section class="section">
    <div class="section-header">
        <h2 class="section-title">🟢 Топ по онлайну</h2>
        <a href="<?= SITE_URL ?>/servers.php?sort=online" class="btn btn-ghost btn-sm">Все серверы →</a>
    </div>
    <div class="server-list">
        <?php foreach ($topOnline as $s): ?>
            <a href="<?= SITE_URL ?>/server.php?id=<?= $s['id'] ?>" class="server-card">
                <?php if ($s['icon']): ?>
                    <img src="<?= SITE_URL . '/' . e($s['icon']) ?>" alt="" class="server-card-icon">
                <?php else: ?>
                    <div class="server-card-icon" style="display:flex;align-items:center;justify-content:center;background:var(--bg);font-size:1.5rem;">📡</div>
                <?php endif; ?>
                <div class="server-card-info">
                    <div class="server-card-name"><?= e($s['name']) ?></div>
                    <div class="server-card-meta">
                        <span><?= e($s['ip'] . ':' . $s['port']) ?></span>
                    </div>
                </div>
                <div class="server-card-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?= $s['players_online'] ?>/<?= $s['players_max'] ?></span>
                        <span class="stat-label">Игроков</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Новые серверы -->
<?php if (!empty($newServers)): ?>
<section class="section">
    <div class="section-header">
        <h2 class="section-title">🆕 Новые серверы</h2>
        <a href="<?= SITE_URL ?>/servers.php?sort=new" class="btn btn-ghost btn-sm">Все серверы →</a>
    </div>
    <div class="server-list">
        <?php foreach ($newServers as $s): ?>
            <a href="<?= SITE_URL ?>/server.php?id=<?= $s['id'] ?>" class="server-card">
                <?php if ($s['icon']): ?>
                    <img src="<?= SITE_URL . '/' . e($s['icon']) ?>" alt="" class="server-card-icon">
                <?php else: ?>
                    <div class="server-card-icon" style="display:flex;align-items:center;justify-content:center;background:var(--bg);font-size:1.5rem;">📡</div>
                <?php endif; ?>
                <div class="server-card-info">
                    <div class="server-card-name"><?= e($s['name']) ?></div>
                    <div class="server-card-meta">
                        <span><?= e($s['ip'] . ':' . $s['port']) ?></span>
                        <?php if ($s['is_online']): ?>
                            <span class="badge badge-online">Онлайн</span>
                        <?php else: ?>
                            <span class="badge badge-offline">Оффлайн</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="server-card-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?= $s['votes_month'] ?></span>
                        <span class="stat-label">Голосов</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php if (empty($topVotes) && empty($topOnline) && empty($newServers)): ?>
<section class="section" style="text-align: center; padding: 40px 0;">
    <p style="color: var(--text-muted); margin-bottom: 16px;">Серверов пока нет. Будьте первым!</p>
    <?php if (isLoggedIn()): ?>
        <a href="<?= SITE_URL ?>/dashboard/add.php" class="btn btn-primary">Добавить сервер</a>
    <?php else: ?>
        <a href="<?= SITE_URL ?>/register.php" class="btn btn-primary">Зарегистрироваться</a>
    <?php endif; ?>
</section>
<?php endif; ?>

<style>
    .hero {
        text-align: center;
        padding: 60px 0 40px;
    }
    .hero h1 {
        font-size: 2.2rem;
        margin-bottom: 8px;
    }
    .hero-subtitle {
        color: var(--text-muted);
        font-size: 1.1rem;
        margin-bottom: 30px;
    }
    .search-form {
        display: flex;
        max-width: 600px;
        margin: 0 auto;
        gap: 8px;
    }
    .search-input {
        flex: 1;
        padding: 12px 16px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        color: var(--text);
        font-size: 1rem;
    }
    .search-input:focus {
        outline: none;
        border-color: var(--accent);
    }
    .home-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px;
        margin-bottom: 30px;
    }
    .home-stat-item {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 20px;
        text-align: center;
    }
    .home-stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--accent);
    }
    .home-stat-label {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-top: 4px;
    }
    .section {
        margin-top: 36px;
    }
    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    .server-rank {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-muted);
        min-width: 36px;
        text-align: center;
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
