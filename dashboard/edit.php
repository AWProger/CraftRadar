<?php
/**
 * CraftRadar — Редактирование сервера
 */

$pageTitle = 'Редактировать сервер';
require_once __DIR__ . '/../includes/header.php';

requireAuth();

$db = getDB();
$userId = currentUserId();
$id = getInt('id');

if (!$id) {
    setFlash('error', 'Сервер не найден.');
    redirect(SITE_URL . '/dashboard/');
}

// Получаем сервер (только свой)
$stmt = $db->prepare('SELECT * FROM servers WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $userId]);
$server = $stmt->fetch();

if (!$server) {
    setFlash('error', 'Сервер не найден или не принадлежит вам.');
    redirect(SITE_URL . '/dashboard/');
}

// Категории
$categories = $db->query('SELECT id, name, slug, icon FROM categories WHERE is_active = 1 ORDER BY sort_order')->fetchAll();

$errors = [];

if (isPost()) {
    if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $errors[] = 'Ошибка безопасности. Обновите страницу.';
    } else {
        $name = post('name');
        $description = post('description');
        $website = post('website');
        $version = post('version');
        $gameMode = post('game_mode');
        $tags = post('tags');

        // Валидация
        if (empty($name) || mb_strlen($name) < 3 || mb_strlen($name) > 100) {
            $errors[] = 'Название сервера: от 3 до 100 символов.';
        }

        if (mb_strlen($description) > DESCRIPTION_MAX_LENGTH) {
            $errors[] = 'Описание не должно превышать ' . DESCRIPTION_MAX_LENGTH . ' символов.';
        }

        if ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
            $errors[] = 'Некорректный URL сайта.';
        }

        // Загрузка баннера
        $bannerPath = $server['banner'];
        if (!empty($_FILES['banner']['name'])) {
            $file = $_FILES['banner'];

            if ($file['size'] > MAX_BANNER_SIZE) {
                $errors[] = 'Баннер не должен превышать ' . (MAX_BANNER_SIZE / 1024) . 'KB.';
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
                $errors[] = 'Допустимые форматы баннера: JPG, PNG, GIF.';
            }

            if (empty($errors)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $dir = ROOT_PATH . 'assets/img/banners/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);

                $filename = 'banner_' . $id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                    // Удаляем старый баннер
                    if ($bannerPath && file_exists(ROOT_PATH . $bannerPath)) {
                        unlink(ROOT_PATH . $bannerPath);
                    }
                    $bannerPath = 'assets/img/banners/' . $filename;
                }
            }
        }

        if (empty($errors)) {
            $stmt = $db->prepare('
                UPDATE servers SET 
                    name = ?, description = ?, website = ?, version = ?, 
                    game_mode = ?, tags = ?, banner = ?, updated_at = ?
                WHERE id = ? AND user_id = ?
            ');
            $stmt->execute([
                $name, $description, $website ?: null, $version ?: null,
                $gameMode ?: null, $tags ?: null, $bannerPath, now(),
                $id, $userId
            ]);

            setFlash('success', 'Сервер обновлён!');
            redirect(SITE_URL . '/dashboard/');
        }
    }
}

// Для формы используем данные из POST (если были ошибки) или из БД
$old = [
    'name' => post('name') ?: $server['name'],
    'description' => post('description') ?: ($server['description'] ?? ''),
    'website' => post('website') ?: ($server['website'] ?? ''),
    'version' => post('version') ?: ($server['version'] ?? ''),
    'game_mode' => post('game_mode') ?: ($server['game_mode'] ?? ''),
    'tags' => post('tags') ?: ($server['tags'] ?? ''),
];
?>

<div class="dashboard">
    <?= dashboardNav('servers') ?>
    <div class="dashboard-header">
        <h1>Редактировать сервер</h1>
    </div>

    <div class="alert alert-info">
        IP и порт изменить нельзя. Если нужно сменить — удалите сервер и добавьте заново.
        <br>Текущий адрес: <strong><?= e($server['ip'] . ':' . $server['port']) ?></strong>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <p><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="" enctype="multipart/form-data">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="name">Название сервера *</label>
                <input type="text" id="name" name="name" value="<?= e($old['name']) ?>"
                       required minlength="3" maxlength="100">
            </div>

            <div class="form-group">
                <label for="description">Описание</label>
                <textarea id="description" name="description" maxlength="<?= DESCRIPTION_MAX_LENGTH ?>"
                          rows="5" oninput="document.getElementById('charCount').textContent=this.value.length"><?= e($old['description']) ?></textarea>
                <small style="color: var(--text-muted);"><span id="charCount"><?= mb_strlen($old['description']) ?></span> / <?= DESCRIPTION_MAX_LENGTH ?></small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="version">Версия Minecraft</label>
                    <input type="text" id="version" name="version" value="<?= e($old['version']) ?>"
                           placeholder="1.20.4">
                </div>
                <div class="form-group">
                    <label for="game_mode">Режим игры</label>
                    <select id="game_mode" name="game_mode">
                        <option value="">— Выберите —</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= e($cat['slug']) ?>" <?= $old['game_mode'] === $cat['slug'] ? 'selected' : '' ?>>
                                <?= e($cat['icon'] . ' ' . $cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="tags">Теги</label>
                <input type="text" id="tags" name="tags" value="<?= e($old['tags']) ?>"
                       placeholder="pvp, выживание, донат (через запятую)">
            </div>

            <div class="form-group">
                <label for="website">Сайт сервера</label>
                <input type="url" id="website" name="website" value="<?= e($old['website']) ?>"
                       placeholder="https://example.com">
            </div>

            <div class="form-group">
                <label for="banner">Баннер (468x60 или 728x90, до 500KB)</label>
                <input type="file" id="banner" name="banner" accept="image/jpeg,image/png,image/gif">
                <?php if ($server['banner']): ?>
                    <div style="margin-top: 8px;">
                        <img src="<?= SITE_URL . '/' . e($server['banner']) ?>" alt="Баннер" style="max-width: 100%; border-radius: var(--radius);">
                    </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Сохранить изменения</button>
        </form>
    </div>

    <!-- Удаление сервера -->
    <div class="card" style="margin-top: 20px; border-color: var(--danger);">
        <h3 style="color: var(--danger); margin-bottom: 12px;">Опасная зона</h3>
        <p style="color: var(--text-muted); margin-bottom: 12px;">Удаление сервера необратимо. Все голоса, отзывы и статистика будут потеряны.</p>
        <form method="POST" action="<?= SITE_URL ?>/dashboard/delete_server.php">
            <?= csrfField() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <button type="submit" class="btn btn-danger btn-sm" data-confirm="Вы уверены? Это действие необратимо!">Удалить сервер</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
