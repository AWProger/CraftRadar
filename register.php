<?php
/**
 * CraftRadar — Регистрация
 */

$pageTitle = 'Регистрация';
require_once __DIR__ . '/includes/header.php';

// Если уже авторизован — в кабинет
if (isLoggedIn()) {
    redirect(SITE_URL . '/dashboard/');
}

$errors = [];
$old = ['username' => '', 'email' => ''];

if (isPost()) {
    // Проверка CSRF
    if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $errors[] = 'Ошибка безопасности. Обновите страницу.';
    } else {
        $username = post('username');
        $email = post('email');
        $password = post('password');
        $passwordConfirm = post('password_confirm');

        $old = ['username' => $username, 'email' => $email];

        $result = registerUser($username, $email, $password, $passwordConfirm);

        if ($result['success']) {
            setFlash('success', 'Регистрация успешна! Теперь войдите в аккаунт.');
            redirect(SITE_URL . '/login.php');
        } else {
            $errors[] = $result['error'];
        }
    }
}
?>

<div class="auth-page">
    <div class="auth-card">
        <h1 class="auth-title">Регистрация</h1>

        <?php if ($errors): ?>
            <div class="alert alert-error">
                <?php foreach ($errors as $err): ?>
                    <p><?= e($err) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="auth-form">
            <?= csrfField() ?>

            <div class="form-group">
                <label for="username">Логин</label>
                <input type="text" id="username" name="username" value="<?= e($old['username']) ?>"
                       required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]+"
                       placeholder="Только латиница, цифры, _">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= e($old['email']) ?>"
                       required maxlength="100" placeholder="your@email.com">
            </div>

            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password"
                       required minlength="<?= MIN_PASSWORD_LENGTH ?>" placeholder="Минимум <?= MIN_PASSWORD_LENGTH ?> символов">
            </div>

            <div class="form-group">
                <label for="password_confirm">Подтверждение пароля</label>
                <input type="password" id="password_confirm" name="password_confirm"
                       required placeholder="Повторите пароль">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Зарегистрироваться</button>
        </form>

        <p class="auth-link">Уже есть аккаунт? <a href="<?= SITE_URL ?>/login.php">Войти</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
