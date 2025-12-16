<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Buscar Libro - BookVibes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body class="bg-light" style="font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background: linear-gradient(135deg,#eef2ff,#ffffff);">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow border-0" style="border-radius: 18px;">
                <div class="card-body p-5 text-center">
                    <h2 class="mb-2 fw-bold">¿Qué estás leyendo?</h2>
                    <p class="text-muted mb-4">Introduce el título y generaremos una experiencia inmersiva.</p>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-warning text-start"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form action="/books/search" method="post">
                        <div class="input-group input-group-lg mb-3">
                            <input type="text" name="query" class="form-control" placeholder="Ej: Cien Años de Soledad..." required>
                            <button class="btn btn-primary fw-semibold" type="submit" style="box-shadow: 0 10px 20px rgba(13,110,253,0.25);">Analizar</button>
                        </div>
                    </form>
                    
                    <a href="/dashboard" class="btn btn-link text-secondary">Volver al Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
