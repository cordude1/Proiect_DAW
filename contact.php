<?php
// contact.php – FINAL cu PHPMailer + comportament IDENTIC cu register.php (redirecționare spre home după 5 secunde)
declare(strict_types=1);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ================== CONFIG CAPTCHA ================== */
$CAPTCHA_SITE_KEY   = '6LdFi9cqAAAAAFlmadT6-dcwL247TqKHII6RbAoZ';
$CAPTCHA_SECRET_KEY = '6LdFi9cqAAAAACI5GxBI1NVRWph7r3cRayC8wGTY';

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

    $context = stream_context_create($options);
    $result  = file_get_contents(
        'https://www.google.com/recaptcha/api/siteverify',
        false,
        $context
    );

    if ($result === false) return false;

    $json = json_decode($result, true);
    return !empty($json['success']);
}

$error = "";
$success = false;
$old = ['nume' => '', 'email' => '', 'subiect' => '', 'mesaj' => ''];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $captchaToken = $_POST['g-recaptcha-response'] ?? '';

    if (!$captchaToken || !verifyRecaptcha($CAPTCHA_SECRET_KEY, $captchaToken)) {
        $error = "Verificare CAPTCHA eșuată!";
    } else {
        $nume    = trim($_POST['nume']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $subiect = trim($_POST['subiect'] ?? '');
        $mesaj   = trim($_POST['mesaj']   ?? '');

        if (strlen($nume) < 2)    $error = "Numele este prea scurt.";
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = "Email invalid.";
        elseif (strlen($subiect) < 3) $error = "Subiect prea scurt.";
        elseif (strlen($mesaj) < 10)  $error = "Mesajul este prea scurt.";
        else {
            // === TRIMITERE CU PHPMailer ===
            $mail = new PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'arri3lla@gmail.com';
                $mail->Password   = 'zirwmsvxdicepzjk';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom($email, $nume);
                $mail->addAddress('arri3lla@gmail.com');
                $mail->addReplyTo($email, $nume);

                $mail->isHTML(true);
                $mail->Subject = $subiect;
                $mail->Body    = "
                    <h2>Mesaj nou de pe site - Agenție Turism</h2>
                    <p><strong>Nume:</strong> " . htmlspecialchars($nume) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
                    <p><strong>Subiect:</strong> " . htmlspecialchars($subiect) . "</p>
                    <p><strong>Mesaj:</strong><br>" . nl2br(htmlspecialchars($mesaj)) . "</p>
                ";
                $mail->AltBody = "Nume: $nume\nEmail: $email\nSubiect: $subiect\nMesaj:\n$mesaj";

                $mail->send();
                $success = true;
            } catch (Exception $e) {
                $error = "Eroare la trimiterea mesajului: " . $mail->ErrorInfo;
            }
        }

        if (!$success) {
            $old = compact('nume', 'email', 'subiect', 'mesaj');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact</title>

<link rel="stylesheet" href="assets/css/register.css">
<link rel="stylesheet" href="backButton.css">

<script src="https://www.google.com/recaptcha/api.js" async defer></script>

<style>
.success-box{
    background:#eafaf0;
    border:1px solid #2ecc71;
    color:#19692c;
    padding:20px;
    border-radius:12px;
    text-align:center;
    font-weight:600;
    max-width: 600px;
    margin: 0 auto;
}
.countdown{
    font-size:28px;
    font-weight:800;
    margin-top:10px;
}
.error {
    color: red;
    text-align: center;
    margin: 10px 0;
}
.register-container {
    max-width: 600px;
    margin: 40px auto;
    padding: 30px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}
</style>

</head>
<body class="register-page">

<div class="register-container">

<h2>Contactează-ne</h2>

<?php if ($error): ?>
<p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($success): ?>

<div class="success-box">
    Mesajul tău a fost trimis cu succes! 🎉 <br>
    Vei fi redirecționat către pagina principală în
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
        window.location.href = "index.php"; // ← ÎNAPOI SPRE HOME
    }
}, 1000);
</script>

<?php else: ?>

<form method="POST" action="contact.php">

<label>Nume complet *</label>
<input type="text" name="nume" value="<?= htmlspecialchars($old['nume']) ?>" required>

<label>Email *</label>
<input type="email" name="email" value="<?= htmlspecialchars($old['email']) ?>" required>

<label>Subiect *</label>
<input type="text" name="subiect" value="<?= htmlspecialchars($old['subiect']) ?>" required>

<label>Mesaj *</label>
<textarea name="mesaj" required><?= htmlspecialchars($old['mesaj']) ?></textarea>

<div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($CAPTCHA_SITE_KEY) ?>"></div>

<button type="submit">Trimite mesaj</button>

</form>

<?php endif; ?>

</div>

<div class="back-button">
<a href="javascript:history.back()" class="btn-back">Înapoi</a>
</div>

</body>
</html>