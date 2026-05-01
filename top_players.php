<?php
/**
 * CraftRadar — Топ игроков
 */

$pageTitle = 'Топ игроков';
require_once __DIR__ . '/includes/header.php';

$db = getDB();

$tab = get('tab', 'points');

// Топ по баллам
$topPoints = $db->query("SELECT id, username, points, role FROM users WHERE is_banned = 0 ORDER BY points DESC LIMIT 20")->fetchAll();

// Топ по голосам
$topVoters = $db->query("
    SELECT u.id, u.username, u.points, COUNT(v.id) as vote_count 
    FROM users u JOIN votes v ON u.id = v.user_id 
    WHERE u.is_banned = 0 
    GROUP BY u.id ORDER BY vote_count DESC LIMIT 20
")->fetchAll();

// Топ по рефералам
$topReferrers = [];
try {
    $topReferrers = $db->query("SELECT id, username, referral_count, points FROM users WHERE is_banned = 0 AND referral_count > 0 ORDER BY referral_count DESC LIMIT 20")->fetchAll();
} catch (\Exception $e) {}
?>

<div class="dashboard">
    <h1 class="section-title" style="margin-bottom: 20px;">🏆 Топ игроков</h1>

    <!-- Табы -->
    <div style="display: flex; gap: 4px; margin-bottom: 16px;">
        <a href="?tab=points" class="btn btn-sm <?= $tab === 'points' ? 'btn-primary' : 'btn-ghost' ?>">💎 По баллам</a>
        <a href="?tab=votes" class="btn btn-sm <?= $tab === 'votes' ? 'btn-primary' : 'btn-ghost' ?>">👍 По голосам</a>
        <?php if (!empty($topReferrers)): ?>
            <a href="?tab=referrals" class="btn btn-sm <?= $tab === 'referrals' ? 'btn-primary' : 'btn-ghost' ?>">👥 По рефералам</a>
        <?php endif; ?>
    </div>

    <?php if ($tab === 'points'): ?>
    <div class="server-list">
        <?php foreach ($topPoints as $i => $p): ?>
            <a href="<?= SITE_URL ?>/profile.php?id=<?= $p['id'] ?>" class="server-card">
                <div class="server-rank">#<?= $i + 1 ?></div>
                <div class="server-card-icon server-card-icon-placeholder" style="width:40px;height:40px;">
                    <?= $i < 3 ? ['🥇','🥈','🥉'][$i] : '👤' ?>
                </div>
                <div class="server-card-info">
                    <div class="server-card-name"><?= e($p['username']) ?></div>
                    <div class="server-card-meta">
                        <?php if ($p['role'] === 'admin'): ?><span>👑 Админ</span><?php elseif ($p['role'] === 'moderator'): ?><span>🛡️ Модератор</span><?php endif; ?>
                    </div>
                </div>
                <div class="server-card-stats">
                    <div class="stat-item">
                        <span class="stat-value" style="color: var(--gold);"><?= $p['points'] ?></span>
                        <span class="stat-label">💎 Баллов</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php elseif ($tab === 'votes'): ?>
    <div class="server-list">
        <?php foreach ($topVoters as $i => $p): ?>
            <a href="<?= SITE_URL ?>/profile.php?id=<?= $p['id'] ?>" class="server-card">
                <div class="server-rank">#<?= $i + 1 ?></div>
                <div class="server-card-icon server-card-icon-placeholder" style="width:40px;height:40px;">
                    <?= $i < 3 ? ['🥇','🥈','🥉'][$i] : '👤' ?>
                </div>
                <div class="server-card-info">
                    <div class="server-card-name"><?= e($p['username']) ?></div>
                </div>
                <div class="server-card-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?= $p['vote_count'] ?></span>
                        <span class="stat-label">Голосов</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <?php elseif ($tab === 'referrals' && !empty($topReferrers)): ?>
    <div class="server-list">
        <?php foreach ($topReferrers as $i => $p): ?>
            <a href="<?= SITE_URL ?>/profile.php?id=<?= $p['id'] ?>" class="server-card">
                <div class="server-rank">#<?= $i + 1 ?></div>
                <div class="server-card-icon server-card-icon-placeholder" style="width:40px;height:40px;">
                    <?= $i < 3 ? ['🥇','🥈','🥉'][$i] : '👤' ?>
                </div>
                <div class="server-card-info">
                    <div class="server-card-name"><?= e($p['username']) ?></div>
                </div>
                <div class="server-card-stats">
                    <div class="stat-item">
                        <span class="stat-value"><?= $p['referral_count'] ?></span>
                        <span class="stat-label">Рефералов</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
