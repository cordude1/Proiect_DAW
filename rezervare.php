<?php
require_once __DIR__.'/bootstrap.php';
require_once __DIR__ . '/csrf.php';

// Cheie publică reCAPTCHA (ca la contact/register)
$CAPTCHA_SITE_KEY = '6LdFi9cqAAAAAFlmadT6-dcwL247TqKHII6RbAoZ';

if (!isset($_GET['id_pachet'])) {
    header("Location: index.php");
    exit();
}

$id_pachet = (int)$_GET['id_pachet'];

$database = new DatabaseConnector();
$pdo = $database->connect();

$query = "SELECT p.*, d.nume AS destinatie, d.tara
          FROM pacheteturistice p
          JOIN destinatii d ON p.id_destinatie = d.id_destinatie
          WHERE p.id_pachet = ?";
$statement = $pdo->prepare($query);
$statement->execute([$id_pachet]);
$pachet = $statement->fetch(PDO::FETCH_ASSOC);

if (!$pachet) {
    header("Location: index.php");
    exit();
}

$prefEmail   = $_SESSION['email']   ?? '';
$prefNume    = $_SESSION['nume']    ?? '';
$prefPrenume = $_SESSION['prenume'] ?? '';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formular de Rezervare</title>

    <link rel="stylesheet" href="admin-ui.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="rezervare.css">

    <!-- reCAPTCHA script -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

<?php include __DIR__.'/partials/header.php'; ?>

<div class="container-user">

    <div class="page-header">
        <a href="javascript:history.back()" class="btn btn-back">
            ← Înapoi
        </a>
        <h1>Formular de Rezervare</h1>
    </div>

    <div class="rez-card">

        <form action="procesare_rezervare.php" method="POST">
            <?= csrf_input() ?>
            <input type="hidden" name="action" value="rezerva">
            <input type="hidden" name="id_pachet" value="<?= (int)$id_pachet ?>">

            <h3>Detalii Pachet</h3>

            <div class="form-group">
                <label>Pachet ales</label>
                <input type="text"
                       value="<?= htmlspecialchars($pachet['nume'].' - '.$pachet['destinatie'].', '.$pachet['tara']) ?>"
                       readonly>
            </div>

            <div class="form-group">
                <label>Preț</label>
                <input type="text"
                       value="<?= htmlspecialchars((string)$pachet['pret']) ?>"
                       readonly>
            </div>

            <div class="form-group">
                <label>Durată</label>
                <input type="text"
                       value="<?= htmlspecialchars((string)$pachet['durata']) ?>"
                       readonly>
            </div>

            <h3>Date de contact</h3>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($prefEmail) ?>" required>
            </div>

            <div class="form-group">
                <label>Nume</label>
                <input type="text" name="nume"
                       value="<?= htmlspecialchars($prefNume) ?>" required>
            </div>

            <div class="form-group">
                <label>Prenume</label>
                <input type="text" name="prenume"
                       value="<?= htmlspecialchars($prefPrenume) ?>" required>
            </div>

            <div class="form-group">
                <label>Telefon</label>
                <input type="text" name="telefon" required>
            </div>

            <h3>Informații rezervare</h3>

            <div class="form-group">
                <label>Număr persoane</label>
                <input type="number" name="nr_persoane" min="1" required>
            </div>

            <div class="form-group">
                <label>Data plecării</label>
                <input type="date" name="data_plecare">
            </div>

            <div class="form-group">
                <label>Adresă</label>
                <input type="text" name="adresa" required>
            </div>

            <div class="form-group">
                <label>Județ</label>
                <input type="text" name="judet" required>
            </div>

            <div class="form-group">
                <label>Localitate</label>
                <input type="text" name="localitate" required>
            </div>

            <div class="form-group">
                <label>Metoda plată</label>
                <select name="metoda_plata" required>
                    <option value="">— alege —</option>
                    <option value="card">Card</option>
                    <option value="cash">Cash</option>
                    <option value="transfer">Transfer bancar</option>
                </select>
            </div>

            <div class="form-group checkbox-wrap">
                <input type="checkbox" name="accept_termeni" required>
                <span>Accept termenii și condițiile</span>
            </div>

            <!-- reCAPTCHA – adăugat aici, înainte de submit -->
            <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($CAPTCHA_SITE_KEY) ?>" style="margin: 20px 0; text-align: center;"></div>

            <div class="actions">
                <button class="btn btn-add">
                    Finalizează Rezervarea
                </button>
            </div>

        </form>

    </div>

</div>

</body>
</html>