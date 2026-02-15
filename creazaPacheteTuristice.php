<?php
require_once __DIR__.'/bootstrap.php';

// acces doar pentru admin (compat role/rol)
$isAdmin = (($_SESSION['role'] ?? '') === 'admin') || (($_SESSION['rol'] ?? '') === 'admin');
if (!isset($_SESSION['user_id']) || !$isAdmin) {
    header('Location: login.php'); exit;
}

// Conexiune DB (ajustează parola/portul dacă e cazul)
$db  = new DatabaseConnector();
$pdo = $db->connect();

$errors = [];

// încărcăm destinațiile pentru select
$destinatii = [];
try {
    $q = $pdo->query("SELECT id_destinatie, nume, tara FROM destinatii ORDER BY tara, nume");
    $destinatii = $q->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $errors[] = 'Nu pot încărca destinațiile: '.$e->getMessage();
}

// procesare formular
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nume          = trim($_POST['nume'] ?? '');
    $pret          = filter_input(INPUT_POST, 'pret', FILTER_VALIDATE_FLOAT);
    $durata        = filter_input(INPUT_POST, 'durata', FILTER_VALIDATE_INT);
    $id_destinatie = filter_input(INPUT_POST, 'id_destinatie', FILTER_VALIDATE_INT);

    if ($nume === '')                           $errors[] = 'Numele pachetului este obligatoriu.';
    if ($pret === false || $pret <= 0)          $errors[] = 'Preț invalid.';
    if ($durata === false || $durata <= 0)      $errors[] = 'Durata trebuie să fie > 0 zile.';
    if ($id_destinatie === false || $id_destinatie <= 0) $errors[] = 'Selectează o destinație.';

    if (!$errors) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO pacheteturistice (nume, pret, durata, id_destinatie)
                VALUES (:nume, :pret, :durata, :id_destinatie)
            ");
            $ok = $stmt->execute([
                ':nume'          => $nume,
                ':pret'          => $pret,
                ':durata'        => $durata,
                ':id_destinatie' => $id_destinatie,
            ]);

            if ($ok) {
                $_SESSION['successMessage'] = "Pachetul „{$nume}” a fost adăugat.";
                header('Location: adminDashboard.php'); // toast 3s apare acolo
                exit;
            } else {
                $_SESSION['errorMessage'] = 'Eroare la adăugare.';
                header('Location: adminDashboard.php');
                exit;
            }
        } catch (Throwable $e) {
            $_SESSION['errorMessage'] = 'Eroare DB: ' . $e->getMessage();
            header('Location: adminDashboard.php');
            exit;
        }
    }
}

// flash local (dacă vii cu mesaje din altă acțiune)
$success = $_SESSION['successMessage'] ?? null;
$error   = $_SESSION['errorMessage']  ?? null;
unset($_SESSION['successMessage'], $_SESSION['errorMessage']);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Adaugă / Modifică Pachete Turistice</title>

  <!-- aceleași CSS-uri pentru look unitar -->
  <link rel="stylesheet" href="style.css?v=1">
  <link rel="stylesheet" href="login.css?v=1">
  <link rel="stylesheet" href="admin-ui.css?v=1">

  <style>
    .form-card{background:#fff;padding:24px;border-radius:16px;box-shadow:0 12px 24px rgba(0,0,0,.08)}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .form-group{margin-bottom:12px}
    label{display:block;font-weight:700;margin-bottom:6px}
    input,select{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;font-size:16px}
    .form-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:12px}
    .login-success-message{display:block}
    #flash-ok,#flash-err{position:fixed;top:20px;right:20px;z-index:9999;box-shadow:0 4px 6px rgba(0,0,0,.1)}
    #flash-err{background:#dc3545}
    @media (max-width:640px){.form-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>

<?php if ($success): ?>
  <div id="flash-ok" class="login-success-message"><p><?= htmlspecialchars($success) ?></p></div>
<?php endif; ?>
<?php if ($error): ?>
  <div id="flash-err" class="login-success-message"><p><?= htmlspecialchars($error) ?></p></div>
<?php endif; ?>

<div class="container">
  <div class="header">
    <a href="adminDashboard.php" class="btn btn-back"><span class="arrow">←</span> Dashboard</a>
    <h1>Adaugă pachet turistic</h1>
    <span></span>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="form-card">
    <form method="POST" action="creazaPacheteTuristice.php" autocomplete="off">
      <div class="form-grid">
        <div class="form-group">
          <label for="nume">Nume pachet *</label>
          <input type="text" id="nume" name="nume" required value="<?= htmlspecialchars($_POST['nume'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="pret">Preț (RON) *</label>
          <input type="number" step="0.01" min="0.01" id="pret" name="pret" required value="<?= htmlspecialchars($_POST['pret'] ?? '') ?>">
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="durata">Durată (zile) *</label>
          <input type="number" min="1" id="durata" name="durata" required value="<?= htmlspecialchars($_POST['durata'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label for="id_destinatie">Destinație *</label>
          <select id="id_destinatie" name="id_destinatie" required>
            <option value="">— alege —</option>
            <?php foreach ($destinatii as $d): ?>
              <option value="<?= (int)$d['id_destinatie'] ?>" <?= (isset($_POST['id_destinatie']) && (int)$_POST['id_destinatie']===(int)$d['id_destinatie'] ? 'selected' : '') ?>>
                <?= htmlspecialchars($d['tara'].' — '.$d['nume']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-actions">
        <a href="adminDashboard.php" class="btn btn-back"><span class="arrow">←</span> Anulează</a>
        <button type="submit" class="btn btn-add">Salvează</button>
      </div>
    </form>
  </div>
</div>

<script>
  // toast-urile (dacă există) se închid după 3s
  setTimeout(function(){
    var ok=document.getElementById('flash-ok'), er=document.getElementById('flash-err');
    if(ok) ok.style.display='none';
    if(er) er.style.display='none';
  },3000);
</script>

</body>
</html>
