<?php
/**
 * CraftRadar — Админка: Редактор страницы
 */

$adminPageTitle = 'Редактирование страницы';
require_once __DIR__ . '/includes/admin_header.php';
requireAdmin();

$db = getDB();
$id = getInt('id');

$stmt = $db->prepare('SELECT * FROM pages WHERE id = ?');
$stmt->execute([$id]);
$page = $stmt->fetch();

if (!$page) {
    setFlash('error', 'Страница не найдена.');
    redirect(SITE_URL . '/admin/pages.php');
}

if (isPost()) {
    if (verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $title = post('title');
        $slug = post('slug');
        $content = $_POST['content'] ?? ''; // Не trim, чтобы сохранить HTML
        $isPublished = (int)post('is_published');

        $db->prepare('UPDATE pages SET title = ?, slug = ?, content = ?, is_published = ?, updated_at = ?, updated_by = ? WHERE id = ?')
            ->execute([$title, $slug, $content, $isPublished, now(), currentUserId(), $id]);

        adminLog('edit_page', 'setting', $id);
        setFlash('success', 'Страница сохранена.');
        redirect(SITE_URL . '/admin/page_edit.php?id=' . $id);
    }
}
?>

<div style="margin-bottom: 16px;">
    <a href="<?= SITE_URL ?>/admin/pages.php" class="btn btn-ghost btn-sm">← Назад к страницам</a>
    <a href="<?= SITE_URL ?>/page.php?slug=<?= e($page['slug']) ?>" class="btn btn-ghost btn-sm" target="_blank">Предпросмотр</a>
</div>

<form method="POST">
    <?= csrfField() ?>

    <div class="card">
        <div class="form-group">
            <label>Заголовок</label>
            <input type="text" name="title" value="<?= e($page['title']) ?>" required>
        </div>

        <div class="form-group">
            <label>Slug (URL)</label>
            <input type="text" name="slug" value="<?= e($page['slug']) ?>" required pattern="[a-z0-9\-]+">
        </div>

        <div class="form-group">
            <label>Контент (HTML)</label>
            <textarea name="content" rows="20" style="font-family: monospace; font-size: 0.85rem;"><?= e($page['content']) ?></textarea>
        </div>

        <div class="form-group">
            <label>Опубликована</label>
            <select name="is_published">
                <option value="1" <?= $page['is_published'] ? 'selected' : '' ?>>Да</option>
                <option value="0" <?= !$page['is_published'] ? 'selected' : '' ?>>Нет</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Сохранить</button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
