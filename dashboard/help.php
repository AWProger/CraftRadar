<?php
/**
 * CraftRadar — Помощь: как пользоваться сервисом
 */

$pageTitle = 'Помощь';
require_once __DIR__ . '/../includes/header.php';

requireAuth();
?>

<div class="dashboard">
    <?= dashboardNav('help') ?>
    <div class="dashboard-header">
        <h1>❓ Как пользоваться CraftRadar</h1>
    </div>

    <!-- Быстрые якоря -->
    <div class="help-nav">
        <a href="#start" class="help-nav-link">🚀 Начало</a>
        <a href="#servers" class="help-nav-link">📡 Серверы</a>
        <a href="#verify" class="help-nav-link">🔐 Верификация</a>
        <a href="#votes" class="help-nav-link">👍 Голосование</a>
        <a href="#diamonds" class="help-nav-link">💎 Алмазы</a>
        <a href="#coins" class="help-nav-link">💰 Монеты</a>
        <a href="#highlight" class="help-nav-link">⚡ Выделение</a>
        <a href="#promote" class="help-nav-link">⭐ Продвижение</a>
        <a href="#favorites" class="help-nav-link">❤️ Избранное</a>
        <a href="#achievements" class="help-nav-link">🏆 Достижения</a>
        <a href="#referrals" class="help-nav-link">👥 Рефералы</a>
        <a href="#profile" class="help-nav-link">👤 Профиль</a>
    </div>

    <!-- Начало работы -->
    <div class="help-section" id="start">
        <div class="help-icon">🚀</div>
        <div class="help-content">
            <h2>Начало работы</h2>
            <p>CraftRadar — мониторинг Minecraft серверов. Здесь игроки находят серверы, а владельцы продвигают свои проекты.</p>
            <div class="help-steps">
                <div class="help-step">
                    <span class="help-step-num">1</span>
                    <span>Зарегистрируйтесь на сайте</span>
                </div>
                <div class="help-step">
                    <span class="help-step-num">2</span>
                    <span>Заполните <a href="<?= SITE_URL ?>/dashboard/profile.php">профиль</a> и укажите Minecraft ник</span>
                </div>
                <div class="help-step">
                    <span class="help-step-num">3</span>
                    <span>Голосуйте за серверы, добавляйте в избранное, оставляйте отзывы</span>
                </div>
                <div class="help-step">
                    <span class="help-step-num">4</span>
                    <span>Если вы владелец сервера — <a href="<?= SITE_URL ?>/dashboard/add.php">добавьте его</a> в каталог</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Серверы -->
    <div class="help-section" id="servers">
        <div class="help-icon">📡</div>
        <div class="help-content">
            <h2>Добавление сервера</h2>
            <p>Вы можете добавить до <strong><?= SERVERS_LIMIT_PER_USER ?></strong> серверов. После добавления сервер попадает на модерацию, но уже виден в каталоге с пометкой «Модерация».</p>
            <div class="help-details">
                <div class="help-detail">
                    <strong>Что нужно указать:</strong> название, IP-адрес, порт, описание, версию, режим игры
                </div>
                <div class="help-detail">
                    <strong>Автоматически:</strong> система пингует сервер, определяет онлайн, версию и иконку
                </div>
                <div class="help-detail">
                    <strong>Редактирование:</strong> можно менять всё кроме IP и порта (для смены — удалите и добавьте заново)
                </div>
            </div>
            <a href="<?= SITE_URL ?>/dashboard/add.php" class="btn btn-sm btn-primary">Добавить сервер</a>
        </div>
    </div>

    <!-- Верификация -->
    <div class="help-section" id="verify">
        <div class="help-icon">🔐</div>
        <div class="help-content">
            <h2>Верификация владельца</h2>
            <p>Подтвердите, что сервер принадлежит вам. Верифицированные серверы отмечаются значком ✓ и вызывают больше доверия у игроков.</p>
            <div class="help-steps">
                <div class="help-step">
                    <span class="help-step-num">1</span>
                    <span>Откройте страницу верификации вашего сервера</span>
                </div>
                <div class="help-step">
                    <span class="help-step-num">2</span>
                    <span>Скопируйте уникальный код</span>
                </div>
                <div class="help-step">
                    <span class="help-step-num">3</span>
                    <span>Добавьте код в <code>server.properties</code> → <code>motd</code></span>
                </div>
                <div class="help-step">
                    <span class="help-step-num">4</span>
                    <span>Перезагрузите сервер и нажмите «Проверить»</span>
                </div>
            </div>
            <div class="help-tip">
                💡 После верификации код можно удалить из MOTD. Подождите 2-3 минуты после перезагрузки перед проверкой.
            </div>
        </div>
    </div>

    <!-- Голосование -->
    <div class="help-section" id="votes">
        <div class="help-icon">👍</div>
        <div class="help-content">
            <h2>Голосование</h2>
            <p>Голосуйте за понравившиеся серверы — это помогает им подняться в рейтинге и приносит вам алмазы.</p>
            <div class="help-details">
                <div class="help-detail">
                    <strong>Лимит:</strong> <?= MAX_VOTES_PER_DAY ?> голосов в день (за разные серверы)
                </div>
                <div class="help-detail">
                    <strong>Кулдаун:</strong> за один сервер можно голосовать раз в <?= VOTE_COOLDOWN ?> часа
                </div>
                <div class="help-detail">
                    <strong>Награда:</strong> +<?= POINTS_PER_VOTE ?> 💎 за каждый голос
                </div>
                <div class="help-detail">
                    <strong>Ник:</strong> если указан Minecraft ник в профиле — он подставится автоматически
                </div>
            </div>
        </div>
    </div>

    <!-- Алмазы -->
    <div class="help-section" id="diamonds">
        <div class="help-icon">💎</div>
        <div class="help-content">
            <h2>Алмазы (бесплатная валюта)</h2>
            <p>Алмазы — бесплатная валюта, которую вы зарабатываете активностью на сайте. Тратятся на <a href="#highlight">выделение сервера</a>.</p>
            <div class="help-details">
                <div class="help-detail">
                    <strong>Голосование:</strong> +<?= POINTS_PER_VOTE ?> 💎 за голос (до <?= MAX_VOTES_PER_DAY ?> в день)
                </div>
                <div class="help-detail">
                    <strong>Достижения:</strong> от +5 до +50 💎 за выполнение условий
                </div>
                <div class="help-detail">
                    <strong>Рефералы:</strong> +<?= REFERRAL_REWARD_REGISTER ?> 💎 за каждого приглашённого друга
                </div>
                <div class="help-detail">
                    <strong>Ежедневные визиты:</strong> заходите каждый день — копите серию и получайте достижения
                </div>
            </div>
            <a href="<?= SITE_URL ?>/dashboard/points.php" class="btn btn-sm btn-outline" style="color: var(--diamond);">💎 Мои алмазы</a>
        </div>
    </div>

    <!-- Монеты -->
    <div class="help-section" id="coins">
        <div class="help-icon">💰</div>
        <div class="help-content">
            <h2>Монеты (платная валюта)</h2>
            <p>Монеты покупаются за реальные деньги через ЮMoney. Тратятся на <a href="#promote">продвижение сервера</a> — самый мощный способ привлечь игроков.</p>
            <div class="help-details">
                <div class="help-detail">
                    <strong>Курс:</strong> 1 монета = 1 рубль
                </div>
                <div class="help-detail">
                    <strong>Пакеты:</strong> от 100 до 1200 монет (чем больше — тем выгоднее)
                </div>
                <div class="help-detail">
                    <strong>Оплата:</strong> ЮMoney (кошелёк, карта)
                </div>
            </div>
            <a href="<?= SITE_URL ?>/dashboard/buy_coins.php" class="btn btn-sm btn-gold">💰 Купить монеты</a>
        </div>
    </div>

    <!-- Выделение -->
    <div class="help-section" id="highlight">
        <div class="help-icon">⚡</div>
        <div class="help-content">
            <h2>Выделение сервера (за 💎)</h2>
            <p>Выделение — бесплатный способ привлечь внимание к серверу. Сервер получает алмазную рамку и поднимается выше в каталоге.</p>
            <table class="help-table">
                <thead><tr><th>Тариф</th><th>Стоимость</th><th>Длительность</th></tr></thead>
                <tbody>
                    <?php foreach (HIGHLIGHT_COSTS as $key => $c): ?>
                    <?php $labels = ['1h' => '1 час', '6h' => '6 часов', '24h' => '24 часа']; ?>
                    <tr>
                        <td><?= $labels[$key] ?? $key ?></td>
                        <td style="color: var(--diamond);"><?= $c['points'] ?> 💎</td>
                        <td><?= $c['hours'] ?> ч.</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="help-tip">
                💡 Время суммируется — если выделить повторно, часы добавятся к текущему сроку.
            </div>
        </div>
    </div>

    <!-- Продвижение -->
    <div class="help-section" id="promote">
        <div class="help-icon">⭐</div>
        <div class="help-content">
            <h2>Продвижение сервера (за 💰)</h2>
            <p>Продвижение — премиум-размещение. Сервер закрепляется в самом верху каталога с золотой рамкой и приоритетом в поиске.</p>
            <table class="help-table">
                <thead><tr><th>Тариф</th><th>Стоимость</th><th>За день</th></tr></thead>
                <tbody>
                    <?php
                    $promoLabels = ['7d' => '7 дней', '14d' => '14 дней', '30d' => '30 дней'];
                    $promoDays = ['7d' => 7, '14d' => 14, '30d' => 30];
                    foreach (PROMOTE_COIN_COSTS as $key => $cost):
                    ?>
                    <tr>
                        <td><?= $promoLabels[$key] ?? $key ?></td>
                        <td style="color: var(--gold);"><?= $cost ?> 💰</td>
                        <td style="color: var(--text-muted);"><?= round($cost / $promoDays[$key], 1) ?> 💰/день</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="help-compare">
                <div class="help-compare-col">
                    <h4 style="color: var(--gold);">⭐ Продвижение</h4>
                    <ul>
                        <li>Золотая рамка</li>
                        <li>Самый верх каталога</li>
                        <li>7-30 дней</li>
                        <li>За монеты (платно)</li>
                    </ul>
                </div>
                <div class="help-compare-col">
                    <h4 style="color: var(--diamond);">⚡ Выделение</h4>
                    <ul>
                        <li>Алмазная рамка</li>
                        <li>Выше обычных серверов</li>
                        <li>1-24 часа</li>
                        <li>За алмазы (бесплатно)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- Избранное -->
    <div class="help-section" id="favorites">
        <div class="help-icon">❤️</div>
        <div class="help-content">
            <h2>Избранное</h2>
            <p>Добавляйте серверы в избранное, чтобы быстро находить их в своём <a href="<?= SITE_URL ?>/dashboard/profile.php">профиле</a>.</p>
            <div class="help-details">
                <div class="help-detail">
                    Нажмите 🤍 на странице любого сервера — он появится в вашем списке избранных
                </div>
                <div class="help-detail">
                    Нажмите ❤️ повторно — сервер удалится из избранного
                </div>
                <div class="help-detail">
                    За 5 избранных серверов вы получите достижение «Коллекционер» (+5 💎)
                </div>
            </div>
        </div>
    </div>

    <!-- Достижения -->
    <div class="help-section" id="achievements">
        <div class="help-icon">🏆</div>
        <div class="help-content">
            <h2>Достижения</h2>
            <p>Выполняйте условия и получайте алмазы. Прогресс виден в <a href="<?= SITE_URL ?>/dashboard/profile.php">профиле</a>.</p>
            <table class="help-table">
                <thead><tr><th>Достижение</th><th>Условие</th><th>Награда</th></tr></thead>
                <tbody>
                    <tr><td>🗳 Первый голос</td><td>Проголосовать 1 раз</td><td>+5 💎</td></tr>
                    <tr><td>🗳 Активный избиратель</td><td>10 голосов</td><td>+10 💎</td></tr>
                    <tr><td>🗳 Голос народа</td><td>50 голосов</td><td>+25 💎</td></tr>
                    <tr><td>🗳 Легенда голосований</td><td>100 голосов</td><td>+50 💎</td></tr>
                    <tr><td>💬 Критик</td><td>Написать 1 отзыв</td><td>+5 💎</td></tr>
                    <tr><td>💬 Обозреватель</td><td>5 отзывов</td><td>+15 💎</td></tr>
                    <tr><td>📡 Владелец</td><td>Добавить сервер</td><td>+10 💎</td></tr>
                    <tr><td>🔐 Подтверждённый</td><td>Верификация MOTD</td><td>+20 💎</td></tr>
                    <tr><td>🔥 Постоянный</td><td>3 дня подряд</td><td>+5 💎</td></tr>
                    <tr><td>🔥 Недельный марафон</td><td>7 дней подряд</td><td>+15 💎</td></tr>
                    <tr><td>🔥 Месячный марафон</td><td>30 дней подряд</td><td>+50 💎</td></tr>
                    <tr><td>❤️ Коллекционер</td><td>5 избранных</td><td>+5 💎</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Рефералы -->
    <div class="help-section" id="referrals">
        <div class="help-icon">👥</div>
        <div class="help-content">
            <h2>Реферальная программа</h2>
            <p>Приглашайте друзей и получайте алмазы. Ваша реферальная ссылка находится в <a href="<?= SITE_URL ?>/dashboard/profile.php">профиле</a>.</p>
            <div class="help-details">
                <div class="help-detail">
                    <strong>Вы получаете:</strong> +<?= REFERRAL_REWARD_REGISTER ?> 💎 за каждого зарегистрировавшегося друга
                </div>
                <div class="help-detail">
                    <strong>Друг получает:</strong> +<?= REFERRAL_REWARD_REFERRED ?> 💎 бонус при регистрации
                </div>
                <div class="help-detail">
                    <strong>Бонус:</strong> +<?= REFERRAL_REWARD_FIRST_VOTE ?> 💎 когда приглашённый проголосует впервые
                </div>
            </div>
            <div class="help-tip">
                💡 Скопируйте ссылку из профиля и отправьте друзьям — алмазы начислятся автоматически.
            </div>
        </div>
    </div>

    <!-- Профиль -->
    <div class="help-section" id="profile">
        <div class="help-icon">👤</div>
        <div class="help-content">
            <h2>Профиль</h2>
            <p>Ваш профиль виден другим пользователям. Чем активнее вы — тем круче выглядит профиль.</p>
            <div class="help-details">
                <div class="help-detail">
                    <strong>Minecraft ник:</strong> указав ник, вы получите аватарку из скина и автозаполнение при голосовании
                </div>
                <div class="help-detail">
                    <strong>Статистика:</strong> алмазы, монеты, голоса, отзывы, избранные, достижения
                </div>
                <div class="help-detail">
                    <strong>Серия визитов:</strong> заходите каждый день — 🔥 счётчик растёт и открывает достижения
                </div>
                <div class="help-detail">
                    <strong>Публичный профиль:</strong> другие пользователи видят вашу активность и достижения
                </div>
            </div>
            <a href="<?= SITE_URL ?>/dashboard/profile.php" class="btn btn-sm btn-outline">👤 Мой профиль</a>
        </div>
    </div>

    <!-- Остались вопросы -->
    <div class="help-section help-section-cta">
        <div class="help-icon">💬</div>
        <div class="help-content">
            <h2>Остались вопросы?</h2>
            <p>Напишите нам — мы поможем разобраться.</p>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="<?= SITE_URL ?>/page.php?slug=faq" class="btn btn-sm btn-outline">📋 FAQ</a>
                <a href="<?= SITE_URL ?>/page.php?slug=rules" class="btn btn-sm btn-outline">📜 Правила</a>
                <a href="<?= SITE_URL ?>/page.php?slug=contacts" class="btn btn-sm btn-outline">✉️ Контакты</a>
            </div>
        </div>
    </div>
</div>

<style>
    /* Быстрая навигация по разделам */
    .help-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 24px;
        padding: 12px;
        background: var(--bg-card);
        border: var(--pixel-border) var(--border);
        box-shadow: var(--shadow);
    }
    .help-nav-link {
        padding: 6px 12px;
        font-size: 0.75rem;
        color: var(--text-muted);
        border: 2px solid var(--border);
        transition: all var(--transition);
    }
    .help-nav-link:hover {
        color: var(--accent);
        border-color: var(--accent);
        background: var(--accent-bg);
    }

    /* Секция помощи */
    .help-section {
        display: flex;
        gap: 16px;
        background: var(--bg-card);
        border: var(--pixel-border) var(--border);
        padding: 20px;
        margin-bottom: 12px;
        box-shadow: var(--shadow);
        scroll-margin-top: 80px;
        transition: border-color var(--transition);
    }
    .help-section:target {
        border-color: var(--accent);
    }
    .help-section-cta {
        border-color: var(--accent);
        background: linear-gradient(135deg, var(--bg-card), rgba(0,255,128,0.03));
    }
    .help-icon {
        font-size: 2rem;
        flex-shrink: 0;
        width: 48px;
        text-align: center;
    }
    .help-content {
        flex: 1;
        min-width: 0;
    }
    .help-content h2 {
        font-family: var(--font-mc);
        font-size: 0.85rem;
        color: var(--accent);
        text-shadow: 2px 2px 0 rgba(0,0,0,0.5);
        margin-bottom: 8px;
    }
    .help-content p {
        color: var(--text-muted);
        font-size: 0.85rem;
        line-height: 1.6;
        margin-bottom: 12px;
    }
    .help-content a:not(.btn) {
        color: var(--accent);
        text-decoration: underline;
    }

    /* Шаги */
    .help-steps {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin: 12px 0;
    }
    .help-step {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.85rem;
        color: var(--text);
    }
    .help-step-num {
        width: 24px;
        height: 24px;
        background: var(--accent);
        color: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.75rem;
        flex-shrink: 0;
    }

    /* Детали */
    .help-details {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin: 12px 0;
    }
    .help-detail {
        font-size: 0.8rem;
        color: var(--text);
        padding: 6px 10px;
        background: var(--bg);
        border-left: 3px solid var(--accent);
    }

    /* Подсказка */
    .help-tip {
        font-size: 0.8rem;
        color: var(--text-muted);
        padding: 10px 12px;
        background: rgba(255, 215, 0, 0.05);
        border: 2px solid var(--gold);
        margin-top: 12px;
    }

    /* Таблица */
    .help-table {
        width: 100%;
        font-size: 0.8rem;
        margin: 12px 0;
    }
    .help-table th {
        text-align: left;
        color: var(--text-muted);
        font-size: 0.7rem;
        text-transform: uppercase;
        padding: 6px 8px;
        border-bottom: 2px solid var(--border);
    }
    .help-table td {
        padding: 6px 8px;
        border-bottom: 1px solid var(--border);
        color: var(--text);
    }

    /* Сравнение */
    .help-compare {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin: 12px 0;
    }
    .help-compare-col {
        padding: 12px;
        background: var(--bg);
        border: 2px solid var(--border);
    }
    .help-compare-col h4 {
        font-family: var(--font-mc);
        font-size: 0.7rem;
        margin-bottom: 8px;
    }
    .help-compare-col ul {
        list-style: none;
        font-size: 0.75rem;
        color: var(--text-muted);
    }
    .help-compare-col ul li {
        padding: 2px 0;
    }
    .help-compare-col ul li::before {
        content: '✓ ';
        color: var(--success);
    }

    /* Мобильная адаптация */
    @media (max-width: 768px) {
        .help-section {
            flex-direction: column;
            gap: 8px;
        }
        .help-icon {
            width: auto;
        }
        .help-compare {
            grid-template-columns: 1fr;
        }
        .help-nav {
            gap: 4px;
        }
        .help-nav-link {
            font-size: 0.65rem;
            padding: 4px 8px;
        }
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
