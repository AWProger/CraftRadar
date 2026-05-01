-- CraftRadar — Создание таблиц
-- MySQL 8 / MariaDB 10.6+
-- База данных уже создана через панель хостинга, выберите её в phpMyAdmin перед импортом

-- ============================================
-- Пользователи
-- ============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'moderator', 'admin') NOT NULL DEFAULT 'user',
    is_banned TINYINT NOT NULL DEFAULT 0,
    ban_reason VARCHAR(255) NULL,
    ban_until DATETIME NULL,
    banned_by INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME NULL,
    last_ip VARCHAR(45) NULL,
    FOREIGN KEY (banned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Категории / режимы
-- ============================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(10) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ============================================
-- Серверы
-- ============================================
CREATE TABLE IF NOT EXISTS servers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    ip VARCHAR(255) NOT NULL,
    port INT NOT NULL DEFAULT 25565,
    description TEXT NULL,
    website VARCHAR(255) NULL,
    version VARCHAR(50) NULL,
    game_mode VARCHAR(50) NULL,
    tags VARCHAR(255) NULL,
    icon VARCHAR(255) NULL,
    banner VARCHAR(255) NULL,
    is_online TINYINT NOT NULL DEFAULT 0,
    players_online INT NOT NULL DEFAULT 0,
    players_max INT NOT NULL DEFAULT 0,
    motd VARCHAR(255) NULL,
    votes_total INT NOT NULL DEFAULT 0,
    votes_month INT NOT NULL DEFAULT 0,
    rating DECIMAL(3,2) NOT NULL DEFAULT 0.00,
    reviews_count INT NOT NULL DEFAULT 0,
    status ENUM('active', 'pending', 'rejected', 'banned') NOT NULL DEFAULT 'pending',
    reject_reason VARCHAR(255) NULL,
    is_promoted TINYINT NOT NULL DEFAULT 0,
    promoted_until DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    last_ping DATETIME NULL,
    consecutive_fails INT NOT NULL DEFAULT 0,
    is_verified TINYINT NOT NULL DEFAULT 0 COMMENT 'Владелец подтверждён через MOTD',
    verify_code VARCHAR(10) NULL COMMENT 'Код верификации для MOTD',
    verified_at DATETIME NULL,
    verified_by INT NULL COMMENT 'Кто подтвердил владение',
    highlighted_until DATETIME NULL COMMENT 'Выделение за баллы до',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_votes_month (votes_month),
    INDEX idx_players_online (players_online),
    INDEX idx_is_online (is_online)
) ENGINE=InnoDB;

-- ============================================
-- Голоса
-- ============================================
CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_id INT NOT NULL,
    user_id INT NOT NULL,
    minecraft_nick VARCHAR(32) NULL COMMENT 'Ник игрока в Minecraft',
    ip_address VARCHAR(45) NOT NULL,
    voted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_server_user_date (server_id, user_id, voted_at)
) ENGINE=InnoDB;

-- ============================================
-- Статистика пингов
-- ============================================
CREATE TABLE IF NOT EXISTS server_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_id INT NOT NULL,
    players_online INT NOT NULL DEFAULT 0,
    is_online TINYINT NOT NULL DEFAULT 0,
    ping_ms INT NULL,
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    INDEX idx_server_recorded (server_id, recorded_at)
) ENGINE=InnoDB;

-- ============================================
-- Отзывы
-- ============================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    server_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL,
    text TEXT NULL,
    status ENUM('active', 'hidden', 'deleted') NOT NULL DEFAULT 'active',
    hidden_by INT NULL,
    hidden_reason VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hidden_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE INDEX idx_server_user (server_id, user_id)
) ENGINE=InnoDB;

-- ============================================
-- Жалобы
-- ============================================
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT NOT NULL,
    target_type ENUM('server', 'review', 'user') NOT NULL,
    target_id INT NOT NULL,
    reason VARCHAR(50) NOT NULL,
    description TEXT NULL,
    status ENUM('new', 'in_progress', 'resolved', 'rejected') NOT NULL DEFAULT 'new',
    resolved_by INT NULL,
    resolution_note VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resolved_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_target (target_type, target_id)
) ENGINE=InnoDB;

-- ============================================
-- Лог действий администрации
-- ============================================
CREATE TABLE IF NOT EXISTS admin_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    target_type ENUM('server', 'user', 'review', 'category', 'report', 'setting') NOT NULL,
    target_id INT NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_admin (admin_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============================================
-- Настройки платформы
-- ============================================
CREATE TABLE IF NOT EXISTS settings (
    `key` VARCHAR(50) PRIMARY KEY,
    `value` TEXT NULL,
    description VARCHAR(255) NULL,
    updated_at DATETIME NULL,
    updated_by INT NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Статические страницы
-- ============================================
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    content TEXT NULL,
    is_published TINYINT NOT NULL DEFAULT 1,
    updated_at DATETIME NULL,
    updated_by INT NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Начальные данные
-- ============================================

-- Категории по умолчанию
INSERT INTO categories (name, slug, icon, sort_order) VALUES
('Анархия', 'anarchy', '🔥', 1),
('Ванилла', 'vanilla', '🌿', 2),
('Выживание', 'survival', '⚔️', 3),
('Креатив', 'creative', '🎨', 4),
('Мини-игры', 'minigames', '🎮', 5),
('RPG', 'rpg', '🗡️', 6),
('Скайблок', 'skyblock', '☁️', 7),
('Техно', 'techno', '⚙️', 8),
('PvP', 'pvp', '🏹', 9),
('Модовый', 'modded', '🧩', 10);

-- Настройки по умолчанию
INSERT INTO settings (`key`, `value`, description) VALUES
('site_name', 'CraftRadar', 'Название сайта'),
('site_description', 'Мониторинг серверов Minecraft', 'Описание сайта (meta)'),
('contact_email', 'admin@craftradar.ru', 'Контактный email'),
('servers_per_user', '5', 'Лимит серверов на аккаунт'),
('moderation_required', '1', 'Модерация новых серверов (1=да, 0=нет)'),
('vote_cooldown_hours', '24', 'Кулдаун голосования (часы)'),
('reviews_enabled', '1', 'Отзывы включены (1=да, 0=нет)'),
('reviews_moderation', '0', 'Модерация отзывов (1=да, 0=нет)'),
('registration_open', '1', 'Регистрация открыта (1=да, 0=нет)'),
('min_password_length', '6', 'Минимальная длина пароля'),
('ping_interval_minutes', '10', 'Интервал пинга (минуты)'),
('ping_timeout_seconds', '5', 'Таймаут пинга (секунды)'),
('max_consecutive_fails', '3', 'Неудачных пингов до оффлайн');

-- Страницы по умолчанию
INSERT INTO pages (slug, title, content, is_published, updated_at) VALUES
('about', 'О проекте', '<p>CraftRadar — мониторинг серверов Minecraft.</p>', 1, NOW()),
('rules', 'Правила', '<p>Правила использования платформы.</p>', 1, NOW()),
('faq', 'FAQ', '<p>Часто задаваемые вопросы.</p>', 1, NOW()),
('offer', 'Публичная оферта', '<h2>Публичная оферта на оказание услуг по продвижению серверов</h2>
<p><strong>Дата публикации:</strong> 30 апреля 2026 г.</p>

<h3>1. Общие положения</h3>
<p>1.1. Настоящая оферта является официальным предложением сервиса CraftRadar (далее — «Исполнитель») заключить договор на оказание услуг по продвижению серверов Minecraft на платформе craftradar.ru.</p>
<p>1.2. Акцептом (принятием) оферты является оплата услуги любым доступным способом.</p>

<h3>2. Описание услуги</h3>
<p>2.1. Услуга «Продвижение сервера» включает:</p>
<ul>
<li>Закрепление сервера в верхней части каталога и главной страницы</li>
<li>Отображение специального значка ⭐ рядом с названием сервера</li>
<li>Приоритет в результатах поиска</li>
</ul>
<p>2.2. Услуга предоставляется на выбранный срок (7, 14 или 30 дней) с момента подтверждения оплаты.</p>

<h3>3. Стоимость услуг</h3>
<ul>
<li>Продвижение на 7 дней — 99 руб.</li>
<li>Продвижение на 14 дней — 179 руб.</li>
<li>Продвижение на 30 дней — 299 руб.</li>
</ul>
<p>Цены являются фиксированными и указаны в российских рублях.</p>

<h3>4. Порядок оказания услуг</h3>
<p>4.1. Заказчик выбирает тариф продвижения в личном кабинете.</p>
<p>4.2. Оплата производится через платёжную систему ЮMoney (банковская карта или кошелёк).</p>
<p>4.3. Услуга активируется автоматически после подтверждения оплаты (обычно в течение нескольких минут).</p>
<p>4.4. Если сервер уже продвигается, оплаченные дни добавляются к текущему сроку.</p>

<h3>5. Возврат средств</h3>
<p>5.1. Возврат средств возможен в течение 24 часов после оплаты, если услуга ещё не была активирована.</p>
<p>5.2. Для возврата свяжитесь с нами по контактам, указанным на странице «Контакты».</p>

<h3>6. Ответственность</h3>
<p>6.1. Исполнитель не гарантирует конкретное количество переходов или голосов.</p>
<p>6.2. Исполнитель оставляет за собой право отказать в предоставлении услуги серверам, нарушающим правила платформы.</p>', 1, NOW()),
('contacts', 'Контакты', '<h2>Контактная информация</h2>
<p><strong>Сервис:</strong> CraftRadar — мониторинг серверов Minecraft</p>
<p><strong>Сайт:</strong> <a href=\"https://craftradar.ru\">craftradar.ru</a></p>
<p><strong>Email:</strong> admin@craftradar.ru</p>
<p><strong>Telegram:</strong> @craftradar</p>

<h3>Реквизиты</h3>
<p><strong>ФИО:</strong> [Укажите ФИО самозанятого]</p>
<p><strong>ИНН:</strong> [Укажите ИНН]</p>
<p><strong>Статус:</strong> Самозанятый (плательщик налога на профессиональный доход)</p>

<h3>По вопросам оплаты</h3>
<p>Если у вас возникли проблемы с оплатой или вы хотите запросить возврат средств, напишите на admin@craftradar.ru с указанием номера платежа.</p>', 1, NOW()),
('services', 'Услуги', '<h2>Платные услуги CraftRadar</h2>

<h3>Продвижение сервера</h3>
<p>Услуга продвижения позволяет выделить ваш сервер среди остальных в каталоге CraftRadar.</p>

<h4>Что входит в услугу:</h4>
<ul>
<li><strong>Закрепление в топе</strong> — ваш сервер отображается в самом верху каталога и на главной странице, выше всех остальных серверов</li>
<li><strong>Значок ⭐</strong> — специальная отметка рядом с названием сервера, привлекающая внимание игроков</li>
<li><strong>Приоритет в поиске</strong> — при поиске серверов продвигаемые серверы показываются первыми</li>
</ul>

<h4>Тарифы:</h4>
<table border=\"1\" cellpadding=\"8\" cellspacing=\"0\" style=\"border-collapse: collapse;\">
<tr><th>Срок</th><th>Стоимость</th><th>Цена за день</th></tr>
<tr><td>7 дней</td><td>99 ₽</td><td>~14 ₽/день</td></tr>
<tr><td>14 дней</td><td>179 ₽</td><td>~13 ₽/день</td></tr>
<tr><td>30 дней</td><td>299 ₽</td><td>~10 ₽/день</td></tr>
</table>

<h4>Как получить услугу:</h4>
<ol>
<li>Зарегистрируйтесь и добавьте свой сервер</li>
<li>Дождитесь модерации (обычно в течение нескольких часов)</li>
<li>В личном кабинете нажмите «⭐ Продвинуть» напротив нужного сервера</li>
<li>Выберите тариф и оплатите через ЮMoney (банковская карта или кошелёк)</li>
<li>Продвижение активируется автоматически после подтверждения оплаты</li>
</ol>

<h4>Способы оплаты:</h4>
<ul>
<li>Банковская карта (Visa, MasterCard, МИР)</li>
<li>Кошелёк ЮMoney</li>
</ul>

<p>Если у вас есть вопросы по оплате, свяжитесь с нами через страницу <a href=\"/page.php?slug=contacts\">Контакты</a>.</p>', 1, NOW());

-- ============================================
-- Платежи (ЮMoney)
-- ============================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    server_id INT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) NOT NULL DEFAULT 'RUB',
    type ENUM('promote_7d', 'promote_14d', 'promote_30d', 'custom') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    payment_id VARCHAR(100) NULL COMMENT 'ID операции в ЮMoney',
    yoomoney_operation_id VARCHAR(100) NULL,
    label VARCHAR(100) NULL COMMENT 'Метка платежа для идентификации',
    description VARCHAR(255) NULL,
    paid_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_status (status),
    INDEX idx_label (label),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Настройки ЮMoney
INSERT INTO settings (`key`, `value`, description) VALUES
('yoomoney_wallet', '', 'Номер кошелька ЮMoney'),
('yoomoney_secret', '', 'Секретный ключ для уведомлений ЮMoney'),
('promote_price_7d', '99', 'Цена продвижения на 7 дней (руб.)'),
('promote_price_14d', '179', 'Цена продвижения на 14 дней (руб.)'),
('promote_price_30d', '299', 'Цена продвижения на 30 дней (руб.)');

-- ============================================
-- Уведомления пользователей
-- ============================================
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL COMMENT 'server_offline, server_rejected, server_approved, new_review, payment_completed',
    title VARCHAR(200) NOT NULL,
    message TEXT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- ============================================
-- Система баллов
-- ============================================
ALTER TABLE users ADD COLUMN IF NOT EXISTS points INT NOT NULL DEFAULT 0 COMMENT 'Баллы пользователя';

CREATE TABLE IF NOT EXISTS point_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    amount INT NOT NULL COMMENT 'Положительное = начисление, отрицательное = списание',
    type VARCHAR(50) NOT NULL COMMENT 'vote_reward, highlight_spend, admin_grant, daily_bonus',
    description VARCHAR(255) NULL,
    server_id INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- Настройки баллов
INSERT INTO settings (`key`, `value`, description) VALUES
('points_per_vote', '1', 'Баллов за голосование'),
('highlight_cost_1h', '5', 'Стоимость выделения на 1 час (баллы)'),
('highlight_cost_6h', '25', 'Стоимость выделения на 6 часов (баллы)'),
('highlight_cost_24h', '80', 'Стоимость выделения на 24 часа (баллы)');
