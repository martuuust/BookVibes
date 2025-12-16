<?php
require_once __DIR__ . '/app/autoload.php';
use App\Core\Env;
use App\Core\Database;
use App\Models\UserBook;

Env::load(__DIR__ . '/.env');
$db = Database::getInstance();

$userId = 1; // Assuming 'marta' is the main user

echo "Linking all orphan books to User ID $userId...\n";
$books = $db->query("SELECT id, title FROM books")->fetchAll();

$count = 0;
foreach ($books as $b) {
    if (UserBook::add($userId, $b['id'])) {
        echo "Linked: {$b['title']}\n";
        $count++;
    } else {
        echo "Already Linked: {$b['title']}\n";
    }
}

echo "Done. Linked $count new books.\n";
