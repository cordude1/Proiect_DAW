<?php
declare(strict_types=1);
class DatabaseConnector
{
    private string $host;
    private string $user;
    private string $pass;
    private string $db;
    private int    $port;
    private ?PDO   $pdo = null;

    /**
     * Poți trece parametrii manual (compat cu codul vechi),
     * sau îi ia automat din .env (prin getenv()).
     */
    public function __construct(
        ?string $host = null,
        ?string $user = null,
        ?string $pass = null,
        ?string $db   = null,
        ?int    $port = null
    ) {
        $this->host = $host ?? (getenv('DB_HOST') ?: '127.0.0.1');
        $this->user = $user ?? (getenv('DB_USER') ?: 'root');
        $this->pass = $pass ?? (getenv('DB_PASS') ?: '');
        $this->db   = $db   ?? (getenv('DB_NAME') ?: 'agentie_turism');
        $this->port = $port ?? (int) (getenv('DB_PORT') ?: 3307);
    }

    public function connect(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $this->host,
            $this->port,
            $this->db
        );

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            if ((getenv('APP_ENV') ?: 'dev') === 'prod') {
                @ini_set('display_errors', '0');
                @ini_set('log_errors', '1');
                $logDir = __DIR__ . '/storage';
                if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
                @ini_set('error_log', $logDir . '/php-error.log');
                @ini_set('session.cookie_httponly', '1');
                @ini_set('session.cookie_samesite', 'Lax');
            }

            return $this->pdo;

        } catch (PDOException $e) {
            $isProd = ((getenv('APP_ENV') ?: 'dev') === 'prod');
            $msg = $isProd
                ? 'Eroare la conectarea la baza de date.'
                : 'Eroare la conectarea la baza de date: ' . $e->getMessage();

            error_log('[DB] ' . $e->getMessage());
            die($msg);
        }
    }
}
