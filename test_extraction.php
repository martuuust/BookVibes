<?php
require_once __DIR__ . '/app/Services/CharacterGenerator.php';

use App\Services\CharacterGenerator;

$generator = new CharacterGenerator();

// Mock Data simulating a book with marketing fluff
$bookData = [
    'title' => 'Harry Potter and the Philosopher\'s Stone',
    'author' => 'J.K. Rowling', // Should be filtered
    'synopsis' => 'Harry Potter is a wizard. He lives with the Dursleys. Fans of Lord of the Rings will love this.',
    'full_data' => [
        'description' => 'A New York Times Best Seller by J.K. Rowling. This book about Harry Potter and Hermione Granger introduces a new world. Copyright J.K. Rowling 2000.'
    ]
];

echo "Testing Extraction Logic...\n";
$chars = $generator->generateCharacters($bookData);

echo "Found " . count($chars) . " characters:\n";
foreach ($chars as $c) {
    echo "- " . $c['name'] . "\n";
}

// Validation
$names = array_column($chars, 'name');
if (in_array('Harry Potter', $names) && !in_array('J.K. Rowling', $names) && !in_array('New York', $names)) {
    echo "\nSUCCESS: Logic filtered Author and Marketing terms.\n";
} else {
    echo "\nFAILURE: Irrelevant terms found or Main Character missing.\n";
    print_r($names);
}
