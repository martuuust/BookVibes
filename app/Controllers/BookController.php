<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;
use App\Core\Logger;
use App\Services\ScraperService;
use App\Services\MoodAnalyzer;
use App\Services\YouTubeSearchService;

use App\Models\Book;


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
        
        // Optimize: Check schema only once per session
        if (!isset($_SESSION['schema_checked'])) {
            $col = $db->query("SHOW COLUMNS FROM users LIKE 'avatar_icon'")->fetch();
            if (!$col) {
                try { $db->query("ALTER TABLE users ADD COLUMN avatar_icon VARCHAR(50) NULL"); } catch (\Exception $e) {}
            }
            $_SESSION['schema_checked'] = true;
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

        // Get Diary Entries
        $diaryEntries = \App\Models\DiaryEntry::getByUser($_SESSION['user_id']);

        return $this->render('dashboard', [
            'user_name' => $_SESSION['user_name'], 
            'books' => $books,
            'stats' => $stats,
            'mood_stats' => $moodStats,
            'avatar_icon' => $avatarIcon,
            'isPro' => !empty($_SESSION['pro']) && $_SESSION['pro'],
            'diary_entries' => $diaryEntries
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
        
        $query = $request->getBody()['query'] ?? '';
        
        Logger::info("Búsqueda de libro iniciada", ['query' => $query, 'user_id' => $userId]);
        
        if (empty($query)) {
            Logger::warning("Búsqueda vacía", ['user_id' => $userId]);
            return $this->render('books/search', ['error' => 'Please enter a book title']);
        }

        // 1. Scrape (Fast enough, usually < 2s)
        $scraper = new ScraperService();
        $bookData = $scraper->scrapeBook($query);
        if (!$bookData) {
            Logger::warning("Libro no encontrado", ['query' => $query]);
            return $this->render('books/search', ['error' => 'No se encontró una sinopsis oficial para este libro en fuentes verificables. Intenta el título exacto o añade el autor.']);
        }
        
        Logger::info("Libro encontrado", ['title' => $bookData['title'] ?? $query, 'author' => $bookData['author'] ?? 'Unknown']);
        
        // 2. Analyze Mood Only (Fast, local keywords)
        $moodAnalyzer = new MoodAnalyzer();
        $moodData = $moodAnalyzer->analyzeMoodOnly($bookData); // New fast method
        $bookData['mood'] = $moodData['mood'];

        // 3. Save Basic Info to DB
        $bookId = Book::create($bookData);
        
        Logger::info("Libro guardado en DB", ['book_id' => $bookId, 'title' => $bookData['title'] ?? $query]);
        
        // Add to User's list (UserBook relation)
        $added = \App\Models\UserBook::add($userId, $bookId);

        // Award Points only if new
        if ($added && $userId > 0) {
            $gamification = new \App\Services\GamificationService();
            $gamification->awardPoints($userId, 'add_book', 10);
            Logger::info("Puntos otorgados por añadir libro", ['user_id' => $userId, 'points' => 10]);
        }
        
        header("Location: /books/show?id=$bookId");
    }




    
    public function apiGeneratePlaylist(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $body = $request->getBody();
        $bookId = $body['book_id'] ?? null;
        
        if (!$bookId) {
            return $this->json(['ok' => false, 'error' => 'No book ID provided'], 400);
        }
        
        $book = Book::find($bookId);
        if (!$book) {
            return $this->json(['ok' => false, 'error' => 'Book not found'], 404);
        }
        
        $userId = $_SESSION['user_id'] ?? 0;
        $accountType = $_SESSION['account_type'] ?? 'Basic';
        $playlistLimit = 7;
        
        $playlist = Playlist::getByBookId($bookId);
        
        // If playlist exists and has songs, just return it
        if ($playlist && !empty($playlist['songs'])) {
             return $this->json(['ok' => true, 'playlist' => $playlist]);
        }
        
        // Generate new playlist
        try {
            // Re-analyze mood to get tracks (Full analysis including YouTube)
            $moodAnalyzer = new MoodAnalyzer();
            $moodData = $moodAnalyzer->analyze($book); // This calls YouTube
            
            $preferredTracks = $moodData['suggested_tracks'] ?? [];
            
            // DEDUPLICATION:
            // 1. Get all existing songs for this user to avoid cross-book duplicates
            $existingUserSongs = Playlist::getAllUserSongs($userId);
            $seenKeys = [];
            foreach ($existingUserSongs as $s) {
                $seenKeys[$this->normalizeForDedupe($s['title'], $s['artist'])] = true;
            }

            // 2. Filter preferredTracks
            $tracks = [];
            foreach ($preferredTracks as $t) {
                $key = $this->normalizeForDedupe($t['title'] ?? '', $t['artist'] ?? '');
                if (!isset($seenKeys[$key])) {
                    $tracks[] = $t;
                    $seenKeys[$key] = true;
                }
                if (count($tracks) >= $playlistLimit) break;
            }
            
            // If not enough tracks, fill with YouTube search
            if (count($tracks) < $playlistLimit) {
                $yt = new YouTubeSearchService();
                $queries = [];
                $t = trim($book['title'] ?? '');
                $a = trim($book['author'] ?? '');
                $m = trim($book['mood'] ?? '');
                 if ($t !== '') {
                    $queries[] = $t . ' soundtrack';
                    $queries[] = $t . ' theme song';
                }
                if ($a !== '') {
                    $queries[] = $a . ' playlist';
                }
                if ($m !== '') {
                    $queries[] = $m . ' songs';
                }
                if (empty($queries)) $queries = ['reading playlist','book theme songs'];
                
                $fill = $yt->searchTracks($queries, ($accountType === 'Pro') ? 30 : 15);
                
                foreach ($fill as $x) {
                    $key = $this->normalizeForDedupe($x['title'] ?? '', $x['artist'] ?? '');
                    if (!isset($seenKeys[$key])) {
                        $tracks[] = $x;
                        $seenKeys[$key] = true;
                    }
                    if (count($tracks) >= $playlistLimit) break;
                }
            }
            
            // Final fallback if still empty or very few
            if (count($tracks) < 3) {
                 $fallbacks = $this->getFallbackTracks($book['mood'] ?? '');
                 foreach ($fallbacks as $fb) {
                     $key = $this->normalizeForDedupe($fb['title'] ?? '', $fb['artist'] ?? '');
                     if (!isset($seenKeys[$key])) {
                        $tracks[] = $fb;
                        $seenKeys[$key] = true;
                     }
                     if (count($tracks) >= $playlistLimit) break;
                 }
            }
            
            $playlistData = ['mood' => $book['mood'], 'suggested_tracks' => $tracks];
            
            if (!$playlist) {
                Playlist::create($bookId, $playlistData);
            } else {
                // Update existing playlist if empty? Implementation of Playlist::create might handle it or we assume it's new
                // For simplicity, we just create new entries if playlist ID exists but songs empty
                // But Playlist::create creates a new playlist row. 
                // Let's rely on Playlist::create logic or just use it.
                // If playlist row exists but no songs, we might want to just insert songs.
                // But simplified:
                 $db = \App\Core\Database::getInstance();
                 // Clean up empty playlist header if exists to avoid dupes?
                 // Or just use existing ID.
                 // Let's reuse create logic which seems robust enough or just insert.
                 // Actually Playlist::create inserts a new playlist.
                 // If one exists, we should use it.
                 if ($playlist) {
                     $playlistId = $playlist['id'];
                     foreach ($tracks as $track) {
                        $db->query(
                            "INSERT INTO songs (playlist_id, title, artist, url) VALUES (?, ?, ?, ?)",
                            [$playlistId, $track['title'], $track['artist'], $track['url']]
                        );
                     }
                 } else {
                     Playlist::create($bookId, $playlistData);
                 }
            }
            
            $finalPlaylist = Playlist::getByBookId($bookId);
            return $this->json(['ok' => true, 'playlist' => $finalPlaylist]);
            
        } catch (\Exception $e) {
            return $this->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiRegeneratePlaylist(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $body = $request->getBody();
        $bookId = $body['book_id'] ?? null;
        
        if (!$bookId) {
            return $this->json(['ok' => false, 'error' => 'No book ID provided'], 400);
        }

        $userId = $_SESSION['user_id'] ?? 0;
        $isPro = !empty($_SESSION['pro']) && $_SESSION['pro'];

        // Check Regeneration Limit
        $db = \App\Core\Database::getInstance();
        
        // Ensure column exists (Migration check)
        try {
             $col = $db->query("SHOW COLUMNS FROM user_books LIKE 'regen_count'")->fetch();
             if (!$col) {
                 $db->query("ALTER TABLE user_books ADD COLUMN regen_count INT DEFAULT 0");
             }
        } catch (\Exception $e) {}

        // Get current count
        $ub = $db->query("SELECT regen_count FROM user_books WHERE user_id = ? AND book_id = ?", [$userId, $bookId])->fetch();
        $currentCount = $ub['regen_count'] ?? 0;

        // Enforce limit for Basic users (1 free regeneration)
        if (!$isPro && $currentCount >= 1) {
            return $this->json([
                'ok' => false, 
                'require_upgrade' => true,
                'error' => 'Has alcanzado el límite de regeneraciones gratuitas'
            ]);
        }

        // Increment Count
        $db->query("UPDATE user_books SET regen_count = regen_count + 1 WHERE user_id = ? AND book_id = ?", [$userId, $bookId]);

        // Clear existing songs but keep playlist record (to preserve spotify_playlist_id)
        Playlist::clearSongs($bookId);

        // Forward to generate new playlist
        return $this->apiGeneratePlaylist($request);
    }

    private function normalizeForDedupe($title, $artist)
    {
        // Combine
        $full = ($title ?? '') . ' ' . ($artist ?? '');
        // Lowercase
        $full = mb_strtolower($full);
        // Remove common garbage in brackets/parentheses
        $full = preg_replace('/[\(\[][^\)\]]*(lyrics|video|official|audio|live|hd|hq|remaster|mix)[\)\]]/', '', $full);
        // Remove keywords if they are not in brackets
        $full = str_replace(['lyrics', 'official video', 'official audio', 'music video', 'full audio'], '', $full);
        // Remove non-alphanumeric (keep spaces)
        $full = preg_replace('/[^a-z0-9\s]/', '', $full);
        // Split into words
        $words = explode(' ', $full);
        // Remove empty
        $words = array_filter($words);
        // Sort words to handle "Artist Title" vs "Title Artist"
        sort($words);
        return implode(' ', $words);
    }

    private function getFallbackTracks(string $mood): array
    {
        // Define fallback tracks based on general moods
        $defaults = [
            'Romántico' => [
                ['title' => 'All of Me', 'artist' => 'John Legend', 'url' => 'https://www.youtube.com/watch?v=450p7goxZqg'],
                ['title' => 'Perfect', 'artist' => 'Ed Sheeran', 'url' => 'https://www.youtube.com/watch?v=2Vv-BfVoq4g'],
                ['title' => 'Just the Way You Are', 'artist' => 'Bruno Mars', 'url' => 'https://www.youtube.com/watch?v=LjhCEhWiKXk']
            ],
            'Misterio' => [ // Handles Intriga y Suspenso too via check
                ['title' => 'Bad Guy', 'artist' => 'Billie Eilish', 'url' => 'https://www.youtube.com/watch?v=DyDfgMOUjCI'],
                ['title' => 'Heathens', 'artist' => 'Twenty One Pilots', 'url' => 'https://www.youtube.com/watch?v=UprcpDW1qQY'],
                ['title' => 'Bury a Friend', 'artist' => 'Billie Eilish', 'url' => 'https://www.youtube.com/watch?v=HUHC9tYz8ik']
            ],
            'Aventura' => [
                ['title' => 'Believer', 'artist' => 'Imagine Dragons', 'url' => 'https://www.youtube.com/watch?v=7wtfhZwyrcc'],
                ['title' => 'Viva La Vida', 'artist' => 'Coldplay', 'url' => 'https://www.youtube.com/watch?v=dvgZkm1xWPE'],
                ['title' => 'Pompeii', 'artist' => 'Bastille', 'url' => 'https://www.youtube.com/watch?v=F90Cw4l-8NY']
            ],
            'Fantasía' => [
                ['title' => 'The Lord of the Rings Theme', 'artist' => 'Howard Shore', 'url' => 'https://www.youtube.com/watch?v=_pGaz_qN0cw'],
                ['title' => 'Game of Thrones Theme', 'artist' => 'Ramin Djawadi', 'url' => 'https://www.youtube.com/watch?v=s7L2PVnzflw'],
                ['title' => 'Into the Unknown', 'artist' => 'Aurora', 'url' => 'https://www.youtube.com/watch?v=gIOyB9ZXn8s']
            ],
            'Terror' => [
                ['title' => 'Thriller', 'artist' => 'Michael Jackson', 'url' => 'https://www.youtube.com/watch?v=sOnqjkJTMaA'],
                ['title' => 'Halloween Theme', 'artist' => 'John Carpenter', 'url' => 'https://www.youtube.com/watch?v=VafWZ4s2tHQ'],
                ['title' => 'Tubular Bells', 'artist' => 'Mike Oldfield', 'url' => 'https://www.youtube.com/watch?v=TXvtDm820zI']
            ],
            'Comedia' => [
                ['title' => 'All Star', 'artist' => 'Smash Mouth', 'url' => 'https://www.youtube.com/watch?v=L_jWHffIx5E'],
                ['title' => 'Happy', 'artist' => 'Pharrell Williams', 'url' => 'https://www.youtube.com/watch?v=ZbZSe6N_BXs'],
                ['title' => 'Uptown Funk', 'artist' => 'Mark Ronson', 'url' => 'https://www.youtube.com/watch?v=OPf0YbXqDm0']
            ],
            'Drama' => [
                ['title' => 'Someone Like You', 'artist' => 'Adele', 'url' => 'https://www.youtube.com/watch?v=hLQl3WQQoQ0'],
                ['title' => 'Fix You', 'artist' => 'Coldplay', 'url' => 'https://www.youtube.com/watch?v=k4V3Mo61fJM'],
                ['title' => 'Skinny Love', 'artist' => 'Birdy', 'url' => 'https://www.youtube.com/watch?v=aNzCDt2ueHI']
            ]
        ];
        
        // Aliases for legacy support
        if (stripos($mood, 'Intriga') !== false) $mood = 'Misterio';
        if (stripos($mood, 'Épico') !== false) $mood = 'Aventura';
        if (stripos($mood, 'Melancólico') !== false) $mood = 'Drama';

        foreach ($defaults as $key => $list) {
            if (stripos($mood, $key) !== false) return $list;
        }

        // Ultimate fallback
        return [
            ['title' => 'As It Was', 'artist' => 'Harry Styles', 'url' => 'https://www.youtube.com/watch?v=H5v3kku4y6Q'],
            ['title' => 'Blinding Lights', 'artist' => 'The Weeknd', 'url' => 'https://www.youtube.com/watch?v=4NRXx6U8ABQ'],
            ['title' => 'Levitating', 'artist' => 'Dua Lipa', 'url' => 'https://www.youtube.com/watch?v=TUVcZfQe-Kw']
        ];
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
        


        $playlist = Playlist::getByBookId($id);
        $spotifyConfigured = (trim(getenv('SPOTIFY_CLIENT_ID') ?: '') !== '') && (trim(getenv('SPOTIFY_REDIRECT_URI') ?: '') !== '');
        
        // Ensure songs exist if previous creation failed
        $isPro = !empty($_SESSION['pro']) && $_SESSION['pro'];

        return $this->render('books/show', [
            'book' => $book,
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
        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        $expected = $_SESSION['spotify_oauth_state'] ?? '';
        $clientId = getenv('SPOTIFY_CLIENT_ID') ?: '';
        $clientSecret = getenv('SPOTIFY_CLIENT_SECRET') ?: '';
        $redirectUri = getenv('SPOTIFY_REDIRECT_URI') ?: '';
        if ($code === '' || $state === '' || $expected === '' || $state !== $expected) {
            header('Location: /dashboard');
            exit;
        }
        $auth = base64_encode($clientId . ':' . $clientSecret);
        
        // FIX: Ensure we use the exact redirect URI from env, preventing mismatch
        // If we are behind Cloudflare (https), force the env value
        $finalRedirectUri = $redirectUri; 

        $data = http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $finalRedirectUri
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
        $bookId = $_GET['book_id'] ?? '';
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

        $db = \App\Core\Database::getInstance();
        // Ensure spotify_playlist_id column exists
        try {
             $col = $db->query("SHOW COLUMNS FROM playlists LIKE 'spotify_playlist_id'")->fetch();
             if (!$col) {
                 $db->query("ALTER TABLE playlists ADD COLUMN spotify_playlist_id VARCHAR(100) NULL");
                 // Refresh playlist data
                 $playlist = Playlist::getByBookId($bookId);
             }
        } catch (\Exception $e) {}

        $targetPlaylistId = $playlist['spotify_playlist_id'] ?? null;
        $playlistUrl = '';
        $existingUris = []; // Track URIs already in Spotify playlist

        // Check if existing playlist is valid
        if ($targetPlaylistId) {
             $check = $this->spotifyApiGet('https://api.spotify.com/v1/playlists/' . $targetPlaylistId, $token);
             if (!$check || isset($check['error'])) {
                 $targetPlaylistId = null; // Invalid/Deleted, create new
             } else {
                 $playlistUrl = $check['external_urls']['spotify'] ?? ('https://open.spotify.com/playlist/' . $targetPlaylistId);
                 
                 // Get existing tracks from the Spotify playlist to avoid duplicates
                 $existingTracks = $this->spotifyApiGet('https://api.spotify.com/v1/playlists/' . $targetPlaylistId . '/tracks?limit=100', $token);
                 if ($existingTracks && isset($existingTracks['items'])) {
                     foreach ($existingTracks['items'] as $item) {
                         if (isset($item['track']['uri'])) {
                             $existingUris[$item['track']['uri']] = true;
                         }
                     }
                 }
             }
        }

        // Create new if needed
        if (!$targetPlaylistId) {
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
            $targetPlaylistId = $created['id'];
            $playlistUrl = $created['external_urls']['spotify'] ?? ('https://open.spotify.com/playlist/' . $targetPlaylistId);
            
            // Save ID
            $db->query("UPDATE playlists SET spotify_playlist_id = ? WHERE id = ?", [$targetPlaylistId, $playlist['id']]);
        }

        $uris = [];
        $seen = [];
        foreach ($playlist['songs'] as $s) {
            $rawTitle = $s['title'] ?? '';
            $rawArtist = $s['artist'] ?? '';
            
            // 1. Clean strings
            $title = $this->cleanForSpotify($rawTitle);
            $artist = $this->cleanForSpotify($rawArtist);
            
            if ($title === '') continue;

            $uri = null;

            // STRATEGY 1: Parse "Artist - Title" from the title field (Most common for YouTube results)
            // Example: "Ariana Grande - Beauty and the Beast" -> Artist: Ariana Grande, Track: Beauty and the Beast
            if (strpos($rawTitle, '-') !== false) {
                $parts = explode('-', $rawTitle, 2);
                $extractedArtist = $this->cleanForSpotify($parts[0]);
                $extractedTitle = $this->cleanForSpotify($parts[1]);
                
                if (!empty($extractedArtist) && !empty($extractedTitle)) {
                    // Try exact match with extracted parts
                    $q = 'track:"' . $extractedTitle . '" artist:"' . $extractedArtist . '"';
                    $uri = $this->spotifyFindTrackUri($token, $q);
                    
                    // Try looser match
                    if (!$uri) {
                         $q = trim($extractedTitle . ' ' . $extractedArtist);
                         $uri = $this->spotifyFindTrackUri($token, $q);
                    }
                }
            }

            // STRATEGY 2: Use DB Artist (if Strategy 1 failed or no hyphen)
            if (!$uri) {
                $searchArtist = $artist;
                
                // If the artist looks like a channel, try to clean it instead of discarding it
                // e.g. "Queen Official" -> "Queen", "DisneyMusicVEVO" -> "DisneyMusic"
                if ($this->isLikelyChannelName($searchArtist)) {
                     $searchArtist = $this->cleanChannelName($searchArtist);
                }

                if (!empty($searchArtist)) {
                    // Try strict first
                    $q = 'track:"' . $title . '" artist:"' . $searchArtist . '"';
                    $uri = $this->spotifyFindTrackUri($token, $q);
                    
                    // Try loose (Title + Artist string)
                    if (!$uri) {
                        $q = trim($title . ' ' . $searchArtist);
                        $uri = $this->spotifyFindTrackUri($token, $q);
                    }
                }
            }
            
            // STRATEGY 3: REMOVED "Title Only" search.
            // If we don't have a valid artist (extracted or from DB), we do NOT add the song.
            // This prevents "random" songs from being added when the title is generic.

            // Only add if not already in Spotify playlist and not a duplicate in this batch
            if ($uri && !isset($seen[$uri]) && !isset($existingUris[$uri])) {
                $uris[] = $uri;
                $seen[$uri] = true;
            }
            if (count($uris) >= 100) break;
        }
        if (!empty($uris)) {
            // Use POST to ADD only the new songs to the existing playlist
            // This preserves existing songs and adds only new recommendations
            $this->spotifyApiPost('https://api.spotify.com/v1/playlists/' . urlencode($targetPlaylistId) . '/tracks', $token, [
                'uris' => $uris
            ]);
        }
        header('Location: ' . $playlistUrl);
        exit;
    }

    private function cleanForSpotify(string $text): string
    {
        // Remove content in parentheses or brackets often found in YouTube titles
        // e.g. (Official Video), [Lyrics], (Audio), (Live), ft. Artist
        $text = preg_replace('/\s*[\(\[][^\)\]]*(video|official|audio|lyrics|hq|hd|4k|live|remaster|mix)[\)\]]/i', '', $text);
        
        // Remove "ft." or "feat." and everything after it (often complicates artist match if format differs)
        // Or better, just remove the "ft. X" part to match main song
        $text = preg_replace('/\s(ft\.|feat\.|featuring)\s.*/i', '', $text);
        
        // Remove common separators like " - " if it looks like "Artist - Title" (though we hope we have them separated)
        // But here we are cleaning individual fields.

        // Remove leading @ (e.g. @sza)
        $text = ltrim($text, '@');
        
        return trim($text);
    }

    private function cleanChannelName(string $text): string
    {
        // Try to remove "VEVO", "Official", "Music", "Channel", "Lyrics"
        // But keep the main part.
        // e.g. "Queen Official" -> "Queen"
        // "DisneyMusicVEVO" -> "DisneyMusic" (or "Disney")
        
        $clean = $text;
        $patterns = [
            '/vevo/i',
            '/official/i',
            '/channel/i',
            '/lyrics/i',
            '/video/i',
            '/audio/i'
        ];
        $clean = preg_replace($patterns, '', $clean);
        
        // Remove "Music" only if it's at the end or separate word, to avoid breaking "Musical"
        $clean = preg_replace('/\bmusic\b/i', '', $clean);
        
        return trim($clean);
    }

    private function isLikelyChannelName(string $text): bool
    {
        $lower = strtolower($text);
        // Common patterns for YouTube channels that aren't artists
        if (strpos($lower, 'vevo') !== false) return true;
        if (strpos($lower, 'official') !== false) return true;
        if (strpos($lower, 'lyrics') !== false) return true;
        if (strpos($lower, 'music') !== false && strpos($lower, ' ') !== false) return true; // e.g. "Dan Music"
        return false;
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

    private function spotifyApiPut(string $url, string $token, array $data): ?array
    {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'PUT',
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

    public function addSongs(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        // Only for Pro users
        $isPro = !empty($_SESSION['pro']) && $_SESSION['pro'];
        if (!$isPro) {
            header('Location: /pro/upgrade');
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: /dashboard');
            exit;
        }

        $playlist = Playlist::getByBookId($id);
        $book = Book::find($id);

        if ($playlist && $book) {
            // Fetch existing songs to avoid duplicates
            $existing = [];
            foreach ($playlist['songs'] as $s) {
                $key = mb_strtolower(trim(($s['title'] ?? '').'|'.($s['artist'] ?? '')));
                if ($key !== '') $existing[$key] = true;
            }

            $yt = new YouTubeSearchService();
            $queries = [];
            $title = trim($book['title'] ?? '');
            $author = trim($book['author'] ?? '');
            $mood = trim($book['mood'] ?? '');

            // Use more specific queries to find different songs
            if ($title !== '') {
                $queries[] = $title . ' ' . $mood . ' soundtrack';
                $queries[] = $title . ' ambient music';
            }
            if ($author !== '') {
                $queries[] = $author . ' inspired music';
            }
            $queries[] = ($book['genre'] ?? '') . ' ' . $mood . ' playlist';
            $queries[] = $mood . ' instrumental music';

            // Search for more tracks (fetch 20 to ensure we find 10 new ones)
            $newTracks = $yt->searchTracks($queries, 20);
            
            $db = \App\Core\Database::getInstance();
            $addedCount = 0;
            
            foreach ($newTracks as $track) {
                if ($addedCount >= 10) break;

                $key = mb_strtolower(trim(($track['title'] ?? '').'|'.($track['artist'] ?? '')));
                if ($key === '' || isset($existing[$key])) continue;

                $db->query(
                    "INSERT INTO songs (playlist_id, title, artist, url) VALUES (?, ?, ?, ?)",
                    [$playlist['id'], $track['title'] ?? 'Desconocido', $track['artist'] ?? '', $track['url'] ?? '']
                );
                $existing[$key] = true;
                $addedCount++;
            }
        }

        header("Location: /books/show?id=$id");
        exit;
    }

    /**
     * Create a new diary entry
     */
    public function apiCreateDiaryEntry(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            return $this->json(['ok' => false, 'error' => 'No autorizado'], 401);
        }

        $body = $request->getBody();
        $bookTitle = trim($body['book_title'] ?? '');
        $content = trim($body['content'] ?? '');

        if ($bookTitle === '' || $content === '') {
            return $this->json(['ok' => false, 'error' => 'Título y contenido son requeridos'], 400);
        }

        $id = \App\Models\DiaryEntry::create($_SESSION['user_id'], $bookTitle, $content);

        return $this->json(['ok' => true, 'id' => $id]);
    }

    /**
     * Update a diary entry
     */
    public function apiUpdateDiaryEntry(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            return $this->json(['ok' => false, 'error' => 'No autorizado'], 401);
        }

        $body = $request->getBody();
        $id = $body['id'] ?? null;
        $bookTitle = trim($body['book_title'] ?? '');
        $content = trim($body['content'] ?? '');

        if (!$id || $bookTitle === '' || $content === '') {
            return $this->json(['ok' => false, 'error' => 'ID, título y contenido son requeridos'], 400);
        }

        // Verify ownership is handled in model or here? Model update has userId check.
        $success = \App\Models\DiaryEntry::update($id, $_SESSION['user_id'], $bookTitle, $content);

        if ($success) {
            return $this->json(['ok' => true]);
        } else {
            return $this->json(['ok' => false, 'error' => 'No se pudo actualizar o no autorizado'], 400);
        }
    }

    /**
     * Delete a diary entry
     */
    public function apiDeleteDiaryEntry(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            return $this->json(['ok' => false, 'error' => 'No autorizado'], 401);
        }

        $body = $request->getBody();
        $entryId = (int)($body['id'] ?? 0);

        if ($entryId <= 0) {
            return $this->json(['ok' => false, 'error' => 'ID inválido'], 400);
        }

        $deleted = \App\Models\DiaryEntry::delete($entryId, $_SESSION['user_id']);

        return $this->json(['ok' => $deleted]);
    }

    /**
     * Diary page with 3D book experience
     */
    public function diaryPage(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }

        $diaryEntries = \App\Models\DiaryEntry::getByUser($_SESSION['user_id']);
        
        return $this->render('diary', [
            'user_name' => $_SESSION['user_name'],
            'diary_entries' => $diaryEntries,
            'isPro' => !empty($_SESSION['pro']) && $_SESSION['pro']
        ]);
    }

    /**
     * Generate interactive map data for a book using AI
     */
    public function apiGenerateMap(Request $request)
    {
        // Prevent PHP warnings/errors from breaking JSON
        if (ob_get_length()) ob_clean();
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $body = $request->getBody();
        $bookId = $body['book_id'] ?? null;
        $forceRegenerate = !empty($body['force_regenerate']);
        
        if (!$bookId) {
            return $this->json(['ok' => false, 'error' => 'No book ID provided'], 400);
        }
        
        $book = Book::find($bookId);
        if (!$book) {
            Logger::warning("Mapa solicitado para libro inexistente", ['book_id' => $bookId]);
            return $this->json(['ok' => false, 'error' => 'Book not found'], 404);
        }
        
        Logger::info("Generando mapa literario", [
            'book_id' => $bookId,
            'title' => $book['title'] ?? 'Unknown',
            'force_regenerate' => $forceRegenerate
        ]);
        
        // Check if map data is cached in the database
        $db = \App\Core\Database::getInstance();
        
        // Ensure map_data column exists
        try {
            $col = $db->query("SHOW COLUMNS FROM books LIKE 'map_data'")->fetch();
            if (!$col) {
                $db->query("ALTER TABLE books ADD COLUMN map_data TEXT NULL");
            }
        } catch (\Exception $e) {}
        
        // Check for cached map data (unless force regenerate)
        if (!$forceRegenerate) {
            $cached = $db->query("SELECT map_data FROM books WHERE id = ?", [$bookId])->fetch();
            if (!empty($cached['map_data'])) {
                $mapData = json_decode($cached['map_data'], true);
                if ($mapData) {
                    Logger::debug("Mapa obtenido de caché", ['book_id' => $bookId]);
                    return $this->json(['ok' => true, 'map' => $mapData, 'cached' => true]);
                }
            }
        }
        
        // Generate new map data using BookMapService
        try {
            $mapService = new \App\Services\BookMapService();
            
            // Pass title, author, AND synopsis for better context
            $synopsis = $book['synopsis'] ?? $book['description'] ?? '';
            
            $mapData = $mapService->generateMapData(
                $book['title'] ?? '',
                $book['author'] ?? '',
                $synopsis
            );
            
            if (!$mapData) {
                Logger::error("Fallo al generar mapa", ['book_id' => $bookId, 'title' => $book['title'] ?? '']);
                return $this->json(['ok' => false, 'error' => 'No se pudo generar el mapa. Verifica tus API keys.'], 500);
            }
            
            // Cache the result
            $db->query("UPDATE books SET map_data = ? WHERE id = ?", [json_encode($mapData), $bookId]);
            
            Logger::info("Mapa generado y cacheado", [
                'book_id' => $bookId,
                'markers' => count($mapData['markers'] ?? [])
            ]);
            
            return $this->json(['ok' => true, 'map' => $mapData, 'cached' => false]);
            
        } catch (\Exception $e) {
            Logger::exception($e, 'apiGenerateMap');
            return $this->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }
}