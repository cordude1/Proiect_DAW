<?php
declare(strict_types=1);

require_once __DIR__.'/bootstrap.php';
require_once __DIR__ . '/vendor/autoload.php'; // dacă ai PHPMailer și vrei email confirmare mai târziu

/* ================== CONFIG CAPTCHA ================== */
$CAPTCHA_SITE_KEY   = '6LdFi9cqAAAAAFlmadT6-dcwL247TqKHII6RbAoZ';
$CAPTCHA_SECRET_KEY = '6LdFi9cqAAAAACI5GxBI1NVRWph7r3cRayC8wGTY';

/* ================== DB ================== */
$pdo = (new DatabaseConnector())->connect();

$error = "";
$success = false;

/* ================== VERIFY CAPTCHA ================== */
function verifyRecaptcha(string $secret, string $response): bool {
    $data = [
        'secret'   => $secret,
        'response' => $response
    ];

    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'content' => http_build_query($data)
        ]
    ];

    $context  = stream_context_create($options);
    $result   = file_get_contents(
        'https://www.google.com/recaptcha/api/siteverify',
        false,
        $context
    );

    if ($result === false) return false;

    $json = json_decode($result, true);
    return !empty($json['success']);
}

/* ================== REGISTER ================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "register") {

    $captchaToken = $_POST['g-recaptcha-response'] ?? '';

    if (!$captchaToken || !verifyRecaptcha($CAPTCHA_SECRET_KEY, $captchaToken)) {
        $error = "Verificare CAPTCHA eșuată!";
    } else {

        $nume              = trim($_POST['nume'] ?? '');
        $prenume           = trim($_POST['prenume'] ?? '');
        $email             = trim($_POST['email'] ?? '');
        $parola            = $_POST['parola'] ?? '';
        $confirmare_parola = $_POST['confirmare_parola'] ?? '';

        if ($parola !== $confirmare_parola) {
            $error = "Parolele nu coincid!";
        } else {

            $stmt = $pdo->prepare("SELECT id FROM useri WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);

            if ($stmt->fetch()) {
                $error = "Acest email este deja înregistrat!";
            } else {

                $hashed = password_hash($parola, PASSWORD_DEFAULT);

                $insert = $pdo->prepare("
                    INSERT INTO useri (nume, prenume, email, parola)
                    VALUES (:nume, :prenume, :email, :parola)
                ");

                $success = $insert->execute([
                    ':nume'    => $nume,
                    ':prenume' => $prenume,
                    ':email'   => $email,
                    ':parola'  => $hashed
                ]);

                if (!$success) {
                    $error = "Eroare la înregistrare.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Înregistrare</title>

    <!-- Stiluri generale + admin-ui (pentru .btn.btn-back) -->
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="admin-ui.css">
    <link rel="stylesheet" href="assets/css/register.css"> <!-- dacă ai stiluri specifice -->

    <script src="https://www.google.com/recaptcha/api.js" async defer></script>

    <style>
        .success-box {
            background: #eafaf0;
            border: 1px solid #2ecc71;
            color: #19692c;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
            max-width: 600px;
            margin: 0 auto 20px;
        }
        .countdown {
            font-size: 28px;
            font-weight: 800;
            margin-top: 10px;
        }
        .register-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        label { display: block; margin: 15px 0 5px; font-weight: bold; }
        input, textarea { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 5px; }
        button { width: 100%; padding: 12px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .error { color: red; text-align: center; margin: 10px 0; }
    </style>
</head>
<body class="register-page">

<div class="register-container">

    <h2>Înregistrare</h2>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>

        <div class="success-box">
            Înregistrare reușită! 🎉 <br>
            Vei fi redirecționat către pagina de autentificare în
            <div class="countdown" id="countdown">5</div>
            secunde...
        </div>

        <script>
            let seconds = 5;
            const countdownEl = document.getElementById("countdown");

            const interval = setInterval(() => {
                seconds--;
                countdownEl.textContent = seconds;

                if (seconds <= 0) {
                    clearInterval(interval);
                    window.location.href = "login.php";
                }
            }, 1000);
        </script>

    <?php else: ?>

        <form method="POST" action="register.php">

            <label>Nume</label>
            <input type="text" name="nume" required>

            <label>Prenume</label>
            <input type="text" name="prenume" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Parolă</label>
            <input type="password" name="parola" required>

            <label>Confirmă parola</label>
            <input type="password" name="confirmare_parola" required>

            <div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($CAPTCHA_SITE_KEY) ?>"></div>

            <input type="hidden" name="action" value="register">

            <button type="submit">Înregistrează-te</button>

        </form>

    <?php endif; ?>

</div>

<!-- Buton Înapoi – EXACT ca în rezervare.php -->
<div class="page-header" style="position: fixed; bottom: 20px; left: 20px; z-index: 1000; margin: 0;">
    <a href="javascript:history.back()" class="btn btn-back">
        ← Înapoi
    </a>
</div>

</body>
</html>