<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - BookVibes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
</head>
<body class="bg-light" style="font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background: radial-gradient(1200px 600px at 0% 0%, #f0f5ff 10%, #ffffff 60%), linear-gradient(180deg, #ffffff 0%, #f7f9fc 100%);">
    
<nav class="navbar navbar-expand-lg navbar-dark mb-4" style="background: linear-gradient(90deg, #1f2937, #111827); box-shadow: 0 8px 20px rgba(0,0,0,0.15);">
  <div class="container">
    <a class="navbar-brand fw-bold" href="/dashboard" style="letter-spacing: .5px;">BookVibes</a>
    <div class="d-flex text-white align-items-center">
        <span class="me-3" style="opacity:.85">Hola, <?= htmlspecialchars($user_name) ?></span>
        <?php if(!empty($pro_enabled)): ?>
            <span class="badge bg-success me-3">Pro</span>
            <a href="/pro/cancel" class="btn btn-outline-light btn-sm me-2">Cancelar Pro</a>
        <?php else: ?>
            <span class="badge bg-secondary me-3">Básica</span>
        <?php endif; ?>
        <a href="/logout" class="btn btn-warning btn-sm fw-semibold">Salir</a>
    </div>
  </div>
</nav>

<style>
    .section-title {
        font-weight: 800;
        letter-spacing: .3px;
        color: #0f172a;
    }
    .book-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        overflow: hidden;
        background: #fff;
    }
    .book-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important;
    }
    .book-cover-container {
        position: relative;
        width: 100%;
        padding-top: 150%; /* Aspect Ratio 2:3 (standard book) */
        overflow: hidden;
        background-color: #f0f0f0;
    }
    .book-cover-img {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover; /* Fill the 2:3 container */
        /* If user REALLY wants 'no cutting' ever, we can use contain, but 'cover' on 2:3 is usually best for books */
    }
    .achievement-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 12px;
    }
    .achievement-badge {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px;
        border-radius: 14px;
        background: linear-gradient(135deg, #f8f9fa, #eceff4);
        border: 1px solid rgba(0,0,0,0.06);
        box-shadow: 0 6px 12px rgba(0,0,0,0.08);
    }
    .achievement-badge.completed {
        background: linear-gradient(135deg, #fff4e6, #fdebd3);
        border-color: rgba(255,193,7,0.35);
    }
    .achievement-badge.in-progress {
        background: linear-gradient(135deg, #f3e9ff, #e7ddff);
        border-color: rgba(111,66,193,0.25);
    }
    .cta-primary {
        background: linear-gradient(90deg,#4c1d95,#7c3aed);
        color: #fff;
        border: none;
    }
    .cta-primary:hover { 
        filter: brightness(1.06); 
        color: #fff;
    }
    .achievement-badge.blocked {
        background: linear-gradient(135deg, #f6f7f9, #eef1f6);
        border-color: rgba(108,117,125,0.2);
        opacity: 0.95;
    }
    .achievement-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        flex-shrink: 0;
    }
    .achievement-icon-btn {
        cursor: pointer;
        border: 2px solid rgba(0,0,0,0.06);
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .achievement-icon-btn:hover {
        border-color: #6f42c1;
        box-shadow: 0 0 0 3px rgba(111,66,193,0.2);
    }
    .achievement-icon-selected {
        border-color: #6f42c1 !important;
        box-shadow: 0 0 0 3px rgba(111,66,193,0.2) !important;
    }
    .achievement-row-icons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }
    .avatar-popover {
        position: fixed;
        z-index: 2000;
        background: #fff;
        border: 1px solid rgba(0,0,0,0.1);
        border-radius: 12px;
        box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        padding: 10px;
        display: none;
    }
    .achievement-icon.icon-completed {
        background: radial-gradient(circle at 30% 30%, #ffe082, #ffca28);
        color: #6f42c1;
    }
    .achievement-icon.icon-inprogress {
        background: radial-gradient(circle at 30% 30%, #d1c4e9, #b39ddb);
        color: #3f2d7d;
    }
    .achievement-icon.icon-blocked {
        background: radial-gradient(circle at 30% 30%, #e9ecef, #dee2e6);
        color: #6c757d;
    }
    .achievement-label {
        font-weight: 700;
        font-size: 0.95rem;
        color: #212529;
        line-height: 1.2;
    }
    .achievement-badge.next {
        flex-direction: column;
        align-items: flex-start;
    }
    .achievement-badge.locked {
        opacity: 0.95;
        background: linear-gradient(135deg, #f8f9fa, #eef1f6);
    }
    .achievement-progress {
        height: 8px;
        border-radius: 6px;
        background-color: rgba(0,0,0,0.05);
        margin-top: 6px;
    }
    .achievement-progress .progress-bar {
        transition: width 0.6s ease;
        position: relative;
    }
    .progress-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 0.78rem;
        margin-top: 6px;
        color: #6c757d;
    }
    .state-pill {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 999px;
        margin-left: auto;
    }
    .state-pill.completed { background-color: #ffc107; color: #212529; }
    .state-pill.in-progress { background-color: #6f42c1; color: #fff; }
    .state-pill.blocked { background-color: #adb5bd; color: #212529; }
    .count-pill {
        font-size: 0.7rem;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 999px;
        background-color: #0d6efd;
        color: #fff;
        margin-left: 6px;
    }
    .mood-chart-container {
        position: relative; 
        height: 200px;
    }
    .cta-primary {
        background: linear-gradient(90deg,#6f42c1,#8b5cf6);
        border: none;
        box-shadow: 0 10px 20px rgba(139,92,246,0.28);
    }
    .cta-primary:hover {
        filter: brightness(1.05);
    }
    @media (max-width: 576px) {
        .achievement-grid {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        }
        .achievement-label {
            font-weight: 700;
        }
    }
</style>

<div class="container">
    <div class="row g-4">
        <!-- Gamification Sidebar -->
        <div class="col-lg-3">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body text-center">
                     <div class="mb-3">
                        <?php if(!empty($avatar_icon)): ?>
                            <i class="bi <?= htmlspecialchars($avatar_icon) ?> display-4" style="color:#6f42c1;"></i>
                        <?php else: ?>
                            <i class="bi bi-person-circle display-4 text-primary"></i>
                        <?php endif; ?>
                     </div>
                     <h5 class="card-title mb-1"><?= htmlspecialchars($user_name) ?></h5>
                     <p class="text-muted small">Lector Apasionado</p>
                     
                     <h2 class="fw-bold text-primary"><?= $stats['total_points'] ?> XP</h2>
                     <div class="progress mb-3" style="height: 10px; border-radius: 5px;">
                        <div class="progress-bar bg-gradient" role="progressbar" style="width: <?= isset($stats['next_progress']) ? (int)$stats['next_progress'] : 0 ?>%; background-color: #6f42c1;"></div>
                     </div>
                     <div class="small text-muted">XP <?= (int)($stats['xp_progress_value'] ?? 0) ?> / <?= (int)($stats['xp_cap'] ?? 0) ?> • <?= (int)($stats['next_progress'] ?? 0) ?>%</div>
                     
                     <div class="text-start mt-4">
                        <h6 class="text-uppercase text-muted small fw-bold mb-3">Logros</h6>
                        <?php 
                            $unlocked = $stats['achievements_unlocked'] ?? [];
                            $locked = $stats['achievements_locked'] ?? [];
                            $inProgress = array_filter($locked, function($a) use ($stats) { 
                                $req = (int)($a['points_required'] ?? 0); 
                                $cur = min((int)$stats['total_points'], $req); 
                                return $cur > 0 && $cur < $req; 
                            });
                            $blocked = array_filter($locked, function($a) use ($stats) { 
                                $req = (int)($a['points_required'] ?? 0); 
                                $cur = min((int)$stats['total_points'], $req); 
                                return $cur === 0; 
                            });
                            usort($inProgress, function($a, $b){
                                return ((int)($b['progress'] ?? 0)) <=> ((int)($a['progress'] ?? 0));
                            });
                        ?>
                        <div class="mb-2 small text-muted">Desbloqueados</div>
                        <div class="achievement-row-icons mb-3">
                            <?php foreach($unlocked as $ach): ?>
                                <?php $isSelected = !empty($avatar_icon) && $avatar_icon === $ach['icon_class']; ?>
                                <span class="achievement-icon icon-completed achievement-icon-btn <?= $isSelected ? 'achievement-icon-selected' : '' ?>" data-icon="<?= htmlspecialchars($ach['icon_class']) ?>" title="<?= htmlspecialchars($ach['name'] ?? '') ?>">
                                    <i class="bi <?= $ach['icon_class'] ?>"></i>
                                </span>
                            <?php endforeach; ?>
                            <?php if(empty($stats['achievements_unlocked'])): ?>
                                <div class="text-muted small">No hay logros desbloqueados.</div>
                            <?php endif; ?>
                        </div>
                        
                        <?php $inCount = count($inProgress); ?>
                        <div class="mb-2">
                            <a href="#" id="toggleInProgress" class="small text-primary text-decoration-none">
                                Progreso <?= $inCount > 0 ? '(' . $inCount . ')' : '' ?>
                            </a>
                        </div>
                        <div class="achievement-grid" id="inProgressPanel" style="display:none;">
                            <?php foreach($inProgress as $ach): ?>
                                <?php 
                                    $p = (int)($ach['progress'] ?? 0); 
                                    $req = (int)($ach['points_required'] ?? 0); 
                                    $cur = min((int)$stats['total_points'], $req); 
                                    $barColor = ($p >= 75) ? '#198754' : '#6f42c1'; 
                                ?>
                                <div class="achievement-badge in-progress" title="<?= htmlspecialchars($ach['name'] ?? '') ?>">
                                    <span class="achievement-icon icon-inprogress">
                                        <i class="bi <?= $ach['icon_class'] ?>"></i>
                                    </span>
                                    <span class="count-pill"><?= $p ?>%</span>
                                    <div class="w-100 mt-1">
                                        <div class="progress achievement-progress">
                                            <div class="progress-bar" role="progressbar" style="width: <?= $p ?>%; background-color: <?= $barColor ?>;"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if(empty($inProgress)): ?>
                                <div class="text-muted small">No hay logros en progreso.</div>
                            <?php endif; ?>
                        </div>
                     </div>
                </div>
            </div>
            
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h6 class="card-title text-center mb-3">Estadísticas de Mood</h6>
                    <div class="mood-chart-container">
                        <canvas id="moodChart"></canvas>
                    </div>
                </div>
            </div>
            
            <a href="/books/search" class="btn cta-primary w-100 py-2 fw-bold shadow-sm">
                <i class="bi bi-plus-lg me-2"></i> Añadir Libro
            </a>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <h3 class="section-title mb-4">Tus Libros Recientes</h3>
            
            <?php if(empty($books)): ?>
                <div class="text-center p-5 bg-white rounded shadow-sm">
                    <i class="bi bi-book display-1 text-muted opacity-25"></i>
                    <h4 class="mt-3">Tu biblioteca está vacía</h4>
                    <p class="text-muted">¡Busca y añade tu primer libro para comenzar!</p>
                    <a href="/books/search" class="btn btn-outline-primary mt-2">Buscar Libros</a>
                </div>
            <?php else: ?>
                <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
                    <?php foreach ($books as $book): ?>
                    <div class="col">
                        <div class="card book-card h-100 shadow-sm">
                            <div class="book-cover-container">
                                <?php if($book['cover_url']): ?>
                                    <img src="<?= htmlspecialchars($book['cover_url']) ?>" class="book-cover-img" alt="<?= htmlspecialchars($book['title']) ?>" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center h-100 bg-secondary text-white">
                                        <i class="bi bi-journal-text fs-1"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="position-absolute top-0 end-0 m-2">
                                     <span class="badge bg-dark bg-opacity-75 backdrop-blur"><?= htmlspecialchars($book['mood']) ?></span>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <h6 class="card-title text-truncate fw-bold mb-1" title="<?= htmlspecialchars($book['title']) ?>">
                                    <?= htmlspecialchars($book['title']) ?>
                                </h6>
                                <p class="card-text small text-muted text-truncate mb-3">
                                    <?= htmlspecialchars($book['author']) ?>
                                </p>
                                <div class="d-flex">
                                    <a href="/books/show?id=<?= $book['id'] ?>" class="btn btn-outline-primary btn-sm flex-grow-1">Ver Detalles</a>
                                    <form action="/books/delete" method="POST" class="ms-2" onsubmit="return confirm('¿Seguro que quieres borrar este libro? Se restarán 10 XP.')">
                                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<div id="avatarPopover" class="avatar-popover">
    <div class="d-flex align-items-center gap-2">
        <button id="avatarUseBtn" class="btn btn-sm btn-primary">Usar como avatar</button>
        <button id="avatarCancelBtn" class="btn btn-sm btn-light">Cancelar</button>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const toggle = document.getElementById('toggleInProgress');
    const panel = document.getElementById('inProgressPanel');
    if (toggle && panel) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const isHidden = panel.style.display === 'none';
            panel.style.display = isHidden ? 'grid' : 'none';
        });
    }
    const achievementButtons = document.querySelectorAll('.achievement-icon-btn');
    const pop = document.getElementById('avatarPopover');
    const useBtn = document.getElementById('avatarUseBtn');
    const cancelBtn = document.getElementById('avatarCancelBtn');
    let currentIconBtn = null;
    function showPopoverFor(btn) {
        const rect = btn.getBoundingClientRect();
        pop.style.top = (rect.top + rect.height + 8) + 'px';
        pop.style.left = (rect.left) + 'px';
        pop.dataset.icon = btn.getAttribute('data-icon');
        pop.style.display = 'block';
        currentIconBtn = btn;
    }
    achievementButtons.forEach(btn => {
        btn.addEventListener('click', async () => {
            showPopoverFor(btn);
        });
    });
    cancelBtn.addEventListener('click', () => {
        pop.style.display = 'none';
    });
    useBtn.addEventListener('click', async () => {
        const icon = pop.dataset.icon;
        try {
            const res = await fetch('/books/avatar', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ icon_class: icon })
            });
            const data = await res.json();
            if (data.ok) {
                const avatarHolder = document.querySelector('.card-body .mb-3 i.bi');
                if (avatarHolder) {
                    avatarHolder.className = `bi ${icon} display-4`;
                    avatarHolder.style.color = '#6f42c1';
                }
                achievementButtons.forEach(b => { b.classList.remove('achievement-icon-selected'); });
                if (currentIconBtn) currentIconBtn.classList.add('achievement-icon-selected');
                pop.style.display = 'none';
            }
        } catch (e) {}
    });
    document.addEventListener('click', (e) => {
        if (pop.style.display === 'block') {
            const within = pop.contains(e.target) || (currentIconBtn && currentIconBtn.contains(e.target));
            if (!within) pop.style.display = 'none';
        }
    });
    const ctx = document.getElementById('moodChart').getContext('2d');
    const moodData = <?= json_encode($mood_stats ?? []) ?>;
    const labels = moodData.map(item => item.mood);
    const data = moodData.map(item => item.count);
    const moodColorsMap = {
        'Neutral': '#94a3b8',
        'Romántico': '#ff6b9e',
        'Intriga y Suspenso': '#ffb74d',
        'Épico y Aventurero': '#4cc3d9',
        'Fantasia': '#9b5de5',
        'Fantasía': '#9b5de5',
        'Misterio': '#0ea5e9',
        'Drama': '#f97316',
        'Comedia': '#22c55e',
        'Terror': '#ef4444'
    };
    const defaultPalette = ['#6f42c1','#22c55e','#ff6b9e','#f59e0b','#4cc3d9','#9b5de5','#0ea5e9','#ef4444','#94a3b8'];
    const colors = labels.map((l, i) => moodColorsMap[l] || defaultPalette[i % defaultPalette.length]);

    if (labels.length > 0) {
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 6,
                    hoverOffset: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            boxWidth: 12, 
                            usePointStyle: true, 
                            pointStyle: 'circle',
                            font: { size: 11 },
                            color: '#6c757d'
                        } 
                    }
                },
                cutout: '62%'
            }
        });
    } else {
        ctx.font = "14px Arial";
        ctx.textAlign = "center";
        ctx.fillText("Sin datos", 100, 100);
    }
</script>
</body>
</html>
