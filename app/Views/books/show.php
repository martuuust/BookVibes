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
        /* Premium Character Card Styles */
        .premium-char-card {
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            background: var(--card-bg, #1e293b);
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            height: 100%;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .premium-char-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px -5px rgba(99, 102, 241, 0.3);
            border-color: rgba(99, 102, 241, 0.5);
            z-index: 5;
        }
        .premium-char-img-wrapper {
            position: relative;
            width: 100%;
            padding-top: 140%; /* Portrait Aspect Ratio */
            overflow: hidden;
        }
        .premium-char-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.7s ease;
        }
        .premium-char-card:hover .premium-char-img {
            transform: scale(1.1);
        }
        .premium-char-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.6) 70%, transparent 100%);
            padding: 2rem 1.25rem 1.25rem;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .premium-char-name {
            color: white;
            font-weight: 800;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.8);
            letter-spacing: -0.02em;
        }
        .premium-char-role {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .premium-char-traits {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        .premium-trait-pill {
            font-size: 0.65rem;
            background: rgba(255, 255, 255, 0.15);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translate3d(0, 20px, 0); }
            to { opacity: 1; transform: translate3d(0, 0, 0); }
        }
        .fade-in-up {
            animation: fadeInUp 0.6s cubic-bezier(0.165, 0.84, 0.44, 1) forwards;
        }

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
        .character-card { 
            position: relative;
            min-height: 320px;
            background-color: var(--card-bg);
            background-size: cover;
            background-position: center;
            border: 1px solid var(--card-border);
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        .character-card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 15px 30px rgba(0,0,0,0.2) !important; 
        }
        .character-placeholder {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
            color: var(--text-muted);
            background: linear-gradient(135deg, rgba(100,100,100,0.05), rgba(150,150,150,0.05));
            opacity: 0.3;
        }
        .character-info-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            padding: 1.5rem 1rem 1rem;
            background: linear-gradient(to top, rgba(15, 23, 42, 0.95) 0%, rgba(15, 23, 42, 0.7) 60%, transparent 100%);
            color: white;
            z-index: 2;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
        }
        .generate-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 20;
            opacity: 0;
            transition: all 0.3s ease;
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.9);
            border: none;
            color: #4f46e5;
            font-weight: 600;
        }
        .character-card:hover .generate-btn {
            opacity: 1;
            transform: translateY(0);
        }
        .generate-btn:hover {
            background: #fff;
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .blur-content {
            filter: blur(6px);
            user-select: none;
            opacity: 0.7;
        }
        .locked-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(100, 100, 100, 0.1);
            backdrop-filter: blur(2px);
            z-index: 10;
        }
        .locked-badge {
            background: var(--primary-gradient);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
        }

        /* --- New Styles for Playlist (Glassmorphism) --- */
        .glass-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
            /* overflow: hidden; Removed to allow popover to show */
            color: var(--text-main);
            transition: all 0.3s ease;
        }
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

        <!-- Characters Col -->
        <div class="col-md-8 order-1 order-md-2">
            <div class="card shadow-sm mb-4 border-0">
                <div class="card-body">
                    <h5>Sinopsis</h5>
                    <p class="text-muted"><?= htmlspecialchars($book['synopsis']) ?></p>
                </div>
            </div>

            <!-- Characters Section (Removed per user request) -->

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const IS_PRO = <?= json_encode($isPro) ?>;
    const BOOK_ID = <?= json_encode($book['id']) ?>;

    document.addEventListener('DOMContentLoaded', () => {
        checkPlaylistLoad();
        checkCharactersLoad();
    });

    function checkCharactersLoad() {
        const loader = document.getElementById('characters-loader');
        if (loader) {
            generateCharacters();
        }
    }





    // Start background loading immediately when page loads

    function checkPlaylistLoad() {
        const loader = document.getElementById('playlist-loader');
        if (loader) {
            loadPlaylist();
        }
    }

    function loadPlaylist() {
        fetch('/books/generate-playlist', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book_id: BOOK_ID })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok && data.playlist) {
                window.location.reload();
            } else {
                const loader = document.getElementById('playlist-loader');
                if (loader) loader.innerHTML = '<div class="text-danger p-3"><i class="bi bi-exclamation-circle me-2"></i>No se pudo generar la playlist.</div>';
            }
        })
        .catch(err => {
            const loader = document.getElementById('playlist-loader');
            if (loader) loader.innerHTML = '<div class="text-danger p-3"><i class="bi bi-wifi-off me-2"></i>Error de conexión.</div>';
        });
    }

    function toggleRegenerateConfirm() {
        const popover = document.getElementById('regenerate-confirm-popover');
        if (popover) {
            popover.classList.toggle('d-none');
        }
    }

    function togglePlaylist() {
        const playlist = document.getElementById('full-playlist');
        const header = document.querySelector('.visual-album-container');
        const icon = header ? header.querySelector('.bi-chevron-down') : null;
        
        if (playlist) {
            const isShowing = playlist.classList.toggle('show');
            
            // Adjust header borders
            if (header) {
                if (isShowing) {
                    header.style.borderBottomLeftRadius = '0';
                    header.style.borderBottomRightRadius = '0';
                    header.style.borderBottom = 'none';
                } else {
                    header.style.borderBottomLeftRadius = '16px';
                    header.style.borderBottomRightRadius = '16px';
                    header.style.borderBottom = ''; // Revert to CSS default
                }
            }

            // Rotate chevron
            if (icon) {
                if (isShowing) {
                    icon.style.transform = 'rotate(180deg)';
                    icon.classList.remove('animate-bounce');
                } else {
                    icon.style.transform = 'rotate(0deg)';
                    icon.classList.add('animate-bounce');
                }
                icon.style.transition = 'transform 0.3s ease';
            }
        }
    }

    function executeRegeneratePlaylist() {
        // Hide popover immediately
        toggleRegenerateConfirm();

        const container = document.querySelector('.glass-card'); // Parent container
        if(container) {
            container.innerHTML = '<div id="playlist-loader" class="p-5 text-center"><div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div><h6 class="text-white">Regenerando...</h6></div>';
        }

        fetch('/books/regenerate-playlist', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book_id: BOOK_ID })
        })
        .then(r => r.json())
        .then(data => {
            if(data.ok) {
                window.location.reload();
            } else if (data.require_upgrade) {
                // Redirect to upgrade page with return URL
                window.location.href = '/pro/upgrade?book_id=' + BOOK_ID + '&return=' + encodeURIComponent(window.location.pathname + window.location.search);
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
                window.location.reload();
            }
        })
        .catch(e => {
            console.error(e);
            alert('Error de conexión');
            window.location.reload();
        });
    }

    function generateCharacters(btn = null) {
        let originalContent = '';
        if (btn) {
            originalContent = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generando...';
            btn.disabled = true;
        }
        
        fetch('/books/generate-characters', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ book_id: BOOK_ID })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                window.location.reload();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
                if (btn) {
                    btn.innerHTML = originalContent;
                    btn.disabled = false;
                }
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error de conexión');
            if (btn) {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        });
    }


</script>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
