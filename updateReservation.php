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
$res    = [
  'id_client'        => null,
  'id_pachet'        => null,
  'nume_rezervare'   => '',
  'prenume_rezervare'=> '',
  'email'            => '',
  'telefon'          => '',
  'numar_persoane'   => '',
  'adresa'           => '',
  'judet'            => '',
  'localitate'       => '',
  'metoda_plata'     => '',
  'nume_pachet'      => '',   // din join
  'nume_user'        => '',   // din join (dacă există user)
  'email_user'       => '',   // din join
];

/* === Precompletare: din JSON (POST) sau din GET (id_client, id_pachet) === */
if (isset($_POST['reservation'])) {
    $parsed = json_decode($_POST['reservation'], true);
    if (is_array($parsed)) {
        $res['id_client']         = (int)($parsed['id_client'] ?? 0);
        $res['id_pachet']         = (int)($parsed['id_pachet'] ?? 0);
        $res['nume_rezervare']    = trim((string)($parsed['nume'] ?? $parsed['nume_rezervare'] ?? ''));
        $res['prenume_rezervare'] = trim((string)($parsed['prenume'] ?? $parsed['prenume_rezervare'] ?? ''));
        $res['email']             = trim((string)($parsed['email'] ?? ''));
        $res['telefon']           = trim((string)($parsed['telefon'] ?? ''));
        $res['numar_persoane']    = (string)($parsed['numar_persoane'] ?? '');
        $res['adresa']            = trim((string)($parsed['adresa'] ?? ''));
        $res['judet']             = trim((string)($parsed['judet'] ?? ''));
        $res['localitate']        = trim((string)($parsed['localitate'] ?? ''));
        $res['metoda_plata']      = trim((string)($parsed['metoda_plata'] ?? ''));
    }
} elseif (isset($_GET['id_client'], $_GET['id_pachet'])) {
    $idClient = (int)$_GET['id_client'];
    $idPachet = (int)$_GET['id_pachet'];

    if ($idClient > 0 && $idPachet > 0) {
        $sql = "
            SELECT
                r.id_client, r.id_pachet,
                r.email, r.nume AS nume_rezervare, r.prenume AS prenume_rezervare,
                r.telefon, r.numar_persoane, r.adresa, r.judet, r.localitate, r.metoda_plata,
                u.nume  AS nume_user, u.email AS email_user,
                p.nume  AS nume_pachet
            FROM rezervari r
            LEFT JOIN useri u ON u.id = r.id_client
            LEFT JOIN pacheteturistice p ON p.id_pachet = r.id_pachet
            WHERE r.id_client = :idc AND r.id_pachet = :idp
            LIMIT 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':idc'=>$idClient, ':idp'=>$idPachet]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $res['id_client']          = (int)$row['id_client'];
            $res['id_pachet']          = (int)$row['id_pachet'];
            $res['nume_rezervare']     = (string)($row['nume_rezervare'] ?? '');
            $res['prenume_rezervare']  = (string)($row['prenume_rezervare'] ?? '');
            $res['email']              = (string)($row['email'] ?? '');
            $res['telefon']            = (string)($row['telefon'] ?? '');
            $res['numar_persoane']     = (string)($row['numar_persoane'] ?? '');
            $res['adresa']             = (string)($row['adresa'] ?? '');
            $res['judet']              = (string)($row['judet'] ?? '');
            $res['localitate']         = (string)($row['localitate'] ?? '');
            $res['metoda_plata']       = (string)($row['metoda_plata'] ?? '');
            $res['nume_pachet']        = (string)($row['nume_pachet'] ?? '');
            $res['nume_user']          = (string)($row['nume_user'] ?? '');
            $res['email_user']         = (string)($row['email_user'] ?? '');
        } else {
            $errors[] = "Rezervarea (client #{$idClient}, pachet #{$idPachet}) nu a fost găsită.";
        }
    } else {
        $errors[] = 'Cheie compusă invalidă (id_client / id_pachet).';
    }
}

/* === Câmpuri de contact / detalii rezervare === */
if (($_POST['action'] ?? '') === 'update-reservation') {
    $idClient = (int)($_POST['id_client'] ?? 0);
    $idPachet = (int)($_POST['id_pachet'] ?? 0);

    $email    = trim((string)($_POST['email'] ?? ''));
    $telefon  = trim((string)($_POST['telefon'] ?? ''));
    $nrPers   = trim((string)($_POST['numar_persoane'] ?? ''));
    $adresa   = trim((string)($_POST['adresa'] ?? ''));
    $judet    = trim((string)($_POST['judet'] ?? ''));
    $local    = trim((string)($_POST['localitate'] ?? ''));
    $metoda   = trim((string)($_POST['metoda_plata'] ?? ''));

    if ($idClient <= 0 || $idPachet <= 0) {
        $errors[] = 'Cheie compusă invalidă.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalid.';
    }
    if ($nrPers !== '' && (!ctype_digit($nrPers) || (int)$nrPers <= 0)) {
        $errors[] = 'Număr persoane invalid.';
    }

    if (!$errors) {
        $fields = [];
        $params = [':idc'=>$idClient, ':idp'=>$idPachet];

        $fields[] = 'email = :email';      $params[':email'] = $email;
        $fields[] = 'telefon = :telefon';  $params[':telefon'] = $telefon;
        $fields[] = 'numar_persoane = :npers'; $params[':npers'] = ($nrPers === '' ? null : (int)$nrPers);
        $fields[] = 'adresa = :adresa';    $params[':adresa'] = $adresa;
        $fields[] = 'judet = :judet';      $params[':judet'] = $judet;
        $fields[] = 'localitate = :local'; $params[':local'] = $local;
        $fields[] = 'metoda_plata = :metoda'; $params[':metoda'] = $metoda;

        $sql = "UPDATE rezervari
                SET ".implode(', ', $fields)."
                WHERE id_client = :idc AND id_pachet = :idp";
        try {
            $upd = $pdo->prepare($sql);
            $upd->execute($params);
            $_SESSION['successMessage'] = 'Rezervarea a fost actualizată.';
            header('Location: manageReservations.php'); exit;
        } catch (Throwable $e) {
            $_SESSION['errorMessage'] = 'Eroare DB: ' . $e->getMessage();
            header('Location: manageReservations.php'); exit;
        }
    }

    // re-populăm formularul în caz de erori
    $res['id_client']       = $idClient;
    $res['id_pachet']       = $idPachet;
    $res['email']           = $email;
    $res['telefon']         = $telefon;
    $res['numar_persoane']  = $nrPers;
    $res['adresa']          = $adresa;
    $res['judet']           = $judet;
    $res['localitate']      = $local;
    $res['metoda_plata']    = $metoda;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Actualizează rezervare</title>

  <!-- stilurile comune -->
  <link rel="stylesheet" href="admin-ui.css?v=13">
  <style>
    .form-card{max-width:920px;margin:24px auto;background:#fff;padding:24px;border-radius:16px;box-shadow:0 12px 24px rgba(0,0,0,.08)}
    .header{display:grid;grid-template-columns:auto 1fr auto;align-items:center;gap:12px;margin-bottom:12px}
    .header h1{margin:0;text-align:center;font-size:28px;text-transform:uppercase}
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .form-group{margin-bottom:12px}
    label{display:block;font-weight:700;margin-bottom:6px}
    input, select{width:100%;padding:10px 12px;border:1px solid #ddd;border-radius:10px;font-size:16px}
    input[readonly]{background:#f7f7f7;color:#666}
    .form-actions{display:flex;gap:10px;justify-content:flex-end;margin-top:12px}
    .btn{display:inline-block;padding:10px 14px;border-radius:10px;text-decoration:none}
    .btn-back{background:#6c757d;color:#fff;border:none}
    .btn-add{background:#28a745;color:#fff;border:none}
    .btn:hover{opacity:.95}
    .arrow{margin-right:6px}
    .alert{padding:12px 14px;border-radius:10px;margin:12px auto;max-width:920px}
    .alert-error{background:#ffe8e8;color:#a40000;border:1px solid #ffb3b3}
    @media (max-width:640px){.form-grid{grid-template-columns:1fr}}
    .mono{font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;}
  </style>
</head>
<body>

<div class="container">
  <div class="header">
    <a href="manageReservations.php" class="btn btn-back"><span class="arrow">←</span> Rezervări</a>
    <h1>Actualizează rezervare</h1>
    <span></span>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error">
      <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="form-card">
    <form method="POST" action="updateReservation.php" autocomplete="off">
      <div class="form-grid">
        <div class="form-group">
          <label>ID client</label>
          <input type="text" value="<?= htmlspecialchars((string)$res['id_client']) ?>" readonly class="mono">
        </div>
        <div class="form-group">
          <label>ID pachet</label>
          <input type="text" value="<?= htmlspecialchars((string)$res['id_pachet']) ?>" readonly class="mono">
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label>Nume (rezervare)</label>
          <input type="text" value="<?= htmlspecialchars($res['nume_rezervare']) ?>" readonly>
        </div>
        <div class="form-group">
          <label>Prenume (rezervare)</label>
          <input type="text" value="<?= htmlspecialchars($res['prenume_rezervare']) ?>" readonly>
        </div>
      </div>

      <div class="form-group">
        <label>Pachet</label>
        <input type="text" value="<?= htmlspecialchars($res['nume_pachet']) ?>" readonly>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="email">Email (rezervare)</label>
          <input type="email" id="email" name="email" value="<?= htmlspecialchars($res['email']) ?>">
        </div>
        <div class="form-group">
          <label for="telefon">Telefon</label>
          <input type="text" id="telefon" name="telefon" value="<?= htmlspecialchars($res['telefon']) ?>">
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="numar_persoane">Număr persoane</label>
          <input type="number" id="numar_persoane" name="numar_persoane" min="1"
                 value="<?= htmlspecialchars((string)$res['numar_persoane']) ?>">
        </div>
        <div class="form-group">
          <label for="metoda_plata">Metoda de plată</label>
          <input type="text" id="metoda_plata" name="metoda_plata" value="<?= htmlspecialchars($res['metoda_plata']) ?>">
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group">
          <label for="adresa">Adresă</label>
          <input type="text" id="adresa" name="adresa" value="<?= htmlspecialchars($res['adresa']) ?>">
        </div>
        <div class="form-group">
          <label for="localitate">Localitate</label>
          <input type="text" id="localitate" name="localitate" value="<?= htmlspecialchars($res['localitate']) ?>">
        </div>
      </div>

      <div class="form-group">
        <label for="judet">Județ</label>
        <input type="text" id="judet" name="judet" value="<?= htmlspecialchars($res['judet']) ?>">
      </div>

      <input type="hidden" name="id_client" value="<?= (int)($res['id_client'] ?? 0) ?>">
      <input type="hidden" name="id_pachet" value="<?= (int)($res['id_pachet'] ?? 0) ?>">
      <input type="hidden" name="action" value="update-reservation">

      <div class="form-actions">
        <a href="manageReservations.php" class="btn btn-back"><span class="arrow">←</span> Anulează</a>
        <button type="submit" class="btn btn-add">Salvează</button>
      </div>
    </form>

    <?php if ($res['nume_user'] || $res['email_user']): ?>
      <div style="margin-top:16px; color:#555;">
        <div><strong>Utilizator asociat:</strong> <?= htmlspecialchars($res['nume_user']) ?></div>
        <div class="mono"><?= htmlspecialchars($res['email_user']) ?></div>
      </div>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
