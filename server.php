<?php
/**
 * CraftRadar — Страница сервера
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$db = getDB();
$id = getInt('id');

if (!$id) {
    setFlash('error', 'Сервер не найден.');
    redirect(SITE_URL . '/servers.php');
}

// Получаем сервер
$stmt = $db->prepare("
    SELECT s.*, u.username as owner_name 
    FROM servers s 
    JOIN users u ON s.user_id = u.id 
    WHERE s.id = ? AND s.status IN ('active', 'pending')
");
$stmt->execute([$id]);
$server = $stmt->fetch();

if (!$server) {
    setFlash('error', 'Сервер не найден или не активен.');
    redirect(SITE_URL . '/servers.php');
}

$pageTitle = $server['name'];
$pageDescription = truncate(strip_tags($server['description'] ?? ''), 160);
$pageImage = $server['icon'] ? SITE_URL . '/' . $server['icon'] : null;

// Проверка: может ли текущий пользователь голосовать
$canVote = false;
$voteMessage = '';
if (isLoggedIn()) {
    $stmt = $db->prepare('
        SELECT voted_at FROM votes 
        WHERE server_id = ? AND user_id = ? 
        ORDER BY voted_at DESC LIMIT 1
    ');
    $stmt->execute([$id, currentUserId()]);
    $lastVote = $stmt->fetchColumn();

    if (!$lastVote || (time() - strtotime($lastVote)) >= VOTE_COOLDOWN * 3600) {
        $canVote = true;
    } else {
        $nextVote = strtotime($lastVote) + VOTE_COOLDOWN * 3600;
        $voteMessage = 'Следующий голос: ' . date('H:i', $nextVote);
    }
}

// Отзывы
$stmt = $db->prepare('
    SELECT r.*, u.username 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.server_id = ? AND r.status = ? 
    ORDER BY r.created_at DESC
');
$stmt->execute([$id, 'active']);
$reviews = $stmt->fetchAll();

// Uptime за 30 дней
$stmt = $db->prepare('
    SELECT 
        COUNT(*) as total,
        SUM(is_online) as online_count
    FROM server_stats 
    WHERE server_id = ? AND recorded_at >= ?
');
$stmt->execute([$id, dateAgo(30, 'day')]);
$uptimeData = $stmt->fetch();
$uptime = $uptimeData['total'] > 0 
    ? round(($uptimeData['online_count'] / $uptimeData['total']) * 100, 1) 
    : 0;

require_once __DIR__ . '/includes/header.php';
?>

<div class="server-page">
    <!-- Заголовок -->
    <div class="server-header">
        <div class="server-header-left">
            <?php if ($server['icon']): ?>
                <img src="<?= SITE_URL . '/' . e($server['icon']) ?>" alt="" class="server-page-icon">
            <?php else: ?>
                <div class="server-page-icon server-page-icon-placeholder">📡</div>
            <?php endif; ?>
            <div>
                <h1 class="server-page-name">
                    <?php if ($server['is_promoted']): ?><span style="color: var(--warning);">⭐</span><?php endif; ?>
                    <?= e($server['name']) ?>
                    <?php if ($server['is_verified']): ?>
                        <span class="badge badge-online" style="font-size: 0.7rem; vertical-align: middle; margin-left: 6px;">✓ Владелец подтверждён</span>
                    <?php endif; ?>
                </h1>
                <div class="server-page-meta">
                    <span class="copy-ip" data-ip="<?= e($server['ip'] . ':' . $server['port']) ?>">
                        <?= e($server['ip'] . ':' . $server['port']) ?> 📋
                    </span>
                    <?php if ($server['is_online']): ?>
                        <span class="badge badge-online">Онлайн</span>
                    <?php else: ?>
                        <span class="badge badge-offline">Оффлайн</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="server-header-right">
            <div class="server-header-stat">
                <div class="stat-value"><?= $server['players_online'] ?>/<?= $server['players_max'] ?></div>
                <div class="stat-label">Игроков</div>
            </div>
            <div class="server-header-stat">
                <div class="stat-value"><?= $server['votes_month'] ?></div>
                <div class="stat-label">За месяц</div>
            </div>
            <div class="server-header-stat">
                <div class="stat-value"><?= $server['votes_total'] ?></div>
                <div class="stat-label">Всего</div>
            </div>
            <div class="server-header-stat">
                <div class="stat-value"><?= $uptime ?>%</div>
                <div class="stat-label">Uptime</div>
            </div>
        </div>
    </div>

    <div class="server-content">
        <!-- Левая колонка -->
        <div class="server-main">
            <!-- Описание -->
            <?php if ($server['description']): ?>
                <div class="card">
                    <h2 class="section-title">Описание</h2>
                    <div class="server-description"><?= nl2br(e($server['description'])) ?></div>
                </div>
            <?php endif; ?>

            <!-- График онлайна -->
            <div class="card" style="margin-top: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                    <h2 class="section-title" style="margin: 0;">График онлайна</h2>
                    <div class="chart-tabs">
                        <button class="chart-tab active" data-period="24h">24ч</button>
                        <button class="chart-tab" data-period="7d">7 дней</button>
                        <button class="chart-tab" data-period="30d">30 дней</button>
                    </div>
                </div>
                <canvas id="onlineChart" height="200"></canvas>
            </div>

            <!-- Отзывы -->
            <div class="card" style="margin-top: 16px;">
                <h2 class="section-title">Отзывы (<?= count($reviews) ?>)</h2>

                <?php if (isLoggedIn() && currentUserId() !== $server['user_id']): ?>
                    <?php
                    // Проверяем, оставлял ли уже отзыв
                    $stmt = $db->prepare('SELECT id FROM reviews WHERE server_id = ? AND user_id = ?');
                    $stmt->execute([$id, currentUserId()]);
                    $hasReview = $stmt->fetch();
                    ?>
                    <?php if (!$hasReview): ?>
                        <form id="reviewForm" class="review-form" style="margin-bottom: 20px;">
                            <input type="hidden" name="server_id" value="<?= $id ?>">
                            <div class="form-group">
                                <label>Оценка</label>
                                <div class="star-rating" id="starRating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star" data-value="<?= $i ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <input type="hidden" name="rating" id="ratingInput" value="0">
                            </div>
                            <div class="form-group">
                                <textarea name="text" placeholder="Ваш отзыв..." 
                                          minlength="<?= REVIEW_MIN_LENGTH ?>" maxlength="<?= REVIEW_MAX_LENGTH ?>"
                                          required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">Отправить отзыв</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (empty($reviews)): ?>
                    <p style="color: var(--text-muted);">Отзывов пока нет.</p>
                <?php else: ?>
                    <div class="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <strong><?= e($review['username']) ?></strong>
                                    <span class="stars"><?= str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']) ?></span>
                                    <span style="color: var(--text-muted); font-size: 0.8rem;"><?= formatDate($review['created_at']) ?></span>
                                </div>
                                <div class="review-text"><?= nl2br(e($review['text'])) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Правая колонка -->
        <div class="server-sidebar">
            <!-- Голосование -->
            <div class="card">
                <h3 style="margin-bottom: 12px;">Голосование</h3>
                <?php if (isLoggedIn()): ?>
                    <?php if ($canVote): ?>
                        <button class="btn btn-primary btn-block" id="voteBtn" data-server="<?= $id ?>">
                            👍 Голосовать
                        </button>
                    <?php else: ?>
                        <button class="btn btn-ghost btn-block" disabled><?= e($voteMessage) ?></button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/login.php" class="btn btn-outline btn-block">Войдите, чтобы голосовать</a>
                <?php endif; ?>
                <div style="text-align: center; margin-top: 8px; color: var(--text-muted); font-size: 0.85rem;">
                    Всего голосов: <?= $server['votes_total'] ?>
                </div>
            </div>

            <!-- Информация -->
            <div class="card" style="margin-top: 12px;">
                <h3 style="margin-bottom: 12px;">Информация</h3>
                <div class="info-list">
                    <?php if ($server['version']): ?>
                        <div class="info-item">
                            <span class="info-label">Версия</span>
                            <span><?= e($server['version']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($server['game_mode']): ?>
                        <div class="info-item">
                            <span class="info-label">Режим</span>
                            <span><?= e($server['game_mode']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($server['motd']): ?>
                        <div class="info-item">
                            <span class="info-label">MOTD</span>
                            <span><?= e($server['motd']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($server['tags']): ?>
                        <div class="info-item">
                            <span class="info-label">Теги</span>
                            <span>
                                <?php foreach (explode(',', $server['tags']) as $tag): ?>
                                    <span class="tag"><?= e(trim($tag)) ?></span>
                                <?php endforeach; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if ($server['website']): ?>
                        <div class="info-item">
                            <span class="info-label">Сайт</span>
                            <a href="<?= e($server['website']) ?>" target="_blank" rel="noopener"><?= e(parse_url($server['website'], PHP_URL_HOST)) ?></a>
                        </div>
                    <?php endif; ?>
                    <?php if ($server['rating'] > 0): ?>
                        <div class="info-item">
                            <span class="info-label">Рейтинг</span>
                            <span class="stars"><?= str_repeat('★', round($server['rating'])) . str_repeat('☆', 5 - round($server['rating'])) ?></span>
                            <span style="color: var(--text-muted);">(<?= $server['reviews_count'] ?>)</span>
                        </div>
                    <?php endif; ?>
                    <div class="info-item">
                        <span class="info-label">Добавлен</span>
                        <span><?= formatDate($server['created_at']) ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Владелец</span>
                        <span><?= e($server['owner_name']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Жалоба -->
            <?php if (isLoggedIn() && currentUserId() !== $server['user_id']): ?>
                <div style="margin-top: 12px; text-align: center;">
                    <button class="btn btn-ghost btn-sm" onclick="document.getElementById('reportModal').style.display='flex'">
                        ⚠️ Пожаловаться
                    </button>
                </div>
            <?php endif; ?>

            <!-- Верификация -->
            <?php if (isLoggedIn() && !$server['is_verified']): ?>
                <div class="card" style="margin-top: 12px; border-color: var(--info);">
                    <h3 style="margin-bottom: 8px; font-size: 0.9rem;">🔐 Это ваш сервер?</h3>
                    <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 10px;">
                        Подтвердите владение через MOTD и получите статус верифицированного владельца.
                    </p>
                    <a href="<?= SITE_URL ?>/dashboard/verify.php?id=<?= $id ?>" class="btn btn-sm btn-outline btn-block">Подтвердить владение</a>
                </div>
            <?php endif; ?>

            <!-- Виджет для сайта -->
            <?php if (isLoggedIn() && currentUserId() === $server['user_id']): ?>
                <div class="card" style="margin-top: 12px;">
                    <h3 style="margin-bottom: 8px; font-size: 0.9rem;">📋 Виджет для сайта</h3>
                    <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 10px;">Вставьте код на свой сайт:</p>
                    <div class="form-group" style="margin: 0;">
                        <label style="font-size: 0.75rem;">JS-виджет</label>
                        <input type="text" readonly value='<script src="<?= SITE_URL ?>/api/widget.php?id=<?= $id ?>"></script>' onclick="this.select()" style="font-size: 0.75rem; font-family: monospace;">
                    </div>
                    <div class="form-group" style="margin-top: 8px; margin-bottom: 0;">
                        <label style="font-size: 0.75rem;">Iframe</label>
                        <input type="text" readonly value='<iframe src="<?= SITE_URL ?>/api/widget.php?id=<?= $id ?>&format=html" width="468" height="60" frameborder="0"></iframe>' onclick="this.select()" style="font-size: 0.75rem; font-family: monospace;">
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Модалка жалобы -->
<?php if (isLoggedIn()): ?>
<div id="reportModal" class="modal" style="display:none;">
    <div class="modal-content card">
        <h3 style="margin-bottom: 16px;">Пожаловаться на сервер</h3>
        <form id="reportForm">
            <input type="hidden" name="target_type" value="server">
            <input type="hidden" name="target_id" value="<?= $id ?>">
            <div class="form-group">
                <label>Причина</label>
                <select name="reason" required>
                    <option value="">— Выберите —</option>
                    <option value="outdated">Неактуальная информация</option>
                    <option value="fraud">Мошенничество</option>
                    <option value="cheating">Накрутка</option>
                    <option value="rules">Нарушение правил</option>
                </select>
            </div>
            <div class="form-group">
                <label>Описание</label>
                <textarea name="description" placeholder="Опишите проблему..." required></textarea>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary btn-sm">Отправить</button>
                <button type="button" class="btn btn-ghost btn-sm" onclick="document.getElementById('reportModal').style.display='none'">Отмена</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
    .server-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 24px;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 16px;
    }
    .server-header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .server-page-icon {
        width: 64px;
        height: 64px;
        border-radius: var(--radius);
        object-fit: cover;
    }
    .server-page-icon-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--bg);
        font-size: 2rem;
    }
    .server-page-name {
        font-size: 1.5rem;
        margin-bottom: 4px;
    }
    .server-page-meta {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }
    .server-header-right {
        display: flex;
        gap: 24px;
    }
    .server-header-stat {
        text-align: center;
    }
    .server-content {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 20px;
    }
    .server-description {
        line-height: 1.7;
        color: var(--text);
    }
    .info-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.9rem;
        gap: 8px;
    }
    .info-label {
        color: var(--text-muted);
        white-space: nowrap;
    }
    .tag {
        display: inline-block;
        padding: 2px 8px;
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: 12px;
        font-size: 0.75rem;
        color: var(--text-muted);
    }
    .review-item {
        padding: 12px 0;
        border-bottom: 1px solid var(--border);
    }
    .review-item:last-child {
        border-bottom: none;
    }
    .review-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 6px;
        flex-wrap: wrap;
    }
    .review-text {
        color: var(--text);
        font-size: 0.9rem;
        line-height: 1.6;
    }
    .star-rating .star {
        font-size: 1.5rem;
        cursor: pointer;
        color: var(--border);
        transition: color var(--transition);
    }
    .star-rating .star.active {
        color: var(--warning);
    }
    .modal {
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 200;
    }
    .modal-content {
        max-width: 480px;
        width: 90%;
    }
    @media (max-width: 768px) {
        .server-content {
            grid-template-columns: 1fr;
        }
        .server-header {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3"></script>
<script>
// График онлайна с переключением периодов
let onlineChart = null;
const chartCanvas = document.getElementById('onlineChart');

function loadChart(period) {
    fetch('<?= SITE_URL ?>/api/server_chart.php?id=<?= $id ?>&period=' + period)
        .then(r => r.json())
        .then(data => {
            if (onlineChart) onlineChart.destroy();

            if (!data.data || data.data.length === 0) {
                chartCanvas.parentElement.querySelector('.no-chart-data')?.remove();
                const p = document.createElement('p');
                p.className = 'no-chart-data';
                p.style.cssText = 'color: var(--text-muted); text-align: center; margin-top: 8px;';
                p.textContent = 'Нет данных для графика';
                chartCanvas.parentElement.appendChild(p);
                return;
            }

            chartCanvas.parentElement.querySelector('.no-chart-data')?.remove();

            const labels = data.data.map(d => {
                const date = new Date(d.time);
                if (period === '30d') return date.toLocaleDateString('ru', {day:'2-digit', month:'2-digit'});
                if (period === '7d') return date.toLocaleDateString('ru', {day:'2-digit', month:'2-digit'}) + ' ' + date.getHours().toString().padStart(2,'0') + ':00';
                return date.getHours().toString().padStart(2,'0') + ':' + date.getMinutes().toString().padStart(2,'0');
            });

            onlineChart = new Chart(chartCanvas.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Игроков онлайн',
                        data: data.data.map(d => d.players),
                        borderColor: '#00ff80',
                        backgroundColor: 'rgba(0, 255, 128, 0.1)',
                        fill: true, tension: 0.3, pointRadius: period === '24h' ? 1 : 2,
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { ticks: { color: '#8b949e', maxTicksLimit: 12 }, grid: { color: 'rgba(48,54,61,0.5)' } },
                        y: { beginAtZero: true, ticks: { color: '#8b949e' }, grid: { color: 'rgba(48,54,61,0.5)' } }
                    }
                }
            });
        });
}

// Переключение периодов
document.querySelectorAll('.chart-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        loadChart(this.dataset.period);
    });
});

// Загружаем 24ч по умолчанию
loadChart('24h');

// Голосование
const voteBtn = document.getElementById('voteBtn');
if (voteBtn) {
    voteBtn.addEventListener('click', function() {
        fetch('<?= SITE_URL ?>/vote.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'server_id=<?= $id ?>&<?= CSRF_TOKEN_NAME ?>=<?= generateCsrfToken() ?>'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                voteBtn.textContent = '✓ Голос принят!';
                voteBtn.disabled = true;
                voteBtn.classList.remove('btn-primary');
                voteBtn.classList.add('btn-ghost');
            } else {
                alert(data.error || 'Ошибка');
            }
        });
    });
}

// Звёзды рейтинга
const stars = document.querySelectorAll('#starRating .star');
const ratingInput = document.getElementById('ratingInput');
stars.forEach(star => {
    star.addEventListener('click', function() {
        const val = parseInt(this.dataset.value);
        ratingInput.value = val;
        stars.forEach(s => {
            s.classList.toggle('active', parseInt(s.dataset.value) <= val);
        });
    });
});

// Отправка отзыва
const reviewForm = document.getElementById('reviewForm');
if (reviewForm) {
    reviewForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('<?= CSRF_TOKEN_NAME ?>', '<?= generateCsrfToken() ?>');
        fetch('<?= SITE_URL ?>/review.php', {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.error || 'Ошибка');
            }
        });
    });
}

// Отправка жалобы
const reportForm = document.getElementById('reportForm');
if (reportForm) {
    reportForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('<?= CSRF_TOKEN_NAME ?>', '<?= generateCsrfToken() ?>');
        fetch('<?= SITE_URL ?>/report.php', {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('reportModal').style.display = 'none';
                alert('Жалоба отправлена!');
            } else {
                alert(data.error || 'Ошибка');
            }
        });
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
