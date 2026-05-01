<?php
/**
 * CraftRadar — Админка: Настройки платформы
 */

$adminPageTitle = 'Настройки';
require_once __DIR__ . '/includes/admin_header.php';
require_once INCLUDES_PATH . 'yoomoney.php';
requireAdmin();

$db = getDB();

// Очистка кэша
if (get('action') === 'clear_cache') {
    require_once INCLUDES_PATH . 'cache.php';
    cacheClear();
    adminLog('clear_cache', 'setting', 0);
    setFlash('success', 'Кэш очищен.');
    redirect(SITE_URL . '/admin/settings.php');
}

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

<!-- Быстрые действия -->
<div style="margin-bottom: 16px; display: flex; gap: 8px;">
    <a href="<?= SITE_URL ?>/admin/settings.php?action=clear_cache" class="btn btn-sm btn-outline" data-confirm="Очистить весь кэш?">🗑 Очистить кэш</a>
</div>

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

<!-- ЮMoney (вне основной формы) -->
<div class="card" style="margin-top: 16px; border-color: var(--warning);">
    <h3 style="margin-bottom: 12px;">💰 ЮMoney — платежи</h3>

    <div class="info-list" style="margin-bottom: 16px;">
        <div class="info-item">
            <span class="info-label">Client ID</span>
            <span style="font-size: 0.75rem; font-family: monospace; color: var(--text-muted);"><?= e(YOOMONEY_CLIENT_ID) ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Кошелёк</span>
            <span><?= settingVal($settings, 'yoomoney_wallet') ? e(settingVal($settings, 'yoomoney_wallet')) : '<span style="color: var(--danger);">Не привязан</span>' ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Секрет уведомлений</span>
            <span><?= settingVal($settings, 'yoomoney_secret') ? '✅ Задан' : '<span style="color: var(--danger);">Не задан</span>' ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">URL уведомлений</span>
            <span style="font-size: 0.8rem;"><?= e(YOOMONEY_NOTIFY_URL) ?></span>
        </div>
    </div>

    <?php
    $yoomoneyObj = new YooMoney();
    $authUrl = $yoomoneyObj->getAuthUrl();
    ?>
    <a href="<?= e($authUrl) ?>" class="btn btn-sm btn-outline" style="margin-bottom: 12px;">
        🔗 Привязать кошелёк через OAuth
    </a>

    <form method="POST">
        <?= csrfField() ?>
        <div class="form-group">
            <label>Номер кошелька (вручную)</label>
            <input type="text" name="yoomoney_wallet" value="<?= e(settingVal($settings, 'yoomoney_wallet')) ?>" placeholder="4100...">
        </div>
        <div class="form-group">
            <label>Секрет для HTTP-уведомлений</label>
            <input type="text" name="yoomoney_secret" value="<?= e(settingVal($settings, 'yoomoney_secret')) ?>" placeholder="Из настроек ЮMoney">
            <small style="color: var(--text-muted);">ЮMoney → Настройки → HTTP-уведомления → Секрет</small>
        </div>
        <div class="form-group">
            <label>Цена 7 дней (₽)</label>
            <input type="number" name="promote_price_7d" value="<?= e(settingVal($settings, 'promote_price_7d')) ?>" min="1">
        </div>
        <div class="form-group">
            <label>Цена 14 дней (₽)</label>
            <input type="number" name="promote_price_14d" value="<?= e(settingVal($settings, 'promote_price_14d')) ?>" min="1">
        </div>
        <div class="form-group">
            <label>Цена 30 дней (₽)</label>
            <input type="number" name="promote_price_30d" value="<?= e(settingVal($settings, 'promote_price_30d')) ?>" min="1">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Сохранить настройки ЮMoney</button>
    </form>

    <div style="margin-top: 16px; padding: 12px; background: var(--bg); border-radius: var(--radius);">
        <strong>Инструкция:</strong>
        <ol style="color: var(--text-muted); font-size: 0.85rem; padding-left: 20px; margin-top: 8px; line-height: 1.8;">
            <li>Нажмите «Привязать кошелёк через OAuth» или введите номер вручную</li>
            <li>В <a href="https://yoomoney.ru/transfer/myservices/http-notification" target="_blank">настройках ЮMoney</a> включите HTTP-уведомления</li>
            <li>Укажите URL: <code><?= e(YOOMONEY_NOTIFY_URL) ?></code></li>
            <li>Скопируйте секрет и вставьте в поле выше</li>
        </ol>
    </div>
</div>

<style>
    .info-list { display: flex; flex-direction: column; gap: 8px; }
    .info-item { display: flex; justify-content: space-between; align-items: center; font-size: 0.9rem; }
    .info-label { color: var(--text-muted); }
    code { background: var(--bg); border: 1px solid var(--border); padding: 1px 6px; border-radius: 3px; font-size: 0.8em; color: var(--accent); }
</style>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
