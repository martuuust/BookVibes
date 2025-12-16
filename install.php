<?php

require_once __DIR__ . '/app/autoload.php';

use App\Core\Env;
use App\Core\Database;

// Load Env
Env::load(__DIR__ . '/.env');

echo "---------------------------------\n";
echo "BookVibes Installer (No Composer)\n";
echo "---------------------------------\n";

// 1. Create DB if not exists
$host = getenv('DB_HOST') ?: '127.0.0.1';
$port = getenv('DB_PORT') ?: '3306';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'bookvibes';

echo "Conectando a MySQL para verificar base de datos '$dbname'...\n";

try {
    $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "Base de datos verificada/creada.\n";
    
} catch (PDOException $e) {
    die("Error crítico al conectar con MySQL: " . $e->getMessage() . "\nVerifica tus credenciales en .env\n");
}

// 2. Install Tables
echo "Instalando tablas...\n";
try {
    // Now assume Database class works because DB exists
    $db = Database::getInstance();
    $sql = file_get_contents(__DIR__ . '/database.sql');
    
    // Split queries roughly by semicolon to ensure execution if driver doesn't support multi
    // But since we are native mysql pdo, we can try robust split
    $queries = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($queries as $query) {
        if (!empty($query)) {
            $db->getConnection()->exec($query);
        }
    }
    
    echo "Tablas instaladas correctamente.\n";
} catch (Exception $e) {
    die("Error al instalar tablas: " . $e->getMessage() . "\n");
}

echo "Instalación completada. Puedes ejecutar el servidor con:\n";
echo "php -S localhost:8000 -t public\n";
