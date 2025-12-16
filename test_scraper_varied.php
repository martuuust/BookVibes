<?php
require_once __DIR__ . '/app/Services/ScraperService.php';

use App\Services\ScraperService;

$scraper = new ScraperService();

// Test 1: Harry Potter (Should work via Google)
echo "--- Test 1: Harry Potter ---\n";
$data = $scraper->scrapeBook('Harry Potter y la piedra filosofal');
echo "Title: " . $data['title'] . "\n";
echo "Source Hint: " . (strpos($data['synopsis'], 'Harry') !== false ? 'Valid' : 'Invalid') . "\n\n";

// Test 2: Don Quijote (Classic)
echo "--- Test 2: Don Quijote ---\n";
$data2 = $scraper->scrapeBook('Don Quijote de la Mancha');
echo "Title: " . $data2['title'] . "\n";
echo "Synopsis Length: " . strlen($data2['synopsis']) . "\n";

