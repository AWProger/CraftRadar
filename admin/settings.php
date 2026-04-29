<?php
/**
 * CraftRadar — Админка: Настройки платформы
 */

$adminPageTitle = 'Настройки';
require_once __DIR__ . '/includes/admin_header.php';
requireAdmin();

$db = getDB();

// Сохранение
if (isPost()) {
    if (verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $settings = $_POST;
        unset($settings[CSRF_TOKEN_NAME]);

        foreach ($settings as $key => $value) {
            $stmt = $db->prepare('UPDATE settings SET `value` = ?, updated_at = ?, updated_by = ? WHERE `key` = ?');
            $stmt->execute([$value, now(), currentUserId(), $key]);
        }

        adminLog('update_settings', 'setting', 0, json_encode(array_keys($settings)));
        setFlash('success', 'Настройки сохранены.');
        redirect(SITE_URL . '/admin/settings.php');
    }
}

// Загрузка настроек
$settingsRaw = $db->query('SELECT * FROM settings ORDER BY `key`')->fetchAll();
$settings = [];
foreach ($settingsRaw as $s) {
    $settings[$s['key']] = $s;
}

function settingVal(array $settings, string $key): string {
    return $settings[$key]['value'] ?? '';
}
?>

<form method="POST">
    <?= csrfField() ?>

    <!-- Общие -->
    <div class="card" style="margin-bottom: 16px;">
        <h3 style="margin-bottom: 12px;">Общие</h3>
        <div class="form-group">
            <label>Название сайта</label>
            <input type="text" name="site_name" value="<?= e(settingVal($settings, 'site_name')) ?>">
        </div>
        <div class="form-group">
            <label>Описание (meta)</label>
            <input type="text" name="site_description" value="<?= e(settingVal($settings, 'site_description')) ?>">
        </div>
        <div class="form-group">
            <label>Контактный email</label>
            <input type="email" name="contact_email" value="<?= e(settingVal($settings, 'contact_email')) ?>">
        </div>
    </div>

    <!-- Серверы -->
    <div class="card" style="margin-bottom: 16px;">
        <h3 style="margin-bottom: 12px;">Серверы</h3>
        <div class="form-group">
            <label>Лимит серверов на аккаунт</label>
            <input type="number" name="servers_per_user" value="<?= e(settingVal($settings, 'servers_per_user')) ?>" min="1">
        </div>
        <div class="form-group">
            <label>Модерация новых серверов (1=да, 0=нет)</label>
            <select name="moderation_required">
                <option value="1" <?= settingVal($settings, 'moderation_required') === '1' ? 'selected' : '' ?>>Да</option>
                <option value="0" <?= settingVal($settings, 'moderation_required') === '0' ? 'selected' : '' ?>>Нет</option>
            </select>
        </div>
        <div class="form-group">
            <label>Интервал пинга (минуты)</label>
            <input type="number" name="ping_interval_minutes" value="<?= e(settingVal($settings, 'ping_interval_minutes')) ?>" min="1">
        </div>
        <div class="form-group">
            <label>Таймаут пинга (секунды)</label>
            <input type="number" name="ping_timeout_seconds" value="<?= e(settingVal($settings, 'ping_timeout_seconds')) ?>" min="1">
        </div>
        <div class="form-group">
            <label>Неудачных пингов до оффлайн</label>
            <input type="number" name="max_consecutive_fails" value="<?= e(settingVal($settings, 'max_consecutive_fails')) ?>" min="1">
        </div>
    </div>

    <!-- Голосование -->
    <div class="card" style="margin-bottom: 16px;">
        <h3 style="margin-bottom: 12px;">Голосование</h3>
        <div class="form-group">
            <label>Кулдаун голосования (часы)</label>
            <input type="number" name="vote_cooldown_hours" value="<?= e(settingVal($settings, 'vote_cooldown_hours')) ?>" min="1">
        </div>
    </div>

    <!-- Отзывы -->
    <div class="card" style="margin-bottom: 16px;">
        <h3 style="margin-bottom: 12px;">Отзывы</h3>
        <div class="form-group">
            <label>Отзывы включены</label>
            <select name="reviews_enabled">
                <option value="1" <?= settingVal($settings, 'reviews_enabled') === '1' ? 'selected' : '' ?>>Да</option>
                <option value="0" <?= settingVal($settings, 'reviews_enabled') === '0' ? 'selected' : '' ?>>Нет</option>
            </select>
        </div>
        <div class="form-group">
            <label>Модерация отзывов</label>
            <select name="reviews_moderation">
                <option value="0" <?= settingVal($settings, 'reviews_moderation') === '0' ? 'selected' : '' ?>>Нет</option>
                <option value="1" <?= settingVal($settings, 'reviews_moderation') === '1' ? 'selected' : '' ?>>Да</option>
            </select>
        </div>
    </div>

    <!-- Регистрация -->
    <div class="card" style="margin-bottom: 16px;">
        <h3 style="margin-bottom: 12px;">Регистрация</h3>
        <div class="form-group">
            <label>Регистрация открыта</label>
            <select name="registration_open">
                <option value="1" <?= settingVal($settings, 'registration_open') === '1' ? 'selected' : '' ?>>Да</option>
                <option value="0" <?= settingVal($settings, 'registration_open') === '0' ? 'selected' : '' ?>>Нет</option>
            </select>
        </div>
        <div class="form-group">
            <label>Минимальная длина пароля</label>
            <input type="number" name="min_password_length" value="<?= e(settingVal($settings, 'min_password_length')) ?>" min="4">
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Сохранить настройки</button>
</form>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
