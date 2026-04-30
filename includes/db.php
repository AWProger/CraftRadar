<?php
/**
 * CraftRadar — Подключение к базе данных (PDO)
 */

require_once __DIR__ . '/config.php';

if (!function_exists('getDB')) {
function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Fallback на SQLite для локальной разработки
            if (DEBUG) {
                $sqliteFile = ROOT_PATH . 'storage/craftradar_dev.sqlite';
                $sqliteDir = dirname($sqliteFile);
                if (!is_dir($sqliteDir)) mkdir($sqliteDir, 0755, true);

                try {
                    $pdo = new PDO('sqlite:' . $sqliteFile);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                    initSQLiteTables($pdo);
                    return $pdo;
                } catch (PDOException $e2) {
                    die('Ошибка подключения к БД: ' . $e->getMessage() . ' | SQLite fallback: ' . $e2->getMessage());
                }
            } else {
                die('Ошибка подключения к базе данных. Попробуйте позже.');
            }
        }
    }

    return $pdo;
}
} // end if !function_exists

/**
 * Инициализация таблиц SQLite для локальной разработки
 */
function initSQLiteTables(PDO $pdo): void
{
    // Проверяем, есть ли уже таблицы
    $check = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'")->fetch();
    if ($check) return;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE, email TEXT UNIQUE, password_hash TEXT,
            role TEXT DEFAULT 'user', is_banned INTEGER DEFAULT 0,
            ban_reason TEXT, ban_until TEXT, banned_by INTEGER,
            points INTEGER DEFAULT 0,
            created_at TEXT, last_login TEXT, last_ip TEXT
        );
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT, slug TEXT UNIQUE, icon TEXT, sort_order INTEGER DEFAULT 0, is_active INTEGER DEFAULT 1
        );
        CREATE TABLE IF NOT EXISTS servers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER, name TEXT, ip TEXT, port INTEGER DEFAULT 25565,
            description TEXT, website TEXT, version TEXT, game_mode TEXT, tags TEXT,
            icon TEXT, banner TEXT, is_online INTEGER DEFAULT 0,
            players_online INTEGER DEFAULT 0, players_max INTEGER DEFAULT 0, motd TEXT,
            votes_total INTEGER DEFAULT 0, votes_month INTEGER DEFAULT 0,
            rating REAL DEFAULT 0, reviews_count INTEGER DEFAULT 0,
            status TEXT DEFAULT 'pending', reject_reason TEXT,
            is_promoted INTEGER DEFAULT 0, promoted_until TEXT,
            is_verified INTEGER DEFAULT 0, verify_code TEXT, verified_at TEXT, verified_by INTEGER,
            highlighted_until TEXT,
            created_at TEXT, updated_at TEXT, last_ping TEXT, consecutive_fails INTEGER DEFAULT 0
        );
        CREATE TABLE IF NOT EXISTS votes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            server_id INTEGER, user_id INTEGER, ip_address TEXT, voted_at TEXT
        );
        CREATE TABLE IF NOT EXISTS server_stats (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            server_id INTEGER, players_online INTEGER DEFAULT 0, is_online INTEGER DEFAULT 0,
            ping_ms INTEGER, recorded_at TEXT
        );
        CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            server_id INTEGER, user_id INTEGER, rating INTEGER, text TEXT,
            status TEXT DEFAULT 'active', hidden_by INTEGER, hidden_reason TEXT, created_at TEXT
        );
        CREATE TABLE IF NOT EXISTS reports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            reporter_id INTEGER, target_type TEXT, target_id INTEGER,
            reason TEXT, description TEXT, status TEXT DEFAULT 'new',
            resolved_by INTEGER, resolution_note TEXT, created_at TEXT, resolved_at TEXT
        );
        CREATE TABLE IF NOT EXISTS admin_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            admin_id INTEGER, action TEXT, target_type TEXT, target_id INTEGER,
            details TEXT, ip_address TEXT, created_at TEXT
        );
        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY, value TEXT, description TEXT, updated_at TEXT, updated_by INTEGER
        );
        CREATE TABLE IF NOT EXISTS pages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT UNIQUE, title TEXT, content TEXT, is_published INTEGER DEFAULT 1,
            updated_at TEXT, updated_by INTEGER
        );
        CREATE TABLE IF NOT EXISTS payments (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER, server_id INTEGER, amount REAL, currency TEXT DEFAULT 'RUB',
            type TEXT, status TEXT DEFAULT 'pending', payment_id TEXT,
            yoomoney_operation_id TEXT, label TEXT, description TEXT,
            paid_at TEXT, created_at TEXT
        );
        CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER, type TEXT, title TEXT, message TEXT, link TEXT,
            is_read INTEGER DEFAULT 0, created_at TEXT
        );
        CREATE TABLE IF NOT EXISTS point_transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER, amount INTEGER, type TEXT, description TEXT,
            server_id INTEGER, created_at TEXT
        );
    ");

    // Начальные данные
    $pdo->exec("
        INSERT OR IGNORE INTO categories (name, slug, icon, sort_order) VALUES
        ('Анархия', 'anarchy', '🔥', 1), ('Ванилла', 'vanilla', '🌿', 2),
        ('Выживание', 'survival', '⚔️', 3), ('Креатив', 'creative', '🎨', 4),
        ('Мини-игры', 'minigames', '🎮', 5), ('RPG', 'rpg', '🗡️', 6),
        ('Скайблок', 'skyblock', '☁️', 7), ('PvP', 'pvp', '🏹', 8);

        INSERT OR IGNORE INTO settings (key, value, description) VALUES
        ('site_name', 'CraftRadar', 'Название сайта'),
        ('site_description', 'Мониторинг серверов Minecraft', 'Описание');

        INSERT OR IGNORE INTO pages (slug, title, content, is_published, updated_at) VALUES
        ('about', 'О проекте', '<p>CraftRadar — мониторинг серверов Minecraft.</p>', 1, datetime('now')),
        ('rules', 'Правила', '<p>Правила использования платформы.</p>', 1, datetime('now')),
        ('faq', 'FAQ', '<p>Часто задаваемые вопросы.</p>', 1, datetime('now'));
    ");
}
