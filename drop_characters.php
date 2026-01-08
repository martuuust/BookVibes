<?php
// Drop characters table
$env = parse_ini_file('.env');
$host = $env['DB_HOST'] ?? 'localhost';
$dbName = $env['DB_NAME'] ?? 'bookvibes';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbName", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Dropping 'characters' table...\n";
    $pdo->exec("DROP TABLE IF EXISTS characters");
    echo "✅ Table 'characters' dropped successfully.\n";
    
} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage();
}
