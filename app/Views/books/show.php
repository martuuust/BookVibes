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
            --bg-body: radial-gradient(1200px 600px at 0% 0%, #f0f5ff 10%, #ffffff 60%), linear-gradient(180deg, #ffffff 0%, #f7f9fc 100%);
            --char-bg: white;
            --text-body: #334155;
        }

        body.dark-mode {
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
            --char-bg: #1e293b;
            --text-body: #f8fafc;
        }

        body { 
            font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; 
            background: var(--bg-body);
            background-attachment: fixed;
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

        /* --- Old Styles for Header & Characters --- */
        .header-starry { 
            position: relative; 
            background:
                radial-gradient(2px 2px at 12% 28%, rgba(255,255,255,0.9), transparent 3px),
                radial-gradient(1.5px 1.5px at 22% 65%, rgba(255,255,255,0.8), transparent 3px),
                radial-gradient(1px 1px at 48% 52%, rgba(255,255,255,0.7), transparent 3px),
                linear-gradient(180deg, #050a13 0%, #070e17 45%, #050a13 100%);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            color: white; /* Always white text in starry header */
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
    </style>
</head>
<body>

<?php 
$userName = $user_name ?? $_SESSION['user_name'] ?? 'Lector';
$isPro = $pro_enabled ?? (!empty($_SESSION['pro']) && $_SESSION['pro']);
?>

<!-- Old Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark sticky-top" style="background: rgba(5, 10, 19, 0.95); backdrop-filter: blur(10px); box-shadow: 0 4px 20px rgba(0,0,0,0.3); border-bottom: 1px solid rgba(255,255,255,0.05);">
  <div class="container">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="/dashboard" style="letter-spacing: -0.5px; font-size: 1.5rem;">
        <div class="position-relative d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(168, 85, 247, 0.2)); border-radius: 12px; border: 1px solid rgba(255,255,255,0.1);">
            <i class="bi bi-book-half text-white" style="font-size: 1.2rem;"></i>
            <i class="bi bi-music-note-beamed position-absolute" style="color: #2dd4bf; font-size: 0.8rem; top: 8px; right: 6px; transform: rotate(15deg);"></i>
        </div>
        <span style="background: linear-gradient(135deg, #a78bfa, #2dd4bf); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-shadow: 0 2px 10px rgba(167, 139, 250, 0.3);">BookVibes</span>
    </a>
    <div class="d-flex text-white align-items-center gap-3">
            <button id="darkModeToggle" class="btn btn-link text-white p-0 border-0" title="Alternar modo oscuro">
                <i class="bi bi-moon-fill fs-5"></i>
            </button>
            <div class="d-none d-md-block text-end lh-1">
            <span class="d-block fw-semibold" style="font-size: 0.9rem;">Hola, <?= htmlspecialchars($userName) ?></span>
            <small class="text-white-50" style="font-size: 0.75rem;">Lector</small>
        </div>
        <?php if($isPro): ?>
            <span class="badge bg-gradient border border-light border-opacity-25" style="background-color: #8b5cf6;">Pro</span>
        <?php else: ?>
            <span class="badge bg-secondary bg-opacity-50 border border-secondary border-opacity-25">Básica</span>
        <?php endif; ?>
        <a href="/dashboard" class="btn btn-outline-light btn-sm rounded-pill px-3" style="font-size: 0.8rem;">Volver</a>
    </div>
  </div>
</nav>

<div class="container-fluid p-0">
    <!-- Old Header with Mood Color -->
    <div class="text-white p-5 text-center header-starry">
        <h1 class="display-4 fw-bold mb-3"><?= htmlspecialchars($book['title']) ?></h1>
        <p class="lead mb-4 opacity-75">por <?= htmlspecialchars($book['author']) ?></p>
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
        <div class="col-md-8">
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
                                        <small class="text-muted">Personaje Principal</small>
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

    // Handle vinyl play
    let currentAudio = null;
    let currentVinyl = null;
</script>
</body>
</html>
