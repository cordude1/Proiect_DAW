<?php
// path.php
// Calculează calea de bază (ex: /AgentieTurism_Optimized/)
if (!defined('BASE_URL_PATH')) {
    $dir = str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = rtrim($dir, '/');
    define('BASE_URL_PATH', $dir === '' ? '/' : $dir . '/');
}

/** Link absolut relativ la proiect (pt. rute/PHP) */
function url(string $path): string {
    return BASE_URL_PATH . ltrim($path, '/');
}

/** Link la asset cu versiune automată (cache-buster pe mtime) */
function asset(string $relPath): string {
    $rel = ltrim($relPath, '/');
    $fs  = __DIR__ . '/' . $rel;              // căutăm pe disc în rădăcina proiectului
    $ver = file_exists($fs) ? filemtime($fs) : time();
    return BASE_URL_PATH . $rel . '?v=' . $ver;
}
