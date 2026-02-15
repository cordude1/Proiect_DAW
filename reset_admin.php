<?php
require_once __DIR__.'/bootstrap.php';

$db  = new DatabaseConnector();
$pdo = $db->connect();

$newHash = password_hash('admin123', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE useri SET parola = :p WHERE rol = 'admin'");
$stmt->execute([':p' => $newHash]);

echo "Parola admin a fost resetata la: admin123";
