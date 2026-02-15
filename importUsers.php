<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import utilizatori</title>
</head>
<body>

<?php
require './DatabaseConnector.php';

// Conexiune (portul 3307 este pus în DSN-ul clasei, cum ai modificat la pasul 1)
$pdo = new DatabaseConnector();
$pdo = $pdo->connect();

$inserted = 0;
$skipped  = 0;

if (!empty($_FILES['file']['tmp_name'])) {
    $file = $_FILES['file']['tmp_name'];

    if (($handle = fopen($file, 'r')) !== false) {
        // Sărim header-ul
        fgetcsv($handle, 1000, ",");

        // Pregătim INSERT corect pe tabela useri
        $stmt = $pdo->prepare("
            INSERT INTO useri (nume, prenume, email, parola, rol)
            VALUES (:nume, :prenume, :email, :parola, :rol)
        ");

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            // Acceptăm două formate:
            // A) 2 coloane: [0]=nume_complet, [1]=email
            // B) 4 coloane: [0]=nume, [1]=prenume, [2]=email, [3]=rol
            $nume = $prenume = $email = $rol = '';

            if (count($data) >= 4) {
                $nume    = trim((string)$data[0]);
                $prenume = trim((string)$data[1]);
                $email   = trim((string)$data[2]);
                $rol     = trim((string)$data[3]) ?: 'user';
            } else {
                // fallback 2 coloane
                $fullName = trim((string)$data[0]);
                $email    = trim((string)$data[1]);
                $rol      = 'user';

                // Spargem numele în nume/prenume (best effort)
                if ($fullName !== '') {
                    $parts = preg_split('/\s+/', $fullName);
                    $nume = array_shift($parts) ?? '';
                    $prenume = implode(' ', $parts);
                }
            }

            // Validări minime
            if ($email === '' || $nume === '') {
                $skipped++;
                continue;
            }

            try {
                $stmt->execute([
                    ':nume'    => $nume,
                    ':prenume' => $prenume,
                    ':email'   => $email,
                    // Parolă implicită: email-ul hash-uit; schimbă dacă vrei altă logică
                    ':parola'  => password_hash($email, PASSWORD_BCRYPT),
                    ':rol'     => ($rol === 'admin' ? 'admin' : 'user'),
                ]);
                $inserted++;
            } catch (Throwable $e) {
                // Dacă ai UNIQ pe (nume, prenume) și există deja, contorizăm ca "sărit"
                $skipped++;
                // Dacă vrei să vezi motivul:
                // echo "<div>Eroare la '$nume $prenume' ($email): ".$e->getMessage()."</div>";
            }
        }

        fclose($handle);
    }
}
?>

<div>Import finalizat.</div>
<ul>
    <li>Inserate: <strong><?= (int)$inserted ?></strong></li>
    <li>Sărite (duplicate/invalid): <strong><?= (int)$skipped ?></strong></li>
</ul>

<a href="index.php">Home</a>

<div class="back-button">
    <a href="javascript:history.back()" class="btn-back">Înapoi</a>
</div>

</body>
</html>
