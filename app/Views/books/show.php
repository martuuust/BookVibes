<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($book['title']) ?> - BookVibes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        .character-card { transition: transform 0.2s; }
        .character-card:hover { transform: translateY(-5px); }
        .playlist-track:hover { background-color: #f8f9fa; }
        body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background: radial-gradient(1200px 600px at 0% 0%, #f0f5ff 10%, #ffffff 60%), linear-gradient(180deg, #ffffff 0%, #f7f9fc 100%); }
        .header-starry { 
            position: relative; 
            background:
                radial-gradient(2px 2px at 12% 28%, rgba(255,255,255,0.9), transparent 3px),
                radial-gradient(1.5px 1.5px at 22% 65%, rgba(255,255,255,0.8), transparent 3px),
                radial-gradient(1.5px 1.5px at 38% 18%, rgba(255,255,255,0.75), transparent 3px),
                radial-gradient(1px 1px at 48% 52%, rgba(255,255,255,0.7), transparent 3px),
                radial-gradient(1px 1px at 62% 33%, rgba(255,255,255,0.85), transparent 3px),
                radial-gradient(1px 1px at 74% 70%, rgba(255,255,255,0.7), transparent 3px),
                radial-gradient(1px 1px at 86% 26%, rgba(255,255,255,0.9), transparent 3px),
                radial-gradient(1px 1px at 91% 58%, rgba(255,255,255,0.8), transparent 3px),
                radial-gradient(1px 1px at 30% 80%, rgba(255,255,255,0.7), transparent 3px),
                radial-gradient(1px 1px at 10% 50%, rgba(255,255,255,0.75), transparent 3px),
                linear-gradient(180deg, #0b1220 0%, #0d1b2a 45%, #0b141f 100%);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .cta-success { background: linear-gradient(90deg,#064e3b,#16a34a); border: none; color: #fff; box-shadow: 0 10px 20px rgba(22,163,74,0.25); }
        .cta-success:hover { filter: brightness(1.05); }
        .header-playlist { background: linear-gradient(90deg,#4c1d95,#6d28d9); }
        .btn-listen { background: linear-gradient(90deg,#4c1d95,#7c3aed); color: #fff; border: none; }
        .btn-listen:hover { filter: brightness(1.08); }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <!-- Header with Mood Color -->
    <div class="text-white p-5 text-center header-starry">
        <h1 class="display-4"><?= htmlspecialchars($book['title']) ?></h1>
        <p class="lead">por <?= htmlspecialchars($book['author']) ?></p>
        <span class="badge bg-warning text-dark fs-6"><?= htmlspecialchars($book['mood']) ?></span>
        <div class="mt-4">
            <a href="/dashboard" class="btn btn-outline-light btn-sm">← Volver</a>
        </div>
    </div>
</div>

<div class="container mt-4">
    <div class="row">
        <!-- Playlist Col -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header text-white header-playlist">
                    <h5 class="mb-0">🎧 Playlist para mood: <?= htmlspecialchars($book['mood']) ?></h5>
                </div>
                <div class="list-group list-group-flush">
                    <?php if($playlist && isset($playlist['songs'])): ?>
                        <?php $limit = (!empty($pro_enabled) && $pro_enabled) ? PHP_INT_MAX : 5; $i=0; foreach($playlist['songs'] as $song): if($i++ >= $limit) break; ?>
                            <div class="list-group-item playlist-track">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?= htmlspecialchars($song['title']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($song['artist']) ?></small>
                                    </div>
                                    <a href="<?= htmlspecialchars($song['url']) ?>" target="_blank" rel="noopener" class="btn btn-sm btn-listen">
                                        Escuchar
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-3 text-muted">No hay playlist disponible.</div>
                    <?php endif; ?>
                </div>
                <div class="card-footer bg-light">
                    <div class="d-grid gap-2">
                        <a href="/pro/upgrade?book_id=<?= urlencode($book['id']) ?>&return=<?= urlencode('/books/show?id=' . $book['id']) ?>" class="btn btn-dark">Recomendar más canciones</a>
                        <?php if($playlist && isset($playlist['songs']) && !empty($playlist['songs'])): ?>
                            <a href="/spotify/create?book_id=<?= urlencode($book['id']) ?>" class="btn cta-success">
                                Convertir a Spotify
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Characters Col -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5>Sinopsis</h5>
                    <p class="text-muted"><?= htmlspecialchars($book['synopsis']) ?></p>
                </div>
            </div>
            <h3 class="mb-3 border-bottom pb-2">Cartas de Personajes</h3>
            <div class="row">
                <?php if (empty($characters)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">No hay información suficiente para mostrar personajes de fuentes verificables.</div>
                    </div>
                <?php endif; ?>
                <?php foreach($characters as $char): ?>
                <div class="col-md-6 mb-4">
                    <div class="card character-card h-100 border-0 shadow">
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

</body>
</html>
