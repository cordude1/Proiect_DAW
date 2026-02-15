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
    die('Eroare DB: '.$e->getMessage());
}

/* ================= GET ID ================= */
$id = (int)($_GET['id_pachet'] ?? 0);
if ($id <= 0) {
    die('ID invalid.');
}

/* ================= LOAD PACKAGE ================= */
$stmt = $pdo->prepare("
    SELECT *
    FROM pacheteturistice
    WHERE id_pachet = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$pachet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pachet) {
    die('Pachetul nu există.');
}

/* ================= LOAD DESTINATII ================= */
$destinatii = $pdo->query("
    SELECT id_destinatie, nume, tara
    FROM destinatii
    ORDER BY tara, nume
")->fetchAll(PDO::FETCH_ASSOC);

/* ================= UPDATE ================= */
if (($_POST['action'] ?? '') === 'update-package') {

    $nume   = trim($_POST['nume'] ?? '');
    $pret   = trim($_POST['pret'] ?? '');
    $durata = trim($_POST['durata'] ?? '');
    $dest_mode = $_POST['dest_mode'] ?? 'existing';

    $errors = [];

    if ($nume === '') $errors[] = 'Numele este obligatoriu.';
    if (!is_numeric(str_replace(',', '.', $pret))) $errors[] = 'Preț invalid.';
    if (!ctype_digit($durata) || (int)$durata <= 0) $errors[] = 'Durată invalidă.';

    try {

        if ($dest_mode === 'existing') {

            $id_destinatie = (int)($_POST['id_destinatie'] ?? 0);
            if ($id_destinatie <= 0) {
                $errors[] = 'Selectează o destinație.';
            }

        } else {

            $dest = trim($_POST['destinatie'] ?? '');
            $tara = trim($_POST['tara'] ?? '');

            if ($dest === '' || $tara === '') {
                $errors[] = 'Completează destinația și țara.';
            } else {

                $q = $pdo->prepare("
                    SELECT id_destinatie 
                    FROM destinatii 
                    WHERE LOWER(nume)=LOWER(:n) 
                    AND LOWER(tara)=LOWER(:t)
                    LIMIT 1
                ");
                $q->execute([':n'=>$dest, ':t'=>$tara]);
                $found = $q->fetchColumn();

                if ($found) {
                    $id_destinatie = (int)$found;
                } else {
                    $ins = $pdo->prepare("
                        INSERT INTO destinatii (nume, tara)
                        VALUES (:n, :t)
                    ");
                    $ins->execute([':n'=>$dest, ':t'=>$tara]);
                    $id_destinatie = (int)$pdo->lastInsertId();
                }
            }
        }

        if (!$errors) {

            $update = $pdo->prepare("
                UPDATE pacheteturistice
                SET nume = :n,
                    pret = :p,
                    durata = :d,
                    id_destinatie = :dest
                WHERE id_pachet = :id
            ");

            $update->execute([
                ':n' => $nume,
                ':p' => (float)str_replace(',', '.', $pret),
                ':d' => (int)$durata,
                ':dest' => $id_destinatie,
                ':id' => $id
            ]);

            $_SESSION['successMessage'] = 'Pachet actualizat cu succes.';
            header('Location: '.url('administrarePachete.php'));
            exit;
        }

    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
<?php $pageTitle='Editează Pachet'; include __DIR__.'/partials/head.php'; ?>
<link rel="stylesheet" href="<?= asset('admin-ui.css') ?>">

<style>
.form-card{
    max-width:900px;
    margin:30px auto;
    background:#fff;
    padding:24px;
    border-radius:16px;
    box-shadow:0 12px 24px rgba(0,0,0,.08);
}
.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px;
}
.form-group label{
    font-weight:700;
    margin-bottom:6px;
    display:block;
}
input, select{
    width:100%;
    padding:10px 12px;
    border:1px solid #ddd;
    border-radius:10px;
}
.form-actions{
    margin-top:20px;
    display:flex;
    justify-content:flex-end;
    gap:10px;
}
.hide{display:none;}
</style>
</head>
<body>

<?php include __DIR__.'/partials/header.php'; ?>

<div class="container">

<div class="header">
<a href="<?= url('administrarePachete.php') ?>" class="btn btn-back">
<span class="arrow">←</span> Înapoi
</a>
<h1>Editează Pachet</h1>
<span></span>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
<?php foreach ($errors as $e): ?>
<div><?= htmlspecialchars((string)$e) ?></div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<div class="form-card">

<form method="POST">

<input type="hidden" name="action" value="update-package">

<div class="form-grid">

<div class="form-group">
<label>Nume pachet *</label>
<input type="text" name="nume" value="<?= htmlspecialchars((string)$pachet['nume']) ?>" required>
</div>

<div class="form-group">
<label>Preț (RON) *</label>
<input type="text" name="pret" value="<?= number_format((float)$pachet['pret'],2,'.','') ?>" required>
</div>

<div class="form-group">
<label>Durată (zile) *</label>
<input type="number" name="durata" value="<?= (int)$pachet['durata'] ?>" min="1" required>
</div>

<div class="form-group">
<label>Destinație existentă</label>
<select name="id_destinatie">
<option value="">-- alege --</option>
<?php foreach($destinatii as $d): ?>
<option value="<?= (int)$d['id_destinatie'] ?>"
<?= $pachet['id_destinatie']==$d['id_destinatie']?'selected':'' ?>>
<?= htmlspecialchars($d['tara'].' - '.$d['nume']) ?>
</option>
<?php endforeach; ?>
</select>
</div>

</div>

<div class="form-actions">
<a href="<?= url('administrarePachete.php') ?>" class="btn btn-back">Anulează</a>
<button type="submit" class="btn btn-add">Salvează</button>
</div>

</form>
</div>
</div>

<?php include __DIR__.'/partials/footer.php'; ?>
</body>
</html>
