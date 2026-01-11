<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir Libro - BookVibes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: #ffffff;
            --card-bg: #ffffff;
            --input-bg: #f8f9fa;
            --text-muted: #64748b;
            --header-bg: linear-gradient(135deg, #e0f2fe 0%, #f3e8ff 50%, #fce7f3 100%);
            --header-text: #1e293b;
            --header-text-sub: rgba(30, 41, 59, 0.6);
            --header-stars-display: none;
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
            --card-bg: #1e293b;
            --input-bg: #334155;
            --text-muted: #94a3b8;
            --header-bg: radial-gradient(ellipse at bottom, #1b2735 0%, #090a0f 100%);
            --header-text: #ffffff;
            --header-text-sub: rgba(255, 255, 255, 0.5);
            --header-stars-display: block;
        }
        body {
            background: var(--bg-body) !important;
            transition: background 0.3s ease, color 0.3s ease;
            min-height: 100vh;
        }
        body.dark-mode { color: #f8fafc; }
        body.dark-mode .bg-white { background-color: var(--card-bg) !important; color: #f8fafc; }
        body.dark-mode .bg-light { background-color: var(--input-bg) !important; color: #f8fafc !important; }
        body.dark-mode .text-muted { color: #94a3b8 !important; }
        body.dark-mode .form-control { background-color: var(--input-bg); color: #f8fafc; border-color: rgba(255,255,255,0.1); }
        body.dark-mode .form-control::placeholder { color: #94a3b8; }
        body.dark-mode .input-group-text { background-color: var(--input-bg); border-color: rgba(255,255,255,0.1); color: #94a3b8; }
        
        /* Stars */
        .stars {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            display: var(--header-stars-display);
        }
        .star {
            position: absolute;
            background: white;
            border-radius: 50%;
            opacity: 0.8;
            box-shadow: 0 0 6px 2px rgba(255, 255, 255, 0.6);
            animation: twinkle var(--duration) infinite ease-in-out;
        }
        @keyframes twinkle {
            0%, 100% { opacity: 0.3; transform: scale(0.8); }
            50% { opacity: 1; transform: scale(1.2); }
        }
        
        .form-label {
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
    </style>
</head>
<body style="font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif;">

    <div class="stars"></div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const starsContainer = document.querySelector('.stars');
            const numStars = 100; // Adjust as needed

            for (let i = 0; i < numStars; i++) {
                let star = document.createElement('div');
                star.className = 'star';
                star.style.width = `${Math.random() * 2 + 1}px`;
                star.style.height = star.style.width;
                star.style.top = `${Math.random() * 100}%`;
                star.style.left = `${Math.random() * 100}%`;
                star.style.animationDelay = `${Math.random() * 5}s`;
                star.style.setProperty('--duration', `${Math.random() * 3 + 2}s`);
                starsContainer.appendChild(star);
            }
        });
    </script>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow-lg border-0 overflow-hidden" style="border-radius: 24px;">
                <!-- Card Header -->
                <div class="p-4 text-center" style="background: var(--header-bg); color: var(--header-text); position: relative;">
                    <!-- Back Button -->
                    <a href="/dashboard" class="position-absolute top-0 start-0 m-3 text-decoration-none d-flex align-items-center gap-2" style="z-index: 10; color: var(--header-text-sub);">
                        <i class="bi bi-arrow-left fs-5"></i>
                        <span class="small fw-semibold">Volver</span>
                    </a>
                    
                    <div style="position: absolute; top:0; left:0; width:100%; height:100%; overflow:hidden; pointer-events:none; display: var(--header-stars-display);">
                        <!-- Static stars for header if needed, but we rely on global stars for dark mode -->
                    </div>

                    <i class="bi bi-cloud-upload display-4 mb-2" style="color: var(--header-text-sub);"></i>
                    <h3 class="mb-1 fw-bold position-relative">Subir Libro</h3>
                    <p class="mb-0 position-relative small" style="color: var(--header-text-sub);">Sube tus archivos PDF, EPUB o MOBI.</p>
                </div>
                
                <div class="card-body p-4 bg-white">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger shadow-sm border-0" role="alert" style="border-radius: 12px;">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form action="/books/storeUpload" method="POST" enctype="multipart/form-data">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label for="book_file" class="form-label fw-bold text-uppercase text-muted">Archivo del Libro</label>
                                <div class="input-group">
                                    <input type="file" class="form-control bg-light" id="book_file" name="book_file" required style="border-radius: 12px;">
                                </div>
                                <div class="form-text small">PDF, EPUB, MOBI.</div>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="title" class="form-label fw-bold text-uppercase text-muted">Título</label>
                                <input type="text" class="form-control bg-light" id="title" name="title" placeholder="Título del libro" style="border-radius: 12px;">
                            </div>
                            <div class="col-md-6">
                                <label for="author" class="form-label fw-bold text-uppercase text-muted">Autor</label>
                                <input type="text" class="form-control bg-light" id="author" name="author" placeholder="Autor del libro" style="border-radius: 12px;">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="genre" class="form-label fw-bold text-uppercase text-muted">Género</label>
                                <input type="text" class="form-control bg-light" id="genre" name="genre" placeholder="Ej: Fantasía" style="border-radius: 12px;">
                            </div>
                            <div class="col-md-6">
                                <label for="mood" class="form-label fw-bold text-uppercase text-muted">Vibe / Mood</label>
                                <input type="text" class="form-control bg-light" id="mood" name="mood" placeholder="Ej: Melancólico" style="border-radius: 12px;">
                            </div>
                        </div>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="cover_file" class="form-label fw-bold text-uppercase text-muted">Subir Portada (Imagen)</label>
                                <input type="file" class="form-control bg-light" id="cover_file" name="cover_file" accept="image/png, image/jpeg, image/jpg, image/webp" style="border-radius: 12px;">
                            </div>
                            <div class="col-md-6">
                                <label for="cover_url" class="form-label fw-bold text-uppercase text-muted">O URL de Portada</label>
                                <input type="url" class="form-control bg-light" id="cover_url" name="cover_url" placeholder="https://..." style="border-radius: 12px;">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="synopsis" class="form-label fw-bold text-uppercase text-muted">Sinopsis</label>
                            <textarea class="form-control bg-light" id="synopsis" name="synopsis" rows="2" placeholder="Breve descripción del libro" style="border-radius: 12px;"></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold py-2" style="border-radius: 12px; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none;">
                                <i class="bi bi-cloud-arrow-up-fill me-2"></i> Subir Libro
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const body = document.body;

            // Apply theme on load
            function applyTheme(isDark) {
                if (isDark) {
                    body.classList.add('dark-mode');
                } else {
                    body.classList.remove('dark-mode');
                }
            }

            // Check local storage for theme preference
            const savedTheme = localStorage.getItem('theme');
            // Check system preference if no saved theme
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

            if (savedTheme === 'dark' || (!savedTheme && prefersDark)) {
                applyTheme(true);
            } else {
                applyTheme(false);
            }
        });
    </script>
</body>
</html>
