<?php
// app/routes/web.php

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\BookController;

/** @var Router $router */

$router->get('/', function() {
    return "<h1>Welcome to BookVibes</h1><p>Running on Custom MVC</p>";
});

$router->get('/', function() {
    return "<div style='text-align:center; padding: 50px;'><h1>Welcome to BookVibes</h1><a href='/login'>Login</a> | <a href='/register'>Register</a></div>";
});

// Auth Routes
$router->get('/login', [App\Controllers\AuthController::class, 'login']);
$router->post('/login', [App\Controllers\AuthController::class, 'login']);
$router->get('/register', [App\Controllers\AuthController::class, 'register']);
$router->post('/register', [App\Controllers\AuthController::class, 'register']);
$router->get('/logout', [App\Controllers\AuthController::class, 'logout']);

// Dashboard
$router->get('/dashboard', [App\Controllers\BookController::class, 'index']);

// Book Routes
$router->get('/books/search', [App\Controllers\BookController::class, 'search']);
$router->post('/books/search', [App\Controllers\BookController::class, 'processSearch']);
$router->get('/books/show', [App\Controllers\BookController::class, 'show']);
$router->post('/books/delete', [App\Controllers\BookController::class, 'delete']);
$router->get('/books/refresh', [App\Controllers\BookController::class, 'refresh']);
$router->post('/books/avatar', [App\Controllers\BookController::class, 'avatar']);

// New Book Upload Routes
$router->get('/books/upload', [App\Controllers\BookController::class, 'upload']);
$router->post('/books/storeUpload', [App\Controllers\BookController::class, 'storeUpload']);

$router->get('/spotify/connect', [App\Controllers\BookController::class, 'spotifyConnect']);
$router->get('/spotify/callback', [App\Controllers\BookController::class, 'spotifyCallback']);
$router->get('/bookvibes/callback', [App\Controllers\BookController::class, 'spotifyCallback']);
$router->get('/spotify/create', [App\Controllers\BookController::class, 'spotifyCreate']);

// Pro Upgrade (simple stub)
$router->get('/pro/upgrade', function() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return "<!DOCTYPE html>
    <html lang='es'><head><meta charset='UTF-8'><title>Activa Pro - BookVibes</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap' rel='stylesheet'>
    <style>
      body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background: radial-gradient(800px 400px at 0% 0%, #eef2ff 10%, #ffffff 60%), linear-gradient(180deg, #ffffff 0%, #f7f9fc 100%); }
      .checkout-card { border-radius: 18px; }
      .product-card .thumb { width: 64px; height: 64px; border-radius: 12px; background: linear-gradient(135deg,#1f2937,#111827); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; }
      .buy-btn { background: linear-gradient(90deg,#2563eb,#3b82f6); color: #fff; border: none; padding: 12px 18px; border-radius: 12px; }
      .pay-select .btn { border-radius: 12px; }
      .badge-icons img { height: 26px; margin-right: 8px; opacity:.9 }
    </style>
    </head><body>
    <div class='container py-5'>
      <div class='row g-4'>
        <div class='col-lg-5'>
          <div class='card checkout-card shadow product-card'>
            <div class='card-header bg-white'>
              <strong>Estás comprando</strong>
            </div>
            <div class='card-body'>
              <div class='d-flex align-items-center mb-3'>
                <div class='thumb me-3'>BV</div>
                <div>
                  <div class='fw-bold'>BookVibes Pro</div>
                  <a href='#' class='text-decoration-none'>Auto-renovación</a>
                </div>
              </div>
              <div class='mb-2'><strong>4,99 €</strong> / mes</div>
              <div class='text-muted mb-1'>IVA (21%): 1,05 €</div>
              <div class='d-flex justify-content-between align-items-center'>
                <span class='fw-bold'>Total</span>
                <span class='fw-bold fs-5'>6,04 €</span>
              </div>
              <div class='mt-4 small text-muted'>
                Al completar la compra, se activará tu modo Pro con recomendaciones ilimitadas de canciones relacionadas con tus libros.
              </div>
            </div>
          </div>
        </div>
        <div class='col-lg-7'>
          <div class='card checkout-card shadow'>
            <div class='card-header bg-white'>
              <strong>Información de pago</strong>
            </div>
            <div class='card-body'>
              <form action='/pro/activate' method='get'>
                <input type='hidden' name='book_id' value='".htmlspecialchars($_GET['book_id'] ?? '')."'>
                <input type='hidden' name='return' value='".htmlspecialchars($_GET['return'] ?? '')."'>
                <div class='row g-3'>
                  <div class='col-12'>
                    <label class='form-label'>Email</label>
                    <input type='email' class='form-control' placeholder='tucorreo@ejemplo.com' required>
                  </div>
                  <div class='col-md-6'>
                    <label class='form-label'>Nombre</label>
                    <input type='text' class='form-control' placeholder='Nombre' required>
                  </div>
                  <div class='col-md-6'>
                    <label class='form-label'>Apellidos</label>
                    <input type='text' class='form-control' placeholder='Apellidos' required>
                  </div>
                  <div class='col-md-6'>
                    <label class='form-label'>País</label>
                    <select class='form-select'>
                      <option>España</option>
                      <option>México</option>
                      <option>Argentina</option>
                      <option>Estados Unidos</option>
                    </select>
                  </div>
                  <div class='col-md-6'>
                    <label class='form-label'>Provincia/Estado</label>
                    <input type='text' class='form-control' placeholder='Provincia/Estado'>
                  </div>
                </div>
                <div class='mt-3 pay-select d-flex gap-2'>
                  <button type='button' class='btn btn-outline-primary flex-fill'>Tarjeta</button>
                  <button type='button' class='btn btn-outline-secondary flex-fill'>PayPal</button>
                </div>
                <div class='row g-3 mt-1'>
                  <div class='col-12'>
                    <label class='form-label'>Número de tarjeta</label>
                    <input type='text' inputmode='numeric' class='form-control' placeholder='1234 5678 9012 3456'>
                  </div>
                  <div class='col-md-6'>
                    <label class='form-label'>Mes</label>
                    <select class='form-select'><option>01</option><option>02</option><option>03</option><option>04</option><option>05</option><option>06</option><option>07</option><option>08</option><option>09</option><option>10</option><option>11</option><option>12</option></select>
                  </div>
                  <div class='col-md-3'>
                    <label class='form-label'>Año</label>
                    <select class='form-select'><option>24</option><option>25</option><option>26</option><option>27</option><option>28</option><option>29</option></select>
                  </div>
                  <div class='col-md-3'>
                    <label class='form-label'>CVV</label>
                    <input type='text' class='form-control' placeholder='123'>
                  </div>
                </div>
                <div class='mt-4 d-grid'>
                  <button class='buy-btn'>Comprar ahora por 6,04 €</button>
                </div>
              </form>
              <div class='mt-3 d-flex align-items-center justify-content-center gap-3 badge-icons'>
                <img src='https://upload.wikimedia.org/wikipedia/commons/0/04/Visa.svg'>
                <img src='https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg'>
                <img src='https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg'>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    </body></html>";
});
$router->get('/pro/activate', function() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['pro'] = true;
    $_SESSION['account_type'] = 'Pro';

    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
        \App\Models\User::updateAccountType($userId, 'Pro');
    }
    $bookId = isset($_GET['book_id']) ? (int)$_GET['book_id'] : 0;
    $returnUrl = isset($_GET['return']) ? $_GET['return'] : '';
    if ($bookId > 0) {
        try {
            $playlist = \App\Models\Playlist::getByBookId($bookId);
            $book = \App\Models\Book::find($bookId);
            if ($playlist && $book) {
                $existing = [];
                foreach ($playlist['songs'] as $s) {
                    $key = mb_strtolower(trim(($s['title'] ?? '').'|'.($s['artist'] ?? '')));
                    if ($key !== '') $existing[$key] = true;
                }
                $currentCount = count($playlist['songs'] ?? []);
                
                $analyzer = new \App\Services\MoodAnalyzer();
                $moodData = $analyzer->analyze($book);
                $mood = $moodData['mood'] ?? 'Neutral';
                $preferredArtists = $analyzer->getPreferredArtistsForMood($mood);
                $candidates = $moodData['suggested_tracks'] ?? [];
                
                $filtered = [];
                foreach ($candidates as $t) {
                    $artistLower = mb_strtolower($t['artist'] ?? '');
                    $ok = false;
                    foreach ($preferredArtists as $pa) {
                        if ($pa && str_contains($artistLower, mb_strtolower($pa))) { $ok = true; break; }
                    }
                    if ($ok) $filtered[] = $t;
                }
                if (count($filtered) < 12) {
                    $yt = new \App\Services\YouTubeSearchService();
                    $queries = [];
                    $title = trim($book['title'] ?? '');
                    $author = trim($book['author'] ?? '');
                    if ($title !== '') {
                        $queries[] = $title . ' ' . $mood . ' songs';
                        $queries[] = $title . ' theme song';
                    }
                    if ($author !== '') {
                        $queries[] = $author . ' playlist';
                    }
                    $queries[] = ($book['genre'] ?? '') . ' ' . $mood . ' soundtrack';
                    $more = $yt->searchTracks($queries, 24, $preferredArtists);
                    $seenKeys = [];
                    foreach ($filtered as $t) { $seenKeys[mb_strtolower(trim(($t['title'] ?? '').'|'.($t['artist'] ?? '')))] = true; }
                    foreach ($more as $t) {
                        $key = mb_strtolower(trim(($t['title'] ?? '').'|'.($t['artist'] ?? '')));
                        if ($key === '' || isset($seenKeys[$key])) continue;
                        $artistLower = mb_strtolower($t['artist'] ?? '');
                        $ok = false;
                        foreach ($preferredArtists as $pa) {
                            if ($pa && str_contains($artistLower, mb_strtolower($pa))) { $ok = true; break; }
                        }
                        if ($ok) {
                            $filtered[] = $t;
                            $seenKeys[$key] = true;
                        }
                        if (count($filtered) >= 24) break;
                    }
                }
                $db = \App\Core\Database::getInstance();
                $added = 0;
                $targetAdd = max(10 - $currentCount, 5);
                foreach ($filtered as $t) {
                    if ($added >= $targetAdd) break;
                    $key = mb_strtolower(trim(($t['title'] ?? '').'|'.($t['artist'] ?? '')));
                    if ($key === '' || isset($existing[$key])) continue;
                    $db->query(
                        "INSERT INTO songs (playlist_id, title, artist, url) VALUES (?, ?, ?, ?)",
                        [$playlist['id'], $t['title'] ?? 'Desconocido', $t['artist'] ?? '', $t['url'] ?? '']
                    );
                    $existing[$key] = true;
                    $added++;
                }
            }
        } catch (\Throwable $e) {}
    }
    if ($returnUrl !== '') {
        header('Location: ' . $returnUrl);
    } else if ($bookId > 0) {
        header('Location: /books/show?id=' . $bookId);
    } else {
        header('Location: /dashboard');
    }
    exit;
});

$router->get('/pro/cancel', function() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    unset($_SESSION['pro']);
    header('Location: /dashboard');
    exit;
});


