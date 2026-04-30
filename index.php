<?php
/**
 * CraftRadar — Главная страница
 */

require_once __DIR__ . '/includes/config.php';

$pageTitle = SITE_DESCRIPTION;
$pageDescription = SITE_NAME . ' — ' . SITE_TAGLINE;
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
    $stmt = $db->prepare("SELECT COUNT(*) FROM votes WHERE DATE(voted_at) = ?");
    $stmt->execute([date('Y-m-d')]);
    return (int)$stmt->fetchColumn();
});
?>

<!-- Hero -->
<section class="hero">
    <div class="hero-particles" id="heroParticles"></div>
    <div class="hero-content">
        <div class="hero-logo">⛏️</div>
        <h1 class="hero-title"><?= SITE_NAME ?></h1>
        <p class="hero-subtitle"><?= e(SITE_DESCRIPTION) ?></p>

        <form class="search-form" action="<?= SITE_URL ?>/servers.php" method="GET">
            <input type="text" name="q" placeholder="🔍 Найти сервер по названию или IP..." class="search-input" data-live-search="<?= SITE_URL ?>/api/search.php" autocomplete="off">
            <button type="submit" class="btn btn-primary">⚡ Найти</button>
        </form>

        <div class="hero-quick-links">
            <a href="<?= SITE_URL ?>/servers.php?sort=votes" class="hero-link">🏆 Топ серверов</a>
            <a href="<?= SITE_URL ?>/servers.php?sort=online" class="hero-link">🟢 Онлайн сейчас</a>
            <a href="<?= SITE_URL ?>/servers.php?sort=new" class="hero-link">🆕 Новые</a>
            <?php if (!isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/register.php" class="hero-link hero-link-accent">📡 Добавить сервер</a>
            <?php else: ?>
                <a href="<?= SITE_URL ?>/dashboard/add.php" class="hero-link hero-link-accent">📡 Добавить сервер</a>
            <?php endif; ?>
        </div>
    </div>
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
    <?= serverList($topVotes, true) ?>
</section>
<?php endif; ?>

<?php if (!empty($topOnline)): ?>
<section class="section">
    <div class="section-header">
        <h2 class="section-title">🟢 Топ по онлайну</h2>
        <a href="<?= SITE_URL ?>/servers.php?sort=online" class="btn btn-ghost btn-sm">Все серверы →</a>
    </div>
    <?= serverList($topOnline) ?>
</section>
<?php endif; ?>

<?php if (!empty($newServers)): ?>
<section class="section">
    <div class="section-header">
        <h2 class="section-title">🆕 Новые серверы</h2>
        <a href="<?= SITE_URL ?>/servers.php?sort=new" class="btn btn-ghost btn-sm">Все серверы →</a>
    </div>
    <?= serverList($newServers) ?>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
