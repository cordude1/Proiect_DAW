<?php
// partials/head.php
require_once dirname(__DIR__).'/config/env.php';
require_once dirname(__DIR__).'/path.php';

if (!isset($pageTitle) || $pageTitle === '') {
  $pageTitle = 'Agenție de Turism';
}
?>
<meta charset="UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta property="og:title" content="Agenție Turism - Vacanțe de vis">
<meta property="og:description" content="Rezervă acum pachete turistice la prețuri bune!">
<meta property="og:image" content="<?= asset('images/hero.jpg') ?>">

<title><?= htmlspecialchars($pageTitle) ?></title>

<!-- Doar stilurile publice -->
<link rel="stylesheet" href="<?= asset('style.css') ?>"/>
<link rel="stylesheet" href="<?= asset('back_button.css') ?>"/>

<script>window.APP_BASE = <?= json_encode(BASE_URL_PATH) ?>;</script>
