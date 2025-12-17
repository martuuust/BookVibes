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
            height: 100%;
            min-height: 100vh;
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
        .back-nav {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 20;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 99px;
            background: rgba(255, 255, 255, 0.9);
            color: #1e293b;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.2s;
            border: 1px solid rgba(0,0,0,0.05);
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
            color: inherit;
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
            max-height: 1200px; /* Sufficient height for content */
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
            overflow: hidden;
        }
        .star {
            position: absolute;
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
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/dashboard" style="letter-spacing: -0.5px; font-size: 1.8rem;">
        <div class="position-relative d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
            <i class="bi bi-book-half" style="font-size: 2.5rem; color: #8b5cf6;"></i>
            <i class="bi bi-music-note-beamed position-absolute" style="color: #2dd4bf; font-size: 1.2rem; top: 10px; right: 5px; transform: rotate(15deg);"></i>
        </div>
        <span id="bookVibesText" class="navbar-brand-text" style="font-size: 1.5rem;">BookVibes</span>
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
            const bookVibesText = document.getElementById('bookVibesText');

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
            const starCount = 300;

            // Set container height to document scroll height
            starsContainer.style.height = document.body.scrollHeight + 'px';

            for (let i = 0; i < starCount; i++) {
                const star = document.createElement('div');
                star.classList.add('star');
                const x = Math.random() * 100; // X position in percentage
                const y = (Math.random() * document.body.scrollHeight / starsContainer.offsetHeight) * 100; // Y position in percentage relative to container height
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
        <a href="/dashboard" class="back-nav">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
        <h1 class="display-4 fw-bold mb-3 book-title-glow"><?= htmlspecialchars($book['title']) ?></h1>
        <p class="lead mb-4 opacity-75" style="color: var(--text-body);">por <?= htmlspecialchars($book['author']) ?></p>
        <span class="badge bg-warning text-black fs-6 px-4 py-2 rounded-pill shadow-sm"><?= htmlspecialchars($book['mood']) ?></span>
    </div>
</div>

<div class="container mt-4">
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
                
                <div class="p-0">
                    <?php if($playlist && isset($playlist['songs'])): ?>
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
                                    foreach($playlist['songs'] as $song): 
                                        if($i++ >= $limit) break; 
                                ?>
                                    <div class="playlist-track">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="me-3 overflow-hidden">
                                                <div class="fw-semibold text-truncate"><?= htmlspecialchars($song['title']) ?></div>
                                                <small class="text-muted text-truncate d-block"><?= htmlspecialchars($song['artist']) ?></small>
                                            </div>
                                            <a href="<?= htmlspecialchars($song['url']) ?>" target="_blank" rel="noopener" class="btn-listen">
                                                <i class="bi bi-play-fill"></i>
                                            </a>
                                        </div>
                                    </div>
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
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-music-note-beamed fs-1 d-block mb-2 opacity-25"></i>
                            No hay playlist disponible.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Characters Col (Old Design) -->
        <div class="col-md-8 order-1 order-md-2">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body">
                    <h5>Sinopsis</h5>
                    <p class="text-muted"><?= htmlspecialchars($book['synopsis']) ?></p>
                </div>
            </div>
            <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                <h3 class="mb-0">Cartas de Personajes</h3>
                <?php if(!$isPro): ?>
                    <span class="badge bg-dark bg-opacity-25 border border-secondary border-opacity-25 text-muted"><i class="bi bi-lock-fill me-1"></i> Pro</span>
                <?php endif; ?>
            </div>

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

                <div class="row <?= !$isPro ? 'blur-content' : '' ?>">
                    <?php if (empty($characters)): ?>
                        <?php if(!$isPro): // Fake placeholders if empty but locked ?>
                             <?php for($i=0; $i<4; $i++): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card character-card h-100 border-0 shadow-sm">
                                        <div class="row g-0 h-100">
                                            <div class="col-md-5 bg-secondary"></div>
                                            <div class="col-md-7"><div class="card-body"></div></div>
                                        </div>
                                    </div>
                                </div>
                             <?php endfor; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info">No hay información suficiente para mostrar personajes de fuentes verificables.</div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
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
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>


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
    </body>
</html>
