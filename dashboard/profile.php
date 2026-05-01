<?php
/**
 * CraftRadar — Профиль пользователя
 */

$pageTitle = 'Мой профиль';
require_once __DIR__ . '/../includes/header.php';
require_once INCLUDES_PATH . 'points.php';

requireAuth();

$userId = currentUserId();
$user = currentUser();
$userPoints = getUserPoints($userId);

// Minecraft ник
try {
    require_once INCLUDES_PATH . 'achievements.php';
    $mcNick = getUserMinecraftNick($userId);
    $achievements = getUserAchievements($userId);
    $favorites = getUserFavorites($userId);
    $earnedCount = count(array_filter($achievements, fn($a) => !empty($a['earned_at'])));
    $totalAchievements = count($achievements);
} catch (\Exception $e) {
    $mcNick = '';
    $achievements = [];
    $favorites = [];
    $earnedCount = 0;
    $totalAchievements = 0;
}

// Сохранение ника
if (isPost() && post('action') === 'save_nick') {
    if (verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $nick = post('minecraft_nick');
        if ($nick && !preg_match('/^[a-zA-Z0-9_]{3,16}$/', $nick)) {
            setFlash('error', 'Некорректный ник (3-16 символов, латиница, цифры, _).');
        } else {
            try { setUserMinecraftNick($userId, $nick); } catch (\Exception $e) {}
            setFlash('success', 'Ник сохранён!');
        }
        redirect(SITE_URL . '/dashboard/profile.php');
    }
}

$db = getDB();
$stmt = $db->prepare('SELECT COUNT(*) FROM votes WHERE user_id = ?');
$stmt->execute([$userId]);
$totalVotes = (int)$stmt->fetchColumn();

$stmt = $db->prepare('SELECT COUNT(*) FROM reviews WHERE user_id = ?');
$stmt->execute([$userId]);
$totalReviews = (int)$stmt->fetchColumn();

$dailyStreak = (int)($user['daily_streak'] ?? 0);
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>👤 Мой профиль</h1>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="<?= SITE_URL ?>/dashboard/" class="btn btn-sm btn-outline">📡 Мои серверы</a>
            <a href="<?= SITE_URL ?>/dashboard/points.php" class="btn btn-sm btn-ghost">💎 Баллы</a>
            <a href="<?= SITE_URL ?>/dashboard/settings.php" class="btn btn-sm btn-ghost">⚙️ Настройки</a>
        </div>
    </div>

    <!-- Карточка профиля -->
    <div class="profile-card">
        <div class="profile-avatar">
            <?php if ($mcNick): ?>
                <img src="https://mc-heads.net/avatar/<?= e($mcNick) ?>/64" alt="" class="profile-avatar-img">
            <?php else: ?>
                <div class="profile-avatar-placeholder">👤</div>
            <?php endif; ?>
        </div>
        <div class="profile-info">
            <h2 class="profile-name"><?= e($user['username']) ?></h2>
            <?php if ($mcNick): ?>
                <div class="profile-nick">🎮 <?= e($mcNick) ?></div>
            <?php endif; ?>
            <div class="profile-meta">
                Зарегистрирован <?= formatDate($user['created_at']) ?>
                <?php if ($dailyStreak > 0): ?> · 🔥 <?= $dailyStreak ?> дн. подряд<?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Статистика -->
    <div class="profile-stats">
        <div class="profile-stat">
            <div class="profile-stat-value" style="color: var(--diamond);"><?= $userPoints ?></div>
            <div class="profile-stat-icon">💎</div>
            <div class="profile-stat-label">Алмазов</div>
        </div>
        <div class="profile-stat">
            <?php $userCoins = 0; try { require_once INCLUDES_PATH . 'coins.php'; $userCoins = getUserCoins($userId); } catch(\Exception $e) {} ?>
            <div class="profile-stat-value" style="color: var(--gold);"><?= $userCoins ?></div>
            <div class="profile-stat-icon">💰</div>
            <div class="profile-stat-label">Монет</div>
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
            <div class="profile-stat-value"><?= count($favorites) ?></div>
            <div class="profile-stat-icon">❤️</div>
            <div class="profile-stat-label">Избранных</div>
        </div>
        <div class="profile-stat">
            <div class="profile-stat-value"><?= $earnedCount ?>/<?= $totalAchievements ?></div>
            <div class="profile-stat-icon">🏆</div>
            <div class="profile-stat-label">Достижений</div>
        </div>
    </div>

    <!-- Minecraft ник -->
    <div class="card" style="margin-bottom: 16px;">
        <h2 class="section-title">🎮 Minecraft ник</h2>
        <form method="POST" style="display: flex; gap: 8px; align-items: end; flex-wrap: wrap;">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="save_nick">
            <div class="form-group" style="margin: 0; flex: 1; min-width: 200px;">
                <input type="text" name="minecraft_nick" value="<?= e($mcNick) ?>" 
                       placeholder="Ваш ник в Minecraft" pattern="[a-zA-Z0-9_]{3,16}" maxlength="16">
            </div>
            <button type="submit" class="btn btn-sm btn-primary">Сохранить</button>
        </form>
    </div>

    <!-- Реферальная программа -->
    <div class="card" style="margin-bottom: 16px;">
        <h2 class="section-title">👥 Реферальная программа</h2>
        <?php
        try {
            require_once INCLUDES_PATH . 'referrals.php';
            $refCode = getReferralCode($userId);
            $refLink = getReferralLink($userId);
            $refStats = getReferralStats($userId);
        } catch (\Exception $e) {
            $refCode = '—'; $refLink = ''; $refStats = ['count' => 0, 'total_reward' => 0];
        }
        ?>
        <div style="display: flex; gap: 16px; margin-bottom: 12px; flex-wrap: wrap;">
            <div style="text-align: center;">
                <div style="font-family: var(--font-mc); font-size: 0.85rem; color: var(--accent);"><?= $refStats['count'] ?></div>
                <div style="font-size: 0.65rem; color: var(--text-muted);">ПРИГЛАШЕНО</div>
            </div>
            <div style="text-align: center;">
                <div style="font-family: var(--font-mc); font-size: 0.85rem; color: var(--gold);"><?= $refStats['total_reward'] ?> 💎</div>
                <div style="font-size: 0.65rem; color: var(--text-muted);">ЗАРАБОТАНО</div>
            </div>
        </div>
        <div class="form-group" style="margin-bottom: 8px;">
            <label>Ваша реферальная ссылка</label>
            <input type="text" readonly value="<?= e($refLink) ?>" onclick="this.select(); navigator.clipboard.writeText(this.value).then(function(){});" style="cursor: pointer;">
        </div>
        <p style="color: var(--text-muted); font-size: 0.7rem;">
            Приглашайте друзей! Вы получите +<?= REFERRAL_REWARD_REGISTER ?> 💎 за каждого, кто зарегистрируется по вашей ссылке.
            Приглашённый получит +<?= REFERRAL_REWARD_REFERRED ?> 💎 бонус.
        </p>
    </div>

    <!-- Достижения -->
    <?php if (!empty($achievements)): ?>
    <div class="card" style="margin-bottom: 16px;">
        <h2 class="section-title">🏆 Достижения (<?= $earnedCount ?>/<?= $totalAchievements ?>)</h2>
        <div class="achievements-grid">
            <?php foreach ($achievements as $a): ?>
                <?php $earned = !empty($a['earned_at']); ?>
                <div class="achievement-card <?= $earned ? 'achievement-earned' : 'achievement-locked' ?>">
                    <div class="achievement-icon"><?= e($a['icon']) ?></div>
                    <div class="achievement-info">
                        <div class="achievement-name"><?= e($a['name']) ?></div>
                        <div class="achievement-desc"><?= e($a['description']) ?></div>
                        <?php if (!$earned): ?>
                            <?php
                            try {
                                $progress = getAchievementProgress($userId, $a['slug']);
                                if ($progress['target'] > 0):
                                    $pct = round(($progress['current'] / $progress['target']) * 100);
                            ?>
                            <div style="margin-top: 4px;">
                                <div style="height: 4px; background: var(--bg); border: 1px solid var(--border);">
                                    <div style="height: 100%; width: <?= $pct ?>%; background: var(--accent);"></div>
                                </div>
                                <div style="font-size: 0.6rem; color: var(--text-muted); margin-top: 1px;"><?= $progress['current'] ?>/<?= $progress['target'] ?></div>
                            </div>
                            <?php endif; } catch (\Exception $e) {} ?>
                        <?php endif; ?>
                        <?php if ($a['points_reward'] > 0): ?>
                            <div class="achievement-reward">+<?= $a['points_reward'] ?> 💎</div>
                        <?php endif; ?>
                    </div>
                    <?php if ($earned): ?>
                        <div class="achievement-check">✓</div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Избранные серверы -->
    <div class="card" style="margin-bottom: 16px;">
        <h2 class="section-title">❤️ Избранные серверы (<?= count($favorites) ?>)</h2>
        <?php if (empty($favorites)): ?>
            <p style="color: var(--text-muted);">Нажмите 🤍 на странице сервера чтобы добавить в избранное.</p>
        <?php else: ?>
            <?= serverList($favorites) ?>
        <?php endif; ?>
    </div>

    <!-- Мои отзывы -->
    <?php
    $myReviews = $db->prepare("
        SELECT r.*, s.name as server_name, s.id as server_id 
        FROM reviews r JOIN servers s ON r.server_id = s.id 
        WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 10
    ");
    $myReviews->execute([$userId]);
    $myReviews = $myReviews->fetchAll();
    ?>
    <?php if (!empty($myReviews)): ?>
    <div class="card">
        <h2 class="section-title">💬 Мои отзывы (<?= $totalReviews ?>)</h2>
        <?php foreach ($myReviews as $r): ?>
            <div style="padding: 10px 0; border-bottom: 1px solid var(--border);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 4px;">
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
    /* Карточка профиля */
    .profile-card {
        display: flex;
        align-items: center;
        gap: 16px;
        background: var(--bg-card);
        border: var(--pixel-border) var(--border);
        padding: 20px;
        margin-bottom: 16px;
        box-shadow: var(--shadow);
    }
    .profile-avatar-img {
        width: 64px; height: 64px;
        border: 3px solid var(--border);
        image-rendering: pixelated;
    }
    .profile-avatar-placeholder {
        width: 64px; height: 64px;
        display: flex; align-items: center; justify-content: center;
        background: var(--bg); border: 3px solid var(--border);
        font-size: 2rem;
    }
    .profile-name {
        font-family: var(--font-mc); font-size: 0.85rem;
        color: var(--accent); text-shadow: 2px 2px 0 rgba(0,0,0,0.5);
    }
    .profile-nick { color: var(--text-muted); font-size: 0.85rem; margin-top: 2px; }
    .profile-meta { color: var(--text-muted); font-size: 0.75rem; margin-top: 4px; }

    /* Статистика */
    .profile-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
        gap: 8px;
        margin-bottom: 16px;
    }
    .profile-stat {
        background: var(--bg-card);
        border: var(--pixel-border) var(--border);
        padding: 14px 8px;
        text-align: center;
        box-shadow: var(--shadow);
        transition: border-color var(--transition);
    }
    .profile-stat:hover { border-color: var(--accent); }
    .profile-stat-value {
        font-family: var(--font-mc); font-size: 0.85rem; font-weight: 700;
        color: var(--accent); text-shadow: 1px 1px 0 rgba(0,0,0,0.5);
    }
    .profile-stat-icon { font-size: 1.2rem; margin: 4px 0; }
    .profile-stat-label { font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; }

    /* Достижения */
    .achievements-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 8px;
    }
    .achievement-card {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 12px;
        border: 2px solid var(--border);
        transition: all var(--transition);
    }
    .achievement-earned {
        border-color: var(--gold);
        background: rgba(255, 215, 0, 0.05);
    }
    .achievement-locked { opacity: 0.4; }
    .achievement-icon { font-size: 1.5rem; flex-shrink: 0; }
    .achievement-info { flex: 1; min-width: 0; }
    .achievement-name { font-weight: 700; font-size: 0.8rem; }
    .achievement-desc { color: var(--text-muted); font-size: 0.7rem; }
    .achievement-reward { color: var(--gold); font-size: 0.65rem; margin-top: 2px; }
    .achievement-check { color: var(--success); font-weight: 700; font-size: 1rem; }

    @media (max-width: 768px) {
        .profile-card { flex-direction: column; text-align: center; }
        .profile-stats { grid-template-columns: repeat(3, 1fr); }
        .achievements-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 480px) {
        .profile-stats { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
