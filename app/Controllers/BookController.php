<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;
use App\Services\ScraperService;
use App\Services\MoodAnalyzer;
use App\Services\CharacterGenerator;
use App\Services\YouTubeSearchService;
use App\Models\Book;
use App\Models\Character;
use App\Models\Playlist;

class BookController extends Controller
{
    public function index()
    {
        // Dashboard
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $db = \App\Core\Database::getInstance();
        $col = $db->query("SHOW COLUMNS FROM users LIKE 'avatar_icon'")->fetch();
        if (!$col) {
            try { $db->query("ALTER TABLE users ADD COLUMN avatar_icon VARCHAR(50) NULL"); } catch (\Exception $e) {}
        }

        // Get User's Books
        $books = \App\Models\UserBook::getByUser($_SESSION['user_id']);
        
        $gamification = new \App\Services\GamificationService();
        $gamification->ensureAchievementsSeed();
        $stats = $gamification->getUserStats($_SESSION['user_id']);
        
        // Stats for Charts
        $moodStats = \App\Models\UserBook::getReadingStats($_SESSION['user_id']);
        $userRow = $db->query("SELECT avatar_icon FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();
        $avatarIcon = $userRow['avatar_icon'] ?? null;

        return $this->render('dashboard', [
            'user_name' => $_SESSION['user_name'], 
            'books' => $books,
            'stats' => $stats,
            'mood_stats' => $moodStats,
            'avatar_icon' => $avatarIcon,
            'pro_enabled' => !empty($_SESSION['pro']) && $_SESSION['pro']
        ]);
    }

    public function search()
    {
        return $this->render('books/search');
    }

    public function processSearch(Request $request)
    {
        @set_time_limit(45);
        @ini_set('default_socket_timeout', '6');
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = $_SESSION['user_id'] ?? 0;
        $accountType = $_SESSION['account_type'] ?? 'Basic'; // Default to Basic if not set
        $playlistLimit = ($accountType === 'Pro') ? 30 : 5; // 30 songs for Pro, 5 for Basic

        $query = $request->getBody()['query'] ?? '';
        
        if (empty($query)) {
            return $this->render('books/search', ['error' => 'Please enter a book title']);
        }

        // 1. Scrape
        $scraper = new ScraperService();
        $bookData = $scraper->scrapeBook($query);
        if (!$bookData) {
            return $this->render('books/search', ['error' => 'No se encontró una sinopsis oficial para este libro en fuentes verificables. Intenta el título exacto o añade el autor.']);
        }
        
        // 2. Analyze Mood & Generate Content
        $moodAnalyzer = new MoodAnalyzer();
        $moodData = $moodAnalyzer->analyze($bookData);
        $bookData['mood'] = $moodData['mood'];

        $charGen = new CharacterGenerator();
        $characters = $charGen->generateCharacters($bookData);

        // 3. Save ALL to DB
        $bookId = Book::create($bookData);
        
        // Check if characters already exist to avoid dupes on re-search (basic check)
        // Check if characters already exist to avoid dupes on re-search (basic check)
        // FORCE REFRESH: Delete old characters to ensure new AI images are used if re-searched
        Character::deleteByBookId($bookId);
        
        $seen = [];
        foreach ($characters as $char) {
            $name = trim($char['name'] ?? '');
            if ($name === '' || $name === 'Personaje principal') continue;
            $key = mb_strtolower($name);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            Character::create($bookId, $char);
        }
        
        // Only create playlist if missing, or clear it too? Let's keep existing playlist to be safe, or just check
        $existingPlaylist = Playlist::getByBookId($bookId);
        if (!$existingPlaylist) {
            $preferredTracks = $moodData['suggested_tracks'] ?? [];
            $tracks = array_slice($preferredTracks, 0, $playlistLimit);
            if (count($tracks) < $playlistLimit) {
                $yt = new YouTubeSearchService();
                $queries = [];
                $t = trim($bookData['title'] ?? '');
                $a = trim($bookData['author'] ?? '');
                $m = trim($bookData['mood'] ?? '');
                if ($t !== '') {
                    $queries[] = $t . ' soundtrack';
                    $queries[] = $t . ' theme song';
                }
                if ($a !== '') {
                    $queries[] = $a . ' playlist';
                }
                if ($m !== '') {
                    $queries[] = $m . ' songs';
                    $queries[] = $m . ' music';
                }
                if (empty($queries)) $queries = ['reading playlist','book theme songs'];
                $fill = $yt->searchTracks($queries, ($accountType === 'Pro') ? 30 : 10);
                $seen = [];
                foreach ($tracks as $x) { $seen[mb_strtolower(trim(($x['title'] ?? '').'|'.($x['artist'] ?? '')))] = true; }
                foreach ($fill as $x) {
                    $key = mb_strtolower(trim(($x['title'] ?? '').'|'.($x['artist'] ?? '')));
                    if ($key === '' || isset($seen[$key])) continue;
                    $tracks[] = $x;
                    $seen[$key] = true;
                    if (count($tracks) >= $playlistLimit) break;
                }
            }
            $playlistData = ['mood' => $bookData['mood'], 'suggested_tracks' => $tracks];
            Playlist::create($bookId, $playlistData);
        }

        // Add to User's list (UserBook relation)
        $added = \App\Models\UserBook::add($userId, $bookId);

        // Award Points only if new
        if ($added && $userId > 0) {
            $gamification = new \App\Services\GamificationService();
            $gamification->awardPoints($userId, 'add_book', 10);
        }
        
        header("Location: /books/show?id=$bookId");
    }

    public function upload()
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        return $this->render('books/upload');
    }

    public function storeUpload(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $userId = $_SESSION['user_id'];

        // Handle file upload
        if (!isset($_FILES['book_file']) || $_FILES['book_file']['error'] !== UPLOAD_ERR_OK) {
            return $this->render('books/upload', ['error' => 'Error al subir el archivo.']);
        }

        $fileTmpPath = $_FILES['book_file']['tmp_name'];
        $fileName = $_FILES['book_file']['name'];
        $fileSize = $_FILES['book_file']['size'];
        $fileType = $_FILES['book_file']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = ['pdf', 'epub', 'mobi']; // Define allowed extensions
        if (!in_array($fileExtension, $allowedfileExtensions)) {
            return $this->render('books/upload', ['error' => 'Tipo de archivo no permitido. Solo se permiten PDF, EPUB, MOBI.']);
        }

        $uploadFileDir = './uploads/books/';
        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0777, true);
        }

        $destPath = $uploadFileDir . md5(time() . $fileName) . '.' . $fileExtension;

        if(move_uploaded_file($fileTmpPath, $destPath)) {
            $body = $request->getBody();
            $title = $body['title'] ?? pathinfo($fileName, PATHINFO_FILENAME);
            $author = $body['author'] ?? 'Desconocido';
            $synopsis = $body['synopsis'] ?? 'Sin sinopsis';
            $genre = $body['genre'] ?? 'General';
            $mood = $body['mood'] ?? 'Neutral';
            $coverUrl = $body['cover_url'] ?? '';

            $bookData = [
                'title' => $title,
                'author' => $author,
                'synopsis' => $synopsis,
                'genre' => $genre,
                'mood' => $mood,
                'image_url' => $coverUrl,
                'file_path' => $destPath // Save the file path
            ];

            $bookId = Book::create($bookData);
            \App\Models\UserBook::add($userId, $bookId);

            $gamification = new \App\Services\GamificationService();
            $gamification->awardPoints($userId, 'upload_book', 15); // Award points for uploading

            header("Location: /books/show?id=$bookId");
            exit;
        } else {
            return $this->render('books/upload', ['error' => 'Hubo un error al mover el archivo subido.']);
        }
    }

    public function show(Request $request) 
    {
        // Extract ID from URL (naive routing, assuming /books/{id} support in router or extracting from path)
        // Since my Router is simple, I might need to adjust it or grab params differently.
        // For now, let's assume query param ?id=X if router doesn't support wildcards yet,
        // OR implement simple wildcard support. I'll use query param key for simplicity if logic fails,
        // but let's try to parse path or use Router param injection.
        
        // My Router implementation in step 29 didn't have regex params.
        // I will use $_GET['id'] for simplicity given the time constraints, 
        // OR I can parse the URI here.
        
        $body = $request->getBody();
        $id = $body['id'] ?? null; 

        if (!is_numeric($id)) {
             // Fallback if ID is not at end
             http_response_code(404);
             echo "Invalid Book ID";
             return;
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        $book = Book::find($id);
        $characters = Character::getByBookId($id);
        $playlist = Playlist::getByBookId($id);
        $spotifyConfigured = (trim(getenv('SPOTIFY_CLIENT_ID') ?: '') !== '') && (trim(getenv('SPOTIFY_REDIRECT_URI') ?: '') !== '');
        
        // Ensure songs exist if previous creation failed
        $isPro = !empty($_SESSION['pro']) && $_SESSION['pro'];
        if (!$playlist || empty($playlist['songs'])) {
            try {
                $yt = new YouTubeSearchService();
                $queries = [];
                $title = trim($book['title'] ?? '');
                $author = trim($book['author'] ?? '');
                $mood = trim($book['mood'] ?? '');
                if ($title !== '') {
                    $queries[] = $title . ' soundtrack';
                    $queries[] = $title . ' theme song';
                    $queries[] = $title . ' playlist';
                }
                if ($author !== '') {
                    $queries[] = $author . ' playlist';
                    $queries[] = $author . ' best songs';
                }
                if ($mood !== '') {
                    $queries[] = $mood . ' songs';
                    $queries[] = $mood . ' music';
                }
                if (empty($queries)) $queries = ['book theme songs','reading playlist','chill music'];
                
                $tracks = $yt->searchTracks($queries, 20);
                if (!$playlist) {
                    // Create playlist with exactly 5 tracks
                    $desired = min(5, count($tracks));
                    $data = [
                        'mood' => $book['mood'] ?? 'General',
                        'suggested_tracks' => array_slice($tracks, 0, $desired)
                    ];
                    Playlist::create($id, $data);
                } else {
                    // Insert missing tracks to satisfy minimum display
                    $existing = [];
                    foreach ($playlist['songs'] as $s) {
                        $key = mb_strtolower(trim(($s['title'] ?? '').'|'.($s['artist'] ?? '')));
                        if ($key !== '') $existing[$key] = true;
                    }
                    $target = 5;
                    $current = count($playlist['songs'] ?? []);
                    $needed = max($target - $current, 0);
                    if ($needed > 0) {
                        $db = \App\Core\Database::getInstance();
                        $added = 0;
                        foreach ($tracks as $t) {
                            if ($added >= $needed) break;
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
                }
                // Re-fetch to reflect changes
                $playlist = Playlist::getByBookId($id);
            } catch (\Throwable $e) {}
        }

        return $this->render('books/show', [
            'book' => $book,
            'characters' => $characters,
            'playlist' => $playlist,
            'spotify_configured' => $spotifyConfigured,
            'pro_enabled' => !empty($_SESSION['pro']) && $_SESSION['pro']
        ]);
    }

    public function delete(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $body = $request->getBody();
        $bookId = $body['book_id'] ?? null;
        if (!is_numeric($bookId)) {
            header('Location: /dashboard');
            exit;
        }
        $userId = $_SESSION['user_id'];
        $linked = \App\Models\UserBook::remove($userId, $bookId);
        \App\Models\Book::delete($bookId);
        $gamification = new \App\Services\GamificationService();
        $gamification->awardPoints($userId, 'delete_book', -10);
        header('Location: /dashboard');
        exit;
    }

    public function avatar(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            return $this->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }
        $body = $request->getBody();
        $icon = trim($body['icon_class'] ?? '');
        if ($icon === '') {
            return $this->json(['ok' => false, 'error' => 'invalid_icon'], 400);
        }
        $db = \App\Core\Database::getInstance();
        $row = $db->query(
            "SELECT a.icon_class FROM achievements a 
             JOIN user_achievements ua ON a.id = ua.achievement_id 
             WHERE ua.user_id = ? AND a.icon_class = ?",
            [$_SESSION['user_id'], $icon]
        )->fetch();
        if (!$row) {
            return $this->json(['ok' => false, 'error' => 'not_unlocked'], 403);
        }
        $db->query("UPDATE users SET avatar_icon = ? WHERE id = ?", [$icon, $_SESSION['user_id']]);
        $_SESSION['avatar_icon'] = $icon;
        return $this->json(['ok' => true, 'icon_class' => $icon]);
    }

    public function refresh(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $books = Book::all();
        $charGen = new CharacterGenerator();
        foreach ($books as $b) {
            $data = [
                'title' => $b['title'],
                'author' => $b['author'],
                'synopsis' => $b['synopsis'] ?? '',
                'genre' => $b['genre'] ?? '',
                'mood' => $b['mood'] ?? '',
                'image_url' => $b['cover_url'] ?? ''
            ];
            $chars = $charGen->generateCharacters($data);
            Character::deleteByBookId($b['id']);
            $seen = [];
            foreach ($chars as $c) {
                $name = trim($c['name'] ?? '');
                if ($name === '' || $name === 'Personaje principal') continue;
                $key = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                Character::create($b['id'], $c);
            }
        }
        header('Location: /dashboard');
        exit;
    }

    public function spotifyConnect(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $body = $request->getBody();
        $bookId = $body['book_id'] ?? '';
        $clientId = getenv('SPOTIFY_CLIENT_ID') ?: '';
        $redirectUri = getenv('SPOTIFY_REDIRECT_URI') ?: '';
        $clientSecret = getenv('SPOTIFY_CLIENT_SECRET') ?: '';
        if ($clientId === '' || $redirectUri === '' || $clientSecret === '') {
            http_response_code(400);
            echo "<html><body style='font-family:system-ui;padding:24px'>";
            echo "<h2>Falta configurar Spotify</h2>";
            echo "<p>Debes definir <code>SPOTIFY_CLIENT_ID</code>, <code>SPOTIFY_CLIENT_SECRET</code> y <code>SPOTIFY_REDIRECT_URI</code> en tu archivo <code>.env</code>.</p>";
            echo "<ul>";
            echo "<li>SPOTIFY_CLIENT_ID: ID de tu app en <a href='https://developer.spotify.com/dashboard' target='_blank' rel='noopener'>Spotify Developer</a></li>";
            echo "<li>SPOTIFY_CLIENT_SECRET: Secret de la app</li>";
            echo "<li>SPOTIFY_REDIRECT_URI: por ejemplo <code>http://localhost:8000/spotify/callback</code></li>";
            echo "</ul>";
            echo "<p>Tras guardar el .env, vuelve atrás y pulsa “Convertir a Spotify”.</p>";
            echo "<p><a href='/books/show?id=" . htmlspecialchars($bookId) . "'>Volver al libro</a></p>";
            echo "</body></html>";
            exit;
        }
        $scope = 'playlist-modify-private playlist-modify-public';
        $state = bin2hex(random_bytes(8)) . '|book:' . $bookId;
        $_SESSION['spotify_oauth_state'] = $state;
        $url = 'https://accounts.spotify.com/authorize?client_id=' . urlencode($clientId) .
               '&response_type=code&redirect_uri=' . urlencode($redirectUri) .
               '&scope=' . urlencode($scope) .
               '&state=' . urlencode($state) .
               '&show_dialog=true';
        header('Location: ' . $url);
        exit;
    }

    public function spotifyCallback(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $body = $request->getBody();
        $code = $body['code'] ?? '';
        $state = $body['state'] ?? '';
        $expected = $_SESSION['spotify_oauth_state'] ?? '';
        $clientId = getenv('SPOTIFY_CLIENT_ID') ?: '';
        $clientSecret = getenv('SPOTIFY_CLIENT_SECRET') ?: '';
        $redirectUri = getenv('SPOTIFY_REDIRECT_URI') ?: '';
        if ($code === '' || $state === '' || $expected === '' || $state !== $expected) {
            header('Location: /dashboard');
            exit;
        }
        $auth = base64_encode($clientId . ':' . $clientSecret);
        $data = http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri
        ]);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\nAuthorization: Basic " . $auth . "\r\n",
                'content' => $data,
                'timeout' => 10
            ]
        ]);
        $resp = @file_get_contents('https://accounts.spotify.com/api/token', false, $ctx);
        $json = $resp ? json_decode($resp, true) : null;
        if (!$json || !isset($json['access_token'])) {
            header('Location: /dashboard');
            exit;
        }
        $_SESSION['spotify_access_token'] = $json['access_token'];
        $_SESSION['spotify_refresh_token'] = $json['refresh_token'] ?? '';
        $bookId = 0;
        if (strpos($state, '|book:') !== false) {
            $parts = explode('|book:', $state, 2);
            $bookId = (int)($parts[1] ?? 0);
        }
        if ($bookId > 0) {
            header('Location: /spotify/create?book_id=' . $bookId);
            exit;
        }
        header('Location: /dashboard');
        exit;
    }

    public function spotifyCreate(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        $body = $request->getBody();
        $bookId = $body['book_id'] ?? '';
        if (!is_numeric($bookId)) {
            header('Location: /dashboard');
            exit;
        }
        $playlist = Playlist::getByBookId($bookId);
        $book = Book::find($bookId);
        if (!$playlist || empty($playlist['songs'])) {
            header('Location: /books/show?id=' . $bookId);
            exit;
        }
        $token = $_SESSION['spotify_access_token'] ?? '';
        if ($token === '') {
            header('Location: /spotify/connect?book_id=' . $bookId);
            exit;
        }
        $user = $this->spotifyApiGet('https://api.spotify.com/v1/me', $token);
        if (!$user || !isset($user['id'])) {
            header('Location: /spotify/connect?book_id=' . $bookId);
            exit;
        }
        $name = $book['title'] ?? ('Libro ' . $bookId);
        $desc = 'Playlist generada por BookVibes para "' . $name . '"';
        $created = $this->spotifyApiPost('https://api.spotify.com/v1/users/' . urlencode($user['id']) . '/playlists', $token, [
            'name' => $name,
            'description' => $desc,
            'public' => false
        ]);
        if (!$created || !isset($created['id'])) {
            header('Location: /books/show?id=' . $bookId);
            exit;
        }
        $uris = [];
        $seen = [];
        foreach ($playlist['songs'] as $s) {
            $title = trim($s['title'] ?? '');
            $artist = trim($s['artist'] ?? '');
            if ($title === '') continue;
            $q1 = 'track:"' . $title . '" artist:"' . $artist . '"';
            $q2 = trim($title . ' ' . $artist);
            $uri = $this->spotifyFindTrackUri($token, $q1);
            if (!$uri) $uri = $this->spotifyFindTrackUri($token, $q2);
            if ($uri && !isset($seen[$uri])) {
                $uris[] = $uri;
                $seen[$uri] = true;
            }
            if (count($uris) >= 30) break;
        }
        if (!empty($uris)) {
            $this->spotifyApiPost('https://api.spotify.com/v1/playlists/' . urlencode($created['id']) . '/tracks', $token, [
                'uris' => $uris
            ]);
        }
        header('Location: https://open.spotify.com/playlist/' . $created['id']);
        exit;
    }

    private function spotifyApiGet(string $url, string $token): ?array
    {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer " . $token . "\r\nAccept: application/json\r\n",
                'timeout' => 10
            ]
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        return $resp ? json_decode($resp, true) : null;
    }

    private function spotifyApiPost(string $url, string $token, array $data): ?array
    {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Authorization: Bearer " . $token . "\r\nContent-Type: application/json\r\nAccept: application/json\r\n",
                'content' => json_encode($data),
                'timeout' => 10
            ]
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        return $resp ? json_decode($resp, true) : null;
    }

    private function spotifyFindTrackUri(string $token, string $q): ?string
    {
        $url = 'https://api.spotify.com/v1/search?type=track&limit=1&q=' . urlencode($q);
        $res = $this->spotifyApiGet($url, $token);
        if ($res && isset($res['tracks']['items'][0]['uri'])) {
            return $res['tracks']['items'][0]['uri'];
        }
        return null;
    }
}
