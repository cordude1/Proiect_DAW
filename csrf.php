<?php
// csrf.php
if (session_status() !== PHP_SESSION_ACTIVE) {
   require_once __DIR__.'/bootstrap.php';
}

/** Generează (o singură dată pe sesiune) și întoarce tokenul */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Întoarce <input type="hidden"...> cu tokenul curent */
function csrf_input(): string {
    return '<input type="hidden" name="_token" value="' .
           htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/** Verifică tokenul; de apelat imediat pe paginile care procesează POST */
function csrf_verify(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit('Metodă invalidă');
    }
    $sent = $_POST['_token'] ?? '';
    $sess = $_SESSION['csrf_token'] ?? '';
    if (!$sent || !$sess || !hash_equals($sess, $sent)) {
        http_response_code(419);
        exit('CSRF validation failed.');
    }
}
