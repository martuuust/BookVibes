<?php
/**
 * Clear map cache for all books
 * This forces regeneration of map data with the improved context system
 * 
 * Run: php clear_map_cache.php
 */

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            putenv(trim($key) . "=" . trim($value, " \t\n\r\0\x0B\"'"));
        }
    }
}

// Database connection
$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'bookvibes';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== BookVibes Map Cache Cleaner ===\n\n";
    
    // Check how many books have cached maps
    $count = $pdo->query("SELECT COUNT(*) as total FROM books WHERE map_data IS NOT NULL AND map_data != ''")->fetch(PDO::FETCH_ASSOC);
    echo "Books with cached map data: " . ($count['total'] ?? 0) . "\n";
    
    // Clear all map caches
    $stmt = $pdo->exec("UPDATE books SET map_data = NULL");
    echo "Cleared map cache for all books.\n\n";
    
    // List books that will regenerate maps on next view
    $books = $pdo->query("SELECT id, title, author FROM books ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    echo "Books ready for map regeneration:\n";
    foreach ($books as $book) {
        echo "  - [{$book['id']}] {$book['title']} by {$book['author']}\n";
    }
    
    echo "\nDone! Maps will regenerate when users view each book.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
