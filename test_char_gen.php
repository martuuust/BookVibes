<?php

require_once __DIR__ . '/app/autoload.php';

use App\Services\CharacterGenerator;

$gen = new CharacterGenerator();

// Test 1: Spanish Title
$titleES = 'La sombra del viento';
echo "Testing '$titleES'...\n";
$bookDataES = [
    'title' => $titleES,
    'author' => 'Carlos Ruiz Zafón'
];

try {
    $chars = $gen->getCharacterList($bookDataES);
    echo "Found " . count($chars) . " characters for '$titleES'.\n";
    foreach (array_slice($chars, 0, 5) as $c) {
        echo "- " . $c['name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error testing '$titleES': " . $e->getMessage() . "\n";
}

echo "\n---------------------------------------------------\n\n";

// Test 2: English Title
$titleEN = 'The Shadow of the Wind';
echo "Testing '$titleEN'...\n";
$bookDataEN = [
    'title' => $titleEN,
    'author' => 'Carlos Ruiz Zafón'
];

try {
    $chars = $gen->getCharacterList($bookDataEN);
    echo "Found " . count($chars) . " characters for '$titleEN'.\n";
    foreach (array_slice($chars, 0, 5) as $c) {
        echo "- " . $c['name'] . "\n";
    }
} catch (Exception $e) {
    echo "Error testing '$titleEN': " . $e->getMessage() . "\n";
}
