<?php
/**
 * CraftRadar — Каталог серверов
 */

$pageTitle = 'Каталог серверов';
$pageDescription = 'Список серверов Minecraft — поиск, фильтры, рейтинг.';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

// Параметры
$page = max(1, getInt('page', 1));
$sort = get('sort', 'votes');
$search = get('q');
$version = get('version');
$mode = get('mode');
$onlineOnly = get('online') === '1';

// Построение запроса
$where = ["s.status = 'active'"];
$params = [];

if ($search) {
    $where[] = '(s.name LIKE ? OR s.ip LIKE ?)';
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($version) {
    $where[] = 's.version LIKE ?';
    $params[] = "%{$version}%";
}

if ($mode) {
    $where[] = 's.game_mode = ?';
    $params[] = $mode;
}

if ($onlineOnly) {
    $where[] = 's.is_online = 1';
}

$whereSQL = implode(' AND ', $where);

// Сортировка
$orderMap = [
    'votes'   => 's.votes_month DESC, s.votes_total DESC',
    'online'  => 's.players_online DESC',
    'new'     => 's.created_at DESC',
    'rating'  => 's.rating DESC, s.reviews_count DESC',
];
$orderSQL = $orderMap[$sort] ?? $orderMap['votes'];

// Promoted серверы всегда вверху
$orderSQL = 's.is_promoted DESC, ' . $orderSQL;

// Подсчёт
$stmt = $db->prepare("SELECT COUNT(*) FROM servers s WHERE {$whereSQL}");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

// Выборка
$offset = ($page - 1) * SERVERS_PER_PAGE;
$stmt = $db->prepare("
    SELECT s.* FROM servers s 
    WHERE {$whereSQL} 
    ORDER BY {$orderSQL} 
    LIMIT " . SERVERS_PER_PAGE . " OFFSET {$offset}
");
$stmt->execute($params);
$servers = $stmt->fetchAll();

// Категории для фильтра
$categories = $db->query('SELECT slug, name, icon FROM categories WHERE is_active = 1 ORDER BY sort_order')->fetchAll();

// Базовый URL для пагинации
$baseUrl = SITE_URL . '/servers.php?sort=' . urlencode($sort);
if ($search) $baseUrl .= '&q=' . urlencode($search);
if ($version) $baseUrl .= '&version=' . urlencode($version);
if ($mode) $baseUrl .= '&mode=' . urlencode($mode);
if ($onlineOnly) $baseUrl .= '&online=1';
?>

<div class="catalog">
    <h1 class="section-title">Каталог серверов</h1>

    <!-- Фильтры -->
    <div class="catalog-filters card">
        <form method="GET" action="" class="filters-form">
            <div class="filters-row">
                <input type="text" name="q" value="<?= e($search) ?>" placeholder="Поиск по названию или IP..." class="filter-search">

                <select name="mode">
                    <option value="">Все режимы</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['slug']) ?>" <?= $mode === $cat['slug'] ? 'selected' : '' ?>>
                            <?= e($cat['icon'] . ' ' . $cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input type="text" name="version" value="<?= e($version) ?>" placeholder="Версия (1.20...)" style="max-width: 140px;">

                <label class="filter-checkbox">
                    <input type="checkbox" name="online" value="1" <?= $onlineOnly ? 'checked' : '' ?>>
                    Только онлайн
                </label>

                <button type="submit" class="btn btn-primary btn-sm">Найти</button>
            </div>
        </form>

        <!-- Сортировка -->
        <div class="sort-tabs">
            <a href="?sort=votes<?= $search ? '&q=' . urlencode($search) : '' ?>" class="sort-tab <?= $sort === 'votes' ? 'active' : '' ?>">По голосам</a>
            <a href="?sort=online<?= $search ? '&q=' . urlencode($search) : '' ?>" class="sort-tab <?= $sort === 'online' ? 'active' : '' ?>">По онлайну</a>
            <a href="?sort=rating<?= $search ? '&q=' . urlencode($search) : '' ?>" class="sort-tab <?= $sort === 'rating' ? 'active' : '' ?>">По рейтингу</a>
            <a href="?sort=new<?= $search ? '&q=' . urlencode($search) : '' ?>" class="sort-tab <?= $sort === 'new' ? 'active' : '' ?>">Новые</a>
        </div>
    </div>

    <!-- Результаты -->
    <div class="catalog-results" style="margin-top: 4px; color: var(--text-muted); font-size: 0.85rem; margin-bottom: 12px;">
        Найдено серверов: <?= $total ?>
    </div>

    <?php if (empty($servers)): ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <p style="color: var(--text-muted);">Серверы не найдены. Попробуйте изменить фильтры.</p>
        </div>
    <?php else: ?>
        <div class="server-list">
            <?php foreach ($servers as $s): ?>
                <a href="<?= SITE_URL ?>/server.php?id=<?= $s['id'] ?>" class="server-card">
                    <?php if ($s['icon']): ?>
                        <img src="<?= SITE_URL . '/' . e($s['icon']) ?>" alt="" class="server-card-icon">
                    <?php else: ?>
                        <div class="server-card-icon" style="display:flex;align-items:center;justify-content:center;background:var(--bg);font-size:1.5rem;">📡</div>
                    <?php endif; ?>

                    <div class="server-card-info">
                        <div class="server-card-name">
                            <?php if ($s['is_promoted']): ?><span style="color: var(--warning);">⭐</span><?php endif; ?>
                            <?= e($s['name']) ?>
                        </div>
                        <div class="server-card-meta">
                            <span><?= e($s['ip'] . ':' . $s['port']) ?></span>
                            <?php if ($s['version']): ?><span>v<?= e($s['version']) ?></span><?php endif; ?>
                            <?php if ($s['game_mode']): ?><span><?= e($s['game_mode']) ?></span><?php endif; ?>
                            <?php if ($s['is_online']): ?>
                                <span class="badge badge-online">Онлайн</span>
                            <?php else: ?>
                                <span class="badge badge-offline">Оффлайн</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="server-card-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?= $s['players_online'] ?></span>
                            <span class="stat-label">Игроков</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?= $s['votes_month'] ?></span>
                            <span class="stat-label">Голосов</span>
                        </div>
                        <?php if ($s['rating'] > 0): ?>
                            <div class="stat-item">
                                <span class="stat-value"><?= number_format($s['rating'], 1) ?></span>
                                <span class="stat-label">Рейтинг</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>

        <?= paginate($total, SERVERS_PER_PAGE, $page, $baseUrl) ?>
    <?php endif; ?>
</div>

<style>
    .catalog-filters {
        margin-bottom: 16px;
    }
    .filters-form {
        margin-bottom: 12px;
    }
    .filters-row {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    .filter-search {
        flex: 1;
        min-width: 200px;
        padding: 8px 14px;
        background: var(--bg-input);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        color: var(--text);
        font-size: 0.9rem;
    }
    .filter-search:focus {
        outline: none;
        border-color: var(--accent);
    }
    .filters-row select {
        padding: 8px 12px;
        background: var(--bg-input);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        color: var(--text);
        font-size: 0.85rem;
    }
    .filters-row input[type="text"] {
        padding: 8px 12px;
        background: var(--bg-input);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        color: var(--text);
        font-size: 0.85rem;
    }
    .filter-checkbox {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.85rem;
        color: var(--text-muted);
        cursor: pointer;
        white-space: nowrap;
    }
    .sort-tabs {
        display: flex;
        gap: 4px;
        border-top: 1px solid var(--border);
        padding-top: 12px;
    }
    .sort-tab {
        padding: 6px 14px;
        border-radius: var(--radius-sm);
        font-size: 0.85rem;
        color: var(--text-muted);
        transition: all var(--transition);
    }
    .sort-tab:hover,
    .sort-tab.active {
        background: var(--accent-bg);
        color: var(--accent);
    }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
