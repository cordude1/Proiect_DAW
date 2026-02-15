<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/config/env.php';
require_once __DIR__.'/path.php';

if (!isset($_SESSION['user_id']) || (($_SESSION['role'] ?? '') !== 'admin')) {
    header('Location: '.url('login.php'));
    exit;
}

$userName = htmlspecialchars((string)($_SESSION['nume'] ?? 'admin'));
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <?php $pageTitle='Dashboard Administrator'; include __DIR__.'/partials/head.php'; ?>
  <link rel="stylesheet" href="<?= asset('admin-ui.css') ?>">
</head>
<body>

<?php include __DIR__.'/partials/header.php'; ?>

<div class="container">

  <h1>Dashboard Administrator</h1>

  <p><strong>Bine ai venit, <?= $userName ?></strong></p>
  <p>Rol: admin</p>

  <hr>

  <h2>Utilizatori</h2>
  <a class="btn btn-add" href="<?= url('administrareUser.php') ?>">Administrare Utilizatori</a>
  <a class="btn btn-edit" href="<?= url('createUser.php') ?>">Adaugă Utilizator</a>
  <a class="btn btn-edit" href="<?= url('administrareUser.php') ?>#import">Importă Utilizatori (CSV)</a>

  <hr>

  <h2>Pachete Turistice</h2>
  <a class="btn btn-add" href="<?= url('administrarePachete.php') ?>">Administrare Pachete</a>
  <a class="btn btn-edit" href="<?= url('creazaPacheteTuristice.php') ?>">Adaugă Pachet</a>
  <a class="btn btn-edit" href="<?= url('administrarePachete.php') ?>#import">Importă Pachete (CSV)</a>

  <hr>

  <h2>Rezervări</h2>
  <a class="btn btn-add" href="<?= url('manageReservations.php') ?>">Administrare Rezervări</a>
  <a class="btn btn-edit" href="<?= url('manageReservations.php') ?>#import">Importă Rezervări (CSV)</a>

  <hr>

  <h2>Exporturi</h2>
  <a class="btn btn-add" href="<?= url('export.php') ?>?table=rezervari&format=excel">Export Rezervări Excel</a>
  <a class="btn btn-add" href="<?= url('export.php') ?>?table=useri&format=excel">Export Utilizatori Excel</a>
  <a class="btn btn-add" href="<?= url('export.php') ?>?table=pacheteturistice&format=excel">Export Pachete Excel</a>

</div>

<?php include __DIR__.'/partials/footer.php'; ?>
</body>
</html>
