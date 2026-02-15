<?php
require_once 'vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Pornirea sesiunii, doar dacă nu este deja activă
if (session_status() == PHP_SESSION_NONE) {
    require_once __DIR__.'/bootstrap.php';

}

// Variabile pentru reCAPTCHA din .env
$CAPTCHA_SITE_KEY = $_ENV['CAPTCHA_SITE_KEY'];
$CAPTCHA_SECRET_KEY = $_ENV['CAPTCHA_SECRET_KEY'];
?>
