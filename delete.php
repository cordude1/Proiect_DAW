<?php
declare(strict_types=1);

require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/config/env.php';
require_once __DIR__.'/path.php';

/* doar admin */
$isAdmin = (($_SESSION['role'] ?? '') === 'admin') || (($_SESSION['rol'] ?? '') === 'admin');
if (!$isAdmin) {
    header('Location: '.url('login.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: '.url('adminDashboard.php'));
    exit;
}

$table = $_GET['table'] ?? '';

/* Tabele permise */
$allowedTables = [
    'useri'            => 'id',
    'pacheteturistice' => 'id_pachet',
    'rezervari'        => 'id_rezervare'
];

if (!array_key_exists($table, $allowedTables)) {
    $_SESSION['errorMessage'] = 'Tabel invalid.';
    header('Location: '.url('adminDashboard.php'));
    exit;
}

$primaryKey = $allowedTables[$table];
$id = (int)($_POST['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['errorMessage'] = 'ID invalid.';
    header('Location: '.url('adminDashboard.php'));
    exit;
}

try {

    $db  = new DatabaseConnector();
    $pdo = $db->connect();

    $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$primaryKey} = :id LIMIT 1");
    $stmt->execute([':id' => $id]);

    $_SESSION['successMessage'] = 'Înregistrarea a fost ștearsă.';

} catch (Throwable $e) {
    $_SESSION['errorMessage'] = 'Eroare la ștergere.';
}

header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('adminDashboard.php')));
exit;
