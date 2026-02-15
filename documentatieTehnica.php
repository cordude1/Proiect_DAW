<?php
require_once __DIR__ . '/bootstrap.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$isAdmin = (($_SESSION['rol'] ?? '') === 'admin') 
        || (($_SESSION['role'] ?? '') === 'admin');

if (!$isAdmin) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<title>Documentație Tehnică - Agenție Turism</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="admin-ui.css">

<style>
.doc-container{
    max-width:1200px;
    margin:40px auto;
    background:#fff;
    padding:40px;
    border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,.08);
}
.doc-title{
    text-align:center;
    margin-bottom:40px;
}
.accordion-item{
    border:1px solid #e5e7eb;
    border-radius:12px;
    margin-bottom:15px;
    overflow:hidden;
}
.accordion-header{
    background:#f8f9fa;
    padding:16px 20px;
    cursor:pointer;
    font-weight:700;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.accordion-content{
    display:none;
    padding:25px;
    background:#fff;
}
.accordion-item.active .accordion-content{
    display:block;
}
.accordion-icon{
    transition:transform .3s ease;
}
.accordion-item.active .accordion-icon{
    transform:rotate(90deg);
}
h3{ margin-top:25px; }
ul{ margin-left:20px; }
</style>
</head>
<body>

<?php include __DIR__.'/partials/header.php'; ?>

<div class="doc-container">

<h1 class="doc-title">
Documentație Tehnică<br>
Aplicație Web – Agenție Turism Online
</h1>

<!-- 1 -->
<div class="accordion-item">
<div class="accordion-header">
1️⃣ Descrierea aplicației web (Cerință intermediară)
<span class="accordion-icon">▶</span>
</div>
<div class="accordion-content">

<p>
Aplicația reprezintă o platformă web pentru gestionarea și rezervarea pachetelor turistice online.
Scopul principal este digitalizarea procesului de rezervare și administrare a ofertelor turistice.
</p>

<h3>Funcționalități principale:</h3>
<ul>
<li>Vizualizare pachete turistice</li>
<li>Filtrare și afișare detalii pachet</li>
<li>Rezervare online</li>
<li>Creare cont și autentificare</li>
<li>Panou administrator</li>
<li>Gestionare utilizatori, pachete, rezervări</li>
<li>Export și import date</li>
<li>Formular contact</li>
</ul>

<h3>Roluri în sistem:</h3>
<ul>
<li><b>Vizitator</b> – poate vizualiza pachete</li>
<li><b>Utilizator autentificat</b> – poate face rezervări și vede istoricul</li>
<li><b>Administrator</b> – gestionează întregul sistem</li>
</ul>

</div>
</div>

<!-- 2 -->
<div class="accordion-item">
<div class="accordion-header">
2️⃣ Arhitectura aplicației
<span class="accordion-icon">▶</span>
</div>
<div class="accordion-content">

<p>
Aplicația utilizează o arhitectură de tip client-server:
</p>

<ul>
<li>Frontend: HTML5, CSS3, JavaScript</li>
<li>Backend: PHP 8</li>
<li>Bază de date: MySQL (MariaDB)</li>
<li>Acces BD: PDO (Prepared Statements)</li>
</ul>

<h3>Structură logică:</h3>
<ul>
<li>Layer prezentare (UI)</li>
<li>Layer logică aplicație (PHP)</li>
<li>Layer acces date (PDO)</li>
<li>Layer persistent (MySQL)</li>
</ul>

<h3>Separarea responsabilităților:</h3>
<ul>
<li>partials/header.php – reutilizare UI</li>
<li>bootstrap.php – inițializare aplicație</li>
<li>clase dedicate pentru conexiune BD</li>
<li>separare pagini admin/user</li>
</ul>

</div>
</div>

<!-- 3 -->
<div class="accordion-item">
<div class="accordion-header">
3️⃣ Proiectare și implementare bază de date
<span class="accordion-icon">▶</span>
</div>
<div class="accordion-content">

<h3>Tabele implementate:</h3>
<ul>
<li>useri</li>
<li>clienti</li>
<li>destinatii</li>
<li>pacheteturistice</li>
<li>rezervari</li>
<li>plati</li>
<li>facturi</li>
<li>recenzii</li>
<li>promotii</li>
<li>newsletter</li>
<li>mesaje_contact</li>
<li>analytics</li>
</ul>

<h3>Relații principale:</h3>
<ul>
<li>destinatii 1 → N pacheteturistice</li>
<li>clienti 1 → N rezervari</li>
<li>pacheteturistice 1 → N rezervari</li>
<li>rezervari 1 → N plati</li>
<li>rezervari 1 → 1 facturi</li>
<li>pacheteturistice 1 → N recenzii</li>
</ul>

<p>
Toate cheile externe sunt definite la nivel de bază de date pentru menținerea integrității referențiale.
</p>

</div>
</div>

<!-- 4 -->
<div class="accordion-item">
<div class="accordion-header">
4️⃣ Implementare CRUD și reutilizare cod
<span class="accordion-icon">▶</span>
</div>
<div class="accordion-content">

<h3>Funcționalități CRUD implementate:</h3>
<ul>
<li>Create – creare utilizatori, pachete, rezervări</li>
<li>Read – afișare liste și detalii</li>
<li>Update – modificare date admin</li>
<li>Delete – ștergere în panoul admin</li>
</ul>

<h3>Reutilizare cod:</h3>
<ul>
<li>Folosire partials pentru header/footer</li>
<li>Clasă generică pentru conexiune BD</li>
<li>Folosire PDO generic pentru interogări</li>
<li>Evitarea codului duplicat</li>
</ul>

<h3>Persistență parametri:</h3>
<ul>
<li>Utilizare GET pentru navigare</li>
<li>Utilizare POST pentru formulare</li>
<li>Validare server-side</li>
</ul>

</div>
</div>

<!-- 5 -->
<div class="accordion-item">
<div class="accordion-header">
5️⃣ Autentificare și securizare acces
<span class="accordion-icon">▶</span>
</div>
<div class="accordion-content">

<h3>Mecanisme implementate:</h3>
<ul>
<li>Autentificare pe bază de sesiuni PHP</li>
<li>Hash parole (password_hash)</li>
<li>Separare roluri admin / user</li>
<li>Restricționare acces pagini admin</li>
</ul>

<h3>Protecție împotriva atacurilor:</h3>
<ul>
<li>SQL Injection – prevenit prin PDO Prepared Statements</li>
<li>XSS – prevenit prin htmlspecialchars()</li>
<li>CSRF – token validat server-side</li>
<li>Form Spoofing – validare server-side</li>
<li>HTTP Request Spoofing – verificare sesiuni</li>
<li>reCAPTCHA – protecție formulare publice</li>
</ul>

</div>
</div>

<!-- 6 -->
<div class="accordion-item">
<div class="accordion-header">
6️⃣ Integrare module externe
<span class="accordion-icon">▶</span>
</div>
<div class="accordion-content">

<ul>
<li>Export CSV (rezervări, utilizatori, pachete)</li>
<li>Export PDF rezervări</li>
<li>Import CSV utilizatori și pachete</li>
<li>Trimitere email (contact / confirmare rezervare)</li>
<li>Integrare reCAPTCHA</li>
<li>Analytics pentru monitorizare trafic</li>
<li>Optimizare SEO (meta tags, structură semantică)</li>
</ul>

</div>
</div>

<!-- 7 -->
<div class="accordion-item">
<div class="accordion-header">
7️⃣ Recomandări și bune practici aplicate
<span class="accordion-icon">▶</span>
</div>
<div class="accordion-content">

<ul>
<li>Separare clară între logică și prezentare</li>
<li>Decuplare module</li>
<li>Reutilizare componente</li>
<li>Structură extensibilă</li>
<li>Fluxuri intuitive pentru utilizatori</li>
<li>Respectarea principiilor securității web</li>
</ul>

</div>
</div>

</div>

<script>
document.querySelectorAll('.accordion-header').forEach(header => {
    header.addEventListener('click', () => {
        header.parentElement.classList.toggle('active');
    });
});
</script>

</body>
</html>
