<?php
/**
 * Access Denied Page — Premium Environmental UI
 * Step 5: Dynamic Access Control System
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/includes/functions.php';
startSecureSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied | State Level Greenery Management System</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- GSAP for animations -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>

    <style>
        :root {
            --primary-green: #2d6a4f;
            --accent-green: #40916c;
            --danger-red: #e63946;
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.08);
        }

        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Outfit', sans-serif;
            overflow: hidden;
            background: #050511;
        }

        /* Fullscreen Hero Background */
        .hero-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1448375240586-882707db888b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
            z-index: -2;
            transform: scale(1.1);
        }

        /* Dark Transparent Overlay with Gradient */
        .hero-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(5, 5, 17, 0.95) 0%, rgba(20, 10, 10, 0.85) 100%);
            z-index: -1;
        }

        /* Animated Floating Leaves */
        .leaf-container {
            position: fixed;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .leaf {
            position: absolute;
            color: rgba(230, 57, 70, 0.15);
            font-size: 24px;
        }

        /* Glassmorphism Denied Form */
        .wrapper {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            position: relative;
        }

        .card-denied {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 50px 40px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
            text-align: center;
            animation: fadeInScale 0.8s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .alert-shield {
            font-size: 70px;
            color: var(--danger-red);
            filter: drop-shadow(0 0 15px rgba(230, 57, 70, 0.5));
            margin-bottom: 25px;
            display: inline-block;
        }

        .card-denied h2 {
            color: #fff;
            font-weight: 700;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
        }

        .card-denied p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .btn-action {
            background: linear-gradient(135deg, var(--primary-green) 0%, var(--accent-green) 100%);
            border: none;
            border-radius: 12px;
            padding: 14px 28px;
            color: #fff;
            font-weight: 600;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(45, 106, 79, 0.25);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(45, 106, 79, 0.35);
            filter: brightness(1.1);
            color: #fff;
        }

        .footer-brand {
            margin-top: 30px;
            color: rgba(255, 255, 255, 0.3);
            font-size: 12px;
        }
    </style>
</head>
<body>

    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="leaf-container" id="leafContainer"></div>

    <div class="wrapper">
        <div class="card-denied">
            <div class="alert-shield" id="shieldIcon">
                <i class="fas fa-shield-halved"></i>
            </div>
            <h2>Security Access Denied</h2>
            <p>
                Your account group does not hold the permissions required to view this system area. 
                If you believe this to be an error, please contact your System Administrator to request elevated access.
            </p>
            
            <a href="dashboard.php" class="btn-action">
                <i class="fas fa-arrow-left"></i> RETURN TO DASHBOARD
            </a>

            <div class="footer-brand">
                &copy; <?php echo date('Y'); ?> State Level Greenery Management System.
            </div>
        </div>
    </div>

    <script>
        // Animate Shield on entry
        gsap.from("#shieldIcon", {
            scale: 0.5,
            rotation: -45,
            opacity: 0,
            duration: 1.2,
            ease: "elastic.out(1, 0.5)"
        });

        // Floating leaves animation (Reddish brown autumn vibe for security caution)
        function createLeaf() {
            const container = document.getElementById('leafContainer');
            const icons = ['fa-leaf', 'fa-wind', 'fa-exclamation-triangle'];
            const leaf = document.createElement('i');
            const icon = icons[Math.floor(Math.random() * (icons.length - 1))];
            
            leaf.className = `fas ${icon} leaf`;
            container.appendChild(leaf);

            const startX = Math.random() * window.innerWidth;
            const duration = 12 + Math.random() * 10;

            gsap.set(leaf, {
                x: startX,
                y: -50,
                opacity: 0,
                rotate: Math.random() * 360
            });

            gsap.to(leaf, {
                y: window.innerHeight + 50,
                x: startX + (Math.random() - 0.5) * 300,
                rotate: 360,
                opacity: 0.25,
                duration: duration,
                ease: "none",
                onComplete: () => leaf.remove()
            });
        }

        setInterval(createLeaf, 3000);
        for(let i=0; i<6; i++) setTimeout(createLeaf, i * 400);

        // Interactive background parallax
        document.addEventListener('mousemove', (e) => {
            const moveX = (e.clientX - window.innerWidth / 2) / 60;
            const moveY = (e.clientY - window.innerHeight / 2) / 60;
            gsap.to('.hero-bg', {
                x: moveX,
                y: moveY,
                duration: 1.2,
                ease: "power2.out"
            });
        });
    </script>
</body>
</html>
