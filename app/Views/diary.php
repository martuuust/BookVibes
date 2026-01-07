<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Diario de Lectura - BookVibes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@400;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Light Mode - Soft Pastel */
            --bg-gradient: linear-gradient(135deg, #e0f2fe 0%, #f3e8ff 50%, #fce7f3 100%);
            --sidebar-bg: rgba(255, 255, 255, 0.7);
            --sidebar-border: rgba(255, 255, 255, 0.9);
            --card-bg: rgba(255, 255, 255, 0.6);
            --card-border: rgba(255, 255, 255, 0.8);
            --notebook-bg: #ffffff;
            --notebook-page: #fafafa;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --text-accent: #7c3aed;
            --accent-color: #8b5cf6;
            --accent-gradient: linear-gradient(135deg, #a78bfa 0%, #f472b6 100%);
            --entry-active: linear-gradient(135deg, #a78bfa 0%, #c084fc 100%);
            --star-color: #a78bfa;
        }

        body.dark-mode {
            /* Dark Mode - Midnight Blue & Violet */
            --bg-gradient: linear-gradient(180deg, #0c0a1d 0%, #1a1744 50%, #2d2769 100%);
            --sidebar-bg: rgba(30, 27, 75, 0.8);
            --sidebar-border: rgba(139, 92, 246, 0.2);
            --card-bg: rgba(45, 39, 105, 0.5);
            --card-border: rgba(139, 92, 246, 0.3);
            --notebook-bg: #2d3748;
            --notebook-page: #374151;
            --text-primary: #f9fafb;
            --text-secondary: #d1d5db;
            --text-accent: #c4b5fd;
            --accent-color: #a78bfa;
            --accent-gradient: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
            --entry-active: linear-gradient(135deg, #8b5cf6 0%, #a855f7 100%);
            --star-color: #a78bfa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            background-attachment: fixed;
            min-height: 100vh;
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* Stars Background for Dark Mode */
        body.dark-mode::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(1px 1px at 5% 10%, rgba(255,255,255,0.9), transparent),
                radial-gradient(1.5px 1.5px at 15% 25%, rgba(255,255,255,0.7), transparent),
                radial-gradient(1px 1px at 25% 8%, rgba(255,255,255,0.8), transparent),
                radial-gradient(2px 2px at 35% 45%, rgba(255,255,255,0.6), transparent),
                radial-gradient(1px 1px at 45% 15%, rgba(255,255,255,0.9), transparent),
                radial-gradient(1.5px 1.5px at 55% 35%, rgba(255,255,255,0.7), transparent),
                radial-gradient(1px 1px at 65% 5%, rgba(255,255,255,0.8), transparent),
                radial-gradient(2px 2px at 75% 55%, rgba(255,255,255,0.6), transparent),
                radial-gradient(1px 1px at 85% 20%, rgba(255,255,255,0.9), transparent),
                radial-gradient(1.5px 1.5px at 95% 40%, rgba(255,255,255,0.7), transparent),
                radial-gradient(1px 1px at 10% 80%, rgba(255,255,255,0.8), transparent),
                radial-gradient(2px 2px at 90% 75%, rgba(255,255,255,0.6), transparent);
            pointer-events: none;
            z-index: 0;
        }

        /* Main Layout */
        .app-container {
            display: flex;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--sidebar-border);
            display: flex;
            flex-direction: column;
            padding: 25px 20px;
        }

        .sidebar-logo {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 30px;
            padding-left: 5px;
        }

        .entries-section {
            flex: 1;
            overflow-y: auto;
        }

        .entry-item {
            display: flex;
            align-items: flex-start;
            padding: 12px 15px;
            margin-bottom: 8px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .entry-item:hover {
            background: rgba(139, 92, 246, 0.1);
        }

        .entry-item.active {
            background: var(--entry-active);
            border-left-color: var(--accent-color);
        }

        .entry-item.active .entry-label,
        .entry-item.active .entry-title {
            color: white;
        }

        .entry-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            font-weight: 600;
            margin-bottom: 2px;
        }

        .entry-title {
            font-size: 0.9rem;
            color: var(--text-primary);
            font-weight: 500;
        }

        /* Nav Buttons */
        .nav-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--sidebar-border);
        }

        .nav-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            color: var(--text-primary);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }

        .nav-btn:hover {
            background: rgba(139, 92, 246, 0.15);
            border-color: var(--accent-color);
            color: var(--accent-color);
        }

        /* Main Content Area */
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            position: relative;
        }

        /* 3D Book Container */
        .notebook-wrapper {
            perspective: 1500px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .notebook-container {
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1);
            background: transparent;
            border: none;
            box-shadow: none;
            backdrop-filter: none;
            padding: 0;
        }

        /* The Open Book */
        .notebook {
            width: 800px;
            height: 550px;
            position: relative;
            background-color: #fdfaf7; /* Paper color */
            border-radius: 5px;
            box-shadow: 
                0 0 5px rgba(0,0,0,0.1),
                inset 0 0 10px rgba(0,0,0,0.05),
                inset 0 0 30px rgba(0,0,0,0.02);
            display: flex;
            transform-style: preserve-3d;
            background: linear-gradient(to right, 
                #e3d5c5 0%, 
                #fdfaf7 5%, 
                #fdfaf7 48%, 
                #e3d5c5 50%, 
                #fdfaf7 52%, 
                #fdfaf7 95%, 
                #e3d5c5 100%
            );
        }

        /* Book Cover (Behind) */
        .notebook::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            /* Premium Leather Texture Gradient */
            background: linear-gradient(135deg, #2c3e50 0%, #1a2530 100%);
            border-radius: 4px;
            transform: translateZ(-20px);
            box-shadow: 
                0 25px 50px -12px rgba(0, 0, 0, 0.5), /* Deep Shadow */
                0 10px 15px -3px rgba(0, 0, 0, 0.3), /* Ambient Shadow */
                inset 0 0 0 1px rgba(255, 255, 255, 0.05), /* Outer Rim */
                inset 0 0 40px rgba(0,0,0,0.6); /* Inner Depth */
            /* Stitching Effect */
            border: 1px solid #111;
        }

        /* Spine */
        .notebook::after {
            content: '';
            position: absolute;
            top: -10px;
            bottom: -10px;
            left: 50%;
            width: 60px;
            transform: translateX(-50%) translateZ(-20px);
            background: #233140; /* Darker spine */
            border-radius: 4px;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.5);
        }
        
        /* Dark Mode Book Cover */
        body.dark-mode .notebook::before {
             background-color: #1a1a2e;
             box-shadow: 0 20px 50px rgba(0,0,0,0.6);
        }
        body.dark-mode .notebook::after {
             background-color: #10101c;
        }

        /* Pages Area */
        .book-spine-crease {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: 2px;
            background: rgba(0,0,0,0.1);
            transform: translateX(-50%);
            z-index: 10;
        }

        .left-page, .right-page {
            flex: 1;
            padding: 40px 30px;
            position: relative;
            z-index: 5;
            display: flex;
            flex-direction: column;
        }

        .left-page {
            border-right: 1px solid rgba(0,0,0,0.05);
            background: linear-gradient(to right, transparent 0%, rgba(0,0,0,0.02) 95%, rgba(0,0,0,0.05) 100%);
        }

        .right-page {
            border-left: 1px solid rgba(0,0,0,0.05);
            background: linear-gradient(to left, transparent 0%, rgba(0,0,0,0.02) 95%, rgba(0,0,0,0.05) 100%);
        }

        .notebook-header {
            text-align: center;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }

        .notebook-title {
            font-family: 'Caveat', cursive;
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-accent);
            margin-bottom: 5px;
        }

        .notebook-date {
            font-family: 'Caveat', cursive;
            font-size: 1.3rem;
            color: var(--text-secondary);
            font-style: italic;
        }

        /* Notebook Content */
        .notebook-content {
            position: relative;
            z-index: 1;
            min-height: 200px;
        }

        .diary-textarea {
            width: 100%;
            min-height: 200px;
            border: none;
            background: transparent;
            resize: none;
            font-family: 'Caveat', cursive;
            font-size: 1.5rem;
            line-height: 32px;
            color: var(--text-primary);
            outline: none;
            text-align: center; /* Center text alignment */
        }

        body.dark-mode .diary-textarea {
            color: #2c3e50; /* Keep text dark since page remains light */
        }

        .diary-textarea::placeholder {
            color: var(--text-secondary);
            opacity: 0.6;
        }

        /* Book Icon at bottom */
        .notebook-icon {
            text-align: center;
            margin-top: 20px;
            position: relative;
            z-index: 1;
        }

        .notebook-icon i {
            font-size: 2rem;
            color: var(--accent-color);
            opacity: 0.5;
        }

        /* Star Rating */
        .star-rating {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 25px;
        }

        .star-rating i {
            font-size: 1.5rem;
            color: var(--star-color);
            cursor: pointer;
            transition: all 0.2s;
        }

        .star-rating i:hover {
            transform: scale(1.2);
        }

        .star-rating i.filled {
            color: var(--star-color);
        }

        .star-rating i.empty {
            color: var(--text-secondary);
            opacity: 0.3;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 50px;
            color: var(--text-primary);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }

        .action-btn:hover {
            background: rgba(139, 92, 246, 0.2);
            border-color: var(--accent-color);
        }

        .action-btn.primary {
            background: var(--accent-gradient);
            border: none;
            color: white;
        }

        .action-btn.primary:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.4);
        }

        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--card-border);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            color: var(--accent-color);
            font-size: 1.3rem;
            z-index: 1000;
        }

        .theme-toggle:hover {
            background: rgba(139, 92, 246, 0.2);
            transform: rotate(20deg) scale(1.1);
        }

        /* Toast */
        .toast-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 9999;
        }

        /* Page Navigation */
        .page-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid var(--card-border);
        }

        .page-nav-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 8px 15px;
            background: transparent;
            border: 1px solid var(--card-border);
            border-radius: 8px;
            color: var(--text-secondary);
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .page-nav-btn:hover:not(:disabled) {
            background: rgba(139, 92, 246, 0.1);
            border-color: var(--accent-color);
            color: var(--accent-color);
        }

        .page-nav-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .page-indicator {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        /* Responsive */
        @media (max-width: 900px) {
            .sidebar {
                display: none;
            }
            .main-content {
                padding: 20px;
            }
            .notebook-container {
                padding: 20px;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .notebook-container {
            animation: fadeIn 0.5s ease;
        }
    </style>
</head>
<body>
    <!-- Stars Background -->
    <div id="stars-container" class="stars"></div>
    


    <style>
        /* Page Flip Animations */
        @keyframes flipNext {
            0% { transform: rotateY(0); }
            100% { transform: rotateY(-180deg); }
        }

        @keyframes flipPrev {
            0% { transform: rotateY(-180deg); }
            100% { transform: rotateY(0); }
        }

        .turning-page {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: 50%;
            background: #fdfaf7;
            transform-origin: left;
            transform-style: preserve-3d;
            z-index: 20;
            backface-visibility: hidden;
            box-shadow: 
                inset 0 0 10px rgba(0,0,0,0.05),
                inset 0 0 30px rgba(0,0,0,0.02);
            display: flex;
            flex-direction: column;
            padding: 40px 30px;
        }

        .turning-page.back {
            transform: rotateY(180deg);
            transform-origin: right;
            left: 0;
            background: linear-gradient(to right, rgba(0,0,0,0.05) 0%, rgba(0,0,0,0.02) 5%, transparent 100%), #fdfaf7;
        }
            background-color: #c82333 !important;
            border-color: #bd2130 !important;
            color: white !important;
        }
        .navbar-text-light {
            color: black !important;
        }
        body.dark-mode .navbar-text-light {
            color: white !important;
        }
        #darkModeIcon {
            color: #1a202c;
        }
        body.dark-mode #darkModeIcon {
            color: #f8fafc !important;
        }
    </style>

    <!-- Main Content -->
    <div class="container-fluid px-4 px-md-5">
        <div class="diary-layout">
            <!-- Entries Sidebar - Inside layout -->
            <div class="entries-panel">
                <!-- Back to Dashboard Button -->
                <a href="/dashboard" class="back-to-home-btn">
                    <i class="bi bi-arrow-left"></i> Volver al Inicio
                </a>
                
                <?php if(empty($diary_entries)): ?>
                    <p style="color: var(--text-secondary); font-size: 0.9rem; text-align: center;">Sin entradas aún</p>
                <?php else: ?>
                    <?php foreach($diary_entries as $index => $entry): ?>
                        <div class="entry-card <?= $index === 0 ? 'active' : '' ?>" onclick="goToEntry(<?= $index ?>)" data-index="<?= $index ?>">
                            <div class="entry-label">Entry <?= $index + 1 ?>:</div>
                            <div class="entry-title"><?= htmlspecialchars($entry['book_title']) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <button class="add-entry-btn" onclick="addNewEntry()">
                    <i class="bi bi-plus-lg"></i> Nueva Entrada
                </button>
            </div>

            <!-- Notebook Container -->
            <div class="notebook-wrapper">
                <div class="notebook-container">
                    <div class="notebook">
                        <div class="book-spine-crease"></div>
                        
                        <!-- Left Page -->
                        <div class="left-page">
                            <div class="notebook-header">
                                <input type="text" class="notebook-title" id="entryTitle" 
                                       value="<?= !empty($diary_entries) ? htmlspecialchars($diary_entries[0]['book_title']) : 'Bookvibes Reading Diary' ?>" 
                                       placeholder="Título del libro..."
                                       style="border: none; background: transparent; text-align: center; width: 100%; outline: none; font-family: 'Caveat', cursive; font-size: 2rem; font-weight: 700; color: var(--text-accent);">
                                <div class="notebook-date" id="entryDate">
                                    <?= !empty($diary_entries) ? date('F d, Y', strtotime($diary_entries[0]['created_at'])) : date('F d, Y') ?>
                                </div>
                            </div>
                        </div>

                        <!-- Right Page -->
                        <div class="right-page">
                            <div class="notebook-content">
                                <textarea class="diary-textarea" id="entryContent" 
                                          placeholder="Escribe tus pensamientos sobre el libro aquí..."><?php 
                                    if(!empty($diary_entries)) {
                                        echo htmlspecialchars($diary_entries[0]['content'] ?? '');
                                    }
                                ?></textarea>
                            </div>

                            <div class="notebook-icon">
                                <i class="bi bi-book"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Star Rating -->
                    <div class="star-rating" id="starRating">
                        <i class="bi bi-star-fill filled" data-rating="1"></i>
                        <i class="bi bi-star-fill filled" data-rating="2"></i>
                        <i class="bi bi-star-fill filled" data-rating="3"></i>
                        <i class="bi bi-star-fill filled" data-rating="4"></i>
                        <i class="bi bi-star-fill filled" data-rating="5"></i>
                    </div>

                    <!-- Page Navigation -->
                    <div class="page-nav">
                        <button class="page-nav-btn" id="prevBtn" onclick="prevEntry()" disabled>
                            <i class="bi bi-chevron-left"></i> Anterior
                        </button>
                        <span class="page-indicator">
                            <span id="currentPage">1</span> / <span id="totalPages"><?= max(count($diary_entries ?? []), 1) ?></span>
                        </span>
                        <button class="page-nav-btn" id="nextBtn" onclick="nextEntry()">
                            Siguiente <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <button class="action-btn" onclick="deleteEntry()">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                        <button class="action-btn primary" onclick="saveEntry()">
                            <i class="bi bi-check-lg"></i> Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* New Layout Styles */
        .diary-layout {
            display: flex;
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            align-items: center; /* Vertically center */
            min-height: 100vh; /* Full height */
            padding-top: 0;
        }
        
        .entries-panel {
            width: 200px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            flex-shrink: 0;
        }
        
        .entry-card {
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 15px 18px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .entry-card:hover {
            transform: translateX(5px);
            border-color: var(--accent-color);
        }
        
        .entry-card.active {
            background: var(--accent-gradient);
            border-color: transparent;
        }
        
        .entry-card.active .entry-label,
        .entry-card.active .entry-title {
            color: white;
        }
        
        .entry-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 3px;
        }
        
        .entry-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .add-entry-btn {
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            border: 1px dashed var(--accent-color);
            border-radius: 16px;
            padding: 12px 18px;
            color: var(--accent-color);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .add-entry-btn:hover {
            background: rgba(139, 92, 246, 0.1);
        }
        
        .back-to-home-btn {
            background: var(--card-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 12px 18px;
            color: var(--text-primary);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            margin-bottom: 10px;
        }
        
        .back-to-home-btn:hover {
            background: rgba(139, 92, 246, 0.1);
            border-color: var(--accent-color);
            color: var(--accent-color);
        }
        
        .notebook-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
        }
        
        @media (max-width: 900px) {
            .diary-layout {
                flex-direction: column;
            }
            .entries-panel {
                width: 100%;
                flex-direction: row;
                overflow-x: auto;
                padding-bottom: 10px;
            }
            .entry-card {
                min-width: 150px;
            }
        }
    </style>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <script>
        // Data
        let entries = <?= json_encode($diary_entries ?? []) ?>;
        let currentIndex = 0;
        let currentRating = 5;

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            applyTheme();
            
            // Dark mode toggle - same as dashboard
            const darkModeToggle = document.getElementById('darkModeToggle');
            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', toggleTheme);
            }
            
            if (entries.length > 0) {
                loadEntry(0);
            }
            updateNavButtons();
            setupStarRating();
        });

        // Theme
        function toggleTheme() {
            const isDark = document.body.classList.toggle('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
            document.getElementById('darkModeIcon').className = isDark ? 'bi bi-sun-fill fs-4' : 'bi bi-moon-fill fs-4';
        }

        function applyTheme() {
            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-mode');
                document.getElementById('darkModeIcon').className = 'bi bi-sun-fill fs-4';
            }
        }

        // Load Entry
        function loadEntry(index) {
            if (index < 0 || index >= entries.length) return;
            
            currentIndex = index;
            const entry = entries[index];
            
            document.getElementById('entryTitle').value = entry.book_title || '';
            document.getElementById('entryContent').value = entry.content || '';
            document.getElementById('entryDate').textContent = formatDate(entry.created_at);
            document.getElementById('currentPage').textContent = index + 1;
            
            updateNavButtons();
            updateSidebarActive();
        }

        function goToEntry(index) {
            loadEntry(index);
        }

        // HTML Generators
        function getEntryLeftHtml(index) {
            const entry = entries[index];
            const dateStr = formatDate(entry.created_at);
            return `
                <div class="notebook-header">
                    <input type="text" class="notebook-title" value="${escapeHtml(entry.book_title)}" readonly
                           style="border: none; background: transparent; text-align: center; width: 100%; outline: none; font-family: 'Caveat', cursive; font-size: 2rem; font-weight: 700; color: var(--text-accent);">
                    <div class="notebook-date">${dateStr}</div>
                </div>
            `;
        }

        function getEntryRightHtml(index) {
            const entry = entries[index];
            return `
                <div class="notebook-content">
                    <textarea class="diary-textarea" readonly>${escapeHtml(entry.content)}</textarea>
                </div>
                <div class="notebook-icon">
                    <i class="bi bi-book"></i>
                </div>
            `;
        }
        
        function escapeHtml(text) {
             if (!text) return '';
             return text
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }

        function prevEntry() {
            if (currentIndex <= 0) return;
            
            const notebook = document.querySelector('.notebook');
            const prevIndex = currentIndex - 1;
            const leftPage = document.querySelector('.left-page');
            const rightPage = document.querySelector('.right-page');
            
            // PREV: Flip Left Page back to Right.
            // Underneath Left is Prev Left.
            leftPage.innerHTML = getEntryLeftHtml(prevIndex);
            
            const flipper = document.createElement('div');
            flipper.classList.add('turning-page');
            flipper.style.transform = 'rotateY(-180deg)';
            flipper.style.zIndex = '100';
            
            // Content leaving (Current Left)
            // Since container starts at -180deg, we must rotate content 180deg to be readable
            flipper.innerHTML = `<div style="transform: rotateY(180deg); height: 100%; width: 100%;">${getEntryLeftHtml(currentIndex)}</div>`;
            
            notebook.appendChild(flipper);
            
            flipper.animate([
                { transform: 'rotateY(-180deg)', background: '#fdfaf7' },
                { transform: 'rotateY(-90deg)', background: '#e3d5c5' },
                { transform: 'rotateY(0deg)', background: '#fdfaf7' }
            ], {
                duration: 600,
                easing: 'ease-in-out',
                fill: 'forwards'
            }).onfinish = () => {
                flipper.remove();
                rightPage.innerHTML = getEntryRightHtml(prevIndex);
                currentIndex = prevIndex;
                updateNavButtons();
                setupStarRating();
                loadEntry(currentIndex); 
            };
            
            setTimeout(() => {
                // Content arriving (Prev Right) - Normal rotation as container approaches 0
                flipper.innerHTML = getEntryRightHtml(prevIndex);
            }, 300);
        }

        function nextEntry() {
            if (currentIndex >= entries.length - 1) return;
            
            const notebook = document.querySelector('.notebook');
            const nextIndex = currentIndex + 1;
            const leftPage = document.querySelector('.left-page');
            const rightPage = document.querySelector('.right-page');
            
            // NEXT: Flip Right Page to Left.
            // Underneath Right is Next Right.
            rightPage.innerHTML = getEntryRightHtml(nextIndex);
            
            const flipper = document.createElement('div');
            flipper.classList.add('turning-page');
            flipper.style.transform = 'rotateY(0deg)';
            flipper.style.zIndex = '100';
            flipper.innerHTML = getEntryRightHtml(currentIndex); // Current Right (starts normal)
            
            notebook.appendChild(flipper);
            
            flipper.animate([
                { transform: 'rotateY(0deg)', background: '#fdfaf7' },
                { transform: 'rotateY(-90deg)', background: '#e3d5c5' },
                { transform: 'rotateY(-180deg)', background: '#fdfaf7' }
            ], {
                duration: 600,
                easing: 'ease-in-out',
                fill: 'forwards'
            }).onfinish = () => {
                flipper.remove();
                leftPage.innerHTML = getEntryLeftHtml(nextIndex);
                currentIndex = nextIndex;
                updateNavButtons();
                setupStarRating();
                loadEntry(currentIndex);
            };
            
            setTimeout(() => {
                // Content arriving (Next Left)
                // Container is approaching -180deg, so we must rotate content 180deg to be readable
                flipper.innerHTML = `<div style="transform: rotateY(180deg); height: 100%; width: 100%;">${getEntryLeftHtml(nextIndex)}</div>`;
            }, 300);
        }

        function updateNavButtons() {
            document.getElementById('prevBtn').disabled = currentIndex <= 0;
            document.getElementById('nextBtn').disabled = currentIndex >= entries.length - 1;
        }

        function updateSidebarActive() {
            document.querySelectorAll('.entry-item').forEach((item, idx) => {
                item.classList.toggle('active', idx === currentIndex);
            });
        }

        // Star Rating
        function setupStarRating() {
            const stars = document.querySelectorAll('#starRating i');
            stars.forEach(star => {
                star.addEventListener('click', () => {
                    const rating = parseInt(star.dataset.rating);
                    currentRating = rating;
                    updateStars(rating);
                });
            });
        }

        function updateStars(rating) {
            const stars = document.querySelectorAll('#starRating i');
            stars.forEach((star, idx) => {
                if (idx < rating) {
                    star.classList.remove('empty');
                    star.classList.add('filled');
                } else {
                    star.classList.remove('filled');
                    star.classList.add('empty');
                }
            });
        }

        // Save Entry
        // Save Entry
        async function saveEntry() {
            const title = document.getElementById('entryTitle').value.trim();
            const content = document.getElementById('entryContent').value.trim();

            if (!title || !content) {
                showToast('Por favor completa el título y el contenido', 'warning');
                return;
            }

            try {
                let url = '/diary/create';
                let method = 'POST'; // Changed to POST for consistency, though Model.update uses query with ID. URL discriminates.
                // Wait, if updating, we need ID.
                let body = { book_title: title, content: content };
                
                // Determine if updating
                if (currentIndex < entries.length && entries[currentIndex].id) {
                     url = '/diary/update';
                     body.id = entries[currentIndex].id;
                }

                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(body)
                });
                const data = await res.json();
                
                if (data.ok) {
                    showToast('¡Entrada guardada!', 'success');
                    // If created new, reload to get ID and update list
                    // If updated, could just update local state, but reloading ensures consistency
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + (data.error || 'No se pudo guardar'), 'danger');
                }
            } catch (e) {
                console.error(e);
                showToast('Error de conexión', 'danger');
            }
        }

        async function deleteEntry() {
            if (entries.length === 0) return;
            
            if (!confirm('¿Eliminar esta entrada?')) return;

            try {
                const res = await fetch('/diary/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: entries[currentIndex].id })
                });
                const data = await res.json();
                
                if (data.ok) {
                    showToast('Entrada eliminada', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error al eliminar', 'danger');
                }
            } catch (e) {
                showToast('Error de conexión', 'danger');
            }
        }

        function addNewEntry() {
            document.getElementById('entryTitle').value = '';
            document.getElementById('entryContent').value = '';
            document.getElementById('entryDate').textContent = formatDate(new Date().toISOString());
            document.getElementById('entryTitle').focus();
            currentIndex = entries.length;
            document.getElementById('currentPage').textContent = entries.length + 1;
        }

        // Helpers
        function formatDate(dateStr) {
            if (!dateStr) return '';
            const date = new Date(dateStr);
            return date.toLocaleDateString('en-US', { 
                month: 'long', 
                day: 'numeric',
                year: 'numeric' 
            });
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} shadow-lg`;
            toast.style.cssText = 'animation: slideIn 0.3s ease; min-width: 250px;';
            toast.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle' : type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>${message}`;
            
            document.getElementById('toastContainer').appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }
    </script>

    <style>
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</body>
</html>
