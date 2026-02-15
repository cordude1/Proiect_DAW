<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once dirname(__DIR__).'/config/env.php';
require_once dirname(__DIR__).'/path.php';

$loggedIn = isset($_SESSION['user_id']);
$isAdmin  = (($_SESSION['rol'] ?? '') === 'admin') || (($_SESSION['role'] ?? '') === 'admin');
?>
<header class="site-header">

<?php if ($loggedIn): ?>
<nav class="navbar-admin">

  <!-- HOME -->
  <a class="home-link" href="<?= url('index.php') ?>">
    <span>Home</span>
  </a>

  <ul id="main-menu">

    <?php if ($isAdmin): ?>

      <!-- 🔵 DASHBOARD TAB -->
      <li>
        <a href="<?= url('adminDashboard.php') ?>" class="toplink admin-tab">
          Dashboard Administrator
        </a>
      </li>

      <!-- 🟣 DOCUMENTAȚIE TEHNICĂ TAB -->
      <li>
        <a href="<?= url('documentatieTehnica.php') ?>" class="toplink doc-tab">
          Documentație Tehnică
        </a>
      </li>

      <!-- ADMIN DROPDOWN -->
      <li class="dropdown">
        <a href="#" class="toplink dropdown-toggle">Administrare</a>
        <ul class="dropdown-content">

          <li class="dropdown">
            <a href="#" class="dropdown-toggle">Utilizatori</a>
            <ul class="dropdown-content">
              <li><a href="<?= url('administrareUser.php') ?>">Administrare utilizatori</a></li>
              <li><a href="<?= url('createUser.php') ?>">Adaugă utilizator</a></li>
              <li><a href="<?= url('administrareUser.php') ?>#import">Importă utilizatori (CSV)</a></li>
            </ul>
          </li>

          <li class="dropdown">
            <a href="#" class="dropdown-toggle">Pachete turistice</a>
            <ul class="dropdown-content">
              <li><a href="<?= url('administrarePachete.php') ?>">Administrare pachete</a></li>
              <li><a href="<?= url('creazaPacheteTuristice.php') ?>">Adaugă pachet</a></li>
              <li><a href="<?= url('administrarePachete.php') ?>#import">Importă pachete (CSV)</a></li>
            </ul>
          </li>

          <li class="dropdown">
            <a href="#" class="dropdown-toggle">Rezervări</a>
            <ul class="dropdown-content">
              <li><a href="<?= url('manageReservations.php') ?>">Administrare rezervări</a></li>
              <li><a href="<?= url('manageReservations.php') ?>#import">Importă rezervări (CSV)</a></li>
            </ul>
          </li>

        </ul>
      </li>

    <?php else: ?>

      <li class="dropdown">
        <a href="#" class="toplink dropdown-toggle">Contul Meu</a>
        <ul class="dropdown-content">
          <li><a href="<?= url('contulMeu.php') ?>?tab=profil">Profil</a></li>
          <li><a href="<?= url('contulMeu.php') ?>?tab=rezervari">Rezervările mele</a></li>
        </ul>
      </li>

    <?php endif; ?>

  </ul>

  <div class="spacer"></div>

  <a class="btn-logout" href="<?= url('logout.php') ?>">
    Logout
  </a>

</nav>

<script>
(function(){
  document.querySelectorAll('.navbar-admin .dropdown-toggle')
    .forEach(tg => tg.addEventListener('click', function(e){
      e.preventDefault();
      this.parentElement.classList.toggle('open');
    }));

  document.addEventListener('click', function(ev){
    if(!ev.target.closest('.navbar-admin')){
      document.querySelectorAll('.navbar-admin .dropdown.open')
        .forEach(el=>el.classList.remove('open'));
    }
  });
})();
</script>

<?php else: ?>

<div class="navbar">
  <ul>
    <li><a href="<?= url('index.php') ?>">Home</a></li>
    <li><a href="<?= url('login.php') ?>">Login</a></li>
    <li><a href="<?= url('register.php') ?>">Înregistrare</a></li>
    <!-- în navbar ne-logat -->
<li><a href="<?= url('contact.php') ?>">Contact</a></li>

<!-- sau în dropdown Contul Meu / Administrare -->
  </ul>
</div>

<?php endif; ?>

</header>
