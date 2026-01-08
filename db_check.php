<?php
require_once __DIR__ . '/app/autoload.php';
\App\Core\Env::load(__DIR__ . '/.env');

try {
    $db = \App\Core\Database::getInstance();
    echo "Connected to DB\n";
    
    // List tables
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
    
    // Check columns in books table
    $columns = $db->query("SHOW COLUMNS FROM books")->fetchAll(PDO::FETCH_COLUMN);
    echo "Books Columns: " . implode(', ', $columns) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
