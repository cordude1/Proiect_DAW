<?php 
declare(strict_types=1);

require_once __DIR__.'/bootstrap.php';
require_once __DIR__.'/config/env.php';
require_once __DIR__.'/path.php';

/* ================= ADMIN CHECK ================= */
$isAdmin = (($_SESSION['role'] ?? '') === 'admin') || (($_SESSION['rol'] ?? '') === 'admin');
if (!$isAdmin) {
    header('Location: '.url('login.php'));
    exit;
}

/* ================= DB ================= */
try {
    $db  = new DatabaseConnector();
    $pdo = $db->connect();
} catch (Throwable $e) {
    die('Eroare conexiune DB: '.$e->getMessage());
}

/* ================= FLASH ================= */
$success = $_SESSION['successMessage'] ?? null;
$error   = $_SESSION['errorMessage']  ?? null;
unset($_SESSION['successMessage'], $_SESSION['errorMessage']);

/* ================= LOAD DATA ================= */
$sql = "
    SELECT p.id_pachet, p.nume, p.pret, p.durata,
           d.nume AS destinatie, d.tara
    FROM pacheteturistice p
    LEFT JOIN destinatii d ON d.id_destinatie = p.id_destinatie
    ORDER BY p.id_pachet DESC
";
$pachete = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <?php $pageTitle='Administrare Pachete Turistice'; include __DIR__.'/partials/head.php'; ?>
  <link rel="stylesheet" href="<?= asset('admin-ui.css') ?>">

  <style>
    .toolbar{
        display:flex;
        gap:10px;
        align-items:center;
        margin:15px 0;
        flex-wrap:wrap;
    }
    .dropdown-export{position:relative;display:inline-block;}
    .dropdown-export .dropdown-menu{
        display:none;
        position:absolute;
        background:#fff;
        min-width:200px;
        box-shadow:0 6px 16px rgba(0,0,0,.15);
        border-radius:8px;
        overflow:hidden;
        z-index:1000;
    }
    .dropdown-export .dropdown-menu a{
        display:block;
        padding:10px 14px;
        text-decoration:none;
        color:#333;
    }
    .dropdown-export .dropdown-menu a:hover{
        background:#f2f2f2;
    }
    .dropdown-export:hover .dropdown-menu{
        display:block;
    }
  </style>
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

  <!-- HEADER -->
  <div class="header">
      <a href="<?= url('adminDashboard.php') ?>" class="btn btn-back">
          <span class="arrow">←</span> Dashboard
      </a>

      <h1>Administrare Pachete Turistice</h1>

      <a href="<?= url('creazaPacheteTuristice.php') ?>" class="btn btn-add">
          Add
      </a>
  </div>

  <!-- TOOLBAR IMPORT + EXPORT -->
  <div class="toolbar">

    <!-- IMPORT CSV -->
    <form action="<?= url('importPachete.php') ?>" 
          method="POST" 
          enctype="multipart/form-data"
          style="display:flex; gap:10px; align-items:center;">

        <input type="file" id="csv_pachete" name="file" accept=".csv" required>
        <button type="submit" class="btn btn-add" id="btn-import" disabled>
            Importă Pachete (CSV)
        </button>
    </form>

    <!-- EXPORT DROPDOWN -->
    <div class="dropdown-export">
        <button type="button" class="btn btn-add">
            Exportă ▼
        </button>

        <div class="dropdown-menu">
            <a href="<?= url('export.php') ?>?table=pacheteturistice&format=csv">Export CSV</a>
            <a href="<?= url('export.php') ?>?table=pacheteturistice&format=excel">Export Excel</a>
            <a href="<?= url('export.php') ?>?table=pacheteturistice&format=pdf">Export PDF</a>
        </div>
    </div>

  </div>

  <script>
    (function(){
      const file = document.getElementById('csv_pachete');
      const btn  = document.getElementById('btn-import');
      if (file && btn) {
        file.addEventListener('change', () => {
          btn.disabled = !file.files.length;
        });
      }
    })();
  </script>

  <!-- TABEL -->
  <div class="user-list-section">
      <table class="user-table">
          <thead>
              <tr>
                  <th>ID</th>
                  <th>Nume</th>
                  <th>Destinație</th>
                  <th>Preț (RON)</th>
                  <th>Durată (zile)</th>
                  <th class="center">Acțiuni</th>
              </tr>
          </thead>
          <tbody>

          <?php if (!empty($pachete)): ?>
              <?php foreach ($pachete as $p): ?>
              <tr>

                  <td class="mono"><?= (int)$p['id_pachet'] ?></td>

                  <td><?= htmlspecialchars((string)$p['nume']) ?></td>

                  <td><?= htmlspecialchars((string)(($p['destinatie'] ?? '').' - '.($p['tara'] ?? ''))) ?></td>

                  <td><?= number_format((float)$p['pret'], 2, '.', '') ?></td>

                  <td><?= (int)$p['durata'] ?></td>

                  <td class="actions">

                      <a class="btn btn-edit"
                         href="<?= url('updatePachet.php') ?>?id_pachet=<?= (int)$p['id_pachet'] ?>">
                         Editează
                      </a>

                      <form class="inline"
                            method="post"
                            action="<?= url('delete.php') ?>?table=pacheteturistice"
                            onsubmit="return confirm('Sigur vrei să ștergi pachetul #<?= (int)$p['id_pachet'] ?>?');">

                          <input type="hidden" name="id" value="<?= (int)$p['id_pachet'] ?>">

                          <button type="submit" class="btn btn-delete">
                              Șterge
                          </button>
                      </form>

                  </td>

              </tr>
              <?php endforeach; ?>

          <?php else: ?>
              <tr>
                  <td colspan="6" class="empty">Nu există pachete turistice.</td>
              </tr>
          <?php endif; ?>

          </tbody>
      </table>
  </div>

</div>

<?php include __DIR__.'/partials/footer.php'; ?>
</body>
</html>
