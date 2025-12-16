<?php
// api_client_test.php

// This script simulates an external client consuming the BookVibes API.
// Run it with: php api_client_test.php

$baseUrl = 'http://localhost:8000/api';

function callApi($endpoint) {
    global $baseUrl;
    $url = $baseUrl . $endpoint;
    echo "Requesting: GET $url ...\n";
    
    // Use file_get_contents as a simple HTTP client
    $options = [
        "http" => [
            "method" => "GET",
            "header" => "Accept: application/json\r\n"
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "❌ Error: Could not connect to API. Is the server running?\n\n";
        return;
    }
    
    echo "✅ Status: 200 OK\n";
    echo "Response Snippet:\n";
    $data = json_decode($response, true);
    print_r(array_slice($data['data'] ?? $data, 0, 2)); // Show partial data
    echo "\n---------------------------------------------------\n\n";
}

echo "========================================\n";
echo "   BookVibes API Interoperability Test  \n";
echo "========================================\n\n";

// 1. Check Status
callApi('/status');

// 2. Get All Books
callApi('/books');

// 3. Get Specific Book Details (Assuming ID 1 exists, otherwise it might perform empty)
// Note: You need to have added a book in the UI first for this to show data.
callApi('/books/detail?id=1');

// 4. Get Playlist for Book 1
callApi('/playlists?id=1');

// 5. Get Characters for Book 1
callApi('/characters?id=1');

echo "Test Completed.\n";
