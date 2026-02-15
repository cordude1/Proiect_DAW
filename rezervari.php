<?php
require_once __DIR__.'/bootstrap.php';

// Verificăm dacă utilizatorul este autentificat
if (!isset($_SESSION['user']['id'])) {
    header("Location: login.php");
    exit();
}

// Conectare la baza de date
$database = new DatabaseConnector();
$pdo = $database->connect();

// Obține rezervările utilizatorului
$query = "SELECT r.*, p.nume AS pachet_nume, d.nume AS destinatie_nume, d.tara
          FROM rezervari r
          JOIN pacheteturistice p ON r.id_pachet = p.id_pachet
          JOIN destinatii d ON p.id_destinatie = d.id_destinatie
          WHERE r.id_client = ?";
$statement = $pdo->prepare($query);
$statement->execute([$_SESSION['user']['id']]);
$reservari = $statement->fetchAll();

?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rezervările tale</title>
</head>
<body>
    <h2>Rezervările tale</h2>
    
    <?php if (empty($reservari)): ?>
        <p>Nu aveți nicio rezervare.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Pachet</th>
                    <th>Destinație</th>
                    <th>Țara</th>
                    <th>Număr persoane</th>
                    <th>Email</th>
                    <th>Telefon</th>
                    <th>Metoda plată</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservari as $rezervare): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rezervare['pachet_nume']); ?></td>
                        <td><?php echo htmlspecialchars($rezervare['destinatie_nume']); ?></td>
                        <td><?php echo htmlspecialchars($rezervare['tara']); ?></td>
                        <td><?php echo htmlspecialchars($rezervare['numar_persoane']); ?></td>
                        <td><?php echo htmlspecialchars($rezervare['email']); ?></td>
                        <td><?php echo htmlspecialchars($rezervare['telefon']); ?></td>
                        <td><?php echo htmlspecialchars($rezervare['metoda_plata']); ?></td>
                        <td><?php echo htmlspecialchars($rezervare['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>
</html>
