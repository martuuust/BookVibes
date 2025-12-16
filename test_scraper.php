<?php
require_once __DIR__ . '/app/Services/ScraperService.php';

use App\Services\ScraperService;

$scraper = new ScraperService();
$query = 'Harry Potter';
echo "Testing Scraper for: $query\n";
$data = $scraper->scrapeBook($query);

echo "Title: " . $data['title'] . "\n";
echo "Synopsis Length: " . strlen($data['synopsis']) . "\n";
echo "Synopsis: " . substr($data['synopsis'], 0, 100) . "...\n";

if (strpos($data['synopsis'], 'Una historia fascinante') !== false) {
    echo "RESULT: FALLBACK DETECTED (Fake Data)\n";
} else {
    echo "RESULT: REAL DATA (Presumably)\n";
}
