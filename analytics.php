<?php

try {
    $db  = new DatabaseConnector();
    $pdo = $db->connect();

    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $page = $_SERVER['REQUEST_URI'] ?? '';

    $stmt = $pdo->prepare("
        INSERT INTO analytics (ip, browser, page)
        VALUES (:ip, :browser, :page)
    ");

    $stmt->execute([
        ':ip' => $ip,
        ':browser' => $agent,
        ':page' => $page
    ]);

} catch (Throwable $e) {
}
