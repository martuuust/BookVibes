<?php
// app/routes/web.php

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\BookController;

/** @var Router $router */

$router->get('/', function() {
    header('Location: /login');
    exit;
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
$router->get('/books/add-songs', [App\Controllers\BookController::class, 'addSongs']);
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
    $userName = $_SESSION['user_name'] ?? 'Lector';
    $isPro = !empty($_SESSION['pro']) && $_SESSION['pro'];
    
    return "<!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Activa Pro - BookVibes</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
        <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css'>
        <link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap' rel='stylesheet'>
        <style>
            :root {
                --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
                --text-main: #1e293b;
                --text-muted: #64748b;
                --card-bg: #ffffff;
                --card-border: #e2e8f0;
                --bg-body: #f8fafc;
            }
            body.dark-mode {
                --text-main: #f8fafc;
                --text-muted: #94a3b8;
                --card-bg: #1e293b;
                --card-border: rgba(255, 255, 255, 0.1);
                --bg-body: 
                    radial-gradient(2px 2px at 5% 15%, rgba(255,255,255,0.8), transparent 3px),
                    radial-gradient(1.5px 1.5px at 12% 28%, rgba(255,255,255,0.9), transparent 3px),
                    radial-gradient(1px 1px at 18% 5%, rgba(255,255,255,0.7), transparent 2px),
                    radial-gradient(2px 2px at 22% 65%, rgba(255,255,255,0.8), transparent 3px),
                    radial-gradient(1.5px 1.5px at 28% 40%, rgba(255,255,255,0.6), transparent 3px),
                    radial-gradient(1px 1px at 35% 12%, rgba(255,255,255,0.8), transparent 2px),
                    radial-gradient(2px 2px at 42% 75%, rgba(255,255,255,0.9), transparent 3px),
                    radial-gradient(1.5px 1.5px at 48% 52%, rgba(255,255,255,0.7), transparent 3px),
                    radial-gradient(1px 1px at 55% 25%, rgba(255,255,255,0.6), transparent 2px),
                    radial-gradient(2px 2px at 62% 85%, rgba(255,255,255,0.8), transparent 3px),
                    radial-gradient(1.5px 1.5px at 68% 35%, rgba(255,255,255,0.7), transparent 3px),
                    radial-gradient(1px 1px at 75% 10%, rgba(255,255,255,0.9), transparent 2px),
                    radial-gradient(2px 2px at 80% 30%, rgba(255,255,255,0.8), transparent 3px),
                    radial-gradient(1.5px 1.5px at 85% 60%, rgba(255,255,255,0.6), transparent 3px),
                    radial-gradient(1px 1px at 92% 18%, rgba(255,255,255,0.8), transparent 2px),
                    radial-gradient(2px 2px at 90% 80%, rgba(255,255,255,0.9), transparent 3px),
                    radial-gradient(1.5px 1.5px at 95% 45%, rgba(255,255,255,0.7), transparent 3px),
                    radial-gradient(1px 1px at 8% 90%, rgba(255,255,255,0.6), transparent 2px),
                    radial-gradient(2px 2px at 3% 40%, rgba(255,255,255,0.8), transparent 3px),
                    radial-gradient(1.5px 1.5px at 98% 5%, rgba(255,255,255,0.9), transparent 3px),
                    linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
                --input-bg: #334155;
            }
            body { 
                font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; 
                background: var(--bg-body);
                background-attachment: fixed;
                color: var(--text-main);
                min-height: 100vh;
                position: relative;
                overflow-x: hidden;
                transition: background 0.3s ease, color 0.3s ease;
            }
            
            /* Dark Mode Overrides */
            body.dark-mode .bg-light { background-color: var(--input-bg) !important; color: var(--text-main) !important; border-color: var(--card-border) !important; }
            body.dark-mode .text-dark { color: var(--text-main) !important; }
            body.dark-mode .text-muted { color: var(--text-muted) !important; }
            body.dark-mode .form-control { background-color: var(--input-bg); color: var(--text-main); border-color: var(--card-border); }
            body.dark-mode .form-control::placeholder { color: var(--text-muted); }
            body.dark-mode .form-control:focus { background-color: var(--input-bg); color: var(--text-main); border-color: #818cf8; }
            body.dark-mode .form-select { background-color: var(--input-bg); color: var(--text-main); border-color: var(--card-border); }
            body.dark-mode .input-group-text { background-color: var(--input-bg); border-color: var(--card-border); color: var(--text-muted); }
            body.dark-mode .pay-select .btn { color: var(--text-muted); border-color: var(--card-border); }
            body.dark-mode .pay-select .btn:hover, body.dark-mode .pay-select .btn.active { background-color: rgba(255,255,255,0.1); color: var(--text-main); border-color: var(--card-border); }
            
            .checkout-card { 
                background: var(--card-bg);
                border: 1px solid var(--card-border);
                border-radius: 24px;
                color: var(--text-main);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }
            .card-header {
                background: transparent;
                border-bottom: 1px solid var(--card-border);
                padding: 1.5rem;
                font-size: 1.1rem;
                font-weight: 700;
                color: var(--text-main);
            }
            .card-body { padding: 2rem; }
            
            .product-card .thumb { 
                width: 72px; height: 72px; 
                border-radius: 16px; 
                background: linear-gradient(135deg, #4f46e5, #9333ea); 
                display: flex; align-items: center; justify-content: center; 
                color: #fff; font-weight: 800; font-size: 1.5rem;
                box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
            }
            
            .buy-btn { 
                background: var(--primary-gradient); 
                color: #fff; border: none; 
                padding: 16px 24px; 
                border-radius: 16px; 
                font-weight: 700;
                font-size: 1.1rem;
                transition: all 0.3s;
                width: 100%;
                position: relative;
                overflow: hidden;
            }
            .buy-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 20px 25px -5px rgba(124, 58, 237, 0.4);
                color: white;
            }
            
            .form-control, .form-select {
                background: #fff;
                border: 1px solid #cbd5e1;
                color: #1e293b;
                padding: 0.8rem 1rem;
                border-radius: 12px;
            }
            .form-control:focus, .form-select:focus {
                background: #fff;
                border-color: #818cf8;
                box-shadow: 0 0 0 4px rgba(129, 140, 248, 0.2);
                color: #1e293b;
                outline: none;
            }
            .form-label { color: #475569; font-size: 0.9rem; margin-bottom: 0.5rem; }
            .form-control::placeholder { color: #94a3b8; }
            
            .pay-select .btn { 
                border-radius: 12px; 
                padding: 10px;
                border: 1px solid #e2e8f0;
                color: #64748b;
            }
            .pay-select .btn:hover, .pay-select .btn.active {
                background: #f1f5f9;
                border-color: #cbd5e1;
                color: #0f172a;
            }
            
            .badge-icons img { height: 26px; margin-right: 12px; opacity: 0.8; filter: none; }
        </style>
    </head>
    <body>
    
    <!-- Navbar -->
    <nav class='navbar navbar-expand-lg navbar-dark mb-5 sticky-top' style='background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); box-shadow: 0 4px 20px rgba(0,0,0,0.1);'>
      <div class='container'>
        <a class='navbar-brand fw-bold d-flex align-items-center gap-2' href='/dashboard' style='letter-spacing: -0.5px; font-size: 1.5rem;'>
            <div class='position-relative d-flex align-items-center justify-content-center' style='width: 40px; height: 40px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(168, 85, 247, 0.2)); border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);'>
                <i class='bi bi-book-half text-white' style='font-size: 1.2rem;'></i>
                <i class='bi bi-music-note-beamed position-absolute' style='color: #2dd4bf; font-size: 0.8rem; top: 8px; right: 6px; transform: rotate(15deg);'></i>
            </div>
            <span style='background: linear-gradient(135deg, #a78bfa, #2dd4bf); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-shadow: 0 2px 10px rgba(167, 139, 250, 0.3);'>BookVibes</span>
        </a>
        <div class='d-flex text-white align-items-center gap-3'>
            <button id='darkModeToggle' class='btn btn-link text-white p-0 border-0' title='Alternar modo oscuro'>
                <i class='bi bi-moon-fill fs-5'></i>
            </button>
            <div class='d-none d-md-block text-end lh-1'>
                <span class='d-block fw-semibold' style='font-size: 0.9rem;'>Hola, ".htmlspecialchars($userName)."</span>
                <small class='text-white-50' style='font-size: 0.75rem;'>Lector</small>
            </div>
            <a href='/dashboard' class='btn btn-outline-light btn-sm rounded-pill px-3' style='font-size: 0.8rem;'>Volver</a>
        </div>
      </div>
    </nav>

    <div class='container pb-5'>
      <div class='row g-4 justify-content-center'>
        <div class='col-lg-5'>
          <div class='card checkout-card shadow-sm product-card h-100'>
            <div class='card-header'>
              Tu Pedido
            </div>
            <div class='card-body'>
              <div class='d-flex align-items-center mb-4'>
                <div class='thumb me-3'><i class='bi bi-stars'></i></div>
                <div>
                  <div class='fw-bold fs-5'>BookVibes Pro</div>
                  <div class='text-muted small'>Suscripción Mensual</div>
                </div>
              </div>
              
              <div class='d-flex justify-content-between mb-2'>
                <span class='text-muted'>Subtotal</span>
                <span class='fw-semibold'>4,99 €</span>
              </div>
              <div class='d-flex justify-content-between mb-3 pb-3 border-bottom'>
                <span class='text-muted'>IVA (21%)</span>
                <span class='fw-semibold'>1,05 €</span>
              </div>
              <div class='d-flex justify-content-between align-items-center mb-4'>
                <span class='fw-bold fs-5'>Total</span>
                <span class='fw-bold fs-4 text-primary'>6,04 €</span>
              </div>
              
              <div class='p-3 rounded-3 mb-3 bg-light border'>
                <div class='d-flex flex-column gap-2'>
                    <div class='d-flex gap-2 align-items-center'>
                        <i class='bi bi-music-note-list text-primary'></i>
                        <span class='text-dark fw-medium'>Canciones ilimitadas</span>
                    </div>
                    <div class='d-flex gap-2 align-items-center'>
                        <i class='bi bi-person-video2 text-primary'></i>
                        <span class='text-dark fw-medium'>Generación de personajes</span>
                    </div>
                    <div class='mt-2 pt-2 border-top'>
                        <small class='text-muted lh-sm d-block'>
                            Desbloquea todo el potencial de BookVibes y lleva tu experiencia de lectura al siguiente nivel.
                        </small>
                    </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class='col-lg-7'>
          <div class='card checkout-card shadow-sm'>
            <div class='card-header'>
              Información de Pago
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
                </div>
                
                <div class='mt-4 mb-2'>
                    <label class='form-label'>Método de Pago</label>
                    <div class='pay-select d-flex gap-2'>
                      <button type='button' class='btn flex-fill active'><i class='bi bi-credit-card me-2'></i>Tarjeta</button>
                      <button type='button' class='btn flex-fill'><i class='bi bi-paypal me-2'></i>PayPal</button>
                    </div>
                </div>

                <div class='row g-3 mt-1'>
                  <div class='col-12'>
                    <label class='form-label'>Número de tarjeta</label>
                    <div class='input-group'>
                        <span class='input-group-text bg-light text-muted border-end-0'><i class='bi bi-credit-card-2-front'></i></span>
                        <input type='text' inputmode='numeric' class='form-control border-start-0' placeholder='0000 0000 0000 0000'>
                    </div>
                  </div>
                  <div class='col-md-6'>
                    <label class='form-label'>Expiración</label>
                    <div class='d-flex gap-2'>
                        <select class='form-select'><option>MM</option><option>01</option><option>02</option><option>03</option><option>04</option><option>05</option><option>06</option><option>07</option><option>08</option><option>09</option><option>10</option><option>11</option><option>12</option></select>
                        <select class='form-select'><option>YY</option><option>24</option><option>25</option><option>26</option><option>27</option><option>28</option></select>
                    </div>
                  </div>
                  <div class='col-md-6'>
                    <label class='form-label'>CVC / CVV</label>
                    <div class='input-group'>
                        <input type='text' class='form-control' placeholder='123'>
                        <span class='input-group-text bg-light text-muted'><i class='bi bi-question-circle'></i></span>
                    </div>
                  </div>
                </div>
                
                <div class='mt-4 pt-2'>
                  <button class='buy-btn'>
                    <i class='bi bi-lock-fill me-2'></i>Pagar 6,04 €
                  </button>
                  <div class='text-center mt-3 text-muted small'>
                    <i class='bi bi-shield-check me-1'></i> Pago 100% seguro y encriptado
                  </div>
                </div>
              </form>
              
              <div class='mt-4 d-flex align-items-center justify-content-center gap-3 badge-icons border-top pt-3'>
                <img src='https://upload.wikimedia.org/wikipedia/commons/0/04/Visa.svg' alt='Visa'>
                <img src='https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg' alt='Mastercard'>
                <img src='https://upload.wikimedia.org/wikipedia/commons/b/b5/PayPal.svg' alt='PayPal'>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script>
    // Dark Mode Toggle
    const toggleBtn = document.getElementById('darkModeToggle');
    const body = document.body;
    const icon = toggleBtn.querySelector('i');

    // Check local storage
    if (localStorage.getItem('theme') === 'dark') {
        body.classList.add('dark-mode');
        icon.classList.replace('bi-moon-fill', 'bi-sun-fill');
    }

    toggleBtn.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        const isDark = body.classList.contains('dark-mode');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        icon.classList.replace(isDark ? 'bi-moon-fill' : 'bi-sun-fill', isDark ? 'bi-sun-fill' : 'bi-moon-fill');
    });
</script>
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
                if (count($filtered) < 20) {
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
                    $more = $yt->searchTracks($queries, 30, $preferredArtists);
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

$router->get('/pro/settings', function() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $userName = $_SESSION['user_name'] ?? 'Lector';
    $isPro = !empty($_SESSION['pro']) && $_SESSION['pro'];
    
    if (!$isPro) {
        header('Location: /pro/upgrade');
        exit;
    }

    return '<!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Mi Suscripción - BookVibes</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
        <style>
            :root {
                --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
                --text-main: #1e293b;
                --text-muted: #64748b;
                --card-bg: #ffffff;
                --card-border: #e2e8f0;
                --bg-body: #f8fafc;
            }
            body.dark-mode {
                --text-main: #f8fafc;
                --text-muted: #94a3b8;
                --card-bg: #1e293b;
                --card-border: rgba(255, 255, 255, 0.1);
                --bg-body: 
                    radial-gradient(2px 2px at 5% 15%, rgba(255,255,255,0.8), transparent 3px),
                    radial-gradient(1.5px 1.5px at 12% 28%, rgba(255,255,255,0.9), transparent 3px),
                    radial-gradient(1px 1px at 18% 5%, rgba(255,255,255,0.7), transparent 2px),
                    radial-gradient(2px 2px at 22% 65%, rgba(255,255,255,0.8), transparent 3px),
                    radial-gradient(1.5px 1.5px at 28% 40%, rgba(255,255,255,0.6), transparent 3px),
                    radial-gradient(1px 1px at 35% 12%, rgba(255,255,255,0.8), transparent 2px),
                    radial-gradient(2px 2px at 42% 75%, rgba(255,255,255,0.9), transparent 3px),
                    radial-gradient(1.5px 1.5px at 48% 52%, rgba(255,255,255,0.7), transparent 3px),
                    radial-gradient(1px 1px at 55% 25%, rgba(255,255,255,0.6), transparent 2px),
                    radial-gradient(2px 2px at 62% 85%, rgba(255,255,255,0.8), transparent 3px),
                    radial-gradient(1.5px 1.5px at 68% 35%, rgba(255,255,255,0.7), transparent 3px),
                    radial-gradient(1px 1px at 75% 10%, rgba(255,255,255,0.9), transparent 2px),
                    radial-gradient(2px 2px at 80% 30%, rgba(255,255,255,0.8), transparent 3px),
                    radial-gradient(1.5px 1.5px at 85% 60%, rgba(255,255,255,0.6), transparent 3px),
                    radial-gradient(1px 1px at 92% 18%, rgba(255,255,255,0.8), transparent 2px),
                    radial-gradient(2px 2px at 90% 80%, rgba(255,255,255,0.9), transparent 3px),
                    radial-gradient(1.5px 1.5px at 95% 45%, rgba(255,255,255,0.7), transparent 3px),
                    radial-gradient(1px 1px at 8% 90%, rgba(255,255,255,0.6), transparent 2px),
                    radial-gradient(2px 2px at 3% 40%, rgba(255,255,255,0.8), transparent 3px),
                    radial-gradient(1.5px 1.5px at 98% 5%, rgba(255,255,255,0.9), transparent 3px),
                    linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            }
            body { 
                font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; 
                background: var(--bg-body);
                background-attachment: fixed;
                color: var(--text-main);
                min-height: 100vh;
                transition: background 0.3s ease, color 0.3s ease;
            }
            .settings-card {
                background: var(--card-bg);
                border: 1px solid var(--card-border);
                border-radius: 24px;
                color: var(--text-main);
                box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.1);
            }
            .text-main { color: var(--text-main); }
            .text-muted-custom { color: var(--text-muted); }
            
            .modal-content {
                background: var(--card-bg);
                color: var(--text-main);
                border: 1px solid var(--card-border);
            }
            .modal-header, .modal-footer {
                border-color: var(--card-border);
            }
            .btn-close {
                filter: invert(var(--close-invert));
            }
            body.dark-mode { --close-invert: 1; }
            body:not(.dark-mode) { --close-invert: 0; }
        </style>
    </head>
    <body>
        <div class="container d-flex flex-column justify-content-center min-vh-100 py-5">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="settings-card shadow-lg overflow-hidden">
                        <div class="card-header bg-transparent border-0 pt-5 px-5 pb-0 text-center">
                            <div class="d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px; background: rgba(139, 92, 246, 0.1); border-radius: 50%;">
                                <i class="bi bi-stars" style="font-size: 2.5rem; color: #8b5cf6;"></i>
                            </div>
                            <h2 class="fw-bold mb-1">Tu Suscripción</h2>
                            <span class="badge bg-gradient px-3 py-2 rounded-pill fs-6 mt-2" style="background-color: #8b5cf6;">Plan Pro Activo</span>
                        </div>
                        <div class="card-body p-5">
                            <div class="mb-5">
                                <h6 class="fw-bold mb-3 text-uppercase small text-muted-custom" style="letter-spacing: 0.5px;">Beneficios Activos</h6>
                                <ul class="list-unstyled">
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill text-success me-3 fs-5"></i>
                                        <span class="fw-medium">Canciones ilimitadas en tus playlists</span>
                                    </li>
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill text-success me-3 fs-5"></i>
                                        <span class="fw-medium">Generación de personajes con IA</span>
                                    </li>
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="bi bi-check-circle-fill text-success me-3 fs-5"></i>
                                        <span class="fw-medium">Acceso prioritario a nuevas funciones</span>
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="p-4 rounded-4 mb-4" style="background-color: rgba(139, 92, 246, 0.05); border: 1px solid rgba(139, 92, 246, 0.1);">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted-custom small fw-bold text-uppercase">Próxima facturación</span>
                                    <span class="fw-bold">17 Enero, 2026</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted-custom small fw-bold text-uppercase">Monto</span>
                                    <span class="fw-bold">6,04 € / mes</span>
                                </div>
                            </div>

                            <div class="d-grid gap-3">
                                <a href="/dashboard" class="btn btn-outline-secondary py-3 rounded-3 fw-bold border-2">Volver al Dashboard</a>
                                <button type="button" class="btn btn-link text-danger text-decoration-none small mt-2" data-bs-toggle="modal" data-bs-target="#cancelModal">
                                    Cancelar Suscripción
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-body p-5 text-center">
                        <div class="mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center" style="width: 70px; height: 70px; background: rgba(220, 53, 69, 0.1); border-radius: 50%;">
                                <i class="bi bi-emoji-frown display-4 text-danger"></i>
                            </div>
                        </div>
                        <h3 class="fw-bold mb-3">¿Seguro que quieres irte?</h3>
                        <p class="text-muted-custom mb-4">Perderás acceso inmediato a las funciones Pro y tus playlists generadas podrían limitarse. ¡Te echaremos de menos!</p>
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-light py-3 rounded-3 fw-bold" data-bs-dismiss="modal">Mantener mi plan</button>
                            <a href="/pro/cancel" class="btn btn-danger py-3 rounded-3 fw-bold">Sí, cancelar suscripción</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            // Check local storage for theme
            if (localStorage.getItem("theme") === "dark" || (!localStorage.getItem("theme") && window.matchMedia("(prefers-color-scheme: dark)").matches)) {
                document.body.classList.add("dark-mode");
            }
        </script>
    </body>
    </html>';
});

$router->get('/pro/cancel', function() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    
    // Update session
    unset($_SESSION['pro']);
    $_SESSION['account_type'] = 'Basic';

    // Update database
    if (isset($_SESSION['user_id'])) {
        \App\Models\User::updateAccountType($_SESSION['user_id'], 'Basic');
    }

    header('Location: /dashboard');
    exit;
});


