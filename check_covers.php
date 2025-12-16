<?php
require_once __DIR__ . '/app/autoload.php';
use App\Core\Env;
use App\Core\Database;

Env::load(__DIR__ . '/.env');
$db = Database::getInstance();

$books = $db->query("SELECT id, title, cover_url FROM books")->fetchAll();

echo "Checking " . count($books) . " books for cover URLs...\n";
foreach ($books as $b) {
    if (empty($b['cover_url'])) {
        echo "[MISSING] ID: {$b['id']} - Title: {$b['title']} has NO cover URL.\n";
    } else {
        echo "[OK] ID: {$b['id']} - {$b['title']} - {$b['cover_url']}\n";
    }
}
