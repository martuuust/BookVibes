<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct()
    {
        $config =  [
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => !empty($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : '3306',
            'user' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
            'dbname' => $_ENV['DB_NAME'] ?? 'bookvibes',
        ];

        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset=utf8mb4";

        // Connect
        try {
            $this->pdo = new PDO($dsn, $config['user'], $config['password']);
        } catch (PDOException $e) {
            // If database does not exist, try to create it
            if ($e->getCode() === 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                $dsnNoDb = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
                $tempPdo = new PDO($dsnNoDb, $config['user'], $config['password']);
                $tempPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                
                // Retry connection
                $this->pdo = new PDO($dsn, $config['user'], $config['password']);
            } else {
                throw $e;
            }
        }

        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    // Helper to prepare and execute
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Get the last inserted ID
    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }
}
