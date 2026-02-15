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

/* FLASH */
$success = $_SESSION['successMessage'] ?? null;
$error   = $_SESSION['errorMessage']  ?? null;
unset($_SESSION['successMessage'], $_SESSION['errorMessage']);

try {

    $db  = new DatabaseConnector();
    $pdo = $db->connect();

    $sql = "
        SELECT
            r.id_client,
            r.id_pachet,
            r.email       AS email_rezervare,
            r.nume        AS nume_rezervare,
            r.prenume     AS prenume_rezervare,
            r.telefon,
            r.numar_persoane,
            r.adresa,
            r.judet,
            r.localitate,
            r.metoda_plata,
            u.nume  AS nume_user,
            u.email AS email_user,
            p.nume  AS nume_pachet
        FROM rezervari r
        LEFT JOIN useri u ON u.id = r.id_client
        LEFT JOIN pacheteturistice p ON p.id_pachet = r.id_pachet
        ORDER BY r.id_pachet DESC, r.id_client DESC
    ";

    $rezervari = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
    die('Eroare: '.$e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
  <?php $pageTitle='Administrare Rezervări'; include __DIR__.'/partials/head.php'; ?>
  <link rel="stylesheet" href="<?= asset('admin-ui.css') ?>">
</head>
<body>

<?php include __DIR__.'/partials/header.php'; ?>

<?php if ($success): ?>
  <div class="alert alert-success"><?= htmlspecialchars((string)$success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars((string)$error) ?></div>
<?php endif; ?>

<div class="container">

  <div class="header">
      <a href="<?= url('adminDashboard.php') ?>" class="btn btn-back">
          <span class="arrow">←</span> Dashboard
      </a>
      <h1>Administrare Rezervări</h1>
      <a href="<?= url('creazaRezervare.php') ?>" class="btn btn-add">Add</a>
  </div>

  <div class="user-list-section">
      <table class="user-table">
          <thead>
              <tr>
                  <th>#</th>
                  <th>Pachet</th>
                  <th>Client</th>
                  <th>Persoane</th>
                  <th>Contact</th>
                  <th>Adresă</th>
                  <th>Plată</th>
                  <th class="center">Acțiuni</th>
              </tr>
          </thead>
          <tbody>

          <?php if (!empty($rezervari)): ?>
              <?php $i = 1; foreach ($rezervari as $r): ?>
              <tr>

                  <td class="mono"><?= $i++; ?></td>

                  <td>
                      <?= htmlspecialchars((string)($r['nume_pachet'] ?? '')) ?>
                      <div class="mono">ID: <?= (int)$r['id_pachet'] ?></div>
                  </td>

                  <td>
                      <?php if (!empty($r['nume_user'])): ?>
                          <div><strong><?= htmlspecialchars((string)$r['nume_user']) ?></strong></div>
                          <div><?= htmlspecialchars((string)$r['email_user']) ?></div>
                      <?php else: ?>
                          <div><strong><?= htmlspecialchars(trim((string)($r['nume_rezervare'].' '.$r['prenume_rezervare']))) ?></strong></div>
                          <div><?= htmlspecialchars((string)$r['email_rezervare']) ?></div>
                      <?php endif; ?>
                      <div class="mono">ID client: <?= (int)$r['id_client'] ?></div>
                  </td>

                  <td><?= (int)$r['numar_persoane'] ?></td>

                  <td>
                      <div><?= htmlspecialchars((string)($r['telefon'] ?? '')) ?></div>
                      <div>
                        <?= htmlspecialchars((string)($r['localitate'] ?? '')) ?>
                        <?= isset($r['judet']) ? ', '.htmlspecialchars((string)$r['judet']) : '' ?>
                      </div>
                  </td>

                  <td><?= htmlspecialchars((string)($r['adresa'] ?? '')) ?></td>

                  <td><?= htmlspecialchars((string)($r['metoda_plata'] ?? '')) ?></td>

                  <td class="actions">

                      <a class="btn btn-edit"
                         href="<?= url('updateReservation.php') ?>?id_client=<?= (int)$r['id_client'] ?>&id_pachet=<?= (int)$r['id_pachet'] ?>">
                         Editează
                      </a>

                      <form class="inline"
                            method="post"
                            action="<?= url('deleteReservation.php') ?>"
                            onsubmit="return confirm('Sigur vrei să ștergi această rezervare?');">

                          <input type="hidden" name="id_client" value="<?= (int)$r['id_client'] ?>">
                          <input type="hidden" name="id_pachet" value="<?= (int)$r['id_pachet'] ?>">

                          <button type="submit" class="btn btn-delete">
                              Șterge
                          </button>
                      </form>

                  </td>

              </tr>
              <?php endforeach; ?>
          <?php else: ?>
              <tr>
                  <td colspan="8" class="empty">Nu există rezervări.</td>
              </tr>
          <?php endif; ?>

          </tbody>
      </table>
  </div>

</div>

<?php include __DIR__.'/partials/footer.php'; ?>
</body>
</html>
