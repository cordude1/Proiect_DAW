<?php
// config/env.php — loader simplu pt. .env (fără Composer)

if (!function_exists('env_load')) {
  function env_load(string $path): void {
    if (!is_file($path) || !is_readable($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '' || $line[0] === '#' || $line[0] === ';') continue;
      if (!str_contains($line, '=')) continue;
      [$key, $val] = array_map('trim', explode('=', $line, 2));
      if (($val[0] ?? '') === '"' && substr($val, -1) === '"') $val = substr($val, 1, -1);
      if (($val[0] ?? '') === "'" && substr($val, -1) === "'") $val = substr($val, 1, -1);
      putenv("$key=$val");
      $_ENV[$key]    = $val;
      $_SERVER[$key] = $val;
    }
  }
}

$root = dirname(__DIR__);      // proiect/.env
env_load($root.'/.env');

if (!defined('APP_ENV'))       define('APP_ENV', getenv('APP_ENV') ?: 'dev');
if (!defined('BASE_URL_PATH')) define('BASE_URL_PATH', rtrim((string)(getenv('BASE_URL_PATH') ?: ''), '/'));
