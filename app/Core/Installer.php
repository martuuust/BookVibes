<?php

namespace App\Core;

use PDO;
use PDOException;

class Installer
{
    public static function checkAndInstall()
    {
        // 1. Check if DB connection works normally
        try {
            $db = Database::getInstance();
            // Try a simple query to see if tables exist
            $db->query("SELECT 1 FROM users LIMIT 1");
            return; // All good
        } catch (\Exception $e) {
            // Connection failed or table doesn't exist.
            // Check if it is "Unknown database" or "Table doesn't exist"
            if (strpos($e->getMessage(), 'Unknown database') !== false || 
                strpos($e->getMessage(), "doesn't exist") !== false) {
                // Proceed to install
                self::install();
            } else {
                 // Other error, rethrow
                 // But for robustness in this simple app, we might just try to install anyway or log it.
            }
        }
    }

    private static function install()
    {
        // Load Env vars directly just in case (though they should be loaded)
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $dbname = getenv('DB_NAME') ?: 'bookvibes';

        try {
            // Connect without DB name to create it
            $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
            $pdo = new PDO($dsn, $user, $pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Create DB
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
            $pdo->exec("USE `$dbname`");

            // Execute SQL
            $sqlPath = __DIR__ . '/../../database.sql';
            if (file_exists($sqlPath)) {
                $sql = file_get_contents($sqlPath);
                
                // Robust split
                $queries = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($queries as $query) {
                    if (!empty($query)) {
                        try {
                            $pdo->exec($query);
                        } catch (PDOException $qe) {
                            // Ignore "table exists" errors if we are re-running
                            if (strpos($qe->getMessage(), 'already exists') === false) {
                                // Log unexpected error
                                error_log("Install Query Failed: " . $qe->getMessage());
                            }
                        }
                    }
                }
            }
            
        } catch (PDOException $e) {
            die("<h1>Error Fatal de Instalación</h1><p>No se pudo conectar a la base de datos para crearla automáticamente.</p><p>" . $e->getMessage() . "</p>");
        }
    }
}
