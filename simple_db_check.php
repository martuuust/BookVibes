<?php
// Simple DB Check without heavy autoload dependencies
$env = parse_ini_file('.env');
$host = $env['DB_HOST'] ?? 'localhost';
$dbName = $env['DB_NAME'] ?? 'bookvibes';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbName", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Tables:\n";
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo "- " . $row[0] . "\n";
        
        if ($row[0] === 'books') {
            echo "  (Columns: ";
            $cols = $pdo->query("SHOW COLUMNS FROM books")->fetchAll(PDO::FETCH_COLUMN);
            echo implode(', ', $cols) . ")\n";
        }
    }
} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage();
}
