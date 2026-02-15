<?php
// external_content.php
// Întoarce factorul RON→EUR (ex: 1 RON * rate = EUR). Cache 1 oră în sesiune.
if (session_status() !== PHP_SESSION_ACTIVE) {
    require_once __DIR__.'/bootstrap.php';

}

function ron_to_eur_rate(): ?float {
    // Cache 1h
    if (
        isset($_SESSION['rate_ron_eur']['v'], $_SESSION['rate_ron_eur']['ts']) &&
        (time() - (int)$_SESSION['rate_ron_eur']['ts'] < 3600)
    ) {
        return (float) $_SESSION['rate_ron_eur']['v'];
    }

    // Încearcă 2 API-uri publice. Necesită ext/curl activată în PHP.
    $urls = [
        'https://api.exchangerate.host/latest?base=RON&symbols=EUR',
        'https://open.er-api.com/v6/latest/RON',
    ];

    foreach ($urls as $u) {
        $ch = curl_init($u);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 6,
        ]);
        $raw = curl_exec($ch);
        curl_close($ch);
        if ($raw === false) continue;

        $j = json_decode($raw, true);
        $rate = null;

        // exchangerate.host
        if (isset($j['rates']['EUR'])) {
            $rate = (float)$j['rates']['EUR'];
        }
        // open.er-api.com
        if ($rate === null && isset($j['result']) && $j['result']==='success' && isset($j['rates']['EUR'])) {
            $rate = (float)$j['rates']['EUR'];
        }

        if ($rate !== null && $rate > 0) {
            $_SESSION['rate_ron_eur'] = ['v' => $rate, 'ts' => time()];
            return $rate;
        }
    }

    // Fallback dacă nu merge internetul/cURL: setează manual un curs aproximativ
    $fallback = 0.20; // ~ 1 RON ≈ 0.20 EUR (ajustează după preferință)
    $_SESSION['rate_ron_eur'] = ['v' => $fallback, 'ts' => time()];
    return $fallback;
}
