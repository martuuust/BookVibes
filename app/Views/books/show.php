<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($book['title']) ?> - BookVibes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <!-- Leaflet.js for interactive maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <style>


        .blur-content {
            filter: blur(8px);
            pointer-events: none;
            user-select: none;
            opacity: 0.8;
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
            color: var(--text-body);
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
        height: 100px;
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

    /* --- New Styles for Playlist (Glassmorphism) --- */
    .glass-card,
    #playlist-container,
    #full-playlist {
        background: #1e293b !important; /* Force dark background (Slate 800) */
        color: #ffffff !important; /* Force white text globally */
    }

    .glass-card {
        border: 1px solid var(--card-border);
        border-radius: 16px;
        backdrop-filter: blur(10px);
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
    }
        
        /* High Specificity Overrides for Playlist Content */
        .glass-card *, 
        #playlist-container *, 
        #full-playlist * {
            color: #ffffff; /* Base color for all children */
        }

        /* FIX: Remove Bootstrap's default white background from list groups */
        .glass-card .list-group,
        .glass-card .list-group-item,
        .glass-card .playlist-track {
            background: transparent !important;
            background-color: transparent !important;
        }
        
        /* Specific overrides for muted text and buttons */
        .glass-card .text-muted, 
        #playlist-container .text-muted, 
        #full-playlist .text-muted {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        
        /* Preserve button colors */
        .glass-card .btn-spotify, 
        .glass-card .btn-outline-warning {
            color: inherit; 
        }
        .glass-card .btn-spotify { color: #ffffff !important; }
        .glass-card .btn-outline-warning { color: #ffc107 !important; }
        
        .card-header-custom {
            padding: 1.25rem;
            border-bottom: 1px solid var(--card-border);
            background: rgba(255, 255, 255, 0.02);
            border-top-left-radius: 16px;
            border-top-right-radius: 16px;
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
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(5px); }
        }
        .animate-bounce {
            animation: bounce 2s infinite;
        }

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
        
        .song-icon {
            width: 42px;
            height: 42px;
            min-width: 42px;
            min-height: 42px;
            flex: 0 0 42px;
            background: linear-gradient(135deg, #8b5cf6 0%, #d946ef 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(139, 92, 246, 0.3);
            aspect-ratio: 1 / 1;
        }

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



        /* Detail Page Back Navigation */
        .book-header {
            position: relative;
        }
        .back-nav {
            position: absolute;
            top: 30px;
            left: 30px;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            color: #334155;
            text-decoration: none !important;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            z-index: 100;
            font-size: 1.25rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.5);
        }
        .back-nav:hover {
            transform: translateX(-5px) scale(1.05);
            background: white;
            color: #6366f1;
            box-shadow: 0 12px 24px rgba(99, 102, 241, 0.2);
        }
        body.dark-mode .back-nav {
            background: rgba(30, 41, 59, 0.8);
            color: #cbd5e1;
            border-color: rgba(255,255,255,0.1);
        }
        body.dark-mode .back-nav:hover {
            background: #1e293b;
            color: white;
        }
        @media (max-width: 768px) {
            .back-nav {
                top: 15px;
                left: 15px;
                width: 38px;
                height: 38px;
            }
        }

        /* Title Visibility in Light Mode */
        body:not(.dark-mode) .book-title-glow {
            color: #ffffff; 
            /* Dark Purple Border */
            -webkit-text-stroke: 2px #2e1065; 
            /* Neon Glow + Hard Shadow */
            text-shadow: 
                3px 3px 0px #2e1065, /* Depth shadow */
                0 0 10px #a855f7,    /* Inner Neon Glow */
                0 0 20px #a855f7,    /* Outer Neon Glow */
                0 0 40px #7e22ce;    /* Far Glow */
            paint-order: stroke fill;
        }
        
        /* Optional: Dark mode style if it needs enhancements, currently inherit or unchanged */
        body.dark-mode .book-title-glow {
             color: var(--text-main);
             text-shadow: 0 0 20px rgba(139, 92, 246, 0.5); /* Soft purple glow for dark mode */
        }

        /* Mobile Optimization */
        @media (max-width: 768px) {
            .book-title-glow {
                font-size: 2rem !important;
            }
            .book-header {
                padding: 2rem 1rem !important;
            }
            .navbar-logo {
                height: 60px;
            }
            .lead {
                font-size: 1rem;
            }
            .glass-card, .card {
                margin-bottom: 1.5rem !important;
            }
            /* Stack columns visually if not handled by grid */
            .order-1 { order: 1 !important; }
            .order-2 { order: 2 !important; }
            
            /* Adjust map card height */
            .card .position-relative[style*="height: 250px"] {
                height: 200px !important;
            }
        }

        /* Modal Styles */
    .upgrade-modal-content {
        border-radius: 24px;
        overflow: hidden;
        transition: background 0.3s, color 0.3s;
        /* Light Mode: Pastel Gradient */
        background: linear-gradient(135deg, #e9d5ff 0%, #bae6fd 100%);
        color: #1e293b;
        border: 1px solid rgba(255,255,255,0.5);
    }
    .upgrade-modal-content h3 { color: #1e293b; }
    .upgrade-modal-content .modal-text-muted { color: #475569 !important; }
    .upgrade-modal-content .modal-icon-bg { background: rgba(255,255,255,0.6); }
    .upgrade-modal-content .btn-link { color: #64748b !important; }

    /* Dark Mode Modal */
    body.dark-mode .upgrade-modal-content {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
        color: white;
        border: 1px solid rgba(255,255,255,0.1);
    }
    body.dark-mode .upgrade-modal-content h3 { color: white; }
    body.dark-mode .upgrade-modal-content .modal-text-muted { color: rgba(255,255,255,0.5) !important; }
    body.dark-mode .upgrade-modal-content .modal-icon-bg { background: rgba(255,255,255,0.1); }
    body.dark-mode .upgrade-modal-content .btn-link { color: rgba(255,255,255,0.5) !important; }

    /* Modal Stars */
    #modal-stars-container {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        pointer-events: none;
        z-index: 0;
        opacity: 1; /* Always visible */
        transition: opacity 0.5s;
    }
    
    .modal-star {
        position: absolute;
        background: #f59e0b; /* Amber 500 for Light Mode */
        border-radius: 50%;
        box-shadow: 0 0 4px rgba(245, 158, 11, 0.5);
        animation: floatModalStar 4s infinite ease-in-out alternate;
    }

    /* Dark Mode Star Override */
    body.dark-mode .modal-star {
        background: white;
        box-shadow: 0 0 4px rgba(255,255,255,0.8);
    }

    @keyframes floatModalStar {
        0% { transform: translateY(0) scale(1); opacity: 0.4; }
        100% { transform: translateY(-10px) scale(1.2); opacity: 0.9; }
    }
</style>
</head>
<body>
    <div id="stars-container"></div>
    
    <!-- Error Toast Container -->
    <div class="position-fixed top-0 start-50 translate-middle-x p-3" style="z-index: 11000">
        <div id="error-toast" class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="error-toast-message">
                    Error message here.
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

<?php 
$userName = $user_name ?? $_SESSION['user_name'] ?? 'Lector';
$isPro = $pro_enabled ?? (!empty($_SESSION['pro']) && $_SESSION['pro']);
?>

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
            <span class="d-block fw-semibold" style="font-size: 1rem;">Hola, <?= htmlspecialchars($userName) ?></span>
        </div>
        <?php if($isPro): ?>
            <a href="/pro/settings" class="text-decoration-none">
                <span class="badge bg-gradient border border-light border-opacity-25" style="background-color: #8b5cf6; font-size: 0.9rem; cursor: pointer;">Pro</span>
            </a>
        <?php else: ?>
            <span class="badge bg-secondary bg-opacity-50 border border-secondary border-opacity-25" style="font-size: 0.9rem;">Básica</span>
        <?php endif; ?>
        <a href="/logout" class="btn btn-outline-light btn-sm rounded-pill px-3 py-1" style="font-size: 0.9rem;">Cerrar Sesión</a>
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

            // Generate Modal Stars
            const modalStarsContainer = document.getElementById('modal-stars-container');
            if (modalStarsContainer) {
                const modalStarCount = 40;
                for (let i = 0; i < modalStarCount; i++) {
                    const star = document.createElement('div');
                    star.classList.add('modal-star');
                    const x = Math.random() * 100;
                    const y = Math.random() * 100;
                    const size = Math.random() * 3 + 2; // 2px to 5px
                    const duration = Math.random() * 3 + 3; // 3s to 6s
                    
                    star.style.left = x + '%';
                    star.style.top = y + '%';
                    star.style.width = size + 'px';
                    star.style.height = size + 'px';
                    star.style.animationDuration = duration + 's';
                    star.style.animationDelay = (Math.random() * 5) + 's';
                    
                    modalStarsContainer.appendChild(star);
                }
            }
        });
    </script>

<div class="container-fluid p-0">
    <!-- Old Header with Mood Color -->
    <div class="p-5 text-center book-header">
        <a href="/dashboard" class="back-nav" aria-label="Volver">
            <i class="bi bi-arrow-left"></i>
        </a>
        <?php if(!empty($book['cover_url'])): ?>
            <div class="mb-4 transition-hover">
                <img src="<?= htmlspecialchars($book['cover_url']) ?>" alt="Portada de <?= htmlspecialchars($book['title']) ?>" class="shadow-lg rounded-4" style="max-height: 320px; max-width: 80%; object-fit: cover; border: 4px solid white; transform: rotate(-2deg);">
            </div>
        <?php endif; ?>
        <h1 class="display-4 fw-bold mb-3 book-title-glow"><?= htmlspecialchars($book['title']) ?></h1>
        <p class="lead mb-4 opacity-75" style="color: var(--text-body);">por <?= htmlspecialchars($book['author']) ?></p>
        <span class="badge bg-warning text-black fs-6 px-4 py-2 rounded-pill shadow-sm"><?= htmlspecialchars($book['mood']) ?></span>
    </div>
</div>

<div class="container-fluid px-4 px-md-5 mt-4">
    <div class="row">
        <!-- Playlist Col (Dynamic Height) -->
        <div class="col-md-4 mb-4 order-2 order-md-1">
            <div class="glass-card">
                <div class="card-header-custom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold text-white"><i class="bi bi-music-note-beamed me-2"></i>Playlist</h5>
                    <?php if($isPro): ?>
                        <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25">Pro Activo</span>
                    <?php endif; ?>
                </div>
                
                <div class="p-0" id="playlist-container">
                    <?php if($playlist && !empty($playlist['songs'])): ?>
                        <!-- Vinyl Header (Visual Only) -->
                        <div class="visual-album-container text-center mb-0" onclick="togglePlaylist()">
                            <div class="vinyl-record">
                                <div class="vinyl-label">
                                    <?= htmlspecialchars(substr($book['title'], 0, 10)) ?>
                                </div>
                            </div>
                            <h6 class="mt-4 mb-1 fw-bold text-white">Playlist Generada</h6>
                            <p class="small text-white-50 mb-0"><?= count($playlist['songs']) ?> Pistas</p>
                            <i class="bi bi-chevron-down text-white-50 mt-2 d-block animate-bounce"></i>
                        </div>

                        <!-- Playlist Items (Collapsible) -->
                        <div id="full-playlist">
                            <div class="list-group list-group-flush border-start border-end border-light border-opacity-10">
                                <?php 
                                    $limit = 7;
                                    $i = 0;
                                    foreach($playlist['songs'] as $song): 
                                        if($i++ >= $limit) break; 
                                ?>
                                    <div class="playlist-track" id="song-row-<?= $i - 1 ?>" data-url="<?= htmlspecialchars($song['url'] ?? '') ?>" data-title="<?= htmlspecialchars($song['title']) ?>" data-artist="<?= htmlspecialchars($song['artist']) ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="me-3 overflow-hidden flex-grow-1">
                                                <div class="fw-semibold text-truncate">
                                                    <?= htmlspecialchars($song['title']) ?>
                                                </div>
                                                <small class="text-muted text-truncate d-block"><?= htmlspecialchars($song['artist']) ?></small>
                                            </div>

                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Pro/Upgrade Actions -->
                            <div class="p-3 bg-dark bg-opacity-25 border border-top-0 border-light border-opacity-10" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                                <?php if(!empty($playlist['songs'])): ?>
                                    <div class="d-grid gap-2 mt-2">
                                        <div class="row g-2">
                                            <div class="col-6">
                                                <a href="/spotify/create?book_id=<?= urlencode($book['id']) ?>" class="btn btn-spotify btn-sm w-100 h-100 d-flex align-items-center justify-content-center py-2">
                                                    <i class="bi bi-spotify me-2"></i>Spotify
                                                </a>
                                            </div>
                                            <div class="col-6 position-relative">
                                                <button onclick="toggleRegenerateConfirm()" class="btn btn-outline-warning btn-sm w-100 h-100 d-flex align-items-center justify-content-center py-2">
                                                    <i class="bi bi-arrow-repeat me-2"></i>Regenerar
                                                </button>
                                                <!-- Custom Popover -->
                                                <div id="regenerate-confirm-popover" class="position-absolute bg-white shadow-lg rounded p-3 d-none fade-in-up" style="bottom: 110%; right: 0; min-width: 180px; white-space: nowrap; z-index: 1050;">
                                                    <div class="text-dark text-center">
                                                        <p class="small mb-2 fw-bold" style="font-size: 0.8rem; line-height: 1.2;">¿Regenerar Playlist?</p>
                                                        <div class="d-flex justify-content-center gap-2">
                                                            <button onclick="toggleRegenerateConfirm()" class="btn btn-xs btn-outline-secondary py-1 px-2" style="font-size: 0.75rem;">No</button>
                                                            <button onclick="executeRegeneratePlaylist()" class="btn btn-xs btn-danger py-1 px-2" style="font-size: 0.75rem;">Sí</button>
                                                        </div>
                                                    </div>
                                                    <!-- Arrow -->
                                                    <div class="position-absolute bg-white" style="bottom: -5px; right: 20px; transform: rotate(45deg); width: 10px; height: 10px;"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php elseif($playlist): ?>
                        <div class="p-5 text-center">
                            <i class="bi bi-music-note-beamed fs-1 text-white-50 mb-3"></i>
                            <h6 class="text-white">No se encontraron canciones.</h6>
                            <p class="text-white-50 small">Las canciones generadas por IA han sido ocultadas.</p>
                            <div class="d-grid mt-3 col-8 mx-auto">
                                <button onclick="executeRegeneratePlaylist()" class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-arrow-repeat me-2"></i>Regenerar Playlist
                                </button>
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

        <!-- Synopsis Col -->
        <div class="col-md-8 order-1 order-md-2">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body">
                    <h5>Sinopsis</h5>
                    <p class="text-muted"><?= htmlspecialchars($book['synopsis']) ?></p>
                </div>
            </div>

            <!-- Interactive Literary Map Entry Card -->
            <div class="card shadow-sm mb-4 border-0 overflow-hidden" style="border-radius: 16px;">
                <div class="position-relative" style="height: 250px; background-color: #e2e8f0;">
                    <!-- Background Map Image (Abstract) -->
                    <div style="position: absolute; top:0; left:0; width:100%; height:100%; background-image: url('https://cartodb-basemaps-a.global.ssl.fastly.net/light_all/10/500/500.png'); background-size: cover; filter: grayscale(100%) opacity(0.4);"></div>
                    <div style="position: absolute; top:0; left:0; width:100%; height:100%; background: linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,0.1) 100%);"></div>
                    
                    <div class="position-absolute top-50 start-50 translate-middle text-center w-100 px-3">
                        <div class="mb-3">
                            <i class="bi bi-geo-alt-fill text-primary" style="font-size: 3rem; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2));"></i>
                        </div>
                        <h4 class="fw-bold mb-2 text-dark">Explora el mundo de la historia</h4>
                        <p class="text-secondary fw-medium mb-4" style="text-shadow: 0 1px 2px rgba(255,255,255,0.8);">Descubre las ubicaciones clave del libro en un mapa interactivo.</p>
                        <button onclick="openFullScreenMap()" class="btn btn-primary btn-lg rounded-pill px-5 shadow-lg d-inline-flex align-items-center gap-2 transition-hover">
                            <?php if(!$isPro): ?><i class="bi bi-lock-fill me-1"></i><?php else: ?><i class="bi bi-map"></i><?php endif; ?>
                            Viajar al Mapa
                        </button>
                    </div>
                </div>
            </div>
    </div>
</div>

<!-- Full Screen Map Overlay -->
<div id="fs-map-overlay" class="d-none">
    <!-- Header -->
    <div class="fs-map-header">
        <button onclick="closeFullScreenMap()" class="btn btn-light btn-sm rounded-circle shadow-sm" style="width: 40px; height: 40px;">
            <i class="bi bi-arrow-left"></i>
        </button>
        <div style="width: 40px;"></div> <!-- Spacer -->
    </div>

    <!-- Fictional Location Alert Banner -->
    <div id="fs-fictional-alert" class="position-absolute start-50 translate-middle-x d-none" style="z-index: 2000; top: 90px; width: 90%; max-width: 400px;">
        <div class="alert alert-light shadow-lg border-0 rounded-4 px-4 py-3 d-flex align-items-center" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(5px);">
            <i class="bi bi-stars fs-3 me-3 text-primary"></i>
            <div>
                <h6 class="fw-bold mb-0 text-dark">Escenario de Fantasía</h6>
                <small class="text-secondary" style="font-size: 0.85rem; line-height: 1.2; display: block;">Esta historia ocurre en lugares ficticios. El mapa muestra la región real de inspiración.</small>
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div id="fs-map-container"></div>
    
    <!-- Loader -->
    <div id="fs-map-loader" class="position-absolute top-50 start-50 translate-middle text-center" style="z-index: 2000;">
        <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
        <div class="bg-white px-3 py-1 rounded shadow-sm text-primary fw-bold">Generando cartografía...</div>
    </div>

    <!-- Side Panel (formerly Bottom Sheet) -->
    <div id="fs-bottom-sheet" class="fs-side-panel">
        <div class="sheet-handle"></div>
        <div id="sheet-content" class="p-4 pt-1">
            <span id="sheet-chapter" class="badge bg-primary bg-opacity-10 text-primary mb-2">Capítulo</span>
            <span id="sheet-fictional-badge" class="badge bg-purple bg-opacity-10 text-purple mb-2 ms-2 d-none" style="background-color: rgba(147, 51, 234, 0.1); color: #9333ea;">
                <i class="bi bi-stars me-1"></i>Ubicación Ficticia
            </span>
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h4 id="sheet-title" class="fw-bold mb-0 location-title-glow">Título del Lugar</h4>
                <!-- Google Maps Link -->
                <a id="sheet-gmaps-link" href="#" target="_blank" class="btn btn-outline-secondary btn-sm rounded-circle" data-bs-toggle="tooltip" title="Ver en Google Maps">
                    <i class="bi bi-geo-alt"></i>
                </a>
            </div>
            
            <p id="sheet-desc" class="text-secondary fw-medium mb-4">Descripción del evento...</p>
            
            <!-- Coordinates Display -->
            <div class="mb-3 d-flex align-items-center text-muted small">
                <i class="bi bi-compass coordinates-icon"></i>
                <span id="sheet-coords">0.0000, 0.0000</span>
            </div>

            <button onclick="openContextModal()" class="btn btn-action-primary w-100 rounded-pill py-3 fw-bold shadow-sm">
                <i class="bi bi-book-half me-2"></i>Leer fragmento del libro
            </button>
        </div>
    </div>
</div>

<!-- Upgrade Modal -->
<div class="modal fade" id="upgradeModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content upgrade-modal-content border-0 shadow-lg position-relative">
      <div id="modal-stars-container"></div>
      <div class="modal-body p-5 text-center position-relative" style="z-index: 2;">
        <div class="mb-4">
            <div class="rounded-circle modal-icon-bg d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                <i class="bi bi-gem text-warning" style="font-size: 2.5rem;"></i>
            </div>
        </div>
        <h3 class="fw-bold mb-3">Desbloquea BookVibes Pro</h3>
        <p class="modal-text-muted mb-4 fs-5">
            Has alcanzado el límite de regeneraciones gratuitas para este libro.
        </p>
        <ul class="text-start d-inline-block mb-4 modal-text-muted">
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Regeneraciones ilimitadas</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Análisis de sentimiento avanzado</li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Mapas literarios completos</li>
        </ul>
        <div class="d-grid gap-3">
             <a href="/pro/upgrade?book_id=<?= $book['id'] ?>" class="btn btn-warning btn-lg rounded-pill fw-bold shadow-sm text-dark">
                 Actualizar a Pro
             </a>
             <button type="button" class="btn btn-link modal-text-muted text-decoration-none" data-bs-dismiss="modal">
                 Quizás más tarde
             </button>
         </div>
      </div>
    </div>
  </div>
</div>

<!-- Fictional Warning Modal -->
<div class="modal fade" id="fictionalModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg text-center p-4" style="border-radius: 24px; background: #fff;">
        <div class="mb-3">
            <div class="rounded-circle bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                <i class="bi bi-stars text-primary" style="font-size: 2.5rem;"></i>
            </div>
        </div>
        <h4 class="fw-bold mb-3 text-dark">Escenario Ficticio</h4>
        <p class="text-secondary mb-4">
            Los sitios donde se desarrolla esta historia son <strong>ficticios y no existen</strong> en el mundo real. 
            <br><br>
            El mapa muestra las ubicaciones reales que sirvieron de inspiración o el entorno geográfico aproximado.
        </p>
        <button type="button" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm" data-bs-dismiss="modal">
            Entendido
        </button>
    </div>
  </div>
</div>

<!-- Context Modal -->
<div class="modal fade" id="contextModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 24px; overflow: hidden; background: #fff1e6; color: #2c1810; border: 4px double #d4c5b0;">
      <div class="modal-header border-0" style="border-bottom: 1px solid rgba(44, 24, 16, 0.1);">
        <h5 class="modal-title fw-bold" style="color: #4a2c2a; font-family: 'Merriweather', serif;"><i class="bi bi-bookmark-star-fill me-2"></i>Contexto Literario</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <h5 id="modal-place-title" class="fw-bold mb-3" style="color: #5d4037;">Lugar</h5>
        <div class="mt-2">
            <p id="modal-context-text" class="fst-italic mb-0" style="font-family: 'Merriweather', serif; line-height: 1.8; color: #3e2723; font-size: 1.1rem;">
                "Aquí aparecerá el texto o descripción detallada del momento en el libro..."
            </p>
        </div>
        <div class="mt-3 text-end">
            <span id="modal-chapter-badge" class="badge" style="background: #5d4037; color: #fff1e6;">Capítulo</span>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
    /* Full Screen Map Styles */
    #fs-map-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 9999;
        background: #f8fafc;
        display: flex;
        flex-direction: column;
    }
    
    .fs-map-header {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        padding: 20px;
        z-index: 10002;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(180deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0) 100%);
        pointer-events: none;
    }
    .fs-map-header > * { pointer-events: auto; }
    
    .fs-map-title {
        font-weight: 800;
        color: #1e293b;
        font-size: 1.1rem;
        background: rgba(255,255,255,0.9);
        padding: 8px 16px;
        border-radius: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        backdrop-filter: blur(5px);
    }
    
    #fs-map-container {
        width: 100%;
        height: 100%;
    }
    
    /* Map Styling Filters */
    .custom-map-tiles {
        /* Removed filters for colorful look */
    }

    /* Side Panel for Map Details */
    .fs-side-panel {
        position: absolute;
        top: auto;
        bottom: 30px;
        right: 30px;
        width: 500px;     /* Increased Width */
        max-width: 90vw;
        background: rgba(255, 255, 255, 0.95); /* Light Mode Default */
        border-radius: 24px;
        box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        z-index: 10003;
        transform: translateX(120%);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), background-color 0.3s, border-color 0.3s;
        max-height: calc(100vh - 60px);
        overflow-y: auto;
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.6);
    }
    .fs-side-panel.active {
        transform: translateX(0);
    }

    /* Light Mode Texts (Default) */
    .fs-side-panel h4.location-title-glow {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        font-size: 1.85rem;
        font-weight: 800;
        color: #1e293b; /* Dark Slate */
        letter-spacing: -0.025em;
        line-height: 1.2;
    }
    .fs-side-panel p, .fs-side-panel span, .fs-side-panel div {
        color: #334155; /* Slate 700 */
    }
    .fs-side-panel .text-muted, .fs-side-panel .text-secondary {
        color: #64748b !important;
    }

    /* DARK MODE STYLES */
    body.dark-mode .fs-side-panel {
        background: rgba(15, 23, 42, 0.95); /* Dark Slate 900 */
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
    }
    body.dark-mode .fs-side-panel h4.location-title-glow {
        color: #f8fafc; /* Slate 50 */
    }
    body.dark-mode .fs-side-panel p, 
    body.dark-mode .fs-side-panel span, 
    body.dark-mode .fs-side-panel div {
        color: #cbd5e1; /* Slate 300 */
    }
    body.dark-mode .fs-side-panel .text-muted,
    body.dark-mode .fs-side-panel .text-secondary {
        color: #94a3b8 !important; /* Slate 400 */
    }
    body.dark-mode .fs-side-panel .bg-light {
        background-color: rgba(255,255,255,0.05) !important;
        border-color: rgba(255,255,255,0.1) !important;
    }
    
    /* Coordinates Icon Styling */
    .coordinates-icon {
        font-size: 1.2rem; /* Larger */
        color: #3b82f6;    /* Bright Blue for visibility */
        margin-right: 8px;
    }
    body.dark-mode .coordinates-icon {
        color: #60a5fa; /* Lighter Blue in Dark Mode */
    }

    /* Action Button (Adaptive) */
    .btn-action-primary {
        /* Light Mode: Pastel Gradient matching Menu (Cyan -> Purple -> Pink) */
        background: linear-gradient(90deg, #bae6fd 0%, #e9d5ff 50%, #fbcfe8 100%);
        color: #1e293b !important; /* Dark Text for breakdown on light pastel */
        border: 1px solid rgba(255,255,255,0.5);
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    .btn-action-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        color: #0f172a !important;
        filter: brightness(1.05);
    }
    .btn-action-primary i {
        color: #1e293b !important;
    }

    /* Dark Mode Override for Button */
    body.dark-mode .btn-action-primary {
        background: linear-gradient(135deg, #0f172a 0%, #334155 100%); /* Starry Night */
        border: 1px solid rgba(255,255,255,0.1);
        color: white !important;
        box-shadow: 0 10px 20px rgba(0,0,0,0.3);
    }
    body.dark-mode .btn-action-primary:hover {
        background: linear-gradient(135deg, #1e293b 0%, #475569 100%);
        box-shadow: 0 15px 30px rgba(0,0,0,0.5);
        color: white !important;
    }
    body.dark-mode .btn-action-primary i {
        color: white !important;
    }

    .fs-side-panel .btn-primary {
         /* Fallback if class missing */
         background-color: #0f172a;
         color: white !important;
    }
    
    .sheet-handle {
        display: none;
    }
    .sheet-handle {
        width: 40px;
        height: 4px;
        background: #cbd5e1;
        border-radius: 2px;
        margin: 12px auto;
    }

    /* Custom Markers */
    .custom-marker {
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s;
    }
    .custom-marker:hover {
        transform: scale(1.2);
        z-index: 1000 !important;
    }
    .marker-pin {
        width: 36px;
        height: 36px;
        border-radius: 50% 50% 50% 0;
        background: #4a148c; /* Dark Purple default */
        transform: rotate(-45deg);
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid white;
    }
    .marker-pin i {
        transform: rotate(45deg);
        color: white;
        font-size: 14px;
    }
    
    /* Types Colors - Vibrante & Literario */
    .marker-danger .marker-pin { background: #6a1b9a; } /* Purple 800 */
    .marker-meetup .marker-pin { background: #4a148c; } /* Purple 900 */
    .marker-discovery .marker-pin { background: #7b1fa2; } /* Purple 700 */
    .marker-event .marker-pin { background: #4a148c; } /* Purple 900 */
    
    /* Pulsing for High Importance */
    .marker-high .marker-pin {
        animation: pulse-marker 2s infinite;
    }
    @keyframes pulse-marker {
        0% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(59, 130, 246, 0); }
        100% { box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
    }
    .marker-danger.marker-high .marker-pin { animation-name: pulse-danger; }
    @keyframes pulse-danger {
        0% { box-shadow: 0 0 0 0 rgba(225, 29, 72, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(225, 29, 72, 0); }
        100% { box-shadow: 0 0 0 0 rgba(225, 29, 72, 0); }
    }

    /* Modal Z-Index Fix for Full Screen Map */
    .modal {
        z-index: 10050 !important;
    }
    .modal-backdrop {
        z-index: 10040 !important;
    }

    /* Custom Badges for Bottom Sheet */
    .badge-meetup { background-color: rgba(139, 92, 246, 0.15) !important; color: #7c3aed !important; }
    .badge-discovery { background-color: rgba(217, 119, 6, 0.15) !important; color: #b45309 !important; }
    .badge-danger-custom { background-color: rgba(225, 29, 72, 0.15) !important; color: #be123c !important; }
    .badge-event { background-color: rgba(59, 130, 246, 0.15) !important; color: #1d4ed8 !important; }

    /* Google Maps Link Styling - High Visibility */
    #sheet-gmaps-link {
        color: #4f46e5; /* Indigo 600 */
        border-color: #e0e7ff; /* Indigo 100 */
        background-color: #eef2ff; /* Indigo 50 */
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }
    #sheet-gmaps-link:hover {
        background-color: #4f46e5;
        color: white;
        transform: translateY(-2px) rotate(10deg);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
        border-color: transparent;
    }
    #sheet-gmaps-link i {
        font-size: 1.2rem;
    }

    /* Dark Mode Support for GMaps Link */
    body.dark-mode #sheet-gmaps-link {
        background-color: rgba(99, 102, 241, 0.2);
        color: #a5b4fc; /* Indigo 300 */
        border-color: rgba(99, 102, 241, 0.3);
    }
    body.dark-mode #sheet-gmaps-link:hover {
        background-color: #6366f1; /* Indigo 500 */
        color: white;
        box-shadow: 0 0 15px rgba(99, 102, 241, 0.5);
        border-color: transparent;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const IS_PRO = <?= json_encode($isPro) ?>;
    const BOOK_ID = <?= json_encode($book['id']) ?>;
    let fsMap = null;
    let mapDataCache = null;

    document.addEventListener('DOMContentLoaded', () => {
        checkPlaylistLoad();
        // Pre-fetch map data silently if possible? 
        // No, wait for user action to save resources, or fetch on idle.
    });

    // ... (Playlist functions remain same) ...
    function checkPlaylistLoad() {
        const loader = document.getElementById('playlist-loader');
        if (loader) loadPlaylist();
    }
    function loadPlaylist() {
        fetch('/books/generate-playlist', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book_id: BOOK_ID })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok && data.playlist) window.location.reload();
            else if(document.getElementById('playlist-loader')) document.getElementById('playlist-loader').innerHTML = '<div class="text-danger p-3">Error playlist.</div>';
        });
    }
    function togglePlaylist() {
        document.getElementById('full-playlist').classList.toggle('show');
    }
    function executeRegeneratePlaylist() {
        const btn = document.querySelector('button[onclick="executeRegeneratePlaylist()"]');
        if(btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>...';
        }

        fetch('/books/regenerate-playlist', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book_id: BOOK_ID })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                window.location.reload();
            } else if (data.require_upgrade) {
                const upgradeModal = new bootstrap.Modal(document.getElementById('upgradeModal'));
                upgradeModal.show();
                // Close popover
                const popover = document.getElementById('regenerate-confirm-popover');
                if(popover) popover.classList.add('d-none');
                
                // Reset button
                if(btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'Sí';
                }
            } else {
                alert(data.error || 'Error al regenerar la playlist');
                if(btn) {
                    btn.disabled = false;
                    btn.innerHTML = 'Sí';
                }
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión');
            if(btn) {
                btn.disabled = false;
                btn.innerHTML = 'Sí';
            }
        });
    }
    function toggleRegenerateConfirm() {
        // ... previous impl ...
        const el = document.getElementById('regenerate-confirm-popover');
        if(el) el.classList.toggle('d-none');
    }

    // ========== NEW FULL SCREEN MAP LOGIC ==========
    
    function openFullScreenMap() {
        if (!IS_PRO) {
            const upgradeModal = new bootstrap.Modal(document.getElementById('upgradeModal'));
            upgradeModal.show();
            return;
        }

        const overlay = document.getElementById('fs-map-overlay');
        overlay.classList.remove('d-none');
        
        if (!fsMap) {
            initMap();
        }
    }

    function closeFullScreenMap() {
        document.getElementById('fs-map-overlay').classList.add('d-none');
    }

    function initMap() {
        // Create map instance
        fsMap = L.map('fs-map-container', {
            zoomControl: false // We can add custom zoom later or let user pinch
        }).setView([20, 0], 2);

        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20,
            className: 'custom-map-tiles'
        }).addTo(fsMap);

        // Fetch Data
        loadMapData();
    }

    function loadMapData() {
        // Cache disabled for testing to ensure we get new "is_fictional" flags
        /*
        if (mapDataCache) {
            renderMapMarkers(mapDataCache);
            return;
        }
        */

        fetch('/books/generate-map', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book_id: BOOK_ID })
        })
        .then(r => r.json())
        .then(data => {
            document.getElementById('fs-map-loader').style.display = 'none';
            if (data.ok && data.map) {
                mapDataCache = data.map;
                renderMapMarkers(data.map);
            } else {
                alert('No se pudo generar el mapa: ' + (data.error || 'Error desconocido'));
                closeFullScreenMap();
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('fs-map-loader').style.display = 'none';
            alert('Error de conexión');
            closeFullScreenMap();
        });
    }

    function renderMapMarkers(data) {
        const config = data.map_config;
        const markers = data.markers;

        // Set View
        if (config.center_coordinates) {
            fsMap.setView(
                [config.center_coordinates.lat, config.center_coordinates.lng],
                config.zoom_level || 12
            );
        }

        // Add Markers
        // Check for Fictional/Inspiration markers to show alert
        const hasFictional = markers.some(m => m.is_fictional === true);
        if (hasFictional) {
             // Show Modal explicitly
             const fictionalModal = new bootstrap.Modal(document.getElementById('fictionalModal'));
             fictionalModal.show();
             
             // Hide the old alert if it exists
             const oldAlert = document.getElementById('fs-fictional-alert');
             if(oldAlert) oldAlert.classList.add('d-none');
        } else {
             const oldAlert = document.getElementById('fs-fictional-alert');
             if(oldAlert) oldAlert.classList.add('d-none');
        }

        markers.forEach(m => {
            if (!m.coordinates || !m.coordinates.lat) return;

            const type = m.location_type || 'event';
            const importance = m.importance || 'medium';
            let iconClass = 'bi-geo-alt-fill';
            
            // Icon mapping
            if (type === 'danger') iconClass = 'bi-exclamation-triangle-fill';
            else if (type === 'meetup') iconClass = 'bi-people-fill';
            else if (type === 'discovery') iconClass = 'bi-lightbulb-fill';
            else if (type === 'event') iconClass = 'bi-book-fill';

            const customIcon = L.divIcon({
                className: 'custom-marker-wrapper', // Wrapper to avoid conflict
                html: `<div class="custom-marker marker-${type} marker-${importance}">
                        <div class="marker-pin"><i class="bi ${iconClass}"></i></div>
                      </div>`,
                iconSize: [36, 36],
                iconAnchor: [18, 36]
            });

            const markerLayer = L.marker([m.coordinates.lat, m.coordinates.lng], { icon: customIcon }).addTo(fsMap);
            
            // Interaction
            markerLayer.on('click', () => {
                showBottomSheet(m);
                // Center map slightly offset to allow sheet space
                const targetLat = m.coordinates.lat - (0.005 * (15 / fsMap.getZoom())); 
                fsMap.flyTo([targetLat, m.coordinates.lng], 14, { duration: 1 });
            });
        });
    }

    function showBottomSheet(marker) {
        const sheet = document.getElementById('fs-bottom-sheet');
        
        document.getElementById('sheet-title').innerText = marker.title;
        document.getElementById('sheet-desc').innerText = marker.snippet;
        document.getElementById('sheet-chapter').innerText = marker.chapter_context || 'Ubicación Clave';
        
        // Colorize badge based on type
        const badge = document.getElementById('sheet-chapter');
        badge.className = 'badge mb-2 ';
        if (marker.location_type === 'danger') badge.classList.add('badge-danger-custom');
        else if (marker.location_type === 'meetup') badge.classList.add('badge-meetup');
        else if (marker.location_type === 'discovery') badge.classList.add('badge-discovery');
        else badge.classList.add('badge-event');

        // Handle Fictional Badge
        const fictionalBadge = document.getElementById('sheet-fictional-badge');
        if (fictionalBadge) {
            if (marker.is_fictional === true) {
                fictionalBadge.classList.remove('d-none');
            } else {
                fictionalBadge.classList.add('d-none');
            }
        }

        sheet.classList.add('active');
        
        // Update Coordinates and Google Maps Link
        const lat = marker.coordinates.lat.toFixed(5);
        const lng = marker.coordinates.lng.toFixed(5);
        document.getElementById('sheet-coords').innerText = `${lat}, ${lng}`;
        document.getElementById('sheet-gmaps-link').href = `https://www.google.com/maps/search/?api=1&query=${marker.coordinates.lat},${marker.coordinates.lng}`;
        
        // Save current marker for modal
        window.currentMarkerContext = marker;
    }
    
    function openContextModal() {
        const marker = window.currentMarkerContext;
        if(!marker) return;
        
        document.getElementById('modal-place-title').innerText = marker.title;
        
        // Use new book_excerpt if available, otherwise fallback to snippet
        const text = marker.book_excerpt || marker.snippet || 'Información no disponible.';
        document.getElementById('modal-context-text').innerText = `"${text}"`; 
        
        document.getElementById('modal-chapter-badge').innerText = marker.chapter_context || 'Referencia';
        
        const myModal = new bootstrap.Modal(document.getElementById('contextModal'));
        myModal.show();
    }
    
    // Close sheet when clicking map
    if(fsMap) {
        fsMap.on('click', () => {
             document.getElementById('fs-bottom-sheet').classList.remove('active');
        });
    }

</script>
</body>
</html>
