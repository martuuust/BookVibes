<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($book['title']) ?> - BookVibes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        .blur-content {
            filter: blur(12px);
            pointer-events: none;
            user-select: none;
            opacity: 0.5;
        }
        .pro-lock-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: rgba(15, 23, 42, 0.05);
        }
        .lock-card {
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(139, 92, 246, 0.3);
            padding: 2.5rem;
            border-radius: 24px;
            text-align: center;
            color: white;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            backdrop-filter: blur(10px);
            max-width: 400px;
            width: 90%;
            transform: translateY(-20px);
        }
        
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            /* Playlist variables (Dark by default) */
            --card-bg: rgba(30, 41, 59, 0.95);
            --card-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            
            /* Page variables (Light by default) */
            --bg-body:
                radial-gradient(1200px 600px at 0% 0%, #f0f5ff 10%, #ffffff 60%), linear-gradient(180deg, #ffffff 0%, #f7f9fc 100%);
            --char-bg: white;
            --text-body: #334155;
        }

        body.dark-mode {
            --bg-body: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            --char-bg: #1e293b;
            --text-body: #f8fafc;
        }

        html, body {
            min-height: 100vh;
            overflow-y: auto !important;
        }
        .glass-card .text-muted {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        body {
            background: var(--bg-body) !important;
            background-attachment: fixed !important;
            color: var(--text-main);
            transition: background 0.3s ease, color 0.3s ease;
        }

        /* Dark Mode Overrides */
        body.dark-mode .text-muted { color: #e2e8f0 !important; }
        body.dark-mode .text-dark { color: #f8fafc !important; }
        body.dark-mode .bg-white { background-color: var(--char-bg) !important; color: var(--text-body); }
        body.dark-mode .bg-light { background-color: #334155 !important; color: var(--text-body); }
        body.dark-mode .list-group-item { background-color: var(--char-bg); border-color: rgba(255,255,255,0.1); color: var(--text-body); }
        body.dark-mode .card { background-color: var(--char-bg); color: var(--text-body); }

        body.dark-mode .btn-outline-light {
        background-color: #dc3545 !important;
        border-color: #dc3545 !important;
        color: white !important;
    }
    body.dark-mode .btn-outline-light:hover {
        background-color: #c82333 !important;
        border-color: #bd2130 !important;
        color: white !important;
    }
    body.dark-mode .book-header .badge {
        color: white !important;
    }
    
    .navbar-brand {
        padding: 0;
    }
    .navbar-logo {
        height: 80px;
        width: auto;
        mix-blend-mode: multiply;
    }
    body.dark-mode .navbar-logo {
        filter: invert(1);
        mix-blend-mode: screen;
    }
    .transition-hover {
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    }
    .transition-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
    }
    .transition-hover:hover img {
        transform: scale(1.05);
    }

        /* --- Old Styles for Header & Characters --- */
        .book-header { 
            position: relative; 
            padding-top: 3rem;
            padding-bottom: 3rem;
        }
        .book-title-glow {
            font-weight: 800;
            color: white;
            text-shadow: 0 0 10px rgba(167, 139, 250, 1), 0 0 20px rgba(167, 139, 250, 0.5);
            /* Ensure visibility on light backgrounds via the glow */
        }
        
        /* Darker shadow for light mode to improve visibility */
        body:not(.dark-mode) .book-title-glow {
            text-shadow: 0 0 10px rgba(124, 58, 237, 1), 0 0 20px rgba(124, 58, 237, 0.8), 0 0 30px rgba(124, 58, 237, 0.6);
        }

        .back-nav {
            position: fixed;
            top: 100px;
            left: 20px;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            padding: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            color: #1e293b;
            text-decoration: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.2s;
            border: 1px solid rgba(0,0,0,0.05);
            font-size: 1.2rem;
        }
        body.dark-mode .back-nav {
            background: rgba(30, 41, 59, 0.8);
            color: #f8fafc;
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(8px);
        }
        .back-nav:hover {
            transform: translateX(-4px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.15);
            color: #1e293b;
        }
        body.dark-mode .back-nav:hover {
            color: #f8fafc;
        }
        .character-card { transition: transform 0.2s; background: var(--char-bg); border: 1px solid rgba(0,0,0,0.05); }
        .character-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important; }

        /* --- New Styles for Playlist (Glassmorphism) --- */
        .glass-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            color: var(--text-main);
            transition: all 0.3s ease;
        }
        .card-header-custom {
            padding: 1.25rem;
            border-bottom: 1px solid var(--card-border);
            background: rgba(255, 255, 255, 0.02);
        }
        .visual-album-container {
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            background: radial-gradient(circle at 50% 50%, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.95));
            border-radius: 16px;
            margin: 1rem;
            border: 1px solid var(--card-border);
            color: #ffffff;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
            padding: 2rem;
        }
        .visual-album-container:hover {
            transform: scale(1.02);
            border-color: rgba(139, 92, 246, 0.3);
            box-shadow: 0 25px 50px -12px rgba(139, 92, 246, 0.15);
        }
        .vinyl-record {
            width: 140px;
            height: 140px;
            background: repeating-radial-gradient(#111 0, #111 2px, #222 3px, #222 4px);
            border-radius: 50%;
            position: relative;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: spin 10s linear infinite;
        }
        .vinyl-label {
            width: 50px;
            height: 50px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.5rem;
            font-weight: 700;
            color: white;
            text-align: center;
            padding: 2px;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.5);
        }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* Animation for playlist expansion */
        #full-playlist {
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height 0.6s ease-in-out, opacity 0.4s ease-in-out;
        }
        #full-playlist.show {
            max-height: 8000px; /* Increased significantly to prevent cutting off long playlists */
            opacity: 1;
        }

        .playlist-track {
            background: transparent;
            border: none;
            border-bottom: 1px solid var(--card-border);
            color: var(--text-main);
            padding: 1rem 1.25rem;
            transition: background 0.2s;
        }
        .playlist-track:hover { background: rgba(255, 255, 255, 0.03); }
        
        /* Stars */
        #stars-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            pointer-events: none;
            z-index: -1; /* Asegura que las estrellas estén detrás de todo */
            overflow: visible;
        }
        .star {
            position: fixed;
            background: rgba(99,102,241,0.8); /* Tono azul-morado */
            border-radius: 50%;
            opacity: 0.8;
            box-shadow: 0 0 6px 2px rgba(168,85,247,0.6); /* Sombra morada */
            animation: twinkle var(--duration) infinite ease-in-out;
        }
        body.dark-mode .star {
            background: white;
            box-shadow: 0 0 6px 2px rgba(255, 255, 255, 0.6);
        }
        body:not(.dark-mode) .star {
            opacity: 0;
            visibility: hidden;
        }
        @keyframes twinkle {
            0%, 100% { opacity: 0.3; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.2); }
        }
        .btn-listen {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-main);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 0.25rem 1rem;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        .btn-listen:hover {
            background: var(--primary-gradient);
            border-color: transparent;
            color: white;
        }
        
        .btn-primary-glow {
            background: var(--primary-gradient);
            border: none;
            color: white;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
            transition: all 0.2s;
        }
        .btn-primary-glow:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(99, 102, 241, 0.4); color: white; }
        .btn-spotify { background: #1db954; border: none; color: white; font-weight: 600; }
        .btn-spotify:hover { background: #1ed760; color: white; }
        .navbar-light-mode {
            background: linear-gradient(to right, #e0f7fa, #f0e0fa, #fae0e0);
        }
        .navbar-dark-mode {
            background: linear-gradient(to right, #1a202c, #2d3748, #4a5568) !important;
        }
        .navbar-brand-text {
            color: #1a202c; /* Light mode color */
        }
        body.dark-mode .navbar-brand-text {
            color: #f8fafc; /* Dark mode color */
        }
        body.dark-mode #darkModeIcon {
            color: #f8fafc !important;
        }
        .navbar-text-light {
            color: black !important;
        }
        body.dark-mode .navbar-text-light {
            color: white !important;
        }
        body:not(.dark-mode) .btn-outline-light {
    background-color: #dc3545 !important;
    color: white !important;
    border-color: transparent !important;
}
body:not(.dark-mode) .btn-outline-light:hover {
    background-color: #c82333 !important;
    color: white !important;
}
        .ai-track {
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.1) 0%, transparent 100%);
            border-left: 2px solid #6366f1;
        }
        
        /* Player Bar Styles */
        .player-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 90px;
            background: rgba(15, 23, 42, 0.98);
            backdrop-filter: blur(12px);
            border-top: 1px solid rgba(255,255,255,0.1);
            z-index: 9999;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 -10px 30px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
        }
        .player-bar.active {
            transform: translateY(0);
        }
        .player-art {
            width: 56px;
            height: 56px;
            background: var(--primary-gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .ai-visualizer {
            position: absolute;
            top: -2px;
            left: 0;
            width: 100%;
            height: 2px;
            display: flex;
            align-items: flex-end;
            gap: 2px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .ai-visualizer.active {
            opacity: 1;
        }
        .ai-visualizer .bar {
            flex: 1;
            background: #a855f7;
            height: 100%;
            animation: visualize 0.8s ease-in-out infinite alternate;
            transform-origin: bottom;
        }
        .ai-visualizer .bar:nth-child(even) { animation-duration: 1.1s; animation-delay: 0.2s; }
        .ai-visualizer .bar:nth-child(3n) { animation-duration: 1.3s; animation-delay: 0.4s; }
        @keyframes visualize {
            0% { transform: scaleY(0.2); opacity: 0.5; }
            100% { transform: scaleY(1); opacity: 1; }
        }
    </style>
</head>
<body>
    <div id="stars-container"></div>
<?php 
$userName = $user_name ?? $_SESSION['user_name'] ?? 'Lector';
$isPro = $pro_enabled ?? (!empty($_SESSION['pro']) && $_SESSION['pro']);
?>

<!-- Old Navbar -->
<nav id="mainNavbar" class="navbar navbar-expand-lg navbar-light sticky-top navbar-light-mode" style="backdrop-filter: blur(10px);">
  <div class="container-fluid px-4 px-md-5">
    <a class="navbar-brand d-flex align-items-center gap-2" href="/dashboard">
        <img src="/logo.png" alt="BookVibes" class="navbar-logo">
    </a>
    <div class="d-flex align-items-center gap-4">
            <button id="darkModeToggle" class="btn btn-link p-0 border-0" title="Alternar modo oscuro">
                <i id="darkModeIcon" class="bi bi-moon-fill fs-4"></i>
            </button>
            <div class="d-none d-md-block text-end lh-1 navbar-text-light">
            <span class="d-block fw-semibold" style="font-size: 1.1rem;">Hola, <?= htmlspecialchars($userName) ?></span>
        </div>
        <?php if($isPro): ?>
            <a href="/pro/settings" class="text-decoration-none">
                <span class="badge bg-gradient border border-light border-opacity-25" style="background-color: #8b5cf6; font-size: 1rem; cursor: pointer;">Pro</span>
            </a>
        <?php else: ?>
            <span class="badge bg-secondary bg-opacity-50 border border-secondary border-opacity-25" style="font-size: 1rem;">Básica</span>
        <?php endif; ?>
        <a href="/logout" class="btn btn-outline-light btn-sm rounded-pill px-4 py-2" style="font-size: 1rem;">Cerrar Sesión</a>
    </div>
  </div>
</nav>
    <script>
        document.addEventListener('DOMContentLoaded', () => {


            const toggleBtn = document.getElementById('darkModeToggle');
            const body = document.body;
            const icon = toggleBtn.querySelector('i');
            const mainNavbar = document.getElementById('mainNavbar');

            function applyTheme(isDark) {
                if (isDark) {
                    document.body.classList.add('dark-mode');
                    icon.classList.replace('bi-moon-fill', 'bi-sun-fill');
                    mainNavbar.classList.replace('navbar-light-mode', 'navbar-dark-mode');
                    mainNavbar.classList.replace('navbar-light', 'navbar-dark');
                } else {
                    document.body.classList.remove('dark-mode');
                    icon.classList.replace('bi-sun-fill', 'bi-moon-fill');
                    mainNavbar.classList.replace('navbar-dark-mode', 'navbar-light-mode');
                    mainNavbar.classList.replace('navbar-dark', 'navbar-light');
                }
            }

            // Apply theme on load
            const savedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (savedTheme === 'dark' || (savedTheme === null && prefersDark)) {
                applyTheme(true);
            } else {
                applyTheme(false);
            }

            toggleBtn.addEventListener('click', () => {
                const isDark = !document.body.classList.contains('dark-mode');
                applyTheme(isDark);
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            });

            // Generate Stars
            const starsContainer = document.getElementById('stars-container');
            const starCount = 150;

            for (let i = 0; i < starCount; i++) {
                const star = document.createElement('div');
                star.classList.add('star');
                const x = Math.random() * 100; // X position in percentage
                const y = Math.random() * 100; // Y position in percentage (viewport)
                const size = Math.random() * 2 + 1; // 1px to 3px
                const duration = Math.random() * 3 + 2; // 2s to 5s
                
                star.style.left = x + '%';
                star.style.top = y + '%';
                star.style.width = size + 'px';
                star.style.height = size + 'px';
                star.style.setProperty('--duration', duration + 's');
                star.style.animationDelay = (Math.random() * 5) + 's';
                
                starsContainer.appendChild(star);
            }
        });
    </script>

<div class="container-fluid p-0">
    <!-- Old Header with Mood Color -->
    <div class="p-5 text-center book-header">
        <a href="/dashboard" class="back-nav" aria-label="Volver">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h1 class="display-4 fw-bold mb-3 book-title-glow"><?= htmlspecialchars($book['title']) ?></h1>
        <p class="lead mb-4 opacity-75" style="color: var(--text-body);">por <?= htmlspecialchars($book['author']) ?></p>
        <span class="badge bg-warning text-black fs-6 px-4 py-2 rounded-pill shadow-sm"><?= htmlspecialchars($book['mood']) ?></span>
    </div>
</div>

<div class="container-fluid px-4 px-md-5 mt-4">
    <div class="row">
        <!-- Playlist Col (Dynamic Height) -->
        <div class="col-md-4 mb-4">
            <div class="glass-card">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-white"><i class="bi bi-music-note-beamed me-2"></i>Playlist</h5>
                    <?php if($isPro): ?>
                        <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25">Pro Activo</span>
                    <?php endif; ?>
                </div>
                
                <div class="p-0" id="playlist-container">
                    <?php if($playlist && !empty($playlist['songs'])): ?>
                        <!-- Vinyl Trigger -->
                        <div class="visual-album-container text-center" onclick="document.getElementById('full-playlist').classList.toggle('show');">
                            <div class="vinyl-record">
                                <div class="vinyl-label">
                                    <?= htmlspecialchars(substr($book['title'], 0, 10)) ?>
                                </div>
                            </div>
                            <h6 class="mt-4 mb-1 fw-bold text-white">Playlist Generada</h6>
                            <p class="small text-white-50 mb-0"><?= count($playlist['songs']) ?> Pistas • Tocar para ver</p>
                        </div>

                        <!-- Playlist Items (Animated) -->
                        <div id="full-playlist">
                            <div class="list-group list-group-flush">
                                <?php 
                                    $limit = $isPro ? PHP_INT_MAX : 7;
                                    $i = 0;
                                    $aiCount = 0;
                                    foreach($playlist['songs'] as $song): 
                                        if($i++ >= $limit) break; 
                                        $isAi = !empty($song['is_ai_generated']);
                                        $variation = 0;
                                        if ($isAi) {
                                            $variation = $aiCount % 2;
                                            $aiCount++;
                                        }
                                ?>
                                    <div class="playlist-track <?= $isAi ? 'ai-track' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="me-3 overflow-hidden">
                                                <div class="fw-semibold text-truncate">
                                                    <?php if($isAi): ?>
                                                        <span class="badge bg-primary me-1" style="font-size: 0.6em; vertical-align: middle;">AI ORIGINAL</span>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($song['title']) ?>
                                                </div>
                                                <small class="text-muted text-truncate d-block"><?= htmlspecialchars($song['artist']) ?></small>
                                                <?php if($isAi): ?>
                                                    <small class="text-info d-block" style="font-size: 0.75em;"><?= htmlspecialchars($song['melody_description'] ?? '') ?></small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if($isAi): ?>
                                                 <button type="button" class="btn-listen" onclick='playAiTrack(<?= json_encode($song['title']) ?>, <?= json_encode($song['lyrics'] ?? '') ?>, <?= json_encode($song['melody_description'] ?? '') ?>, <?= json_encode($book['mood'] ?? 'Misterio') ?>, <?= $variation ?>)'>
                                                    <i class="bi bi-play-fill"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn-listen" onclick="playYouTubeTrack('<?= htmlspecialchars($song['url']) ?>', '<?= htmlspecialchars(addslashes($song['title'])) ?>', '<?= htmlspecialchars(addslashes($song['artist'])) ?>')">
                                                    <i class="bi bi-play-fill"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if($isAi): ?>
                                    <!-- Modal for AI Song -->
                                    <div class="modal fade" id="songModal<?= $song['id'] ?>" tabindex="-1" aria-hidden="true">
                                      <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content" style="background: var(--card-bg); color: var(--text-main); border: 1px solid var(--card-border);">
                                          <div class="modal-header border-bottom border-secondary border-opacity-25">
                                            <h5 class="modal-title">
                                                <i class="bi bi-stars text-warning me-2"></i><?= htmlspecialchars($song['title']) ?>
                                            </h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                          </div>
                                          <div class="modal-body">
                                            <h6 class="text-muted mb-3">by BookVibes AI</h6>
                                            <div class="p-3 rounded mb-3" style="background: rgba(0,0,0,0.2);">
                                                <small class="text-uppercase text-secondary fw-bold">Melodía</small>
                                                <p class="mb-0 fst-italic"><?= htmlspecialchars($song['melody_description'] ?? '') ?></p>
                                            </div>
                                            <div class="p-3 rounded" style="background: rgba(0,0,0,0.2);">
                                                <small class="text-uppercase text-secondary fw-bold mb-2 d-block">Letra Generada</small>
                                                <pre style="white-space: pre-wrap; font-family: inherit; color: var(--text-main);"><?= htmlspecialchars($song['lyrics'] ?? '') ?></pre>
                                            </div>
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                                    <?php endif; ?>

                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Pro/Upgrade Actions -->
                            <div class="p-3 bg-dark bg-opacity-25 border-top border-secondary border-opacity-10">
                                <?php if($isPro): ?>
                                    <div class="d-grid gap-2">
                                        <a href="/books/add-songs?id=<?= urlencode($book['id']) ?>" class="btn btn-outline-light btn-sm">
                                            <i class="bi bi-plus-circle me-2"></i>Añadir 10 canciones más
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php if(!$isPro && count($playlist['songs']) > 7): ?>
                                    <div class="text-center py-2">
                                        <small class="text-muted d-block mb-2">Mostrando 7 de <?= count($playlist['songs']) ?> canciones</small>
                                        <a href="/pro/upgrade?book_id=<?= urlencode($book['id']) ?>" class="btn btn-sm btn-primary-glow w-100">
                                            <i class="bi bi-unlock-fill me-1"></i> Desbloquear Todo (Pro)
                                        </a>
                                    </div>
                                <?php elseif(!$isPro): ?>
                                    <div class="d-grid mt-2">
                                         <a href="/pro/upgrade?book_id=<?= urlencode($book['id']) ?>&return=<?= urlencode('/books/show?id=' . $book['id']) ?>" class="btn btn-outline-light btn-sm">
                                            <i class="bi bi-stars me-1"></i> Mejorar Recomendaciones
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <?php if(!empty($playlist['songs'])): ?>
                                    <div class="d-grid mt-3">
                                        <a href="/spotify/create?book_id=<?= urlencode($book['id']) ?>" class="btn btn-spotify btn-sm">
                                            <i class="bi bi-spotify me-2"></i>Guardar en Spotify
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div id="playlist-loader" class="p-5 text-center">
                            <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                            <h6 class="text-white">Componiendo banda sonora...</h6>
                            <p class="text-white-50 small">Analizando emociones y buscando canciones perfectas</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Characters Col -->
        <div class="col-md-8 order-1 order-md-2">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body">
                    <h5>Sinopsis</h5>
                    <p class="text-muted"><?= htmlspecialchars($book['synopsis']) ?></p>
                </div>
            </div>

            <!-- Generation UI (Pro Only) -->
            <?php if($isPro): ?>
                <div id="char-gen-ui" class="mb-4 d-none">
                    <div class="card border-0 bg-dark bg-opacity-10 p-4 rounded-4" style="backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.1);">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0 fw-bold"><i class="bi bi-magic me-2 text-primary"></i>Generar Nuevo Personaje</h5>
                            <button onclick="hideCharGen()" class="btn btn-sm btn-link text-muted p-0"><i class="bi bi-x-lg"></i></button>
                        </div>
                        
                        <!-- Step 1: Loading List -->
                        <div id="char-list-loader" class="text-center py-4">
                            <div class="spinner-border text-primary mb-3" role="status"></div>
                            <h6 class="fw-normal">Consultando fuentes oficiales...</h6>
                            <p class="small text-muted mb-0">Buscando personajes en Wikipedia, Fandom y Goodreads</p>
                        </div>

                        <!-- Step 2: Selection -->
                        <div id="char-selection-area" class="d-none">
                            <div class="mb-3" id="char-select-container">
                                <label class="form-label small text-muted text-uppercase fw-bold">Personaje</label>
                                <select id="char-select" class="form-select form-select-lg" onchange="updateCharPreview()">
                                    <option value="">-- Selecciona un personaje --</option>
                                </select>
                            </div>
                            
                            <div id="char-manual-container" class="mb-3 d-none">
                                <label class="form-label small text-muted text-uppercase fw-bold">Nombre del Personaje</label>
                                <input type="text" id="char-manual-input" class="form-control form-control-lg" placeholder="Ej: Harry Potter" oninput="updateManualPreview()">
                                <div class="form-text text-white-50 small"><i class="bi bi-info-circle me-1"></i>No encontramos una lista oficial. Escribe el nombre manualmente.</div>
                            </div>
                            
                            <div id="char-desc-preview" class="p-3 rounded-3 bg-white bg-opacity-10 mb-3 d-none">
                                <small class="d-block text-muted mb-1">Descripción detectada:</small>
                                <p class="mb-0 fst-italic small" id="char-desc-text"></p>
                            </div>

                            <div class="d-grid">
                                <button id="btn-create-char" class="btn btn-primary btn-lg" onclick="generateSelectedChar()" disabled>
                                    <i class="bi bi-stars me-2"></i>Generar con IA
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Generating -->
                        <div id="char-generating-loader" class="d-none text-center py-4">
                            <div class="spinner-border text-purple mb-3" role="status" style="color: #a855f7;"></div>
                            <h6 class="fw-normal">Imaginando personaje...</h6>
                            <p class="small text-muted mb-0">Analizando rasgos y generando retrato fiel</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="position-relative">
                <?php if(!$isPro): ?>
                    <div class="pro-lock-overlay">
                        <div class="lock-card">
                            <div class="mb-4">
                                <div style="width: 80px; height: 80px; background: rgba(99, 102, 241, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                    <i class="bi bi-stars text-warning display-4"></i>
                                </div>
                            </div>
                            <h4 class="fw-bold mb-3">Descubre los Personajes</h4>
                            <p class="text-white-50 mb-4 lh-sm">Visualiza a los protagonistas de tu historia con arte generado por IA. Exclusivo para miembros Pro.</p>
                            <a href="/pro/upgrade?book_id=<?= urlencode($book['id']) ?>&feature=characters" class="btn btn-primary-glow fw-bold px-4 py-3 w-100 rounded-pill">
                                <i class="bi bi-unlock-fill me-2"></i>Desbloquear Ahora
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Empty State Trigger (Pro Only, No Characters) -->
                <?php if($isPro && empty($characters)): ?>
                    <div id="char-empty-state" class="text-center py-5">
                        <div class="mb-4">
                            <div style="width: 80px; height: 80px; background: rgba(99, 102, 241, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                                <i class="bi bi-person-badge text-primary display-4"></i>
                            </div>
                        </div>
                        <h4 class="fw-bold mb-3">Personajes de la Historia</h4>
                        <p class="text-muted mb-4">Aún no has generado ningún personaje. ¡Empieza ahora!</p>
                        <button onclick="showCharGen()" class="btn btn-primary btn-lg rounded-pill px-5 shadow-lg transition-hover">
                            <i class="bi bi-magic me-2"></i>Generar Personajes
                        </button>
                    </div>
                <?php endif; ?>

                <div id="characters-section" class="<?= ($isPro && empty($characters)) ? 'd-none' : '' ?>">
                    <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-2">
                        <h3 class="mb-0 fw-bold">Personajes</h3>
                        <?php if($isPro): ?>
                            <button onclick="showCharGen()" class="btn btn-sm btn-outline-primary rounded-pill px-3 transition-hover">
                                <i class="bi bi-plus-lg me-1"></i>Generar
                            </button>
                        <?php endif; ?>
                    </div>

                    <div class="row <?= !$isPro ? 'blur-content' : '' ?>" id="characters-list">
                        <?php foreach($characters as $char): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card character-card h-100 border-0 shadow-sm">
                                <div class="row g-0 h-100">
                                    <div class="col-md-5">
                                        <img src="<?= htmlspecialchars($char['image_url']) ?>" class="img-fluid rounded-start h-100" style="object-fit: cover;" alt="<?= htmlspecialchars($char['name']) ?>">
                                    </div>
                                    <div class="col-md-7">
                                        <div class="card-body">
                                            <h5 class="card-title"><?= htmlspecialchars($char['name']) ?></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if(!$isPro): ?>
                            <?php for($i=0; $i<4; $i++): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card character-card h-100 border-0 shadow-sm" style="min-height: 200px;">
                                    <div class="card-body d-flex align-items-center justify-content-center">
                                        <span class="text-muted">Contenido Bloqueado</span>
                                    </div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>


    // Global variables for Character Generation
    let fetchedCharacters = [];
    const BOOK_ID = <?= json_encode($book['id']) ?>;

    function showCharGen() {
        const ui = document.getElementById('char-gen-ui');
        ui.classList.remove('d-none');
        
        // Reset state
        document.getElementById('char-list-loader').classList.remove('d-none');
        document.getElementById('char-selection-area').classList.add('d-none');
        document.getElementById('char-generating-loader').classList.add('d-none');
        document.getElementById('char-select').innerHTML = '<option value="">-- Selecciona un personaje --</option>';
        document.getElementById('btn-create-char').disabled = true;
        document.getElementById('char-desc-preview').classList.add('d-none');
        
        const existingMsg = document.getElementById('char-manual-msg');
        if (existingMsg) existingMsg.remove();
        
        // Reset manual input
        document.getElementById('char-manual-input').value = '';
        document.getElementById('char-select-container').classList.remove('d-none');
        document.getElementById('char-manual-container').classList.add('d-none');

        // Fetch List
        fetch('/books/fetch-character-list', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book_id: BOOK_ID })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                fetchedCharacters = data.list.filter(c => c.name && c.name.trim().length > 0);
                const select = document.getElementById('char-select');
                
                document.getElementById('char-list-loader').classList.add('d-none');
                document.getElementById('char-selection-area').classList.remove('d-none');

                if (fetchedCharacters.length === 0) {
                    // Switch to Manual Mode
                    document.getElementById('char-select-container').classList.add('d-none');
                    document.getElementById('char-manual-container').classList.remove('d-none');
                    
                    if (data.total_found > 0 && data.already_added_count > 0) {
                         const msg = document.createElement('div');
                         msg.id = 'char-manual-msg';
                         msg.className = 'alert alert-info small mt-2';
                         msg.innerHTML = '<i class="bi bi-info-circle me-1"></i>Todos los personajes encontrados ya han sido generados.';
                         document.getElementById('char-manual-container').prepend(msg);
                    }
                } else {
                    // Populate Select
                    select.disabled = false;
                    fetchedCharacters.forEach((char, index) => {
                        const opt = document.createElement('option');
                        opt.value = index;
                        opt.text = char.name + (char.source_count > 1 ? ` (${char.source_count} fuentes)` : '');
                        select.add(opt);
                    });
                }
            } else {
                // Error fallback -> Manual Mode
                console.warn('API Error:', data.error);
                document.getElementById('char-list-loader').classList.add('d-none');
                document.getElementById('char-selection-area').classList.remove('d-none');
                document.getElementById('char-select-container').classList.add('d-none');
                document.getElementById('char-manual-container').classList.remove('d-none');
            }
        })
        .catch(err => {
            console.error(err);
            // Network Error fallback -> Manual Mode
            document.getElementById('char-list-loader').classList.add('d-none');
            document.getElementById('char-selection-area').classList.remove('d-none');
            document.getElementById('char-select-container').classList.add('d-none');
            document.getElementById('char-manual-container').classList.remove('d-none');
        });
    }

    function hideCharGen() {
        document.getElementById('char-gen-ui').classList.add('d-none');
    }

    function updateCharPreview() {
        const index = document.getElementById('char-select').value;
        const previewBox = document.getElementById('char-desc-preview');
        const descText = document.getElementById('char-desc-text');
        const btn = document.getElementById('btn-create-char');

        if (index === "" || !fetchedCharacters[index]) {
            previewBox.classList.add('d-none');
            btn.disabled = true;
            return;
        }

        const char = fetchedCharacters[index];
        if (char.description) {
            descText.textContent = char.description.substring(0, 150) + (char.description.length > 150 ? '...' : '');
            previewBox.classList.remove('d-none');
        } else {
            previewBox.classList.add('d-none');
        }
        btn.disabled = false;
    }

    function updateManualPreview() {
        const val = document.getElementById('char-manual-input').value.trim();
        const btn = document.getElementById('btn-create-char');
        btn.disabled = (val.length < 2);
    }

    function generateSelectedChar() {
        const isManual = !document.getElementById('char-manual-container').classList.contains('d-none');
        let charData = null;

        if (isManual) {
            const name = document.getElementById('char-manual-input').value.trim();
            if (name.length < 2) return;
            charData = { name: name, description: '' };
        } else {
            const index = document.getElementById('char-select').value;
            if (index === "" || !fetchedCharacters[index]) return;
            charData = fetchedCharacters[index];
        }
        
        document.getElementById('char-selection-area').classList.add('d-none');
        document.getElementById('char-generating-loader').classList.remove('d-none');

        fetch('/books/generate-single-character', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                book_id: BOOK_ID,
                character: charData
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                addCharacterToGrid(data.character);
                // Reset UI to allow another generation
                document.getElementById('char-generating-loader').classList.add('d-none');
                document.getElementById('char-selection-area').classList.remove('d-none');
                
                if (!isManual) {
                    // Remove generated char from dropdown
                    const select = document.getElementById('char-select');
                    select.remove(select.selectedIndex);
                    select.value = "";
                    updateCharPreview();
                    
                    // If list empty
                    if (select.options.length <= 1) {
                        // Switch to manual if list exhausted
                        document.getElementById('char-select-container').classList.add('d-none');
                        document.getElementById('char-manual-container').classList.remove('d-none');
                    }
                } else {
                    document.getElementById('char-manual-input').value = '';
                    updateManualPreview();
                }
            } else {
                alert('Error generando personaje: ' + (data.error || 'Inténtalo de nuevo'));
                document.getElementById('char-generating-loader').classList.add('d-none');
                document.getElementById('char-selection-area').classList.remove('d-none');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión');
            document.getElementById('char-generating-loader').classList.add('d-none');
            document.getElementById('char-selection-area').classList.remove('d-none');
        });
    }

    function addCharacterToGrid(char) {
        const list = document.getElementById('characters-list');
        const noMsg = document.getElementById('no-chars-msg');
        if (noMsg) noMsg.remove();

        const col = document.createElement('div');
        col.className = 'col-md-6 mb-4 fade-in-up'; // Add animation class if available
        col.innerHTML = `
            <div class="card character-card h-100 border-0 shadow-sm">
                <div class="row g-0 h-100">
                    <div class="col-md-5">
                        <img src="${char.image_url}" class="img-fluid rounded-start h-100" style="object-fit: cover; min-height: 200px;" alt="${char.name}">
                    </div>
                    <div class="col-md-7">
                        <div class="card-body">
                            <h5 class="card-title fw-bold">${char.name}</h5>
                            <p class="card-text small text-muted line-clamp-3">${char.description || 'Sin descripción'}</p>
                            <div class="mt-2">
                                ${(char.traits || []).slice(0,3).map(t => `<span class="badge bg-light text-dark border me-1">${t}</span>`).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        list.prepend(col);
    }

    // Handle vinyl play
    let currentAudio = null;
    let currentVinyl = null;
</script>
<script>
            // Generate Stars
            const starsContainer = document.getElementById('stars-container');
            const starCount = 300;

            for (let i = 0; i < starCount; i++) {
                const star = document.createElement('div');
                star.classList.add('star');
                const x = Math.random() * 100;
                const y = Math.random() * 100;
                const size = Math.random() * 2 + 1; // 1px to 3px
                const duration = Math.random() * 3 + 2; // 2s to 5s
                
                star.style.left = x + '%';
                star.style.top = y + '%';
                star.style.width = size + 'px';
                star.style.height = size + 'px';
                star.style.setProperty('--duration', duration + 's');
                star.style.animationDelay = (Math.random() * 5) + 's';
                
                starsContainer.appendChild(star);
            }
        </script>
        <!-- Player Bar -->
        <div id="player-bar" class="player-bar d-none">
            <div id="ai-visualizer" class="ai-visualizer">
                <div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div>
                <div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div>
                <div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div>
                <div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div><div class="bar"></div>
            </div>
            <div class="container d-flex align-items-center justify-content-between h-100 position-relative" style="z-index: 2;">
                <div class="d-flex align-items-center gap-3" style="width: 30%;">
                    <div id="player-art" class="player-art">
                        <i class="bi bi-music-note-beamed"></i>
                    </div>
                    <div class="overflow-hidden">
                        <h6 id="player-title" class="mb-0 text-white text-truncate">Selecciona una canción</h6>
                        <small id="player-artist" class="text-white-50 text-truncate">Artist</small>
                    </div>
                </div>
                
                <div class="d-flex align-items-center gap-3 justify-content-center" style="width: 40%;">
                    <button id="player-prev" class="btn btn-link text-white p-0 opacity-50"><i class="bi bi-skip-start-fill fs-4"></i></button>
                    <button id="player-play-pause" onclick="togglePlay()" class="btn btn-light rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                        <i class="bi bi-play-fill fs-4"></i>
                    </button>
                    <button id="player-next" class="btn btn-link text-white p-0 opacity-50"><i class="bi bi-skip-end-fill fs-4"></i></button>
                </div>
                
                <div class="d-flex align-items-center justify-content-end gap-3" style="width: 30%;">
                    <!-- Hidden YouTube Container -->
                    <div id="youtube-container" style="width: 1px; height: 1px; opacity: 0; pointer-events: none; position: absolute; bottom: -1000px;"></div>
                    <button onclick="closePlayer()" class="btn btn-link text-white-50 p-0"><i class="bi bi-x-lg"></i></button>
                </div>
            </div>
        </div>

        <script>
            let isPlaying = false;
            let currentMode = null; // 'youtube' or 'ai'
            let audioContext = null;
            let aiOscillators = [];

            function playYouTubeTrack(url, title, artist) {
                stopPlayback();
                currentMode = 'youtube';
                showPlayer(title, artist);
                
                let videoId = '';
                // Handle different YouTube URL formats
                const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
                const match = url.match(regExp);
                if (match && match[2].length === 11) {
                    videoId = match[2];
                } else {
                    console.error('Invalid YouTube URL');
                    return;
                }
                
                const container = document.getElementById('youtube-container');
                // Autoplay=1 starts it
                container.innerHTML = `<iframe id="yt-iframe" width="200" height="200" src="https://www.youtube.com/embed/${videoId}?autoplay=1&enablejsapi=1" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>`;
                
                updatePlayButton(true);
            }

            function playAiTrack(title, lyrics, melody, mood, variation = 0) {
                stopPlayback();
                currentMode = 'ai';
                const artistEl = document.getElementById('player-artist');
                artistEl.textContent = 'Componiendo una obra maestra... (puede tardar unos segundos)';
                artistEl.classList.remove('text-truncate');
                showPlayer(title, 'BookVibes AI Composer');
                
                // Visualizer
                document.getElementById('ai-visualizer').classList.add('active');
                
                // 1. Setup Context (Minimum 1:30 = 90 seconds)
                const duration = 96; // 96 seconds for full bars
                const sampleRate = 44100;
                const OfflineContext = window.OfflineAudioContext || window.webkitOfflineAudioContext;
                const audioCtx = new OfflineContext(2, sampleRate * duration, sampleRate);
                
                // 2. Music Theory / Mood Logic
                // BPM: Terror/Mystery (Slow/Heavy), Adventure/Joy (Fast), Romance (Medium/Slow)
                let bpm = 90;
                let scaleType = 'Minor'; // Default
                
                if (mood === 'Terror' || mood === 'Misterio') {
                    bpm = 70;
                    scaleType = 'Phrygian';
                } else if (mood === 'Aventura' || mood === 'Alegría') {
                    bpm = 120;
                    scaleType = 'Major';
                } else if (mood === 'Romance') {
                    bpm = 75;
                    scaleType = 'Major'; // Lydian-ish
                }
                
                // Variation Logic (Force Distinctness)
                if (variation === 1) {
                    // Invert/Shift vibe
                    if (bpm < 100) {
                        bpm = bpm + 40; // Slow -> Fast
                    } else {
                        bpm = bpm - 30; // Fast -> Slow
                    }
                    
                    // Shift Scale
                    if (scaleType === 'Major') scaleType = 'Mixolydian';
                    else if (scaleType === 'Minor') scaleType = 'Dorian';
                    else if (scaleType === 'Phrygian') scaleType = 'Locrian';
                }
                
                const beatTime = 60 / bpm;
                const barTime = beatTime * 4;
                
                // Scales (MIDI Note Numbers relative to Root)
                const scales = {
                    'Major': [0, 2, 4, 5, 7, 9, 11],
                    'Minor': [0, 2, 3, 5, 7, 8, 10],
                    'Phrygian': [0, 1, 3, 5, 7, 8, 10],
                    'Dorian': [0, 2, 3, 5, 7, 9, 10],
                    'Mixolydian': [0, 2, 4, 5, 7, 9, 10],
                    'Locrian': [0, 1, 3, 5, 6, 8, 10]
                };
                const scale = scales[scaleType] || scales['Minor'];
                const rootNote = 60; // C4 (Middle C)

                // Helpers
                function m2f(note) { return 440 * Math.pow(2, (note - 69) / 12); }
                function getNote(octaveOffset = 0) {
                    const degree = Math.floor(Math.random() * scale.length);
                    const note = rootNote + scale[degree] + (octaveOffset * 12);
                    return m2f(note);
                }
                function getChord(rootIdx) {
                    return [0, 2, 4].map(offset => {
                        const idx = (rootIdx + offset) % scale.length;
                        const octave = Math.floor((rootIdx + offset) / scale.length);
                        return rootNote + scale[idx] + (octave * 12);
                    });
                }

                // 3. Sequencer / Arranger
                // Structure: 
                // Intro: 0 - 16s (4 bars)
                // Verse 1: 16 - 48s (8 bars)
                // Chorus: 48 - 64s (4 bars)
                // Verse 2: 64 - 80s (4 bars)
                // Outro: 80 - 96s (4 bars)
                
                // -- Master FX --
                const masterGain = audioCtx.createGain();
                masterGain.gain.value = 0.5;
                const compressor = audioCtx.createDynamicsCompressor();
                compressor.threshold.value = -20;
                compressor.ratio.value = 12;
                masterGain.connect(compressor);
                compressor.connect(audioCtx.destination);
                
                // -- Instruments Setup --
                
                // Reverb (Simple Delay for now as Convolution is heavy without file)
                const reverbInput = audioCtx.createGain();
                reverbInput.gain.value = 0.3;
                const reverbDelay = audioCtx.createDelay();
                reverbDelay.delayTime.value = 0.1; // Slapback
                reverbInput.connect(reverbDelay);
                reverbDelay.connect(masterGain);

                // A. Pads (Chords/Atmosphere) - Background Texture
                const padGain = audioCtx.createGain();
                padGain.gain.value = 0.15;
                padGain.connect(masterGain);
                padGain.connect(reverbInput);

                // B. Bass - Foundation
                const bassGain = audioCtx.createGain();
                bassGain.gain.value = 0.35;
                bassGain.connect(masterGain);

                // C. Lead/Arp - Melody
                const leadGain = audioCtx.createGain();
                leadGain.gain.value = 0.2;
                leadGain.connect(masterGain);
                leadGain.connect(reverbInput);

                // D. Drums
                const drumGain = audioCtx.createGain();
                drumGain.gain.value = 0.4;
                drumGain.connect(masterGain);

                // Noise Buffer for Drums
                const noiseLen = sampleRate * 2;
                const noiseBuffer = audioCtx.createBuffer(1, noiseLen, sampleRate);
                const noiseData = noiseBuffer.getChannelData(0);
                for (let i = 0; i < noiseLen; i++) noiseData[i] = Math.random() * 2 - 1;

                // -- Scheduling Loop --
                // We iterate by Bars
                const totalBars = Math.ceil(duration / barTime);
                
                for (let bar = 0; bar < totalBars; bar++) {
                    const time = bar * barTime;
                    const isIntro = bar < 4;
                    const isVerse = bar >= 4 && bar < 12; // 8 bars
                    const isChorus = bar >= 12 && bar < 16; // 4 bars
                    const isVerse2 = bar >= 16 && bar < 20; // 4 bars
                    const isOutro = bar >= 20;

                    // Chord Progression (Random but consistent per bar)
                    const chordRootIdx = Math.floor(Math.random() * scale.length);
                    const chord = getChord(chordRootIdx);
                    
                    // 1. Pads (Always active except maybe very start)
                    if (!isOutro || Math.random() > 0.5) {
                        chord.forEach((note, i) => {
                            const osc = audioCtx.createOscillator();
                            osc.type = mood === 'Terror' ? 'sawtooth' : 'triangle';
                            osc.frequency.value = m2f(note - 12); // Lower octave
                            
                            const env = audioCtx.createGain();
                            env.gain.setValueAtTime(0, time);
                            env.gain.linearRampToValueAtTime(0.1, time + 1);
                            env.gain.exponentialRampToValueAtTime(0.001, time + barTime);
                            
                            osc.connect(env).connect(padGain);
                            osc.start(time);
                            osc.stop(time + barTime);
                        });
                    }

                    // 2. Bass (Verse, Chorus, Verse2)
                    if (isVerse || isChorus || isVerse2) {
                        // Simple pattern: Root on beat 1, maybe others
                        const steps = isChorus ? 8 : 4; // 8th notes in chorus, quarter in verse
                        for(let s=0; s<steps; s++) {
                            const stepTime = time + (s * (barTime/steps));
                            const osc = audioCtx.createOscillator();
                            osc.type = 'square';
                            osc.frequency.value = m2f(chord[0] - 24); // Deep bass
                            
                            // Lowpass Filter
                            const filter = audioCtx.createBiquadFilter();
                            filter.type = 'lowpass';
                            filter.frequency.setValueAtTime(400, stepTime);
                            filter.frequency.exponentialRampToValueAtTime(100, stepTime + 0.1);

                            const env = audioCtx.createGain();
                            env.gain.setValueAtTime(0.3, stepTime);
                            env.gain.exponentialRampToValueAtTime(0.01, stepTime + (barTime/steps)*0.8);
                            
                            osc.connect(filter).connect(env).connect(bassGain);
                            osc.start(stepTime);
                            osc.stop(stepTime + (barTime/steps));
                        }
                    }

                    // 3. Lead Melody (Verse: Sparse, Chorus: Active)
                    if (isVerse || isChorus || isVerse2) {
                        const density = isChorus ? 0.8 : 0.4;
                        const steps = 8; // 8th notes
                        for(let s=0; s<steps; s++) {
                            if(Math.random() < density) {
                                const stepTime = time + (s * (barTime/steps));
                                const osc = audioCtx.createOscillator();
                                osc.type = 'sine';
                                osc.frequency.value = getNote(isChorus ? 1 : 0); // Higher in chorus
                                
                                const env = audioCtx.createGain();
                                env.gain.setValueAtTime(0, stepTime);
                                env.gain.linearRampToValueAtTime(0.1, stepTime + 0.05);
                                env.gain.exponentialRampToValueAtTime(0.001, stepTime + 0.3);
                                
                                osc.connect(env).connect(leadGain);
                                osc.start(stepTime);
                                osc.stop(stepTime + 0.4);
                            }
                        }
                    }

                    // 4. Drums (Chorus: Heavy, Verse: Light)
                    if (isVerse || isChorus || isVerse2) {
                        // Kick: Beats 1 and 3 (and more in Chorus)
                        [0, 2].forEach(beat => {
                            const beatT = time + (beat * beatTime);
                            const osc = audioCtx.createOscillator();
                            osc.frequency.setValueAtTime(150, beatT);
                            osc.frequency.exponentialRampToValueAtTime(0.01, beatT + 0.5);
                            const env = audioCtx.createGain();
                            env.gain.setValueAtTime(0.8, beatT);
                            env.gain.exponentialRampToValueAtTime(0.001, beatT + 0.5);
                            osc.connect(env).connect(drumGain);
                            osc.start(beatT);
                            osc.stop(beatT + 0.5);
                        });
                        
                        // Snare/Hihat: Beats 2 and 4
                        [1, 3].forEach(beat => {
                             const beatT = time + (beat * beatTime);
                             const src = audioCtx.createBufferSource();
                             src.buffer = noiseBuffer;
                             const filt = audioCtx.createBiquadFilter();
                             filt.type = 'highpass';
                             filt.frequency.value = isChorus ? 1000 : 3000; // Snare vs Hihat-ish
                             const env = audioCtx.createGain();
                             env.gain.setValueAtTime(0.3, beatT);
                             env.gain.exponentialRampToValueAtTime(0.01, beatT + 0.1);
                             src.connect(filt).connect(env).connect(drumGain);
                             src.start(beatT);
                        });
                    }
                }
                
                audioCtx.startRendering().then(function(renderedBuffer) {
                    const blob = bufferToWave(renderedBuffer, renderedBuffer.length);
                    const url = URL.createObjectURL(blob);
                    
                    const container = document.getElementById('youtube-container'); // Reuse container
                    // Create standard audio element
                    const audio = document.createElement('audio');
                    audio.id = 'ai-audio-player';
                    audio.controls = true;
                    audio.src = url;
                    audio.autoplay = true;
                    audio.style.display = 'none'; // hidden player, controlled by custom UI
                    
                    audio.onended = () => {
                        document.getElementById('ai-visualizer').classList.remove('active');
                        updatePlayButton(false);
                    };
                    
                    container.appendChild(audio);
                    updatePlayButton(true);
                    
                    // Add Download Link to Player
                    artistEl.innerHTML = `BookVibes AI &bull; <a href="${url}" download="${title}.wav" class="text-white text-decoration-underline ms-2">Descargar MP3 (Inédita)</a>`;
 
                 }).catch(function(err) {
                     console.error('Rendering failed: ' + err);
                     artistEl.textContent = 'Error al generar audio';
                 });
             }
 
             // Helper to convert AudioBuffer to WAV Blob
             function bufferToWave(abuffer, len) {
                 var numOfChan = abuffer.numberOfChannels,
                     length = len * numOfChan * 2 + 44,
                     buffer = new ArrayBuffer(length),
                     view = new DataView(buffer),
                     channels = [], i, sample, offset = 0,
                     pos = 0;
 
                 function setUint16(data) { view.setUint16(pos, data, true); pos += 2; }
                 function setUint32(data) { view.setUint32(pos, data, true); pos += 4; }
 
                 setUint32(0x46464952); // "RIFF"
                 setUint32(length - 8); // file length - 8
                 setUint32(0x45564157); // "WAVE"
 
                 setUint32(0x20746d66); // "fmt " chunk
                 setUint32(16); // length = 16
                 setUint16(1); // PCM (uncompressed)
                 setUint16(numOfChan);
                 setUint32(abuffer.sampleRate);
                 setUint32(abuffer.sampleRate * 2 * numOfChan); // avg. bytes/sec
                 setUint16(numOfChan * 2); // block-align
                 setUint16(16); // 16-bit
 
                 setUint32(0x61746164); // "data" - chunk
                 setUint32(length - pos - 4); // chunk length
 
                 for(i = 0; i < abuffer.numberOfChannels; i++)
                     channels.push(abuffer.getChannelData(i));
 
                 // Write interleaved data
                 for(let k = 0; k < len; k++) {
                      for(i = 0; i < numOfChan; i++) {
                         sample = channels[i][k];
                         // Clamp and scale to 16-bit PCM
                         sample = Math.max(-1, Math.min(1, sample));
                         sample = (sample < 0 ? sample * 0x8000 : sample * 0x7FFF) | 0;
                         view.setInt16(pos, sample, true);
                         pos += 2;
                      }
                 }
 
                 return new Blob([buffer], {type: "audio/wav"});
             }

            function stopAiAudio() {
                // No need to stop oscillators, just stop the audio element
                const audio = document.getElementById('ai-audio-player');
                if(audio) {
                    audio.pause();
                    audio.src = '';
                }
            }

            function togglePlay() {
                if (currentMode === 'ai') {
                    const audio = document.getElementById('ai-audio-player');
                    if (audio) {
                        if (audio.paused) {
                            audio.play();
                            document.getElementById('ai-visualizer').classList.add('active');
                            updatePlayButton(true);
                        } else {
                            audio.pause();
                            document.getElementById('ai-visualizer').classList.remove('active');
                            updatePlayButton(false);
                        }
                    }
                } else if (currentMode === 'youtube') {
                    // For YouTube iframe, we can't easily toggle without API, 
                    // but we can just rebuild/clear. 
                    // Better: use postMessage to pause/play if possible, or just stop for now.
                    // Simple implementation: Stop if playing.
                    const iframe = document.getElementById('yt-iframe');
                    if (iframe) {
                        // This is a hacky toggle for iframe without full API object
                        // To properly toggle, we'd need the YT Player API object.
                        // For now, let's just Close/Stop on pause.
                        closePlayer();
                    }
                }
            }

            function stopPlayback() {
                // Stop YouTube
                document.getElementById('youtube-container').innerHTML = '';
                
                // Stop TTS & Audio
                window.speechSynthesis.cancel();
                stopAiAudio();
                
                document.getElementById('ai-visualizer').classList.remove('active');
                updatePlayButton(false);
                isPlaying = false;
            }

            function showPlayer(title, artist) {
                const bar = document.getElementById('player-bar');
                bar.classList.remove('d-none');
                // Trigger reflow
                void bar.offsetWidth; 
                bar.classList.add('active');
                
                document.getElementById('player-title').textContent = title;
                document.getElementById('player-artist').textContent = artist;
            }

            function closePlayer() {
                stopPlayback();
                const bar = document.getElementById('player-bar');
                bar.classList.remove('active');
                setTimeout(() => bar.classList.add('d-none'), 300);
            }
            
            function updatePlayButton(playing) {
                isPlaying = playing;
                const btn = document.getElementById('player-play-pause');
                if (playing) {
                    btn.innerHTML = '<i class="bi bi-pause-fill fs-4"></i>';
                } else {
                    btn.innerHTML = '<i class="bi bi-play-fill fs-4"></i>';
                }
            }
        </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const bookId = <?= json_encode($book['id']) ?>;
    const isPro = <?= json_encode($isPro) ?>;

    // Playlist Generation
    const playlistLoader = document.getElementById('playlist-loader');
    if (playlistLoader) {
        fetch('/books/generate-playlist', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book_id: bookId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.ok && data.playlist && data.playlist.songs && data.playlist.songs.length > 0) {
                const songs = data.playlist.songs;
                const limit = isPro ? 999 : 7;
                let html = '';
                
                // Vinyl Trigger
                html += `
                <div class="visual-album-container text-center" onclick="document.getElementById('full-playlist').classList.toggle('show');">
                    <div class="vinyl-record">
                        <div class="vinyl-label">
                            ${(data.playlist.mood || 'Vibes').substring(0, 10)}
                        </div>
                    </div>
                    <h6 class="mt-4 mb-1 fw-bold text-white">Playlist Generada</h6>
                    <p class="small text-white-50 mb-0">${songs.length} Pistas • Tocar para ver</p>
                </div>
                <div id="full-playlist">
                    <div class="list-group list-group-flush">
                `;

                let aiCount = 0;
                songs.forEach((song, index) => {
                    if (index >= limit) return;
                    const isAi = !!song.is_ai_generated;
                    const variation = isAi ? (aiCount++ % 2) : 0;
                    const aiClass = isAi ? 'ai-track' : '';
                    const aiBadge = isAi ? '<span class="badge bg-primary me-1" style="font-size: 0.6em; vertical-align: middle;">AI ORIGINAL</span>' : '';
                    const melodyDesc = isAi ? `<small class="text-info d-block" style="font-size: 0.75em;">${song.melody_description || ''}</small>` : '';
                    
                    let playBtn = '';
                    if (isAi) {
                        const safeTitle = JSON.stringify(song.title).replace(/'/g, "&#39;");
                        const safeLyrics = JSON.stringify(song.lyrics || '').replace(/'/g, "&#39;");
                        const safeMelody = JSON.stringify(song.melody_description || '').replace(/'/g, "&#39;");
                        const safeMood = JSON.stringify(data.playlist.mood || 'General').replace(/'/g, "&#39;");
                        playBtn = `<button type="button" class="btn-listen" onclick='playAiTrack(${safeTitle}, ${safeLyrics}, ${safeMelody}, ${safeMood}, ${variation})'><i class="bi bi-play-fill"></i></button>`;
                    } else {
                         const safeUrl = (song.url || '').replace(/'/g, "\\'");
                         const safeTitle = (song.title || '').replace(/'/g, "\\'");
                         const safeArtist = (song.artist || '').replace(/'/g, "\\'");
                         playBtn = `<button type="button" class="btn-listen" onclick="playYouTubeTrack('${safeUrl}', '${safeTitle}', '${safeArtist}')"><i class="bi bi-play-fill"></i></button>`;
                    }

                    html += `
                    <div class="playlist-track ${aiClass}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="me-3 overflow-hidden">
                                <div class="fw-semibold text-truncate">
                                    ${aiBadge} ${song.title}
                                </div>
                                <small class="text-muted text-truncate d-block">${song.artist}</small>
                                ${melodyDesc}
                            </div>
                            ${playBtn}
                        </div>
                    </div>`;
                });

                html += `</div>`; // Close list-group
                
                // Add Pro/Upgrade Actions
                html += `<div class="p-3 bg-dark bg-opacity-25 border-top border-secondary border-opacity-10">`;
                if (!isPro && songs.length > 7) {
                     html += `<div class="text-center py-2"><small class="text-muted d-block mb-2">Mostrando 7 de ${songs.length} canciones</small><a href="/pro/upgrade?book_id=${bookId}" class="btn btn-sm btn-primary-glow w-100"><i class="bi bi-unlock-fill me-1"></i> Desbloquear Todo (Pro)</a></div>`;
                } else if (!isPro) {
                     html += `<div class="d-grid mt-2"><a href="/pro/upgrade?book_id=${bookId}" class="btn btn-outline-light btn-sm"><i class="bi bi-stars me-1"></i> Mejorar Recomendaciones</a></div>`;
                } else {
                    html += `<div class="d-grid gap-2"><a href="/books/add-songs?id=${bookId}" class="btn btn-outline-light btn-sm"><i class="bi bi-plus-circle me-2"></i>Añadir 10 canciones más</a></div>`;
                }
                html += `</div></div>`; // Close full-playlist and footer

                document.getElementById('playlist-container').innerHTML = html;
            } else {
                document.getElementById('playlist-container').innerHTML = '<div class="p-4 text-center text-muted">No se pudo generar la playlist.</div>';
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('playlist-container').innerHTML = '<div class="p-4 text-center text-muted">Error al cargar playlist.</div>';
        });
    }

    // Character Generation
    const charLoader = document.getElementById('characters-loader');
    if (charLoader) {
        fetch('/books/generate-characters', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book_id: bookId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.ok && data.characters && data.characters.length > 0) {
                charLoader.remove();
                const container = document.getElementById('characters-list');
                
                data.characters.forEach(char => {
                    let traits = [];
                    try {
                        traits = (typeof char.traits === 'string') ? JSON.parse(char.traits) : char.traits;
                    } catch(e) {}
                    if (!Array.isArray(traits)) traits = [];
                    const traitsHtml = traits.slice(0, 3).map(t => `<span class="badge bg-secondary bg-opacity-10 text-dark border border-secondary border-opacity-10 me-1 mb-1" style="font-weight: 500;">${t}</span>`).join('');
                    
                    const html = `
                    <div class="col-6 col-md-4 col-lg-3 mb-4">
                        <div class="card character-card h-100 border-0 shadow-sm overflow-hidden transition-hover">
                            <div class="character-img-wrapper position-relative" style="padding-top: 100%;">
                                <img src="${char.image_url}" class="position-absolute top-0 start-0 w-100 h-100" style="object-fit: cover; transition: transform 0.5s ease;" alt="${char.name}">
                                <div class="position-absolute bottom-0 start-0 w-100 p-3" style="background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);">
                                    <h5 class="card-title text-white mb-0 fw-bold" style="text-shadow: 0 2px 4px rgba(0,0,0,0.5); font-size: 1.1rem;">${char.name}</h5>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <div class="mb-2">
                                    ${traitsHtml}
                                </div>
                                <p class="card-text small text-muted" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5;">${char.description || 'Sin descripción disponible.'}</p>
                            </div>
                        </div>
                    </div>`;
                    container.insertAdjacentHTML('beforeend', html);
                });
            } else {
                charLoader.innerHTML = '<div class="alert alert-info">No se encontraron personajes.</div>';
            }
        })
        .catch(err => {
            console.error(err);
            charLoader.innerHTML = '<div class="alert alert-warning">Error al generar personajes.</div>';
        });
    }
});
</script>
</body>
</html>
