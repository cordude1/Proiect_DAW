<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__.'/security.php';
require_once __DIR__.'/DatabaseConnector.php';

/* NU ruleaza analytics la export */
if (!str_contains($_SERVER['SCRIPT_NAME'], 'exportUsers.php')) {
    require_once __DIR__.'/analytics.php';
}
