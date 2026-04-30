<?php
/**
 * CraftRadar — Подтверждение прав на сервер (верификация через MOTD)
 * 
 * Логика:
 * - Любой может добавить сервер на мониторинг
 * - Чтобы получить статус "Владелец", нужно подтвердить владение
 * - Для этого нужно добавить уникальный код в MOTD сервера (server.properties → motd)
 * - После перезагрузки сервера нажать "Проверить"
 * - Система пингует сервер и ищет код в MOTD
 * - При успехе — пользователь получает статус владельца
 */

$pageTitle = 'Подтверждение прав на сервер';
require_once __DIR__ . '/../includes/header.php';
require_once INCLUDES_PATH . 'minecraft_ping.php';

requireAuth();

$db = getDB();
$userId = currentUserId();
$serverId = getInt('id');

if (!$serverId) {
    setFlash('error', 'Сервер не найден.');
    redirect(SITE_URL . '/dashboard/');
}

// Получаем сервер
$stmt = $db->prepare('SELECT * FROM servers WHERE id = ?');
$stmt->execute([$serverId]);
$server = $stmt->fetch();

if (!$server) {
    setFlash('error', 'Сервер не найден.');
    redirect(SITE_URL . '/dashboard/');
}

// Если уже верифицирован этим пользователем
if ($server['is_verified'] && $server['verified_by'] == $userId) {
    setFlash('info', 'Вы уже подтвердили владение этим сервером.');
    redirect(SITE_URL . '/dashboard/');
}

// Генерируем код верификации если его нет или он принадлежит другому пользователю
$verifyCode = $server['verify_code'];
if (!$verifyCode || $server['user_id'] != $userId) {
    // Генерируем новый код для текущего пользователя
    $verifyCode = generateVerifyCode();
    $db->prepare('UPDATE servers SET verify_code = ? WHERE id = ?')->execute([$verifyCode, $serverId]);
}

$verifyResult = null;
$verifyError = null;

// Обработка проверки
if (isPost() && post('action') === 'verify') {
    if (!verifyCsrfToken(post(CSRF_TOKEN_NAME))) {
        $verifyError = 'Ошибка безопасности.';
    } else {
        // Пингуем сервер
        $pingResult = pingMinecraftServer($server['ip'], $server['port'], PING_TIMEOUT);

        if (!$pingResult) {
            $verifyError = 'Сервер не отвечает. Убедитесь, что он запущен и доступен.';
        } else {
            $motd = $pingResult['motd'];

            // Ищем код в MOTD
            if (stripos($motd, $verifyCode) !== false) {
                // Успех! Передаём владение
                $db->prepare('
                    UPDATE servers SET 
                        is_verified = 1, 
                        verified_by = ?, 
                        verified_at = ?,
                        user_id = ?,
                        verify_code = NULL
                    WHERE id = ?
                ')->execute([$userId, now(), $userId, $serverId]);

                setFlash('success', 'Поздравляем! Вы подтвердили владение сервером «' . $server['name'] . '». Теперь вы можете удалить код из MOTD.');
                redirect(SITE_URL . '/dashboard/');
            } else {
                $verifyError = 'Код не найден в MOTD сервера. Текущий MOTD: «' . e($motd) . '». Убедитесь, что код ' . $verifyCode . ' добавлен в server.properties и сервер перезагружен.';
            }
        }
    }
}

/**
 * Генерация короткого кода верификации
 */
function generateVerifyCode(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}
?>

<div class="dashboard">
    <div class="dashboard-header">
        <h1>Подтверждение прав на сервер</h1>
        <a href="<?= SITE_URL ?>/dashboard/" class="btn btn-ghost">← Назад</a>
    </div>

    <!-- Информация о сервере -->
    <div class="card" style="margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <?php if ($server['icon']): ?>
                <img src="<?= SITE_URL . '/' . e($server['icon']) ?>" alt="" style="width: 48px; height: 48px; border-radius: var(--radius);">
            <?php endif; ?>
            <div>
                <h2 style="margin: 0;"><?= e($server['name']) ?></h2>
                <span style="color: var(--text-muted);"><?= e($server['ip'] . ':' . $server['port']) ?></span>
                <?php if ($server['is_verified']): ?>
                    <span class="badge badge-online" style="margin-left: 8px;">✓ Верифицирован</span>
                <?php else: ?>
                    <span class="badge badge-pending" style="margin-left: 8px;">Не верифицирован</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($verifyError): ?>
        <div class="alert alert-error"><?= $verifyError ?></div>
    <?php endif; ?>

    <!-- Инструкция -->
    <div class="card verify-card">
        <h2 class="section-title">🔐 Подтверждение прав на сервер</h2>

        <div class="verify-steps">
            <div class="verify-step">
                <div class="verify-step-num">1</div>
                <div class="verify-step-content">
                    <h3>Добавьте код в MOTD сервера</h3>
                    <p>Откройте файл <code>server.properties</code> на вашем сервере и добавьте в параметр <code>motd</code> следующий код:</p>
                    <div class="verify-code-box">
                        <span class="verify-code" id="verifyCode"><?= e($verifyCode) ?></span>
                        <button class="btn btn-sm btn-outline" onclick="navigator.clipboard.writeText('<?= e($verifyCode) ?>').then(() => this.textContent = '✓ Скопировано')">
                            📋 Копировать
                        </button>
                    </div>
                    <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 8px;">
                        Пример строки в server.properties:<br>
                        <code>motd=Мой сервер <?= e($verifyCode) ?></code>
                    </p>
                </div>
            </div>

            <div class="verify-step">
                <div class="verify-step-num">2</div>
                <div class="verify-step-content">
                    <h3>Перезагрузите сервер</h3>
                    <p>После изменения <code>server.properties</code> перезагрузите Minecraft сервер и дождитесь его выхода в онлайн.</p>
                </div>
            </div>

            <div class="verify-step">
                <div class="verify-step-num">3</div>
                <div class="verify-step-content">
                    <h3>Нажмите кнопку проверки</h3>
                    <p>Система пингнёт ваш сервер и проверит наличие кода в MOTD.</p>
                    <form method="POST" style="margin-top: 12px;">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="verify">
                        <button type="submit" class="btn btn-primary">
                            🔍 Проверить MOTD
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="verify-note">
            <p>✅ При успешном подтверждении права на этот сервер перейдут на ваш аккаунт.</p>
            <p>✅ После верификации вы можете удалить код из MOTD.</p>
            <p>✅ Верифицированные серверы отмечаются значком ✓ в каталоге.</p>
        </div>
    </div>
</div>

<style>
    .verify-card {
        max-width: 700px;
    }
    .verify-steps {
        display: flex;
        flex-direction: column;
        gap: 24px;
        margin: 20px 0;
    }
    .verify-step {
        display: flex;
        gap: 16px;
    }
    .verify-step-num {
        width: 36px;
        height: 36px;
        background: var(--accent);
        color: #000;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1rem;
        flex-shrink: 0;
    }
    .verify-step-content {
        flex: 1;
    }
    .verify-step-content h3 {
        margin-bottom: 6px;
        font-size: 1rem;
    }
    .verify-step-content p {
        color: var(--text-muted);
        font-size: 0.9rem;
        line-height: 1.6;
    }
    .verify-code-box {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        background: var(--bg);
        border: 2px dashed var(--accent);
        border-radius: var(--radius);
        padding: 12px 20px;
        margin-top: 8px;
    }
    .verify-code {
        font-size: 1.8rem;
        font-weight: 800;
        font-family: monospace;
        color: var(--accent);
        letter-spacing: 3px;
    }
    .verify-note {
        background: var(--bg);
        border-radius: var(--radius);
        padding: 16px;
        margin-top: 20px;
    }
    .verify-note p {
        color: var(--text-muted);
        font-size: 0.85rem;
        margin-bottom: 4px;
    }
    code {
        background: var(--bg);
        border: 1px solid var(--border);
        padding: 1px 6px;
        border-radius: 3px;
        font-size: 0.85em;
        color: var(--accent);
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
