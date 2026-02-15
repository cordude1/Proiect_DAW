<?php
// backButton.php — buton „înapoi” flotant, reutilizabil
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__.'/config/env.php';
require_once __DIR__.'/path.php';

$current = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');

/**
 * Reguli:
 * - dacă NU suntem pe adminDashboard.php -> butonul duce la Dashboard (pentru admin)
 *   altfel, dacă nu e admin, duce la Home.
 * - dacă suntem pe adminDashboard.php -> folosește history.back() (nu stricăm URL-ul).
 */
$isAdmin = (($_SESSION['role'] ?? $_SESSION['rol'] ?? '') === 'admin');

if ($current !== 'adminDashboard.php') {
    $href  = $isAdmin ? url('adminDashboard.php') : url('index.php');
    $title = $isAdmin ? 'Înapoi la Dashboard' : 'Înapoi la Acasă';
} else {
    $href  = 'javascript:history.back()';
    $title = 'Înapoi';
}
?>
<a href="<?= htmlspecialchars($href) ?>"
   class="back-fab"
   aria-label="<?= htmlspecialchars($title) ?>"
   title="<?= htmlspecialchars($title) ?>">
  <svg class="back-fab__icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
    <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
  </svg>
</a>
