<?php
/**
 * CraftRadar — Настройки профиля
 */

$pageTitle = 'Настройки профиля';
require_once __DIR__ . '/../includes/header.php';

requireAuth();

$db = getDB();
$userId = currentUserId();
$user = currentUser();

$errors = [];
$action = post('action');

if (isPost()) {
    if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $errors[] = 'Ошибка безопасности.';
    } else {
        switch ($action) {
            case 'change_password':
                $currentPassword = post('current_password');
                $newPassword = post('new_password');
                $confirmPassword = post('confirm_password');

                if (!password_verify($currentPassword, $user['password_hash'])) {
                    $errors[] = 'Неверный текущий пароль.';
                } elseif (mb_strlen($newPassword) < MIN_PASSWORD_LENGTH) {
                    $errors[] = 'Новый пароль должен быть не менее ' . MIN_PASSWORD_LENGTH . ' символов.';
                } elseif ($newPassword !== $confirmPassword) {
                    $errors[] = 'Пароли не совпадают.';
                } else {
                    $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                    $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $stmt->execute([$hash, $userId]);
                    setFlash('success', 'Пароль изменён!');
                    redirect(SITE_URL . '/dashboard/settings.php');
                }
                break;

            case 'change_email':
                $newEmail = post('new_email');

                if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Некорректный email.';
                } else {
                    // Проверка уникальности
                    $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
                    $stmt->execute([$newEmail, $userId]);
                    if ($stmt->fetch()) {
                        $errors[] = 'Этот email уже используется.';
                    } else {
                        $stmt = $db->prepare('UPDATE users SET email = ? WHERE id = ?');
                        $stmt->execute([$newEmail, $userId]);
                        setFlash('success', 'Email изменён!');
                        redirect(SITE_URL . '/dashboard/settings.php');
                    }
                }
                break;

            case 'delete_account':
                $confirmDelete = post('confirm_delete');
                if ($confirmDelete !== $user['username']) {
                    $errors[] = 'Введите свой логин для подтверждения удаления.';
                } else {
                    // Удаляем файлы серверов
                    $servers = $db->prepare('SELECT icon, banner FROM servers WHERE user_id = ?');
                    $servers->execute([$userId]);
                    foreach ($servers->fetchAll() as $s) {
                        if ($s['icon'] && file_exists(ROOT_PATH . $s['icon'])) unlink(ROOT_PATH . $s['icon']);
                        if ($s['banner'] && file_exists(ROOT_PATH . $s['banner'])) unlink(ROOT_PATH . $s['banner']);
                    }

                    // Удаляем пользователя (каскадно удалятся серверы, голоса, отзывы)
                    $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
                    $stmt->execute([$userId]);

                    logoutUser();
                    // Перезапускаем сессию для flash
                    session_start();
                    setFlash('success', 'Аккаунт удалён.');
                    redirect(SITE_URL . '/');
                }
                break;
        }
    }
}
?>

<div class="dashboard">
    <?= dashboardNav('settings') ?>
    <div class="dashboard-header">
        <h1>Настройки профиля</h1>
        <a href="<?= SITE_URL ?>/dashboard/" class="btn btn-ghost">← Назад</a>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $err): ?>
                <p><?= e($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Информация -->
    <div class="card">
        <h2 class="section-title">Профиль</h2>
        <div class="info-list">
            <div class="info-item">
                <span class="info-label">Логин</span>
                <span><?= e($user['username']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Email</span>
                <span><?= e($user['email']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Minecraft ник</span>
                <span><?= !empty($user['minecraft_nick']) ? e($user['minecraft_nick']) : '<a href="' . SITE_URL . '/dashboard/profile.php" style="color:var(--text-muted);">Не указан →</a>' ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Роль</span>
                <span><?= e($user['role']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Дата регистрации</span>
                <span><?= formatDate($user['created_at']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">Профиль</span>
                <a href="<?= SITE_URL ?>/dashboard/profile.php">👤 Достижения и избранное →</a>
            </div>
        </div>
    </div>

    <!-- Смена пароля -->
    <div class="card" style="margin-top: 16px;">
        <h2 class="section-title">Смена пароля</h2>
        <form method="POST" action="">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="change_password">
            <div class="form-group">
                <label for="current_password">Текущий пароль</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">Новый пароль</label>
                <input type="password" id="new_password" name="new_password" required minlength="<?= MIN_PASSWORD_LENGTH ?>">
            </div>
            <div class="form-group">
                <label for="confirm_password">Подтверждение</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Сменить пароль</button>
        </form>
    </div>

    <!-- Смена email -->
    <div class="card" style="margin-top: 16px;">
        <h2 class="section-title">Смена email</h2>
        <form method="POST" action="">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="change_email">
            <div class="form-group">
                <label for="new_email">Новый email</label>
                <input type="email" id="new_email" name="new_email" value="<?= e($user['email']) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Сменить email</button>
        </form>
    </div>

    <!-- Удаление аккаунта -->
    <div class="card" style="margin-top: 16px; border-color: var(--danger);">
        <h2 class="section-title" style="color: var(--danger);">Удаление аккаунта</h2>
        <p style="color: var(--text-muted); margin-bottom: 12px;">
            Это действие необратимо. Все ваши серверы, голоса и отзывы будут удалены.
        </p>
        <form method="POST" action="">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="delete_account">
            <div class="form-group">
                <label for="confirm_delete">Введите свой логин <strong><?= e($user['username']) ?></strong> для подтверждения</label>
                <input type="text" id="confirm_delete" name="confirm_delete" required placeholder="<?= e($user['username']) ?>">
            </div>
            <button type="submit" class="btn btn-danger btn-sm" data-confirm="Вы уверены? Это действие НЕОБРАТИМО!">Удалить аккаунт</button>
        </form>
    </div>
</div>

<style>
    .info-list { display: flex; flex-direction: column; gap: 10px; }
    .info-item { display: flex; justify-content: space-between; font-size: 0.9rem; }
    .info-label { color: var(--text-muted); }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
