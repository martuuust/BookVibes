<?php
require_once __DIR__ . '/app/autoload.php';
use App\Core\Env;
use App\Core\Database;
use App\Models\Character;

// Load Env
Env::load(__DIR__ . '/.env');

// Clean DB for test
$db = Database::getInstance();
$db->query("DELETE FROM characters WHERE book_id IN (99991, 99992)");

// Setup Data
$bookId1 = 99991;
$bookId2 = 99992;

// Add Char to Book 1
Character::create($bookId1, [
    'name' => 'Harry Potter',
    'description' => 'Wizard',
    'traits' => ['Brave'],
    'image_url' => 'http://example.com/hp.jpg'
]);

// Add Char to Book 2
Character::create($bookId2, [
    'name' => 'Frodo Baggins',
    'description' => 'Hobbit',
    'traits' => ['Ringbearer'],
    'image_url' => 'http://example.com/frodo.jpg'
]);

// Test Fetch
echo "Fetching characters for Book 1 (Should only satisfy Harry)...\n";
$chars1 = Character::getByBookId($bookId1);

$foundError = false;
foreach ($chars1 as $c) {
    echo "Found: " . $c['name'] . "\n";
    if ($c['name'] === 'Frodo Baggins') {
        echo "ERROR: Found Frodo in Book 1 list!\n";
        $foundError = true;
    }
}

if (count($chars1) !== 1) {
    echo "ERROR: Expected 1 character, found " . count($chars1) . "\n";
    $foundError = true;
}

if (!$foundError) {
    echo "SUCCESS: Filtering works correctly at Model level.\n";
} else {
    echo "FAILURE: Filtering is broken.\n";
}

// Cleanup
$db->query("DELETE FROM characters WHERE book_id IN (99991, 99992)");
