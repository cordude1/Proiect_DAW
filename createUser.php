<?php
require_once __DIR__.'/bootstrap.php';

$connector = new DatabaseConnector();
$pdo = $connector->connect();

$errors = [];
$old = [
    'nume'    => '',
    'prenume' => '',
    'email'   => '',
    'rol'     => 'user',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Colectare + validare
    $old['nume']    = trim($_POST['nume'] ?? '');
    $old['prenume'] = trim($_POST['prenume'] ?? '');
    $old['email']   = trim($_POST['email'] ?? '');
    $old['rol']     = ($_POST['rol'] ?? 'user') === 'admin' ? 'admin' : 'user';
    $parola         = $_POST['parola'] ?? '';

    if ($old['nume'] === '') { $errors[] = 'Numele este obligatoriu.'; }
    if ($old['email'] === '' || !filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalid.';
    }
    if (strlen($parola) < 6) { $errors[] = 'Parola trebuie să aibă minim 6 caractere.'; }

    // 2) Unicitate nume & email (tabela useri are UNIQUE pe ambele)
    if (!$errors) {
        $q = $pdo->prepare("SELECT nume, email FROM useri WHERE nume = :nume OR email = :email LIMIT 1");
        $q->execute([':nume' => $old['nume'], ':email' => $old['email']]);
        if ($row = $q->fetch(PDO::FETCH_ASSOC)) {
            if (strcasecmp($row['nume'], $old['nume']) === 0)  { $errors[] = 'Numele este deja folosit.'; }
            if (strcasecmp($row['email'], $old['email']) === 0) { $errors[] = 'Emailul este deja folosit.'; }
        }
    }

    // 3) Inserare
    if (!$errors) {
        $stmt = $pdo->prepare("
            INSERT INTO useri (nume, prenume, parola, email, rol)
            VALUES (:nume, :prenume, :parola, :email, :rol)
        ");
        $ok = $stmt->execute([
            ':nume'    => $old['nume'],
            ':prenume' => $old['prenume'] !== '' ? $old['prenume'] : null,
            ':parola'  => password_hash($parola, PASSWORD_DEFAULT),
            ':email'   => $old['email'],
            ':rol'     => $old['rol'],
        ]);

        if ($ok) {
            $_SESSION['successMessage'] = 'Utilizatorul a fost adăugat cu succes.';
            header('Location: administrareUser.php');
            exit;
        } else {
            $errors[] = 'A apărut o eroare la salvare.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Creare utilizator</title>
  <link rel="stylesheet" href="admin-ui.css?v=6">
  <style>
    .form-card{
      max-width: 720px; margin: 24px auto; background:#fff; padding:24px;
      border-radius:16px; box-shadow:0 12px 24px rgba(0,0,0,.08);
    }
    .page-header{
      display:grid; grid-template-columns:auto 1fr auto; align-items:center;
      max-width:1200px; margin:0 auto 12px; padding:0 24px;
    }
    .page-header h1{ text-align:center; margin:0; font-size:28px; text-transform:uppercase; }
    .form-row{ display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .form-group{ margin-bottom:12px; }
    label{ display:block; font-weight:700; margin-bottom:6px; }
    input, select{
      width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:10px; font-size:16px;
    }
    .form-actions{ display:flex; gap:10px; justify-content:flex-end; margin-top:12px; }
  </style>
</head>
<body>

<div class="page-header">
  <a href="javascript:history.back()" class="btn btn-back"><span class="arrow">←</span> Înapoi</a>
  <h1>Creare utilizator</h1>
  <span></span>
</div>

<div class="form-card">
  <?php if ($errors): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" action="createUser.php" autocomplete="off">
    <div class="form-row">
      <div class="form-group">
        <label for="nume">Nume *</label>
        <input type="text" id="nume" name="nume" required value="<?= htmlspecialchars($old['nume']) ?>">
      </div>
      <div class="form-group">
        <label for="prenume">Prenume</label>
        <input type="text" id="prenume" name="prenume" value="<?= htmlspecialchars($old['prenume']) ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="email">Email *</label>
        <input type="email" id="email" name="email" required value="<?= htmlspecialchars($old['email']) ?>">
      </div>
      <div class="form-group">
        <label for="rol">Rol *</label>
        <select id="rol" name="rol" required>
          <option value="user"  <?= $old['rol']==='user' ? 'selected' : '' ?>>user</option>
          <option value="admin" <?= $old['rol']==='admin' ? 'selected' : '' ?>>admin</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label for="parola">Parolă *</label>
      <input type="password" id="parola" name="parola" minlength="6" required>
    </div>

    <div class="form-actions">
      <a href="administrareUser.php" class="btn btn-back"><span class="arrow">←</span> Anulează</a>
      <button type="submit" class="btn btn-add">Salvează</button>
    </div>
  </form>
</div>

</body>
</html>
