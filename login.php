<?php
/**
 * CraftRadar — Авторизация
 */

$pageTitle = 'Вход';
require_once __DIR__ . '/includes/header.php';

// Если уже авторизован — в кабинет
if (isLoggedIn()) {
    redirect(SITE_URL . '/dashboard/');
}

$errors = [];
$old = ['login' => ''];

if (isPost()) {
    if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $errors[] = 'Ошибка безопасности. Обновите страницу.';
    } else {
        $login = post('login');
        $password = post('password');

        $old = ['login' => $login];

        $result = loginUser($login, $password);

        if ($result['success']) {
            setFlash('success', 'Добро пожаловать!');
            redirect(SITE_URL . '/dashboard/');
        } else {
            $errors[] = $result['error'];
        }
    }
}
?>

<div class="auth-page">
    <div class="auth-card">
        <h1 class="auth-title">Вход</h1>

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
                <label for="login">Логин или Email</label>
                <input type="text" id="login" name="login" value="<?= e($old['login']) ?>"
                       required placeholder="Логин или email">
            </div>

            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password"
                       required placeholder="Ваш пароль">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Войти</button>
        </form>

        <p class="auth-link">Нет аккаунта? <a href="<?= SITE_URL ?>/register.php">Зарегистрироваться</a></p>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
