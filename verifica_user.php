<?php
declare(strict_types=1);
header('Content-Type: text/plain; charset=UTF-8');

require_once __DIR__.'/bootstrap.php';


$pdo = (new DatabaseConnector())->connect();

if (
    isset($_POST['email']) &&
    isset($_POST['nume']) &&
    isset($_POST['prenume'])
) {
    $email   = trim((string)$_POST['email']);
    $nume    = trim((string)$_POST['nume']);
    $prenume = trim((string)$_POST['prenume']);

    $query = "SELECT id FROM useri WHERE email = :email OR (nume = :nume AND prenume = :prenume) LIMIT 1";
    $stmt  = $pdo->prepare($query);
    $stmt->execute([
        ':email'   => $email,
        ':nume'    => $nume,
        ':prenume' => $prenume
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo $user ? "exista" : "nu_exista";
    exit;
}

http_response_code(400);
echo "parametri lipsa";
