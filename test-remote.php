<?php
try {
  $pdo = new PDO(
    "mysql:host=agentieturism.hstn.me;port=3306;dbname=agentie_turism;charset=utf8mb4",
    "mseet_38324991",
    "S7iqkgpFtdcU",
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]
  );
  echo "Conexiune REMOTE OK!";
} catch (PDOException $e) {
  echo "Eroare: ".$e->getMessage();
  $dsn = "mysql:host=agentieturism.hstn.me;port=3306;dbname=agentie_turism;charset=utf8mb4";

}
