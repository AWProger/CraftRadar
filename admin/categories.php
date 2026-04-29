<?php
/**
 * CraftRadar — Админка: Управление категориями
 */

$adminPageTitle = 'Категории';
require_once __DIR__ . '/includes/admin_header.php';
requireAdmin();

$db = getDB();

// Действия
if (isPost()) {
    if (verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $action = post('action');

        switch ($action) {
            case 'add':
                $name = post('name');
                $slug = post('slug');
                $icon = post('icon');
                $sortOrder = (int)post('sort_order');
                if ($name && $slug) {
                    $db->prepare('INSERT INTO categories (name, slug, icon, sort_order) VALUES (?, ?, ?, ?)')
                        ->execute([$name, $slug, $icon, $sortOrder]);
                    adminLog('add_category', 'category', (int)$db->lastInsertId());
                    setFlash('success', 'Категория добавлена.');
                }
                break;

            case 'edit':
                $catId = (int)post('cat_id');
                $name = post('name');
                $slug = post('slug');
                $icon = post('icon');
                $sortOrder = (int)post('sort_order');
                $db->prepare('UPDATE categories SET name = ?, slug = ?, icon = ?, sort_order = ? WHERE id = ?')
                    ->execute([$name, $slug, $icon, $sortOrder, $catId]);
                adminLog('edit_category', 'category', $catId);
                setFlash('success', 'Категория обновлена.');
                break;

            case 'toggle':
                $catId = (int)post('cat_id');
                $db->prepare('UPDATE categories SET is_active = NOT is_active WHERE id = ?')->execute([$catId]);
                adminLog('toggle_category', 'category', $catId);
                setFlash('success', 'Статус изменён.');
                break;
        }
        redirect(SITE_URL . '/admin/categories.php');
    }
}

$categories = $db->query('
    SELECT c.*, (SELECT COUNT(*) FROM servers WHERE game_mode = c.slug) as server_count 
    FROM categories c ORDER BY c.sort_order
')->fetchAll();
?>

<!-- Добавить категорию -->
<div class="card" style="margin-bottom: 20px;">
    <h3 style="margin-bottom: 12px;">Добавить категорию</h3>
    <form method="POST" style="display: flex; gap: 8px; flex-wrap: wrap; align-items: end;">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="add">
        <div class="form-group" style="margin: 0;">
            <label>Иконка</label>
            <input type="text" name="icon" placeholder="🎮" style="width: 60px;">
        </div>
        <div class="form-group" style="margin: 0;">
            <label>Название</label>
            <input type="text" name="name" required placeholder="Название">
        </div>
        <div class="form-group" style="margin: 0;">
            <label>Slug</label>
            <input type="text" name="slug" required placeholder="slug" pattern="[a-z0-9\-]+">
        </div>
        <div class="form-group" style="margin: 0;">
            <label>Порядок</label>
            <input type="number" name="sort_order" value="0" style="width: 70px;">
        </div>
        <button type="submit" class="btn btn-sm btn-primary">Добавить</button>
    </form>
</div>

<!-- Список -->
<div class="table-wrap">
    <table>
        <thead>
            <tr><th>Иконка</th><th>Название</th><th>Slug</th><th>Порядок</th><th>Серверов</th><th>Активна</th><th>Действия</th></tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $cat): ?>
                <tr>
                    <form method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                        <td><input type="text" name="icon" value="<?= e($cat['icon']) ?>" style="width: 50px; padding: 4px; background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text);"></td>
                        <td><input type="text" name="name" value="<?= e($cat['name']) ?>" style="padding: 4px; background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text);"></td>
                        <td><input type="text" name="slug" value="<?= e($cat['slug']) ?>" style="padding: 4px; background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text);"></td>
                        <td><input type="number" name="sort_order" value="<?= $cat['sort_order'] ?>" style="width: 60px; padding: 4px; background: var(--bg-input); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text);"></td>
                        <td><?= $cat['server_count'] ?></td>
                        <td>
                            <span class="badge <?= $cat['is_active'] ? 'badge-online' : 'badge-offline' ?>">
                                <?= $cat['is_active'] ? 'Да' : 'Нет' ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button type="submit" class="btn btn-sm btn-ghost">💾</button>
                            </div>
                        </td>
                    </form>
                    <td>
                        <form method="POST" style="display: inline;">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="toggle">
                            <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                            <button class="btn btn-sm btn-ghost"><?= $cat['is_active'] ? '🔴' : '🟢' ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
