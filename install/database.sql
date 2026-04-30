-- CraftRadar — Создание базы данных и таблиц
-- MySQL 8 / MariaDB 10.6+

CREATE DATABASE IF NOT EXISTS craftradar
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE craftradar;

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
('faq', 'FAQ', '<p>Часто задаваемые вопросы.</p>', 1, NOW());

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
