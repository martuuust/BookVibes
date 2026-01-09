<?php
/**
 * Test script for BookMapService
 * Run: php test_map.php
 */

// Include the service class directly
require_once __DIR__ . '/app/Services/BookMapService.php';

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

use App\Services\BookMapService;

$title = "Binding 13";
$author = "Chloe Walsh";

echo "=== Testing BookMapService ===\n";
echo "Book: $title by $author\n";
echo str_repeat("=", 50) . "\n\n";

$service = new BookMapService();

// Test fetching context (via reflection to access private method)
$reflection = new ReflectionClass($service);
$fetchMethod = $reflection->getMethod('fetchWikipediaContext');
$fetchMethod->setAccessible(true);

echo "FETCHING CONTEXT FROM SOURCES...\n";
echo str_repeat("-", 50) . "\n";

$context = $fetchMethod->invoke($service, $title, $author);

if (!empty($context)) {
    echo $context;
} else {
    echo "(No context found from external sources)\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "GENERATING MAP DATA...\n";
echo str_repeat("-", 50) . "\n";

$mapData = $service->generateMapData($title, $author);

if ($mapData) {
    echo "\nMAP GENERATED SUCCESSFULLY!\n\n";
    echo "Region: " . ($mapData['map_config']['region_name'] ?? 'N/A') . "\n";
    echo "Center: " . ($mapData['map_config']['center_coordinates']['lat'] ?? 0) . ", " . ($mapData['map_config']['center_coordinates']['lng'] ?? 0) . "\n";
    echo "Zoom: " . ($mapData['map_config']['zoom_level'] ?? 12) . "\n\n";
    
    echo "MARKERS (" . count($mapData['markers']) . "):\n";
    foreach ($mapData['markers'] as $i => $marker) {
        echo "\n  " . ($i + 1) . ". " . ($marker['title'] ?? 'Unknown') . "\n";
        echo "     Coords: " . ($marker['coordinates']['lat'] ?? 0) . ", " . ($marker['coordinates']['lng'] ?? 0) . "\n";
        echo "     " . ($marker['snippet'] ?? '') . "\n";
        echo "     " . ($marker['chapter_context'] ?? 'N/A') . " | Type: " . ($marker['location_type'] ?? 'event') . " | Importance: " . ($marker['importance'] ?? 'medium') . "\n";
    }
} else {
    echo "\nFAILED TO GENERATE MAP DATA\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Test complete.\n";
