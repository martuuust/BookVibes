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
<body>
    
<nav class="navbar navbar-expand-lg navbar-dark mb-5 sticky-top" style="background: rgba(15, 23, 42, 0.95); backdrop-filter: blur(10px); box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
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
            <span class="d-block fw-semibold" style="font-size: 0.9rem;">Hola, <?= htmlspecialchars($user_name) ?></span>
            <small class="text-white-50" style="font-size: 0.75rem;">Lector</small>
        </div>
        <?php if(!empty($pro_enabled)): ?>
            <span class="badge bg-gradient border border-light border-opacity-25" style="background-color: #8b5cf6;">Pro</span>
            <a href="/pro/cancel" class="btn btn-outline-light btn-sm" style="font-size: 0.8rem; border-radius: 8px;">Cancelar</a>
        <?php else: ?>
            <span class="badge bg-secondary bg-opacity-50 border border-secondary border-opacity-25">Básica</span>
        <?php endif; ?>
        <a href="/logout" class="btn btn-danger btn-sm fw-semibold rounded-pill px-3" style="font-size: 0.8rem;">Salir</a>
    </div>
  </div>
</nav>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        --card-bg: #ffffff;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --bg-body: #f8fafc;
        --border-color: rgba(226, 232, 240, 0.8);
    }
    
    body.dark-mode {
        --card-bg: #1e293b;
        --text-main: #f8fafc;
        --text-muted: #94a3b8;
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
        --border-color: rgba(255, 255, 255, 0.1);
    }
    
    body {
        background: var(--bg-body) !important;
        background-attachment: fixed !important;
        color: var(--text-main);
        transition: background 0.3s ease, color 0.3s ease;
    }
    
    /* Dark Mode Overrides for Bootstrap */
    body.dark-mode .bg-white { background-color: var(--card-bg) !important; color: var(--text-main); }
    body.dark-mode .bg-light { background-color: #0f172a !important; color: var(--text-main); }
    body.dark-mode .text-muted { color: #94a3b8 !important; }
    body.dark-mode .text-dark { color: #f8fafc !important; }
    body.dark-mode .card { background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-main); }
    body.dark-mode .list-group-item { background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-main); }
    body.dark-mode .modal-content { background-color: var(--card-bg); color: var(--text-main); }
    body.dark-mode .dropdown-menu { background-color: var(--card-bg); border-color: var(--border-color); }
    body.dark-mode .dropdown-item { color: var(--text-main); }
    body.dark-mode .dropdown-item:hover { background-color: rgba(255,255,255,0.1); }
    body.dark-mode .avatar-popover { background-color: var(--card-bg); border-color: var(--border-color); color: var(--text-main); }

    .section-title {
        font-weight: 800;
        letter-spacing: -0.5px;
        color: var(--text-main);
        font-size: 1.75rem;
    }
    .card {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    }
    .book-card {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: none;
        overflow: hidden;
        height: 100%;
        position: relative;
    }
    .book-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .book-cover-container {
        position: relative;
        width: 100%;
        padding-top: 150%; /* 2:3 */
        overflow: hidden;
        background-color: #f1f5f9;
        border-radius: 12px;
        box-shadow: inset 0 0 20px rgba(0,0,0,0.05);
    }
    .book-cover-img {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }
    .book-card:hover .book-cover-img {
        transform: scale(1.05);
    }
    .badge-mood {
        backdrop-filter: blur(8px);
        background: rgba(15, 23, 42, 0.8);
        border: 1px solid rgba(255,255,255,0.1);
        padding: 6px 12px;
        font-weight: 600;
        letter-spacing: 0.3px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    
    /* Gamification & Sidebar */
    .profile-card-header {
        background: linear-gradient(to right, #f8fafc, #f1f5f9);
        padding: 2rem 1.5rem;
        border-radius: 16px 16px 0 0;
        border-bottom: 1px solid #e2e8f0;
    }
    .xp-badge {
        background: rgba(99, 102, 241, 0.1);
        color: #6366f1;
        font-weight: 800;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 1.2rem;
    }
    
    .cta-primary {
        background: var(--primary-gradient);
        color: white;
        border: none;
        border-radius: 12px;
        transition: all 0.2s;
    }
    .cta-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(168, 85, 247, 0.4);
        color: white;
    }
    
    .achievement-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 12px;
    }
    .achievement-icon-btn {
        transition: transform 0.2s;
    }
    .achievement-icon-btn:hover {
        transform: scale(1.1);
    }
    
    /* Scrollbar for achievements */
    .achievement-scroll {
        max-height: 300px;
        overflow-y: auto;
        padding-right: 5px;
    }
    .achievement-scroll::-webkit-scrollbar { width: 4px; }
    .achievement-scroll::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

    /* Restored & Modernized Achievement Styles */
    .achievement-row-icons {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    .achievement-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }
    .achievement-icon.icon-completed { background: linear-gradient(135deg, #fcd34d, #f59e0b); color: #fff; }
    .achievement-icon.icon-inprogress { background: linear-gradient(135deg, #e9d5ff, #c084fc); color: #7e22ce; }
    .achievement-icon.icon-blocked { background: #f1f5f9; color: #94a3b8; box-shadow: none; }
    
    .achievement-badge {
        display: flex; align-items: center; gap: 12px;
        padding: 12px;
        border-radius: 12px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        margin-bottom: 8px;
    }
    .achievement-badge.in-progress { background: #faf5ff; border-color: #e9d5ff; }
    
    .count-pill {
        background: #3b82f6; color: white;
        font-size: 0.7rem; font-weight: 700;
        padding: 2px 8px; border-radius: 99px;
    }
    .achievement-progress {
        height: 6px; border-radius: 99px; background: #e2e8f0;
        margin-top: 8px; overflow: hidden;
    }
    .achievement-progress .progress-bar { border-radius: 99px; }
    
    .avatar-popover {
        position: fixed; z-index: 1050;
        background: white; padding: 12px;
        border-radius: 12px;
        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
        display: none;
    }
    
    .mood-chart-container { position: relative; height: 220px; }

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
                                     <span class="badge bg-white text-black bg-opacity-75 backdrop-blur"><?= htmlspecialchars($book['mood']) ?></span>
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
                    borderColor: 'transparent',
                    borderWidth: 0,
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

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })
    
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
</script>
</body>
</html>
