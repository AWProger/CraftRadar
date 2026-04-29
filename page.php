<?php
/**
 * CraftRadar — Статические страницы
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$slug = get('slug');

if (!$slug) {
    redirect(SITE_URL . '/');
}

$db = getDB();
$stmt = $db->prepare('SELECT * FROM pages WHERE slug = ? AND is_published = 1');
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    http_response_code(404);
    $pageTitle = 'Страница не найдена';
    require_once __DIR__ . '/includes/header.php';
    echo '<div style="text-align: center; padding: 60px 0;"><h1>404</h1><p style="color: var(--text-muted);">Страница не найдена.</p><a href="' . SITE_URL . '/" class="btn btn-primary" style="margin-top: 16px;">На главную</a></div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $page['title'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="static-page">
    <h1><?= e($page['title']) ?></h1>
    <div class="static-content">
        <?= $page['content'] ?>
    </div>
</div>

<style>
    .static-page { max-width: 800px; margin: 0 auto; }
    .static-page h1 { margin-bottom: 24px; }
    .static-content { line-height: 1.8; color: var(--text); }
    .static-content p { margin-bottom: 16px; }
    .static-content h2 { margin: 24px 0 12px; }
    .static-content a { color: var(--accent); }
    .static-content ul, .static-content ol { margin: 12px 0; padding-left: 24px; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
