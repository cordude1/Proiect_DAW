<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
/* === doar admin === */
if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    header('Location: login.php'); exit;
}

/* === conexiune DB === */
$db  = new DatabaseConnector();
$pdo = $db->connect();

$errors = [];
$user   = [
  'id'      => null,
  'nume'    => '',
  'prenume' => '',
  'email'   => ''
];

/* === Precompletare: din JSON sau din ?id= === */
if (isset($_POST['user'])) {
    $parsed = json_decode($_POST['user'], true);
    if (is_array($parsed)) {
        $user['id']      = (int)($parsed['id'] ?? $parsed['id_utilizator'] ?? 0);
        $user['nume']    = trim($parsed['nume'] ?? $parsed['name'] ?? '');
        $user['prenume'] = trim($parsed['prenume'] ?? '');
        $user['email']   = trim($parsed['email'] ?? '');
    }
} elseif (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT id, nume, prenume, email FROM useri WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user['id']      = (int)$row['id'];
            $user['nume']    = $row['nume'];
            $user['prenume'] = $row['prenume'] ?? '';
            $user['email']   = $row['email'];
        } else {
            $errors[] = "Utilizatorul cu ID {$id} nu a fost găsit.";
        }
    } else {
        $errors[] = 'ID invalid.';
    }
}

/* === UPDATE:doar email+parola === */
if (($_POST['action'] ?? '') === 'update-user') {
    $id       = (int)($_POST['id'] ?? 0);
    $email    = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($id <= 0) {
        $errors[] = 'ID invalid.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalid.';
    }

    if (!$errors) {
        $set    = ['email = :email'];
        $params = [':email'=>$email, ':id'=>$id];
if ($password!=='') { $params[':parola'] = password_hash($password, PASSWORD_BCRYPT); }


        $sql = "UPDATE useri SET email = :email".($password!=='' ? ", parola = :parola" : "")." WHERE id = :id";


        try {
            $upd = $pdo->prepare($sql);
            $upd->execute($params);
            $_SESSION['successMessage'] = 'Datele au fost actualizate.';
            header('Location: adminDashboard.php'); exit;
        } catch (Throwable $e) {
            $_SESSION['errorMessage'] = 'Eroare DB: ' . $e->getMessage();
            header('Location: adminDashboard.php'); exit;
        }
    }

    // re-populăm formularul în caz de erori
    $user['id']    = $id;
    $user['email'] = $email;
}

?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Actualizează utilizator</title>

  <!-- stiluri -->
  <link rel="stylesheet" href="admin-ui.css?v=13">
  <style>
    .form-card{max-width:820px;margin:24px auto;background:#fff;padding:24px;border-radius:16px;box-shadow:0 12px 24px rgba(0,0,0,.08)}
    .header{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:12px;margin-bottom:12px}
    .header h1{margin:0;text-align:center;font-size:28px;text-transform:uppercase}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .form-group{margin-bottom:12px}
    label{display:block;font-weight:700;margin-bottom:6px}
    input{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;font-size:16px}
    input[readonly]{background:#f7f7f7;color:#666}
    .form-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:12px}
    .btn{display:inline-block;padding:10px 14px;border-radius:10px;text-decoration:none}
    .btn-back{background:#f1f3f5;color:#111;border:1px solid #ddd}
    .btn-add{background:#2ecc71;color:#fff;border:none}
    .btn:hover{opacity:.9}
    .arrow{margin-right:6px}
    .alert{padding:12px 14px;border-radius:10px;margin:12px auto;max-width:820px}
    .alert-error{background:#ffe8e8;color:#a40000;border:1px solid #ffb3b3}
    @media (max-width:640px){.form-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>

<div class="container">
  <div class="header">
    <a href="adminDashboard.php" class="btn btn-back"><span class="arrow">←</span> Dashboard</a>
    <h1>Actualizează utilizator</h1>
    <span></span>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="form-card">
    <form method="POST" action="updateUser.php" autocomplete="off">
      <div class="form-grid">
        <div class="form-group">
          <label for="nume">Nume (unic)</label>
          <input type="text" id="nume" value="<?= htmlspecialchars($user['nume'] ?? '') ?>" readonly>
        </div>
        <div class="form-group">
          <label for="prenume">Prenume (unic)</label>
          <input type="text" id="prenume" value="<?= htmlspecialchars($user['prenume'] ?? '') ?>" readonly>
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="email">Email *</label>
          <input type="email" id="email" name="email" required
                 value="<?= htmlspecialchars($user['email'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="password">Parolă nouă (opțional)</label>
          <input type="password" id="password" name="password" placeholder="Lasă gol dacă nu o schimbi">
        </div>
      </div>

      <input type="hidden" name="id" value="<?= (int)($user['id'] ?? 0) ?>">
      <input type="hidden" name="action" value="update-user">

      <div class="form-actions">
        <a href="adminDashboard.php" class="btn btn-back"><span class="arrow">←</span> Anulează</a>
        <button type="submit" class="btn btn-add">Salvează</button>
      </div>
    </form>
  </div>
</div>

<div class="back-button">
  <a href="javascript:history.back()" class="btn-back">Înapoi</a>
</div>

</body>
</html>
