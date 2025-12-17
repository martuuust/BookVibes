<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - BookVibes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --secondary-gradient: linear-gradient(135deg, #a78bfa 0%, #2dd4bf 100%);
            --bg-gradient: radial-gradient(ellipse at bottom, #1b2735 0%, #090a0f 100%);
            --card-bg: rgba(30, 41, 59, 0.7);
            --card-border: rgba(255, 255, 255, 0.1);
            --input-bg: rgba(255, 255, 255, 0.05);
            --input-focus-bg: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            height: 100vh;
            overflow: hidden;
            background: var(--bg-gradient);
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            color: var(--text-main);
        }

        /* Stars */
        .stars {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
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

        /* Floating Elements */
        .floating-container {
            position: absolute;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        }
        .floater {
            position: absolute;
            color: rgba(255, 255, 255, 0.15);
            animation: float-around infinite ease-in-out alternate;
        }
        @keyframes float-around {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(30px, 30px) rotate(10deg); }
        }
        
        /* Orbit animation for some items */
        .orbiter {
            position: absolute;
            top: 50%;
            left: 50%;
            color: rgba(255, 255, 255, 1);
            animation: orbit linear infinite;
        }
        @keyframes orbit {
            from { transform: rotate(0deg) translateX(300px) rotate(0deg); }
            to { transform: rotate(360deg) translateX(300px) rotate(-360deg); }
        }

        /* Login Card */
        .login-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 3rem;
            width: 90%;
            max-width: 420px;
            color: white;
            z-index: 10;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            position: relative;
            overflow: hidden;
            animation: fadeIn 1s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Inner glow/shine */
        .login-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 60%);
            transform: rotate(45deg);
            pointer-events: none;
        }

        .form-control {
            background: var(--input-bg);
            border: 1px solid var(--card-border);
            color: white;
            padding: 0.8rem 1rem;
            border-radius: 12px;
            transition: all 0.3s;
        }
        .form-control:focus {
            background: var(--input-focus-bg);
            border-color: #a855f7;
            box-shadow: 0 0 0 4px rgba(168, 85, 247, 0.2);
            color: white;
            outline: none;
        }
        .form-control::placeholder { color: rgba(255, 255, 255, 0.4); }
        
        .btn-primary {
            background: var(--primary-gradient);
            border: none;
            padding: 0.8rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px rgba(168, 85, 247, 0.5);
        }
        .btn-primary::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: 0.5s;
        }
        .btn-primary:hover::after { left: 100%; }

        .auth-title { 
            font-size: 2.5rem; 
            font-weight: 800; 
            margin-bottom: 0.5rem; 
            text-align: center; 
            letter-spacing: -1px;
            background: var(--secondary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .auth-subtitle { color: var(--text-muted); margin-bottom: 2rem; text-align: center; font-weight: 500; }
        
        .form-label { color: var(--text-main); font-size: 0.9rem; margin-left: 0.2rem; font-weight: 500; }
        .form-check-label { color: var(--text-muted); font-size: 0.9rem; }
        
        a { color: #a78bfa; transition: color 0.2s; }
        a:hover { color: #c4b5fd; }
        
        .icon-header {
            width: 80px;
            height: 80px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            position: relative;
            margin-bottom: 1.5rem;
            font-size: 4rem; 
        }
        .icon-header i.bi-book-half {
            color: white;
            -webkit-text-fill-color: white;
        }
        .icon-header i.bi-music-note-beamed, 
        .icon-header i.bi-music-note {
            color: #2dd4bf;
            -webkit-text-fill-color: #2dd4bf;
        }

    </style>
</head>
<body>

<!-- Stars Background -->
<div id="stars-container" class="stars"></div>

<!-- Floating Icons Background -->
<div class="floating-container">
    <!-- Static floaters (drifting) -->
    <i class="bi bi-book floater" style="top: 15%; left: 10%; font-size: 3rem; animation-duration: 6s;"></i>
    <i class="bi bi-music-note-beamed floater" style="top: 25%; right: 15%; font-size: 2.5rem; animation-duration: 7s; animation-delay: 1s;"></i>
    <i class="bi bi-book-half floater" style="bottom: 20%; left: 20%; font-size: 4rem; animation-duration: 8s; animation-delay: 2s;"></i>
    <i class="bi bi-music-note floater" style="bottom: 30%; right: 10%; font-size: 2rem; animation-duration: 5s;"></i>
    <i class="bi bi-journal-richtext floater" style="top: 10%; right: 30%; font-size: 2.2rem; animation-duration: 9s;"></i>
    <i class="bi bi-headphones floater" style="bottom: 15%; left: 40%; font-size: 3.5rem; animation-duration: 7s; animation-delay: 1.5s;"></i>
    
    <!-- Orbiting elements (moving around center) -->
    <div class="orbiter" style="animation-duration: 20s;"><i class="bi bi-star-fill" style="font-size: 1rem;"></i></div>
    <div class="orbiter" style="animation-duration: 25s; animation-delay: -5s;"><i class="bi bi-music-note-list" style="font-size: 1.5rem;"></i></div>
    <div class="orbiter" style="animation-duration: 30s; animation-delay: -10s;"><i class="bi bi-book" style="font-size: 1.2rem;"></i></div>
</div>

<div class="login-card">
    <div class="text-center">
        <div class="icon-header">
            <i class="bi bi-book-half"></i>
            <i class="bi bi-music-note-beamed" style="font-size: 0.5em; position: absolute; top: -5px; right: -15px; transform: rotate(15deg);"></i>
            <i class="bi bi-music-note" style="font-size: 0.4em; position: absolute; top: 15px; right: -22px; transform: rotate(25deg);"></i>
        </div>
    </div>
    <h1 class="auth-title">BookVibes</h1>
    <p class="auth-subtitle">Donde las historias encuentran su ritmo.</p>

    <?php if(isset($error)): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert" style="background: rgba(220, 53, 69, 0.2); border: 1px solid rgba(220, 53, 69, 0.3); color: #ff8b94;">
            <i class="bi bi-exclamation-circle-fill me-2"></i>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    <?php endif; ?>

    <form method="post" action="/login">
        <div class="mb-3">
            <label for="email" class="form-label">Correo Electrónico</label>
            <div class="input-group">
                <span class="input-group-text" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: rgba(255,255,255,0.5);"><i class="bi bi-envelope"></i></span>
                <input type="email" id="email" name="email" class="form-control" placeholder="nombre@ejemplo.com" required>
            </div>
        </div>
        
        <div class="mb-4">
            <label for="password" class="form-label">Contraseña</label>
            <div class="input-group">
                <span class="input-group-text" style="background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.1); color: rgba(255,255,255,0.5);"><i class="bi bi-lock"></i></span>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="remember" style="background-color: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.3);">
                <label class="form-check-label" for="remember">Recuérdame</label>
            </div>
            <a href="#" class="text-decoration-none" style="font-size: 0.9rem;">¿Olvidaste tu contraseña?</a>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-4">
            Iniciar Sesión
        </button>

        <p class="text-center mb-0" style="color: rgba(255,255,255,0.6);">
            ¿No tienes una cuenta? <a href="/register" class="text-decoration-none fw-bold">Regístrate</a>
        </p>
    </form>
</div>

<script>
    // Generate Stars
    const starsContainer = document.getElementById('stars-container');
    const starCount = 300;

    for (let i = 0; i < starCount; i++) {
        const star = document.createElement('div');
        star.classList.add('star');
        const x = Math.random() * 100;
        const y = Math.random() * 100;
        const size = Math.random() * 2 + 1; // 1px to 3px
        const duration = Math.random() * 3 + 2; // 2s to 5s
        
        star.style.left = x + '%';
        star.style.top = y + '%';
        star.style.width = size + 'px';
        star.style.height = size + 'px';
        star.style.setProperty('--duration', duration + 's');
        // Random delay
        star.style.animationDelay = (Math.random() * 5) + 's';
        
        starsContainer.appendChild(star);
    }
</script>

</body>
</html>
