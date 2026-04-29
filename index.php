<?php
/**
 * CraftRadar — Главная страница
 */

$pageTitle = 'Мониторинг серверов Minecraft';
$pageDescription = 'CraftRadar — каталог серверов Minecraft с рейтингом, статистикой и голосованием.';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <h1>📡 <?= SITE_NAME ?></h1>
    <p class="hero-subtitle">Мониторинг серверов Minecraft — рейтинг, статистика, голосование</p>

    <form class="search-form" action="<?= SITE_URL ?>/servers.php" method="GET">
        <input type="text" name="q" placeholder="Поиск сервера по названию или IP..." class="search-input">
        <button type="submit" class="btn btn-primary">Найти</button>
    </form>
</section>

<style>
    .hero {
        text-align: center;
        padding: 60px 0 40px;
    }
    .hero h1 {
        font-size: 2.2rem;
        margin-bottom: 8px;
    }
    .hero-subtitle {
        color: var(--text-muted);
        font-size: 1.1rem;
        margin-bottom: 30px;
    }
    .search-form {
        display: flex;
        max-width: 600px;
        margin: 0 auto;
        gap: 8px;
    }
    .search-input {
        flex: 1;
        padding: 12px 16px;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        color: var(--text);
        font-size: 1rem;
    }
    .search-input:focus {
        outline: none;
        border-color: var(--accent);
    }
    .section {
        margin-top: 40px;
    }
</style>

<section class="section">
    <h2 class="section-title">🏆 Топ серверов</h2>
    <p class="text-muted" style="color: var(--text-muted);">Серверы появятся здесь после добавления и модерации.</p>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
