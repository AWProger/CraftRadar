<?php
/**
 * CraftRadar — Выход
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

logoutUser();
setFlash('success', 'Вы вышли из аккаунта.');
redirect(SITE_URL . '/');
