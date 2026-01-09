<?php
/**
 * Diagnostic script to test BookMapService
 * Run: php diagnose_map.php "Book Title" "Author Name"
 */

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

echo "=== BookMapService Diagnostic ===\n\n";

// Check API Keys
$groqKey = getenv('GROQ_API_KEY') ?: '';
$geminiKey = getenv('GEMINI_API_KEY') ?: '';

echo "1. API KEYS STATUS:\n";
echo "   GROQ_API_KEY: " . (empty($groqKey) ? "❌ NOT SET" : "✅ Set (" . strlen($groqKey) . " chars)") . "\n";
echo "   GEMINI_API_KEY: " . (empty($geminiKey) ? "❌ NOT SET" : "✅ Set (" . strlen($geminiKey) . " chars)") . "\n\n";

// Get book title from argument or use default
$title = $argv[1] ?? "Érase una vez un corazón roto";
$author = $argv[2] ?? "Stephanie Garber";

echo "2. TESTING BOOK: \"$title\" by $author\n\n";

// Test external context fetching
echo "3. FETCHING EXTERNAL CONTEXT...\n";

// Wikipedia EN
$bookQuery = urlencode("$title novel");
$wikiUrl = "https://en.wikipedia.org/api/rest_v1/page/summary/" . $bookQuery;
$wikiData = fetchUrl($wikiUrl);
echo "   Wikipedia EN: " . ($wikiData && isset($wikiData['extract']) ? "✅ Found" : "❌ Not found") . "\n";
if ($wikiData && isset($wikiData['extract'])) {
    echo "   -> " . substr($wikiData['extract'], 0, 200) . "...\n";
}

// Wikipedia ES
$esQuery = urlencode("$title libro");
$esUrl = "https://es.wikipedia.org/api/rest_v1/page/summary/" . $esQuery;
$esData = fetchUrl($esUrl);
echo "   Wikipedia ES: " . ($esData && isset($esData['extract']) ? "✅ Found" : "❌ Not found") . "\n";
if ($esData && isset($esData['extract'])) {
    echo "   -> " . substr($esData['extract'], 0, 200) . "...\n";
}

// Open Library
$olUrl = "https://openlibrary.org/search.json?title=" . urlencode($title) . "&limit=1";
$olData = fetchUrl($olUrl);
$olDoc = $olData['docs'][0] ?? null;
echo "   Open Library: " . ($olDoc ? "✅ Found" : "❌ Not found") . "\n";
if ($olDoc) {
    if (!empty($olDoc['place'])) echo "   -> Places: " . implode(', ', array_slice($olDoc['place'], 0, 5)) . "\n";
    if (!empty($olDoc['subject'])) echo "   -> Subjects: " . implode(', ', array_slice($olDoc['subject'], 0, 5)) . "\n";
}

// Google Books
$gbUrl = "https://www.googleapis.com/books/v1/volumes?q=" . urlencode("intitle:$title+inauthor:$author") . "&maxResults=1";
$gbData = fetchUrl($gbUrl);
$gbItem = $gbData['items'][0]['volumeInfo'] ?? null;
echo "   Google Books: " . ($gbItem ? "✅ Found" : "❌ Not found") . "\n";
if ($gbItem && !empty($gbItem['description'])) {
    echo "   -> Description: " . substr($gbItem['description'], 0, 300) . "...\n";
}

echo "\n4. TESTING AI APIS...\n";

// Build a simple test prompt
$testPrompt = "Para el libro '$title' de $author, devuelve SOLO un JSON con la ubicación principal donde ocurre la historia. Formato: {\"location\": \"nombre de la ciudad/país\", \"lat\": 0.0, \"lng\": 0.0}";

// Test Groq
if (!empty($groqKey)) {
    echo "   Testing GROQ API...\n";
    $groqResult = testGroqApi($groqKey, $testPrompt);
    echo "   GROQ: " . ($groqResult['success'] ? "✅ " . $groqResult['response'] : "❌ " . $groqResult['error']) . "\n";
} else {
    echo "   GROQ: ⏭️ Skipped (no API key)\n";
}

// Test Gemini
if (!empty($geminiKey)) {
    echo "   Testing GEMINI API...\n";
    $geminiResult = testGeminiApi($geminiKey, $testPrompt);
    echo "   GEMINI: " . ($geminiResult['success'] ? "✅ " . $geminiResult['response'] : "❌ " . $geminiResult['error']) . "\n";
} else {
    echo "   GEMINI: ⏭️ Skipped (no API key)\n";
}

echo "\n=== Diagnostic Complete ===\n";

// Helper functions
function fetchUrl($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'BookVibes/1.0');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300 && $result) {
        return json_decode($result, true);
    }
    return null;
}

function testGroqApi($apiKey, $prompt) {
    $url = 'https://api.groq.com/openai/v1/chat/completions';
    $data = [
        "model" => "llama3-70b-8192",
        "messages" => [
            ["role" => "system", "content" => "Respond only with valid JSON."],
            ["role" => "user", "content" => $prompt]
        ],
        "response_format" => ["type" => "json_object"],
        "temperature" => 0.3
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) return ['success' => false, 'error' => "Curl: $error"];
    if ($httpCode >= 400) {
        $json = json_decode($result, true);
        return ['success' => false, 'error' => "HTTP $httpCode: " . ($json['error']['message'] ?? $result)];
    }
    
    $json = json_decode($result, true);
    $content = $json['choices'][0]['message']['content'] ?? '';
    return ['success' => true, 'response' => substr($content, 0, 200)];
}

function testGeminiApi($apiKey, $prompt) {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;
    $data = [
        "contents" => [["parts" => [["text" => $prompt]]]],
        "generationConfig" => ["response_mime_type" => "application/json", "temperature" => 0.3]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) return ['success' => false, 'error' => "Curl: $error"];
    if ($httpCode >= 400) {
        $json = json_decode($result, true);
        return ['success' => false, 'error' => "HTTP $httpCode: " . ($json['error']['message'] ?? $result)];
    }
    
    $json = json_decode($result, true);
    $content = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
    return ['success' => true, 'response' => substr($content, 0, 200)];
}
