<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Database;

$db = Database::getInstance();

$sql = "
CREATE TABLE IF NOT EXISTS characters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    book_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    role TEXT DEFAULT 'Unknown',
    source TEXT DEFAULT 'Manual',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE
);
";

try {
    $db->query($sql);
    echo "Table 'characters' created successfully.\n";
} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
