<?php
/**
 * Database.php — Lidhja PDO me MySQL/MariaDB.
 * Kredencialet lexohen VETËM nga mjedisi (.env). Asnjë sekret i hardkoduar.
 */
declare(strict_types=1);

class Database
{
    private ?PDO $conn = null;

    public function connect(): PDO
    {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        $host    = (string) env('DB_HOST', '127.0.0.1');
        $port    = (string) env('DB_PORT', '3306');
        $dbName  = (string) env('DB_NAME', 'albsale-vlora');
        $user    = (string) env('DB_USER', '');
        $pass    = (string) env('DB_PASS', '');
        $charset = (string) env('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";

        try {
            $this->conn = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // prepared statements të vërteta
            ]);
        } catch (PDOException $e) {
            // Mos ekspozo kurrë detajet e lidhjes te përdoruesi.
            error_log('DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['message' => 'Database unavailable'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        return $this->conn;
    }

    /** Alias historik për kompatibilitet me kodin e vjetër. */
    public function getConnection(): PDO
    {
        return $this->connect();
    }
}
