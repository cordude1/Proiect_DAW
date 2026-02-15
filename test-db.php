<?php
try {
    // Schimbă portul în 3307 în DSN-ul de conectare din DatabaseConnector.php, apoi testează conexiunea aici:
    $pdo = new PDO(
        "mysql:host=127.0.0.1;port=3307;dbname=agentie_turism;charset=utf8mb4",
        "root",
        "",  // parola goală implicit în XAMPP
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ Conexiune reușită la baza de date agentie_turism";
} catch (PDOException $e) {
    echo "❌ Eroare DB: " . $e->getMessage();
}
