<?php
// app/routes/api.php

use App\Core\Router;

/** @var Router $router */

$router->get('/api/status', function() {
    header('Content-Type: application/json');
    return json_encode(['status' => 'ok', 'message' => 'API is running']);
});

$router->get('/api/books', [App\Controllers\ApiController::class, 'getBooks']);
$router->get('/api/books/detail', [App\Controllers\ApiController::class, 'getBookDetails']); // Uses ?id=
$router->get('/api/playlists', [App\Controllers\ApiController::class, 'getPlaylist']); // Uses ?id= (book_id)

