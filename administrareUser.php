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

/* ================= PAGINARE ================= */
$perPage = 30;
$page    = max(1, (int)($_GET['page'] ?? 1));
$limit   = max(1, min(100000, $perPage * $page));

$totalUsers = (int)$pdo->query("SELECT COUNT(*) FROM useri")->fetchColumn();

$sql = "
    SELECT id, nume, prenume, email, rol
    FROM useri
    ORDER BY id ASC
    LIMIT {$limit}
";
$users = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* ================= FLASH ================= */
$success = $_SESSION['successMessage'] ?? null;
$error   = $_SESSION['errorMessage']  ?? null;
unset($_SESSION['successMessage'], $_SESSION['errorMessage']);
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <?php $pageTitle='Administrare Utilizatori'; include __DIR__.'/partials/head.php'; ?>
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

    <h1>LISTA UTILIZATORI</h1>

    <a href="<?= url('createUser.php') ?>" class="btn btn-add">
      Add
    </a>
  </div>

  <!-- TOOLBAR -->
  <div class="toolbar">

    <!-- IMPORT CSV -->
    <form action="<?= url('importUsers.php') ?>" 
          method="POST"
          enctype="multipart/form-data"
          style="display:flex; gap:10px; align-items:center;">

      <input type="file" id="csv_users" name="file" accept=".csv" required>
      <button type="submit" class="btn btn-add" id="btn-import" disabled>
        Importă CSV
      </button>
    </form>

    <!-- EXPORT DROPDOWN -->
    <div class="dropdown-export">
      <button type="button" class="btn btn-add">
        Exportă ▼
      </button>

      <div class="dropdown-menu">
        <a href="<?= url('export.php') ?>?table=useri&format=csv">Export CSV</a>
        <a href="<?= url('export.php') ?>?table=useri&format=excel">Export Excel</a>
        <a href="<?= url('export.php') ?>?table=useri&format=pdf">Export PDF</a>
      </div>
    </div>

  </div>

  <script>
    (function(){
      const file = document.getElementById('csv_users');
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
          <th>Prenume</th>
          <th>Email</th>
          <th>Rol</th>
          <th class="center">Acțiuni</th>
        </tr>
      </thead>
      <tbody>

      <?php if (!empty($users)): ?>
        <?php foreach ($users as $u): ?>
          <tr>

            <td class="mono"><?= (int)$u['id'] ?></td>

            <td><?= htmlspecialchars((string)$u['nume']) ?></td>

            <td><?= htmlspecialchars((string)$u['prenume']) ?></td>

            <td><?= htmlspecialchars((string)$u['email']) ?></td>

            <td><?= htmlspecialchars((string)$u['rol']) ?></td>

            <td class="actions">

              <a class="btn btn-edit"
                 href="<?= url('updateUser.php') ?>?id=<?= (int)$u['id'] ?>">
                 Editează
              </a>

              <form class="inline"
                    method="post"
                    action="<?= url('delete.php') ?>?table=useri"
                    onsubmit="return confirm('Sigur vrei să ștergi utilizatorul #<?= (int)$u['id'] ?>?');">

                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                <button type="submit" class="btn btn-delete">
                    Șterge
                </button>

              </form>

            </td>

          </tr>
        <?php endforeach; ?>

      <?php else: ?>
        <tr>
          <td colspan="6" class="empty">
            Nu există utilizatori în baza de date.
          </td>
        </tr>
      <?php endif; ?>

      </tbody>
    </table>

    <!-- PAGINARE -->
    <div class="meta-list">
      <div>
        Afișezi <strong><?= count($users) ?></strong>
        din <strong><?= $totalUsers ?></strong> utilizatori
      </div>

      <?php if ($limit < $totalUsers): ?>
        <a class="btn btn-add"
           href="<?= url('administrareUser.php') ?>?page=<?= $page + 1 ?>">
           Încarcă mai multe
        </a>
      <?php endif; ?>
    </div>

  </div>

</div>

<?php include __DIR__.'/partials/footer.php'; ?>
</body>
</html>
