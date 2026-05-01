<?php
/**
 * CraftRadar — Профиль пользователя (достижения, избранное, ник)
 */

$pageTitle = 'Мой профиль';
require_once __DIR__ . '/../includes/header.php';
require_once INCLUDES_PATH . 'achievements.php';
require_once INCLUDES_PATH . 'points.php';

requireAuth();

$userId = currentUserId();
$user = currentUser();
$userPoints = getUserPoints($userId);

// Сохранение ника
if (isPost() && post('action') === 'save_nick') {
    if (verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $nick = post('minecraft_nick');
        if ($nick && !preg_match('/^[a-zA-Z0-9_]{3,16}$/', $nick)) {
            setFlash('error', 'Некорректный ник (3-16 символов, латиница, цифры, _).');
        } else {
            setUserMinecraftNick($userId, $nick);
            setFlash('success', 'Ник сохранён!');
        }
        redirect(SITE_URL . '/dashboard/profile.php');
    }
}

$mcNick = getUserMinecraftNick($userId);
$achievements = getUserAchievements($userId);
$favorites = getUserFavorites($userId);
$earnedCount = count(array_filter($achievements, fn($a) => !empty($a['earned_at'])));
$totalCount = count($achievements);

$db = getDB();
$totalVotes = (int)$db->prepare('SELECT COUNT(*) FROM votes WHERE user_id = ?')->execute([$userId]) ? 0 : 0;
$stmt = $db->prepare('SELECT COUNT(*) FROM votes WHERE user_id = ?');
$stmt->execute([$userId]);
$totalVotes = (int)$stmt->fetchColumn();

$totalReviews = 0;
$stmt = $db->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = ?');
$stmt->execute([$userId]);
$totalReviews = (int)$stmt->fetchColumn();
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>👤 Мой профиль</h1>
        <a href="<?= SITE_URL ?>/dashboard/" class="btn btn-ghost">← Кабинет</a>
    </div>

    <!-- Сводка -->
    <div class="admin-stats-grid" style="margin-bottom: 20px;">
        <div class="admin-stat-card">
            <div class="admin-stat-value"><?= e($user['username']) ?></div>
            <div class="admin-stat-label"><?= $mcNick ? '🎮 ' . e($mcNick) : 'Ник не указан' ?></div>
        </div>
        <div class="admin-stat-card" style="border-color: var(--gold);">
            <div class="admin-stat-value" style="color: var(--gold);"><?= $userPoints ?> 💎</div>
            <div class="admin-stat-label">Баллов</div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-value"><?= $totalVotes ?></div>
            <div class="admin-stat-label">Голосов</div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-value"><?= $totalReviews ?></div>
            <div class="admin-stat-label">Отзывов</div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-value"><?= $user['daily_streak'] ?? 0 ?> 🔥</div>
            <div class="admin-stat-label">Дней подряд</div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-value"><?= $earnedCount ?>/<?= $totalCount ?></div>
            <div class="admin-stat-label">Достижений</div>
        </div>
    </div>

    <!-- Minecraft ник -->
    <div class="card" style="margin-bottom: 16px;">
        <h2 class="section-title">🎮 Minecraft ник</h2>
        <form method="POST" style="display: flex; gap: 8px; align-items: end;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_nick">
            <div class="form-group" style="margin: 0; flex: 1;">
                <input type="text" name="minecraft_nick" value="<?= e($mcNick) ?>" 
                       placeholder="Ваш ник в Minecraft" pattern="[a-zA-Z0-9_]{3,16}" maxlength="16">
            </div>
            <button type="submit" class="btn btn-sm btn-primary">Сохранить</button>
        </form>
        <p style="color: var(--text-muted); font-size: 0.75rem; margin-top: 6px;">
            Ник автоматически подставляется при голосовании за серверы.
        </p>
    </div>

    <!-- Достижения -->
    <div class="card" style="margin-bottom: 16px;">
        <h2 class="section-title">🏆 Достижения (<?= $earnedCount ?>/<?= $totalCount ?>)</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 8px;">
            <?php foreach ($achievements as $a): ?>
                <?php $earned = !empty($a['earned_at']); ?>
                <div style="padding: 12px; border: 2px solid <?= $earned ? 'var(--gold)' : 'var(--border)' ?>; 
                            background: <?= $earned ? 'rgba(255,215,0,0.05)' : 'var(--bg)' ?>;
                            opacity: <?= $earned ? '1' : '0.5' ?>;">
                    <div style="font-size: 1.5rem; margin-bottom: 4px;"><?= e($a['icon']) ?></div>
                    <div style="font-weight: 700; font-size: 0.85rem;"><?= e($a['name']) ?></div>
                    <div style="color: var(--text-muted); font-size: 0.7rem;"><?= e($a['description']) ?></div>
                    <?php if ($a['points_reward'] > 0): ?>
                        <div style="color: var(--gold); font-size: 0.7rem; margin-top: 4px;">+<?= $a['points_reward'] ?> 💎</div>
                    <?php endif; ?>
                    <?php if ($earned): ?>
                        <div style="color: var(--success); font-size: 0.65rem; margin-top: 4px;">✓ <?= formatDate($a['earned_at']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Избранные серверы -->
    <div class="card">
        <h2 class="section-title">❤️ Избранные серверы (<?= count($favorites) ?>)</h2>
        <?php if (empty($favorites)): ?>
            <p style="color: var(--text-muted);">Нет избранных серверов. Нажмите 🤍 на странице сервера чтобы добавить.</p>
        <?php else: ?>
            <?= serverList($favorites) ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
