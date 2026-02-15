<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php';


require_once __DIR__.'/config/env.php';
require_once __DIR__.'/path.php';


/* Conectare DB (local pe 3307) */
$database = new DatabaseConnector();
$pdo = $database->connect();

/* Interogare pachete + destinații */
$sql = "
    SELECT p.*, d.nume AS destinatie, d.tara
    FROM pacheteturistice p
    JOIN destinatii d ON p.id_destinatie = d.id_destinatie
";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$pachete = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Curs RON->EUR (sursă externă) */
require_once __DIR__ . '/external_content.php';
$rate = ron_to_eur_rate(); // float|false|null

$pdo = null;
?>
<!DOCTYPE html>
<html lang="ro">
<head>
  <?php $pageTitle='Agenție Turism - Acasă'; include __DIR__.'/partials/head.php'; ?>
  <style>.price-eur{margin-top:-6px;color:#555}</style>
</head>
<body>

<?php include __DIR__.'/partials/header.php'; ?>

<div class="content">
  <h1 class="pulse-title">Rezervă-ți Vacanța Acum</h1>

  <div class="pachete-container">
    <?php if (!empty($pachete)): ?>
      <?php foreach ($pachete as $pachet): ?>
        <?php
          // Siguranță la afișare
          $nume  = htmlspecialchars((string)($pachet['nume'] ?? 'Pachet'));
          $dest  = htmlspecialchars((string)($pachet['destinatie'] ?? ''));
          $tara  = htmlspecialchars((string)($pachet['tara'] ?? ''));

          // Numeric: NU folosim htmlspecialchars pe int/float
          $pretRon = isset($pachet['pret']) && is_numeric($pachet['pret'])
            ? number_format((float)$pachet['pret'], 2)
            : htmlspecialchars((string)($pachet['pret'] ?? '')); // fallback dacă vine ca text

          $durataZile = isset($pachet['durata']) && $pachet['durata'] !== ''
            ? (int)$pachet['durata']
            : null;

          $eurVal = ($rate && isset($pachet['pret']) && is_numeric($pachet['pret']))
            ? number_format((float)$pachet['pret'] * (float)$rate, 2)
            : null;

          $idP = (int)($pachet['id_pachet'] ?? 0);
        ?>
        <div class="pachet-card">
          <h3><?= $nume; ?></h3>
          <p><strong>Destinație:</strong> <?= $dest ?><?= $tara !== '' ? ', '.$tara : '' ?></p>

          <p><strong>Preț:</strong> <?= $pretRon ?> RON</p>
          <?php if ($eurVal !== null): ?>
            <p class="price-eur"><strong>~ <?= $eurVal ?> EUR</strong> (curs live)</p>
          <?php endif; ?>

          <p><strong>Durată:</strong> <?= $durataZile !== null ? ($durataZile . ' zile') : '—' ?></p>

          <a href="<?= url('rezervare.php') ?>?id_pachet=<?= $idP ?>&destinatie=<?= urlencode($dest) ?>&pret=<?= isset($pachet['pret']) ? (float)$pachet['pret'] : 0 ?>&durata=<?= $durataZile ?? 0 ?>">
            Rezervă Acum
          </a>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>Nu sunt disponibile pachete turistice.</p>
    <?php endif; ?>
  </div>
</div>

<?php include __DIR__.'/partials/footer.php'; ?>
</body>
</html>
