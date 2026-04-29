<?php
/**
 * CraftRadar — Админка: Статические страницы
 */

$adminPageTitle = 'Страницы';
require_once __DIR__ . '/includes/admin_header.php';
requireAdmin();

$db = getDB();

// Действия
if (isPost()) {
    if (verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $action = post('action');

        switch ($action) {
            case 'create':
                $slug = post('slug');
                $title = post('title');
                if ($slug && $title) {
                    $db->prepare('INSERT INTO pages (slug, title, content, is_published, updated_at, updated_by) VALUES (?, ?, ?, 1, ?, ?)')
                        ->execute([$slug, $title, '', now(), currentUserId()]);
                    $pageId = (int)$db->lastInsertId();
                    adminLog('create_page', 'setting', $pageId);
                    setFlash('success', 'Страница создана.');
                    redirect(SITE_URL . '/admin/page_edit.php?id=' . $pageId);
                }
                break;

            case 'toggle':
                $pageId = (int)post('page_id');
                $db->prepare('UPDATE pages SET is_published = NOT is_published WHERE id = ?')->execute([$pageId]);
                adminLog('toggle_page', 'setting', $pageId);
                setFlash('success', 'Статус изменён.');
                break;

            case 'delete':
                $pageId = (int)post('page_id');
                $db->prepare('DELETE FROM pages WHERE id = ?')->execute([$pageId]);
                adminLog('delete_page', 'setting', $pageId);
                setFlash('success', 'Страница удалена.');
                break;
        }
        redirect(SITE_URL . '/admin/pages.php');
    }
}

$pages = $db->query('SELECT * FROM pages ORDER BY slug')->fetchAll();
?>

<!-- Создать страницу -->
<div class="card" style="margin-bottom: 20px;">
    <h3 style="margin-bottom: 12px;">Создать страницу</h3>
    <form method="POST" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: end;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="create">
        <div class="form-group" style="margin: 0;">
            <label>Slug (URL)</label>
            <input type="text" name="slug" required placeholder="about" pattern="[a-z0-9\-]+">
        </div>
        <div class="form-group" style="margin: 0;">
            <label>Заголовок</label>
            <input type="text" name="title" required placeholder="О проекте">
        </div>
        <button type="submit" class="btn btn-sm btn-primary">Создать</button>
    </form>
</div>

<!-- Список -->
<div class="table-wrap">
    <table>
        <thead>
            <tr><th>Slug</th><th>Заголовок</th><th>Опубликована</th><th>Обновлена</th><th>Действия</th></tr>
        </thead>
        <tbody>
            <?php foreach ($pages as $p): ?>
                <tr>
                    <td><?= e($p['slug']) ?></td>
                    <td><?= e($p['title']) ?></td>
                    <td>
                        <span class="badge <?= $p['is_published'] ? 'badge-online' : 'badge-offline' ?>">
                            <?= $p['is_published'] ? 'Да' : 'Нет' ?>
                        </span>
                    </td>
                    <td><?= $p['updated_at'] ? formatDate($p['updated_at']) : '—' ?></td>
                    <td>
                        <div class="action-btns">
                            <a href="<?= SITE_URL ?>/admin/page_edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline">✏️</a>
                            <a href="<?= SITE_URL ?>/page.php?slug=<?= e($p['slug']) ?>" class="btn btn-sm btn-ghost" target="_blank">👁</a>
                            <form method="POST" style="display: inline;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="page_id" value="<?= $p['id'] ?>">
                                <button class="btn btn-sm btn-ghost"><?= $p['is_published'] ? '🔴' : '🟢' ?></button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <?= csrfField() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="page_id" value="<?= $p['id'] ?>">
                                <button class="btn btn-sm btn-ghost" data-confirm="Удалить страницу?">🗑</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
