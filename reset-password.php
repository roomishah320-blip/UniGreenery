<?php
/**
 * Reset Password Page — Premium Animated Password Reset
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/includes/functions.php';
startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) { redirect('dashboard.php'); }

$error = '';
$success = '';
$token = sanitize($_GET['token'] ?? '');

if (empty($token)) {
    setFlash('danger', 'Invalid or missing password reset token.');
    redirect('login.php');
}

// Find user with valid token that hasn't expired yet
$user = db()->fetchOne(
    "SELECT * FROM users WHERE reset_token = ? AND reset_token_expiry > NOW() AND is_active = 1",
    [$token]
);

if (!$user) {
    $error = 'The password reset link is invalid or has expired. Please request a new link.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Security validation failed. Please try again.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($password) || empty($confirmPassword)) {
            $error = 'Please fill in all password fields.';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $error = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            // Update password & clear token
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updated = db()->update(
                "UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?",
                [$hashedPassword, $user['id']]
            );
            
            if ($updated) {
                // Log activity (manually tracking user ID)
                db()->insert(
                    "INSERT INTO activities (user_id, activity_type, description, module, reference_id, ip_address) VALUES (?, ?, ?, ?, ?, ?)",
                    [$user['id'], 'password_reset_success', 'Password reset successfully', 'auth', $user['id'], $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']
                );
                
                setFlash('success', 'Your password has been reset successfully! You can now log in.');
                redirect('login.php');
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | State Level Greenery Management System</title>
    
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
            --primary-purple: #5a189a;
            --accent-purple: #9d4edd;
            --glass-bg: rgba(255, 255, 255, 0.05);
            --glass-border: rgba(255, 255, 255, 0.10);
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
            background: url('https://images.unsplash.com/photo-1502082553048-f009c37129b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
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
            background: linear-gradient(135deg, rgba(5, 5, 17, 0.9) 0%, rgba(30, 27, 75, 0.7) 100%);
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
            color: rgba(168, 85, 247, 0.2);
            font-size: 24px;
        }

        /* Glassmorphism Reset Form */
        .login-wrapper {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            position: relative;
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 50px 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeInScale 0.8s ease-out;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .brand-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-logo i {
            font-size: 50px;
            color: var(--accent-purple);
            filter: drop-shadow(0 0 10px rgba(168, 85, 247, 0.5));
            margin-bottom: 15px;
        }

        .brand-logo h2 {
            color: #fff;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .brand-logo p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
        }

        /* Form Controls */
        .form-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 25px;
        }

        .input-group-custom i:not(.toggle-password) {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            transition: 0.3s;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: rgba(255, 255, 255, 0.4);
            transition: 0.3s;
            z-index: 10;
        }

        .toggle-password:hover {
            color: var(--accent-purple);
        }

        .input-group-custom input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 12px 15px 12px 45px;
            color: #fff;
            transition: 0.3s;
            font-size: 15px;
        }

        .input-group-custom input:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--accent-purple);
            outline: none;
            box-shadow: 0 0 15px rgba(168, 85, 247, 0.2);
        }

        .input-group-custom input:focus + i {
            color: var(--accent-purple);
        }

        /* Premium Buttons */
        .btn-login {
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--accent-purple) 100%);
            border: none;
            border-radius: 12px;
            padding: 14px;
            color: #fff;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(79, 70, 229, 0.4);
            filter: brightness(1.1);
        }

        .alert-custom {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff8a94;
            border-radius: 10px;
            padding: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }

        .forgot-password {
            color: var(--accent-purple);
            text-decoration: none;
            transition: 0.3s;
        }

        .forgot-password:hover {
            color: #fff;
        }

        /* Loading Animation Overlay */
        .auth-loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(5, 5, 17, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 100;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.4s ease;
        }

        .auth-loading-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        .loader-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(82, 183, 136, 0.1);
            border-top: 4px solid var(--accent-purple);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 576px) {
            .login-card {
                margin: 20px;
                padding: 40px 25px;
            }
        }
    </style>
</head>
<body>

    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="leaf-container" id="leafContainer"></div>

    <div class="login-wrapper">
        <div class="login-card position-relative">
            <!-- Loading Overlay -->
            <div class="auth-loading-overlay" id="authLoader">
                <div class="loader-spinner"></div>
                <h5 class="fw-semibold text-white mb-1">Updating Password...</h5>
                <p class="text-white-50 small mb-0">Securing your new account credentials</p>
            </div>

            <div class="brand-logo">
                <i class="fas fa-user-lock"></i>
                <h2>Reset Password</h2>
                <p>Choose a secure new password for your account</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-custom">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($user): ?>
                <form action="" method="POST" id="resetForm">
                    <?php echo csrfField(); ?>
                    
                    <div class="form-label">New Password</div>
                    <div class="input-group-custom">
                        <input type="password" name="password" id="password" placeholder="••••••••" required autofocus>
                        <i class="fas fa-lock"></i>
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>

                    <div class="form-label">Confirm New Password</div>
                    <div class="input-group-custom">
                        <input type="password" name="confirm_password" id="confirmPassword" placeholder="••••••••" required>
                        <i class="fas fa-lock"></i>
                        <i class="fas fa-eye toggle-password" id="toggleConfirmPassword"></i>
                    </div>

                    <button type="submit" class="btn-login w-100 mb-3" id="resetSubmitBtn">
                        <i class="fas fa-check me-2"></i> UPDATE PASSWORD
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center mt-3">
                    <a href="forgot-password.php" class="btn btn-outline-light px-4 py-2 border-white-10 text-white" style="border-radius: 12px; background: rgba(255,255,255,0.05);">
                        <i class="fas fa-redo me-2"></i> Request New Link
                    </a>
                </div>
            <?php endif; ?>

            <div class="mt-4 text-center">
                <p style="color: rgba(255,255,255,0.6); font-size: 14px;">
                    Back to <a href="login.php" class="forgot-password fw-bold">Sign In</a>
                </p>
            </div>

            <div class="mt-4 text-center">
                <p style="color: rgba(255,255,255,0.4); font-size: 12px;">
                    &copy; <?php echo date('Y'); ?> State Level Greenery Management. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    <script>
        // GSAP Leaf Animation
        function createLeaf() {
            const container = document.getElementById('leafContainer');
            const icons = ['fa-leaf', 'fa-seedling', 'fa-wind'];
            const leaf = document.createElement('i');
            const icon = icons[Math.floor(Math.random() * icons.length)];
            
            leaf.className = `fas ${icon} leaf`;
            container.appendChild(leaf);

            const startX = Math.random() * window.innerWidth;
            const duration = 10 + Math.random() * 15;

            gsap.set(leaf, {
                x: startX,
                y: -50,
                opacity: 0,
                rotate: Math.random() * 360
            });

            gsap.to(leaf, {
                y: window.innerHeight + 50,
                x: startX + (Math.random() - 0.5) * 400,
                rotate: 720,
                opacity: 0.3,
                duration: duration,
                ease: "none",
                onComplete: () => leaf.remove()
            });

            // Subtle opacity fade in/out
            gsap.to(leaf, {
                opacity: 0.5,
                duration: 2,
                repeat: 1,
                yoyo: true
            });
        }

        // Generate leaves periodically
        setInterval(createLeaf, 2000);
        for(let i=0; i<8; i++) setTimeout(createLeaf, i * 500);

        // Smooth background parallax
        document.addEventListener('mousemove', (e) => {
            const moveX = (e.clientX - window.innerWidth / 2) / 50;
            const moveY = (e.clientY - window.innerHeight / 2) / 50;
            gsap.to('.hero-bg', {
                x: moveX,
                y: moveY,
                duration: 1,
                ease: "power2.out"
            });
        });

        // Toggle Password Visibility
        const setupToggle = (toggleId, inputId) => {
            const el = document.getElementById(toggleId);
            if (el) {
                el.addEventListener('click', function() {
                    const input = document.getElementById(inputId);
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }
        };

        setupToggle('togglePassword', 'password');
        setupToggle('toggleConfirmPassword', 'confirmPassword');

        // Trigger Loading Animation Overlay during submission
        const form = document.getElementById('resetForm');
        if (form) {
            form.addEventListener('submit', function() {
                document.getElementById('authLoader').classList.add('active');
            });
        }
    </script>
</body>
</html>
