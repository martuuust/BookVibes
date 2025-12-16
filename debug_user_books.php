<?php
require_once __DIR__ . '/app/autoload.php';
use App\Core\Env;
use App\Core\Database;

Env::load(__DIR__ . '/.env');
$db = Database::getInstance();

echo "--- Users ---\n";
$users = $db->query("SELECT * FROM users")->fetchAll();
foreach ($users as $u) {
    echo "ID: {$u['id']} - {$u['username']} - {$u['email']}\n";
}

echo "\n--- User Books Linkage ---\n";
$links = $db->query("SELECT * FROM user_books")->fetchAll();
if (empty($links)) {
    echo "NO BOOKS LINKED TO ANY USER!\n";
} else {
    foreach ($links as $l) {
        echo "Link ID: {$l['id']} -> User {$l['user_id']} has Book {$l['book_id']}\n";
    }
}

echo "\n--- Total Books ---\n";
$count = $db->query("SELECT Count(*) as c FROM books")->fetch()['c'];
echo "Total Books in DB: $count\n";
