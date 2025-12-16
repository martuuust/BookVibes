<?php
require_once __DIR__ . '/app/autoload.php';
use App\Core\Env;
use App\Core\Database;
use App\Models\Book;
use App\Models\Character;
use App\Services\CharacterGenerator;

Env::load(__DIR__ . '/.env');
$db = Database::getInstance();
$books = $db->query("SELECT * FROM books")->fetchAll();
$gen = new CharacterGenerator();

foreach ($books as $b) {
    $data = [
        'title' => $b['title'],
        'author' => $b['author'],
        'synopsis' => $b['synopsis'] ?? '',
        'genre' => $b['genre'] ?? '',
        'mood' => $b['mood'] ?? '',
        'image_url' => $b['cover_url'] ?? ''
    ];
    $chars = $gen->generateCharacters($data);
    Character::deleteByBookId($b['id']);
    foreach ($chars as $c) {
        Character::create($b['id'], $c);
    }
    echo "Refreshed characters for: " . $b['title'] . PHP_EOL;
}
echo "Done." . PHP_EOL;
