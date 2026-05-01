<?php
/**
 * CraftRadar — Добавление сервера
 */

$pageTitle = 'Добавить сервер';
require_once __DIR__ . '/../includes/header.php';
require_once INCLUDES_PATH . 'minecraft_ping.php';

requireAuth();

$db = getDB();
$userId = currentUserId();

// Проверка лимита серверов
$stmt = $db->prepare('SELECT COUNT(*) FROM servers WHERE user_id = ?');
$stmt->execute([$userId]);
$serverCount = (int)$stmt->fetchColumn();

if ($serverCount >= SERVERS_LIMIT_PER_USER) {
    setFlash('error', 'Достигнут лимит серверов (' . SERVERS_LIMIT_PER_USER . '). Удалите один из существующих.');
    redirect(SITE_URL . '/dashboard/');
}

// Загрузка категорий для выпадающего списка
$categories = $db->query('SELECT id, name, slug, icon FROM categories WHERE is_active = 1 ORDER BY sort_order')->fetchAll();

$errors = [];
$old = [
    'name' => '', 'ip' => '', 'port' => '25565', 'description' => '',
    'website' => '', 'version' => '', 'game_mode' => '', 'tags' => ''
];

if (isPost()) {
    if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $errors[] = 'Ошибка безопасности. Обновите страницу.';
    } elseif (!empty(post('fax_number'))) {
        $errors[] = 'Ошибка безопасности.';
    } else {
        $name = post('name');
        $ip = post('ip');
        $port = (int)post('port', '25565');
        $description = post('description');
        $website = post('website');
        $version = post('version');
        $gameMode = post('game_mode');
        $tags = post('tags');

        $old = compact('name', 'ip', 'description', 'website', 'version', 'tags');
        $old['port'] = (string)$port;
        $old['game_mode'] = $gameMode;

        // Валидация
        if (empty($name) || mb_strlen($name) < 3 || mb_strlen($name) > 100) {
            $errors[] = 'Название сервера: от 3 до 100 символов.';
        }

        if (empty($ip)) {
            $errors[] = 'Укажите IP-адрес сервера.';
        } elseif (!isValidServerIP($ip)) {
            $errors[] = 'Недопустимый IP-адрес (localhost и приватные сети запрещены).';
        }

        if (!isValidPort($port)) {
            $errors[] = 'Порт должен быть от 1 до 65535.';
        }

        if (mb_strlen($description) > DESCRIPTION_MAX_LENGTH) {
            $errors[] = 'Описание не должно превышать ' . DESCRIPTION_MAX_LENGTH . ' символов.';
        }

        if ($website && !filter_var($website, FILTER_VALIDATE_URL)) {
            $errors[] = 'Некорректный URL сайта.';
        }

        // Проверка дубликата IP:port
        $stmt = $db->prepare('SELECT id FROM servers WHERE ip = ? AND port = ?');
        $stmt->execute([$ip, $port]);
        if ($stmt->fetch()) {
            $errors[] = 'Сервер с таким IP и портом уже добавлен.';
        }

        if (empty($errors)) {
            // Пробуем пинг сервера
            $pingResult = pingMinecraftServer($ip, $port, PING_TIMEOUT);

            $isOnline = 0;
            $playersOnline = 0;
            $playersMax = 0;
            $motd = '';
            $iconPath = null;

            if ($pingResult) {
                $isOnline = 1;
                $playersOnline = $pingResult['players'];
                $playersMax = $pingResult['max_players'];
                $motd = mb_substr($pingResult['motd'], 0, 255);

                // Сохранение иконки из favicon (base64 PNG)
                if (!empty($pingResult['favicon'])) {
                    $iconPath = saveServerIcon($pingResult['favicon']);
                }

                // Автоопределение версии если не указана
                if (empty($version) && !empty($pingResult['version'])) {
                    $version = $pingResult['version'];
                }
            }

            // Вставка в БД
            $stmt = $db->prepare('
                INSERT INTO servers 
                (user_id, name, ip, port, description, website, version, game_mode, tags, icon, 
                 is_online, players_online, players_max, motd, status, created_at, updated_at, last_ping)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
                $userId, $name, $ip, $port, $description, $website ?: null, $version ?: null,
                $gameMode ?: null, $tags ?: null, $iconPath,
                $isOnline, $playersOnline, $playersMax, $motd,
                'pending', now(), now(), $isOnline ? now() : null
            ]);

            setFlash('success', 'Сервер добавлен и отправлен на модерацию!' . 
                ($isOnline ? " Пинг: ✅ Онлайн ({$playersOnline}/{$playersMax})" : ' Пинг: ⚠️ Сервер не ответил, статус обновится при следующем пинге.'));

            // Достижение за первый сервер
            try {
                require_once INCLUDES_PATH . 'achievements.php';
                checkAchievement($userId, 'server_add');
            } catch (\Exception $e) {}

            redirect(SITE_URL . '/dashboard/');
        }
    }
}

/**
 * Сохранение иконки сервера из base64
 */
function saveServerIcon(string $favicon): ?string
{
    // Формат: data:image/png;base64,iVBOR...
    if (!preg_match('/^data:image\/png;base64,(.+)$/', $favicon, $matches)) {
        return null;
    }

    $imageData = base64_decode($matches[1]);
    if (!$imageData) return null;

    $dir = ROOT_PATH . 'assets/img/icons/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = 'server_' . uniqid() . '.png';
    $path = $dir . $filename;

    if (file_put_contents($path, $imageData)) {
        return 'assets/img/icons/' . $filename;
    }

    return null;
}
?>

<div class="dashboard">
    <?= dashboardNav('servers') ?>
    <div class="dashboard-header">
        <h1>Добавить сервер</h1>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <p><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <form method="POST" action="">
            <?= csrfField() ?>
            <div style="position:absolute;left:-9999px;"><input type="text" name="fax_number" value="" tabindex="-1" autocomplete="off"></div>

            <div class="form-group">
                <label for="name">Название сервера *</label>
                <input type="text" id="name" name="name" value="<?= e($old['name']) ?>"
                       required minlength="3" maxlength="100" placeholder="Мой крутой сервер">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="ip">IP-адрес *</label>
                    <input type="text" id="ip" name="ip" value="<?= e($old['ip']) ?>"
                           required placeholder="play.example.com или 123.45.67.89">
                </div>
                <div class="form-group" style="max-width: 120px;">
                    <label for="port">Порт</label>
                    <input type="number" id="port" name="port" value="<?= e($old['port']) ?>"
                           min="1" max="65535" placeholder="25565">
                </div>
            </div>

            <div class="form-group">
                <label for="description">Описание</label>
                <textarea id="description" name="description" maxlength="<?= DESCRIPTION_MAX_LENGTH ?>"
                          placeholder="Расскажите о вашем сервере..."
                          rows="5" oninput="document.getElementById('charCount').textContent=this.value.length"><?= e($old['description']) ?></textarea>
                <small style="color: var(--text-muted);"><span id="charCount"><?= mb_strlen($old['description']) ?></span> / <?= DESCRIPTION_MAX_LENGTH ?></small>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="version">Версия Minecraft</label>
                    <input type="text" id="version" name="version" value="<?= e($old['version']) ?>"
                           placeholder="1.20.4 (определится автоматически)">
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

            <button type="submit" class="btn btn-primary btn-block">Добавить сервер</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
