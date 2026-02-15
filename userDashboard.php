<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';


if (!isset($_SESSION['user_id'])) {
  header('Location: login.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Dashboard</title>
  <style>
    body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;display:flex;align-items:center;justify-content:center;min-height:100vh}
    .card{background:#fff;border-radius:12px;box-shadow:0 10px 24px rgba(0,0,0,.08);padding:24px;max-width:520px;width:100%;text-align:center}
    .btn{display:inline-block;margin-top:12px;padding:10px 14px;border-radius:8px;text-decoration:none;color:#fff;background:#0d6efd}
    .btn:hover{filter:brightness(.95)}
  </style>
</head>
<body>
  <div class="card">
    <h2>Bun venit, <?= htmlspecialchars($_SESSION['nume'] ?? $_SESSION['email']) ?>!</h2>
    <p>Te-ai autentificat cu rolul <strong><?= htmlspecialchars($_SESSION['role'] ?? 'user') ?></strong>.</p>
    <a class="btn" href="index.php">Înapoi la Home</a>
    <a class="btn" href="logout.php" style="background:#dc3545">Logout</a>
  </div>
</body>
</html>
