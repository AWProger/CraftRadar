<?php
/**
 * CraftRadar — Публичный профиль пользователя
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$id = getInt('id');

if (!$id) {
    setFlash('error', 'Пользователь не найден.');
    redirect(SITE_URL . '/');
}

$stmt = $db->prepare('SELECT id, username, role, created_at, points, daily_streak FROM users WHERE id = ? AND is_banned = 0');
$stmt->execute([$id]);
$profile = $stmt->fetch();

if (!$profile) {
    setFlash('error', 'Пользователь не найден.');
    redirect(SITE_URL . '/');
}

// MC ник
$mcNick = '';
try {
    $stmt = $db->prepare('SELECT minecraft_nick FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $mcNick = $stmt->fetchColumn() ?: '';
} catch (\Exception $e) {}

$pageTitle = $profile['username'];

// Статистика
$stmt = $db->prepare('SELECT COUNT(*) FROM votes WHERE user_id = ?');
$stmt->execute([$id]);
$totalVotes = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ? AND status = 'active'");
$stmt->execute([$id]);
$totalReviews = (int)$stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM servers WHERE user_id = ? AND status IN ('active', 'pending')");
$stmt->execute([$id]);
$totalServers = (int)$stmt->fetchColumn();

// Достижения
$achievements = [];
$earnedCount = 0;
try {
    require_once __DIR__ . '/includes/achievements.php';
    $achievements = getUserAchievements($id);
    $earnedCount = count(array_filter($achievements, fn($a) => !empty($a['earned_at'])));
} catch (\Exception $e) {}

// Серверы пользователя
$servers = $db->prepare("SELECT * FROM servers WHERE user_id = ? AND status IN ('active', 'pending') ORDER BY votes_month DESC");
$servers->execute([$id]);
$servers = $servers->fetchAll();

// Отзывы
$reviews = $db->prepare("
    SELECT r.*, s.name as server_name, s.id as server_id 
    FROM reviews r JOIN servers s ON r.server_id = s.id 
    WHERE r.user_id = ? AND r.status = 'active' ORDER BY r.created_at DESC LIMIT 5
");
$reviews->execute([$id]);
$reviews = $reviews->fetchAll();

// Уровень
$level = 1;
$points = (int)$profile['points'];
if ($points >= 500) $level = 10;
elseif ($points >= 200) $level = 7;
elseif ($points >= 100) $level = 5;
elseif ($points >= 50) $level = 4;
elseif ($points >= 20) $level = 3;
elseif ($points >= 5) $level = 2;

$levelNames = [1 => 'Новичок', 2 => 'Игрок', 3 => 'Активный', 4 => 'Опытный', 5 => 'Ветеран', 7 => 'Мастер', 10 => 'Легенда'];
$levelName = $levelNames[$level] ?? 'Уровень ' . $level;

$roleBadges = ['admin' => '👑 Админ', 'moderator' => '🛡️ Модератор', 'user' => ''];

require_once __DIR__ . '/includes/header.php';
?>

<div class="dashboard">
    <!-- Карточка -->
    <div class="profile-card">
        <div class="profile-avatar">
            <?php if ($mcNick): ?>
                <img src="https://mc-heads.net/avatar/<?= e($mcNick) ?>/64" alt="" class="profile-avatar-img">
            <?php else: ?>
                <div class="profile-avatar-placeholder">👤</div>
            <?php endif; ?>
        </div>
        <div class="profile-info">
            <h1 class="profile-name">
                <?= e($profile['username']) ?>
                <?php if (!empty($roleBadges[$profile['role']])): ?>
                    <span style="font-size: 0.6rem; vertical-align: middle;"><?= $roleBadges[$profile['role']] ?></span>
                <?php endif; ?>
            </h1>
            <?php if ($mcNick): ?>
                <div class="profile-nick">🎮 <?= e($mcNick) ?></div>
            <?php endif; ?>
            <div class="profile-meta">
                Уровень <?= $level ?>: <?= $levelName ?>
                · Зарегистрирован <?= formatDate($profile['created_at']) ?>
                <?php if ($profile['daily_streak'] > 0): ?> · 🔥 <?= $profile['daily_streak'] ?> дн.<?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Статистика -->
    <div class="profile-stats">
        <div class="profile-stat">
            <div class="profile-stat-value" style="color: var(--gold);"><?= $points ?></div>
            <div class="profile-stat-icon">💎</div>
            <div class="profile-stat-label">Алмазов</div>
        </div>
        <div class="profile-stat">
            <div class="profile-stat-value"><?= $totalVotes ?></div>
            <div class="profile-stat-icon">👍</div>
            <div class="profile-stat-label">Голосов</div>
        </div>
        <div class="profile-stat">
            <div class="profile-stat-value"><?= $totalReviews ?></div>
            <div class="profile-stat-icon">💬</div>
            <div class="profile-stat-label">Отзывов</div>
        </div>
        <div class="profile-stat">
            <div class="profile-stat-value"><?= $totalServers ?></div>
            <div class="profile-stat-icon">📡</div>
            <div class="profile-stat-label">Серверов</div>
        </div>
        <div class="profile-stat">
            <div class="profile-stat-value"><?= $earnedCount ?></div>
            <div class="profile-stat-icon">🏆</div>
            <div class="profile-stat-label">Достижений</div>
        </div>
    </div>

    <!-- Достижения -->
    <?php if (!empty($achievements)): ?>
    <div class="card" style="margin-bottom: 16px;">
        <h2 class="section-title">🏆 Достижения</h2>
        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
            <?php foreach ($achievements as $a): ?>
                <?php if (!empty($a['earned_at'])): ?>
                    <span title="<?= e($a['name'] . ': ' . $a['description']) ?>" 
                          style="font-size: 1.5rem; cursor: help;"><?= e($a['icon']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($earnedCount === 0): ?>
                <span style="color: var(--text-muted); font-size: 0.8rem;">Пока нет достижений</span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Серверы -->
    <?php if (!empty($servers)): ?>
    <div class="card" style="margin-bottom: 16px;">
        <h2 class="section-title">📡 Серверы <?= e($profile['username']) ?></h2>
        <?php require_once __DIR__ . '/includes/components.php'; ?>
        <?= serverList($servers) ?>
    </div>
    <?php endif; ?>

    <!-- Отзывы -->
    <?php if (!empty($reviews)): ?>
    <div class="card">
        <h2 class="section-title">💬 Последние отзывы</h2>
        <?php foreach ($reviews as $r): ?>
            <div style="padding: 8px 0; border-bottom: 1px solid var(--border);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                    <a href="<?= SITE_URL ?>/server.php?id=<?= $r['server_id'] ?>"><?= e($r['server_name']) ?></a>
                    <span class="stars" style="font-size: 0.8rem;"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?></span>
                </div>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 4px;"><?= e(truncate($r['text'], 100)) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<style>
    .profile-card { display: flex; align-items: center; gap: 16px; background: var(--bg-card); border: var(--pixel-border) var(--border); padding: 20px; margin-bottom: 16px; box-shadow: var(--shadow); }
    .profile-avatar-img { width: 64px; height: 64px; border: 3px solid var(--border); image-rendering: pixelated; }
    .profile-avatar-placeholder { width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; background: var(--bg); border: 3px solid var(--border); font-size: 2rem; }
    .profile-name { font-family: var(--font-mc); font-size: 0.85rem; color: var(--accent); text-shadow: 2px 2px 0 rgba(0,0,0,0.5); }
    .profile-nick { color: var(--text-muted); font-size: 0.85rem; margin-top: 2px; }
    .profile-meta { color: var(--text-muted); font-size: 0.75rem; margin-top: 4px; }
    .profile-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 8px; margin-bottom: 16px; }
    .profile-stat { background: var(--bg-card); border: var(--pixel-border) var(--border); padding: 14px 8px; text-align: center; box-shadow: var(--shadow); }
    .profile-stat-value { font-family: var(--font-mc); font-size: 0.85rem; font-weight: 700; color: var(--accent); text-shadow: 1px 1px 0 rgba(0,0,0,0.5); }
    .profile-stat-icon { font-size: 1.2rem; margin: 4px 0; }
    .profile-stat-label { font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; }
    @media (max-width: 768px) { .profile-card { flex-direction: column; text-align: center; } .profile-stats { grid-template-columns: repeat(3, 1fr); } }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
