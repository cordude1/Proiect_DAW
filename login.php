<?php
declare(strict_types=1);

require_once __DIR__.'/bootstrap.php';

$error = null;

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'login' &&
    !empty($_POST['email']) &&
    !empty($_POST['password'])
) {

    // VALIDARE CSRF
    validateCSRF();

    $email = trim($_POST['email']);
    $pass  = (string)$_POST['password'];

    $db  = new DatabaseConnector();
    $pdo = $db->connect();

    $stmt = $pdo->prepare("
        SELECT id, nume, prenume, email, parola, rol
        FROM useri
        WHERE email = :email
        LIMIT 1
    ");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "User not found!";
    } else {
        $dbHash   = (string)$user['parola'];
        $passOkay = password_verify($pass, $dbHash);

        // MIGRARE parole vechi
        if (!$passOkay && hash_equals($dbHash, $pass)) {
            $newHash = password_hash($pass, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE useri SET parola = :h WHERE id = :id");
            $upd->execute([':h' => $newHash, ':id' => (int)$user['id']]);
            $passOkay = true;
        }

        if ($passOkay) {

            $role = strtolower(trim((string)($user['rol'] ?? 'user')));
            if ($role !== 'admin' && $role !== 'user') {
                $role = 'user';
            }

            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['email']   = (string)$user['email'];
            $_SESSION['role']    = $role;
            $_SESSION['nume']    = (string)$user['nume'];
            $_SESSION['prenume'] = (string)($user['prenume'] ?? '');

            $_SESSION['rol']  = $role;
            $_SESSION['user'] = [
                'id'    => (int)$user['id'],
                'email' => (string)$user['email']
            ];

            header('Location: ' . ($role === 'admin'
                ? 'adminDashboard.php'
                : 'userDashboard.php'
            ));
            exit;
        } else {
            $error = "Your credentials do not match any record from our database.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Login - Agenție de Turism</title>

    <!-- Stilurile generale + admin-ui (pentru .btn.btn-back) + login specific -->
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="admin-ui.css" />
    <link rel="stylesheet" href="login.css" />

</head>
<body class="login-page">

    <div class="login-container" id="login-container">
        <h2>Login</h2>

        <?php if ($error): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="login.php" id="login-form" novalidate>

            <!-- CSRF TOKEN -->
            <input type="hidden" name="csrf_token" value="<?= generateCSRF() ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required />

            <label for="password">Parolă</label>
            <input type="password" id="password" name="password" required />

            <input type="hidden" name="action" value="login" />

            <button type="submit" name="login">Login</button>
        </form>

        <p>Nu ai un cont? <a href="register.php">Înregistrează-te aici!</a></p>
    </div>

    <!-- Buton Înapoi-->
    <div class="page-header" style="position: fixed; bottom: 20px; left: 20px; z-index: 1000; margin: 0;">
        <a href="javascript:history.back()" class="btn btn-back">
            ← Înapoi
        </a>
    </div>

</body>
</html>