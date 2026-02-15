<?php
require './DatabaseConnector.php';

// EDIT: pune aici emailul cu care încerci să te loghezi
$testEmail = 'admin@example.com';

$db = new DatabaseConnector();
$pdo = $db->connect();

$stmt = $pdo->prepare("SELECT * FROM useri WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $testEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: text/plain; charset=utf-8');

if (!$user) {
  echo "NU există user cu emailul: $testEmail\n";
  exit;
}

echo "User găsit:\n";
print_r($user);

// Testează verificarea parolei (pune parola pe care o tastezi la login)
$parolaIntrodusa = 'Test1234!'; // <- parola cu care încerci
echo "\npassword_verify(): ";
var_dump(password_verify($parolaIntrodusa, $user['parola']));

// Test „ brut ” (doar ca să vedem dacă ai parolă în clar în DB)
echo "comparatie directa (NU e pt. productie): ";
var_dump($parolaIntrodusa === $user['parola']);
