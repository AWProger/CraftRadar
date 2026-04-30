        </div>
    </main>

    <?php
    // Боковой виджет топ-серверов (не показываем в админке)
    if (!isset($adminPageTitle)) {
        require_once __DIR__ . '/sidebar_top.php';
    }
    ?>

    <footer class="footer">
        <div class="container">
            <div class="footer-inner">
                <div class="footer-copy">
                    &copy; <?= date('Y') ?> <?= SITE_NAME ?> — <?= e(SITE_DESCRIPTION) ?>
                </div>
                <div class="footer-links">
                    <a href="<?= SITE_URL ?>/page.php?slug=about">О проекте</a>
                    <a href="<?= SITE_URL ?>/page.php?slug=services">Услуги</a>
                    <a href="<?= SITE_URL ?>/page.php?slug=rules">Правила</a>
                    <a href="<?= SITE_URL ?>/page.php?slug=offer">Оферта</a>
                    <a href="<?= SITE_URL ?>/page.php?slug=contacts">Контакты</a>
                    <a href="<?= SITE_URL ?>/page.php?slug=faq">FAQ</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="<?= SITE_URL ?>/assets/js/app.js"></script>
    <script>document.body.dataset.siteUrl = '<?= SITE_URL ?>'; document.body.dataset.sidebarInterval = '<?= SIDEBAR_SLIDE_INTERVAL ?>';</script>
</body>
</html>
