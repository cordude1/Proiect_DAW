<?php
require_once __DIR__.'/bootstrap.php';
 // Începe sesiunea

// Distruge sesiunea și șterge toate variabilele de sesiune
session_unset();
session_destroy();

// Redirecționează utilizatorul la pagina de login
header("Location: login.php");
exit();
?>
