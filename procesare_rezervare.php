<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* ================== CONFIG CAPTCHA ================== */
$CAPTCHA_SITE_KEY   = '6LdFi9cqAAAAAFlmadT6-dcwL247TqKHII6RbAoZ';
$CAPTCHA_SECRET_KEY = '6LdFi9cqAAAAACI5GxBI1NVRWph7r3cRayC8wGTY';

/* ================== VERIFY CAPTCHA FUNCTION ================== */
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

/** Verifică metoda și CSRF imediat */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodă invalidă');
}
csrf_verify();

/** Verifică autentificare */
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

/** Validare acțiune */
if (($_POST['action'] ?? '') !== 'rezerva') {
    http_response_code(400);
    exit('Acțiune invalidă');
}

/** Verifică reCAPTCHA */
$captchaToken = $_POST['g-recaptcha-response'] ?? '';
if (!$captchaToken || !verifyRecaptcha($CAPTCHA_SECRET_KEY, $captchaToken)) {
    $_SESSION['errorMessage'] = 'Verificare CAPTCHA eșuată! Încearcă din nou.';
    header('Location: rezervare.php?id_pachet=' . urlencode((string)($_POST['id_pachet'] ?? '')));
    exit;
}

/** Preluare & validare input */
$idUser      = (int) $_SESSION['user_id'];
$idPachet    = filter_var($_POST['id_pachet']   ?? null, FILTER_VALIDATE_INT) ?: 0;
$nrPersoane  = filter_var($_POST['nr_persoane'] ?? ($_POST['numar_persoane'] ?? null), FILTER_VALIDATE_INT) ?: 0;
$dataPlecare = trim((string)($_POST['data_plecare'] ?? ''));

$email       = trim((string)($_POST['email']     ?? ''));
$nume        = trim((string)($_POST['nume']      ?? ''));
$prenume     = trim((string)($_POST['prenume']   ?? ''));
$telefon     = trim((string)($_POST['telefon']   ?? ''));
$adresa      = trim((string)($_POST['adresa']    ?? ''));
$judet       = trim((string)($_POST['judet']     ?? ''));
$localitate  = trim((string)($_POST['localitate']?? ''));
$metoda      = trim((string)($_POST['metoda_plata'] ?? ''));

/** Validări de bază */
if (empty($_POST['accept_termeni'])) {
    $_SESSION['errorMessage'] = 'Trebuie să accepți termenii și condițiile.';
    header('Location: rezervare.php?id_pachet=' . urlencode((string)$idPachet));
    exit;
}
if ($dataPlecare !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataPlecare)) {
    $_SESSION['errorMessage'] = 'Data plecării este invalidă (format așteptat: YYYY-MM-DD).';
    header('Location: rezervare.php?id_pachet=' . urlencode((string)$idPachet));
    exit;
}
if ($idPachet <= 0 || $nrPersoane <= 0) {
    $_SESSION['errorMessage'] = 'Date lipsă/greșite.';
    header('Location: rezervare.php?id_pachet=' . urlencode((string)$idPachet));
    exit;
}

/** Conectare DB */
$db  = new DatabaseConnector();
$pdo = $db->connect();

/** Detectează coloanele disponibile */
function rezv_cols(PDO $pdo): array {
    try {
        $colNames = $pdo->query("SHOW COLUMNS FROM `rezervari`")->fetchAll(PDO::FETCH_COLUMN, 0);
    } catch (Throwable $e) {
        http_response_code(500);
        exit("Nu pot inspecta tabela `rezervari`: " . $e->getMessage());
    }

    $has = array_flip($colNames);

    $pick = function(array $choices) use ($has): ?string {
        foreach ($choices as $c) {
            if (isset($has[$c])) return $c;
        }
        return null;
    };

    return [
        'colUser'       => $pick(['id_user','user_id','id_client','id_utilizator']),
        'colPachet'     => $pick(['id_pachet','id_pachet_turistic']),
        'colNPers'      => $pick(['nr_persoane','numar_persoane','persoane']),
        'colData'       => $pick(['data_plecare','data_calatorie']),
        'colStatus'     => $pick(['status']),
        'colEmail'      => $pick(['email']),
        'colNume'       => $pick(['nume']),
        'colPrenume'    => $pick(['prenume']),
        'colTelefon'    => $pick(['telefon']),
        'colAdresa'     => $pick(['adresa']),
        'colJudet'      => $pick(['judet']),
        'colLocalitate' => $pick(['localitate']),
        'colMetoda'     => $pick(['metoda_plata']),
    ];
}

$bt = static function (string $col): string {
    return '`' . str_replace('`', '``', $col) . '`';
};

$C = rezv_cols($pdo);
if (!$C['colUser'] || !$C['colPachet']) {
    http_response_code(500);
    exit("Tabela `rezervari` trebuie să aibă coloane pentru user și pachet.");
}

/** Construim INSERT dinamic */
$fields = [$bt($C['colUser']), $bt($C['colPachet'])];
$values = [':u', ':p'];
$params = [':u' => $idUser, ':p' => $idPachet];

if ($C['colNPers'])      { $fields[] = $bt($C['colNPers']);      $values[]=':n';  $params[':n']  = $nrPersoane; }
if ($C['colData'] && $dataPlecare !== '') { $fields[] = $bt($C['colData']); $values[]=':d';  $params[':d']  = $dataPlecare; }
if ($C['colStatus'])     { $fields[] = $bt($C['colStatus']);     $values[]=':s';  $params[':s']  = 'in_asteptare'; }

if ($C['colEmail']   && $email   !== '') { $fields[] = $bt($C['colEmail']);    $values[]=':e';  $params[':e']  = $email; }
if ($C['colNume']    && $nume    !== '') { $fields[] = $bt($C['colNume']);     $values[]=':nn'; $params[':nn'] = $nume; }
if ($C['colPrenume'] && $prenume !== '') { $fields[] = $bt($C['colPrenume']);  $values[]=':pp'; $params[':pp'] = $prenume; }
if ($C['colTelefon'] && $telefon !== '') { $fields[] = $bt($C['colTelefon']);  $values[]=':t';  $params[':t']  = $telefon; }
if ($C['colAdresa']  && $adresa  !== '') { $fields[] = $bt($C['colAdresa']);   $values[]=':a';  $params[':a']  = $adresa; }
if ($C['colJudet']   && $judet   !== '') { $fields[] = $bt($C['colJudet']);    $values[]=':j';  $params[':j']  = $judet; }
if ($C['colLocalitate'] && $localitate !== '') { $fields[]=$bt($C['colLocalitate']); $values[]=':l'; $params[':l'] = $localitate; }
if ($C['colMetoda']  && $metoda  !== '') { $fields[] = $bt($C['colMetoda']);   $values[]=':m';  $params[':m']  = $metoda; }

$sql = "INSERT INTO `rezervari` (" . implode(',', $fields) . ") VALUES (" . implode(',', $values) . ")";

try {
    $st = $pdo->prepare($sql);
    $st->execute($params);

    // === TRIMITERE EMAIL DUPĂ SUCCES ===
    $lastId = $pdo->lastInsertId(); // ID-ul rezervării noi

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'arri3lla@gmail.com';
        $mail->Password   = 'zirwmsvxdicepzjk'; // App Password-ul tău real
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // === Email către client ===
        $mail->clearAddresses();
        $mail->setFrom('no-reply@agentieturism.ro', 'Agenție Turism');
        $mail->addAddress($email);
        $mail->addReplyTo('arri3lla@gmail.com', 'Suport Agenție Turism');
        $mail->Subject = 'Confirmare rezervare #' . $lastId;
        $mail->isHTML(true);
        $mail->Body = "
            <h2>Rezervare confirmată!</h2>
            <p>Bună {$nume} {$prenume},</p>
            <p>Rezervarea ta pentru pachetul #{$idPachet} a fost înregistrată cu succes.</p>
            <ul>
                <li><strong>Data rezervării:</strong> " . date('d.m.Y H:i') . "</li>
                <li><strong>Număr persoane:</strong> {$nrPersoane}</li>
                <li><strong>Data plecării:</strong> " . ($dataPlecare ?: 'Nespecificată') . "</li>
                <li><strong>Metoda plată:</strong> {$metoda}</li>
            </ul>
            <p>Te vom contacta în curând pentru detalii și confirmare finală.</p>
            <p>Mulțumim că ai ales Agenția noastră!</p>
        ";
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $mail->Body));
        $mail->send();

        // === Email către admin (tu) ===
        $mail->clearAddresses();
        $mail->addAddress('arri3lla@gmail.com');
        $mail->Subject = 'Rezervare nouă #' . $lastId;
        $mail->Body = "
            <h2>Rezervare nouă!</h2>
            <p>Client: {$nume} {$prenume} ({$email})</p>
            <p>Telefon: {$telefon}</p>
            <p>Pachet: #{$idPachet}</p>
            <p>Persoane: {$nrPersoane}</p>
            <p>Data plecării: " . ($dataPlecare ?: 'Nespecificată') . "</p>
            <p>Adresă: {$adresa}, {$localitate}, {$judet}</p>
            <p>Metoda plată: {$metoda}</p>
        ";
        $mail->send();

        $_SESSION['successMessage'] = 'Rezervarea a fost înregistrată. Vei primi confirmare pe email.';
    } catch (Exception $e) {
        // Nu stricăm rezervarea dacă email-ul eșuează
        error_log("Email rezervare eșuat: " . $mail->ErrorInfo);
        $_SESSION['successMessage'] = 'Rezervarea a fost înregistrată, dar confirmarea pe email nu a fost trimisă.';
    }

    header('Location: contulMeu.php');
    exit;

} catch (Throwable $e) {
    $_SESSION['errorMessage'] = 'Eroare DB: ' . $e->getMessage();
    header('Location: rezervare.php?id_pachet=' . urlencode((string)$idPachet));
    exit;
}