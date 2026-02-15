<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
/* doar admin */
$isAdmin = (($_SESSION['role'] ?? '') === 'admin') || (($_SESSION['rol'] ?? '') === 'admin');
if (!$isAdmin) {
    $_SESSION['errorMessage'] = 'Acces interzis.';
    header('Location: administrarePachete.php'); exit;
}

/* conexiune DB */
try {
    $db  = new DatabaseConnector();
    $pdo = $db->connect();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Throwable $e) {
    $_SESSION['errorMessage'] = 'Eroare conexiune DB: ' . $e->getMessage();
    header('Location: administrarePachete.php'); exit;
}

/* validare upload */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $_SESSION['errorMessage'] = 'Metodă invalidă.';
    header('Location: administrarePachete.php'); exit;
}
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $err = $_FILES['file']['error'] ?? -1;
    $map = [
        UPLOAD_ERR_INI_SIZE   => 'Fișier prea mare (php.ini).',
        UPLOAD_ERR_FORM_SIZE  => 'Fișier prea mare (FORM).',
        UPLOAD_ERR_PARTIAL    => 'Încărcare parțială.',
        UPLOAD_ERR_NO_FILE    => 'Nu ai selectat fișierul.',
        UPLOAD_ERR_NO_TMP_DIR => 'Lipsește folderul temporar.',
        UPLOAD_ERR_CANT_WRITE => 'Nu pot scrie pe disc.',
        UPLOAD_ERR_EXTENSION  => 'Blocată de extensie.',
    ];
    $_SESSION['errorMessage'] = 'Eroare upload: ' . ($map[$err] ?? 'necunoscută');
    header('Location: administrarePachete.php'); exit;
}
$filename = $_FILES['file']['name'] ?? '';
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
if ($ext !== 'csv') {
    $_SESSION['errorMessage'] = 'Te rog încarcă un fișier .csv';
    header('Location: administrarePachete.php'); exit;
}

/* helperi */
function norm_str(?string $s): string { return trim((string)$s); }
function to_int($v): ?int {
    $v = norm_str((string)$v);
    if ($v === '') return null;
    if (!preg_match('/^\d+$/', $v)) return null;
    return (int)$v;
}
function to_float($v): ?float {
    $s = str_replace([' ', ','], ['', '.'], (string)$v); // 1 234,56 -> 1234.56
    $s = trim($s);
    if ($s === '' || !is_numeric($s)) return null;
    return (float)$s;
}

/* map antete acceptate -> chei interne */
$headerMap = [
    'nume'          => ['nume', 'nume_pachet', 'titlu', 'denumire'],
    'pret'          => ['pret', 'preț', 'price'],
    'durata'        => ['durata', 'durată', 'zile', 'days'],
    'destinatie'    => ['destinatie', 'destinație', 'oras', 'oraș', 'city'],
    'tara'          => ['tara', 'țara', 'country'],
    'id_destinatie' => ['id_destinatie', 'id destination', 'destination_id'],
];

/* deschide CSV */
$path = $_FILES['file']['tmp_name'];
if (!is_readable($path)) {
    $_SESSION['errorMessage'] = 'Nu pot citi fișierul încărcat.';
    header('Location: administrarePachete.php'); exit;
}
$fh = fopen($path, 'r');
if ($fh === false) {
    $_SESSION['errorMessage'] = 'Nu pot deschide fișierul CSV.';
    header('Location: administrarePachete.php'); exit;
}

/* citește header */
$rawHeader = fgetcsv($fh);
if ($rawHeader === false) {
    fclose($fh);
    $_SESSION['errorMessage'] = 'Fișierul CSV este gol.';
    header('Location: administrarePachete.php'); exit;
}
$rawHeader = array_map(fn($h)=>strtolower(norm_str($h)), $rawHeader);

/* construiește maparea coloanelor */
$idx = []; // cheie internă -> index în csv
foreach ($headerMap as $key => $aliases) {
    foreach ($aliases as $alias) {
        $pos = array_search(strtolower($alias), $rawHeader, true);
        if ($pos !== false) { $idx[$key] = $pos; break; }
    }
}

/* reguli minime: trebuie să avem nume + (id_destinatie sau destinatie+tara) */
if (!isset($idx['nume'])) {
    fclose($fh);
    $_SESSION['errorMessage'] = "CSV invalid: coloana 'nume' lipsește.";
    header('Location: administrarePachete.php'); exit;
}

/* prepared statements */
$getDestById   = $pdo->prepare("SELECT id_destinatie FROM destinatii WHERE id_destinatie = :id LIMIT 1");
$getDestByName = $pdo->prepare("SELECT id_destinatie FROM destinatii WHERE LOWER(nume)=LOWER(:n) AND LOWER(tara)=LOWER(:t) LIMIT 1");
$insDest       = $pdo->prepare("INSERT INTO destinatii (nume, tara) VALUES (:n, :t)");
$getPackByName = $pdo->prepare("SELECT id_pachet FROM pacheteturistice WHERE LOWER(nume)=LOWER(:n) LIMIT 1");
$insPack       = $pdo->prepare("INSERT INTO pacheteturistice (nume, pret, durata, id_destinatie) VALUES (:n, :p, :d, :id_dest)");
$updPack       = $pdo->prepare("UPDATE pacheteturistice SET pret=:p, durata=:d, id_destinatie=:id_dest WHERE id_pachet=:id");

/* counters */
$line = 1; // deja am citit headerul
$okInserts = 0; $okUpdates = 0; $skipped = 0; $errors = 0;
$errorLines = [];

/* tranzacție pentru viteză/coerență */
$pdo->beginTransaction();

while (($row = fgetcsv($fh)) !== false) {
    $line++;

    // extrage valori
    $nume   = norm_str($row[$idx['nume']] ?? '');
    $pret   = isset($idx['pret'])   ? to_float($row[$idx['pret']]) : null;
    $durata = isset($idx['durata']) ? to_int($row[$idx['durata']]) : null;

    // destinație
    $destId = null;
    if (isset($idx['id_destinatie'])) {
        $cand = to_int($row[$idx['id_destinatie']] ?? null);
        if ($cand) {
            $getDestById->execute([':id'=>$cand]);
            if ($getDestById->fetchColumn()) $destId = $cand;
        }
    }
    if ($destId === null) {
        $dest  = isset($idx['destinatie']) ? norm_str($row[$idx['destinatie']]) : '';
        $tara  = isset($idx['tara']) ? norm_str($row[$idx['tara']]) : '';
        if ($dest !== '' && $tara !== '') {
            $getDestByName->execute([':n'=>$dest, ':t'=>$tara]);
            $found = $getDestByName->fetchColumn();
            if ($found) {
                $destId = (int)$found;
            } else {
                // creează destinația
                $insDest->execute([':n'=>$dest, ':t'=>$tara]);
                $destId = (int)$pdo->lastInsertId();
            }
        }
    }

    // validări de rând
    if ($nume === '' || $destId === null) {
        $skipped++; 
        $errorLines[] = "#{$line} (nume sau destinație lipsă/invalidă)";
        continue;
    }

    // upsert pe pachet după nume
    try {
        $getPackByName->execute([':n'=>$nume]);
        $packId = $getPackByName->fetchColumn();

        $p = $pret ?? 0.0;
        $d = $durata ?? null;

        if ($packId) {
            $updPack->execute([
                ':p'=>$p, ':d'=>$d, ':id_dest'=>$destId, ':id'=>$packId
            ]);
            $okUpdates++;
        } else {
            $insPack->execute([
                ':n'=>$nume, ':p'=>$p, ':d'=>$d, ':id_dest'=>$destId
            ]);
            $okInserts++;
        }
    } catch (Throwable $e) {
        $errors++;
        $errorLines[] = "#{$line} (" . $e->getMessage() . ")";
        // continuăm importul
    }
}

fclose($fh);
$pdo->commit();

/* feedback */
$msg = "Import Pachete: inserate {$okInserts}, actualizate {$okUpdates}, sărite {$skipped}";
if ($errors > 0) {
    $msg .= ", erori {$errors}.";
    if (!empty($errorLines)) {
        $msg .= " Linii cu probleme: " . implode('; ', array_slice($errorLines, 0, 10));
        if (count($errorLines) > 10) $msg .= ' ...';
    }
    $_SESSION['errorMessage'] = $msg;
} else {
    $_SESSION['successMessage'] = $msg;
}

header('Location: administrarePachete.php'); exit;
