<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';

/* --- Autentificare --- */
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = (int)$_SESSION['user_id'];

/* --- DB --- */
$db  = new DatabaseConnector();
$pdo = $db->connect();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* --- Flash --- */
$success = $_SESSION['successMessage'] ?? null;
$error   = $_SESSION['errorMessage']  ?? null;
unset($_SESSION['successMessage'], $_SESSION['errorMessage']);

/* --- Load user --- */
$st = $pdo->prepare("SELECT id, nume, prenume, email, rol FROM useri WHERE id = :id LIMIT 1");
$st->execute([':id'=>$userId]);
$user = $st->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    header('Location: index.php');
    exit;
}

/* --- Rezervări --- */
$rows = $pdo->prepare("
    SELECT r.*, p.nume AS pachet_nume
    FROM rezervari r
    JOIN pacheteturistice p ON p.id_pachet = r.id_pachet
    WHERE r.id_client = :uid
    ORDER BY r.id_rezervare DESC
");
$rows->execute([':uid'=>$userId]);
$rows = $rows->fetchAll(PDO::FETCH_ASSOC);

$activeTab = $_GET['tab'] ?? 'rezervari';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contul Meu</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="admin-ui.css">

<style>
.container-user {
    max-width: 1100px;
    margin: 40px auto;
    padding: 0 20px;
}

.page-header {
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    margin-bottom: 20px;
}

.page-header h1 {
    text-align: center;
    margin: 0;
    font-size: 26px;
}

.tabs {
    display: flex;
    gap: 10px;
    border-bottom: 1px solid #ddd;
    margin-bottom: 15px;
}

.tab-link {
    padding: 10px 15px;
    text-decoration: none;
    color: #333;
    font-weight: 600;
}

.tab-link.active {
    border-bottom: 3px solid #0d6efd;
}

.form-group {
    margin-bottom: 15px;
}

input {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 15px;
}
</style>

</head>
<body>

<?php include __DIR__.'/partials/header.php'; ?>

<div class="container-user">

    <!-- HEADER CU BACK SUS -->
    <div class="page-header">
        <a href="index.php" class="btn btn-back">
            ← Înapoi
        </a>
        <h1>Contul meu</h1>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- TABS -->
    <div class="tabs">
        <a class="tab-link <?= $activeTab==='rezervari'?'active':'' ?>"
           href="?tab=rezervari">Rezervările mele</a>

        <a class="tab-link <?= $activeTab==='profil'?'active':'' ?>"
           href="?tab=profil">Profil</a>
    </div>

    <?php if ($activeTab === 'profil'): ?>

        <form method="post">
            <div class="form-group">
                <label>Nume</label>
                <input type="text" name="nume" value="<?= htmlspecialchars($user['nume']) ?>">
            </div>

            <div class="form-group">
                <label>Prenume</label>
                <input type="text" name="prenume" value="<?= htmlspecialchars($user['prenume']) ?>">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>">
            </div>

            <div class="actions">
                <button class="btn btn-add">Salvează</button>
            </div>
        </form>

    <?php else: ?>

      <div class="user-list-section">
    <table class="user-table">
        <thead>
            <tr>
                <th>ID Rezervare</th>
                <th>ID Pachet</th>
                <th>Data rezervare</th>
                <th>Nr. persoane</th>
                <th>Total (RON)</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!empty($rows)): ?>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td><?= htmlspecialchars((string)$r['id_rezervare']) ?></td>
                    <td><?= htmlspecialchars((string)$r['id_pachet']) ?></td>
                    <td><?= htmlspecialchars((string)$r['data_rezervare']) ?></td>
                    <td><?= htmlspecialchars((string)$r['numar_persoane']) ?></td>
                    <td><?= htmlspecialchars(number_format((float)$r['total'], 2)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" class="empty">Nu există rezervări.</td>
            </tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>


    <?php endif; ?>

</div>

</body>
</html>
