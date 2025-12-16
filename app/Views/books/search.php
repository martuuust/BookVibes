<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Buscar Libro - BookVibes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-body: linear-gradient(135deg,#eef2ff,#ffffff);
            --card-bg: #ffffff;
            --input-bg: #f8f9fa;
            --text-muted: #64748b; /* Added text-muted variable */
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
            --text-muted: #94a3b8; /* Added text-muted variable */
        }
        body {
            background: var(--bg-body) !important;
            background-attachment: fixed !important;
            transition: background 0.3s ease, color 0.3s ease;
        }
        body.dark-mode { color: #f8fafc; }
        body.dark-mode .bg-white { background-color: var(--card-bg) !important; color: #f8fafc; }
        body.dark-mode .bg-light { background-color: var(--input-bg) !important; color: #f8fafc !important; }
        body.dark-mode .text-muted { color: #94a3b8 !important; }
        body.dark-mode .form-control { background-color: var(--input-bg); color: #f8fafc; border-color: rgba(255,255,255,0.1); }
        body.dark-mode .form-control::placeholder { color: #94a3b8; }
        body.dark-mode .input-group-text { background-color: var(--input-bg); border-color: rgba(255,255,255,0.1); color: #94a3b8; }
    </style>
</head>
<body style="font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; min-height: 100vh; display: flex; align-items: center;">

<?php 
// Helper to get user data safely
$userName = $user_name ?? $_SESSION['user_name'] ?? 'Lector';
$isPro = $pro_enabled ?? (!empty($_SESSION['pro']) && $_SESSION['pro']);
?>

<!-- Navbar removed as per user request -->

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0 overflow-hidden" style="border-radius: 24px;">
                <!-- Card Header with Starry Vibe -->
                <div class="p-5 text-center text-white" style="background: radial-gradient(ellipse at bottom, #1b2735 0%, #090a0f 100%); position: relative;">
                    <!-- Back Button Inside Card -->
                    <a href="/dashboard" class="position-absolute top-0 start-0 m-3 text-white-50 text-decoration-none d-flex align-items-center gap-2" style="z-index: 10;">
                        <i class="bi bi-arrow-left fs-5"></i>
                        <span class="small fw-semibold">Volver</span>
                    </a>

                    <div style="position: absolute; top:0; left:0; width:100%; height:100%; overflow:hidden; pointer-events:none;">
                         <div style="position: absolute; width: 2px; height: 2px; background: white; top: 20%; left: 30%; opacity: 0.8; box-shadow: 0 0 4px white;"></div>
                         <div style="position: absolute; width: 1px; height: 1px; background: white; top: 60%; left: 80%; opacity: 0.6;"></div>
                         <div style="position: absolute; width: 2px; height: 2px; background: white; top: 40%; left: 10%; opacity: 0.7;"></div>
                         <div style="position: absolute; width: 1px; height: 1px; background: white; top: 80%; left: 50%; opacity: 0.5;"></div>
                    </div>
                    <i class="bi bi-search display-1 mb-3 text-white-50"></i>
                    <h2 class="mb-2 fw-bold position-relative">¿Qué estás leyendo?</h2>
                    <p class="text-white-50 mb-0 position-relative">Introduce el título y generaremos una experiencia inmersiva.</p>
                </div>
                
                <div class="card-body p-5 bg-white">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-warning text-start border-0 shadow-sm" style="border-radius: 12px; background-color: #fffbeb; color: #92400e;">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="/books/search" method="post">
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold text-uppercase">Título del Libro</label>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light border-end-0 text-muted" style="border-top-left-radius: 12px; border-bottom-left-radius: 12px;"><i class="bi bi-book"></i></span>
                                <input type="text" name="query" class="form-control bg-light border-start-0" placeholder="Ej: Cien Años de Soledad..." required style="border-top-right-radius: 12px; border-bottom-right-radius: 12px; font-size: 1.1rem;">
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button class="btn btn-primary btn-lg fw-bold py-3" type="submit" style="border-radius: 12px; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border: none; box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);">
                                <i class="bi bi-magic me-2"></i> Analizar y Generar Vibe
                            </button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">¿Tienes el archivo? <a href="/books/upload" class="text-decoration-none fw-semibold" style="color: #6366f1;">Súbelo aquí</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Check local storage for Dark Mode preference
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark-mode');
    }
</script>
</body>
</html>
