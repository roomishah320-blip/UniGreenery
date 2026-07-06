<?php
/**
 * Login Page — Premium Animated Login
 * Step 2: Professional Login System
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/includes/functions.php';
startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) { redirect('dashboard.php'); }

$error = '';
$roles = db()->fetchAll("SELECT * FROM roles WHERE is_active = 1 ORDER BY id ASC");

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username/email and password.';
    } else {
        // Enforce Brute-Force lockout checks
        $lockoutSeconds = checkLoginLockout($username);
        if ($lockoutSeconds > 0) {
            $error = 'Too many failed login attempts. Your account/IP is temporarily locked. Please try again in ' . ceil($lockoutSeconds / 60) . ' minutes.';
        } else {
            // Find user by username or email
            $user = db()->fetchOne(
                "SELECT u.*, r.role_name, r.role_slug FROM users u 
                 JOIN roles r ON u.role_id = r.id 
                 WHERE (u.username = ? OR u.email = ?) AND r.is_active = 1",
                [$username, $username]
            );

            if ($user && password_verify($password, $user['password'])) {
                // Check if user account is active
                if ($user['is_active'] != 1) {
                    $error = 'Your account is pending verification and activation by the Super Admin. You cannot log in until approved.';
                    recordLoginAttempt($username, 'failed_inactive', $user['id']);
                } else {
                    // Validate selected role matches user's actual role
                    $selectedRole = sanitize($_POST['login_role'] ?? '');
                    if (!empty($selectedRole) && $selectedRole !== $user['role_slug']) {
                        $error = 'Your account is not assigned to the selected role. Please select your correct role.';
                        recordLoginAttempt($username, 'failed', $user['id']);
                    } else {
                        // Record successful login
                        recordLoginAttempt($username, 'success', $user['id']);
                        
                        // Set Session Data
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role_slug'] = $user['role_slug'];
                        $_SESSION['role_name'] = $user['role_name'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $_SESSION['last_activity'] = time();

                        // Update last login
                        db()->update("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
                        
                        // Handle Remember Me persistent token registration
                        if (isset($_POST['remember'])) {
                            setRememberMeToken($user['id']);
                        }
                        
                        // Log activity
                        logActivity('login', 'User logged in successfully', 'auth');

                        // Redirect to dashboard or intended page
                        $redirectTo = $_SESSION['redirect_after_login'] ?? 'dashboard.php';
                        unset($_SESSION['redirect_after_login']);
                        redirect($redirectTo);
                    }
                }
            } else {
                $userId = $user ? $user['id'] : null;
                recordLoginAttempt($username, 'failed', $userId);
                
                $error = 'Invalid username or password.';
                logActivity('login_failed', "Failed login attempt for: $username", 'auth');
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
    <title>Login | State Level Greenery Management System</title>
    
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
            background: url('https://images.unsplash.com/photo-1542273917363-3b1817f69a2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
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

        /* Glassmorphism Login Form */
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

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 13px;
        }

        .remember-me {
            color: rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .forgot-password {
            color: var(--accent-purple);
            text-decoration: none;
            transition: 0.3s;
        }

        .forgot-password:hover {
            color: #fff;
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

        .alert-success-custom {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #85ea9a;
            border-radius: 10px;
            padding: 12px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* Role Selector */
        .role-selector {
            margin-bottom: 25px;
        }

        .role-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            padding: 15px 10px;
            text-align: center;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            color: rgba(255, 255, 255, 0.5);
            position: relative;
            overflow: hidden;
        }

        .role-card i {
            display: block;
            font-size: 20px;
            margin-bottom: 8px;
            transition: 0.3s;
        }

        .role-card span {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }

        .role-card:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            transform: translateY(-3px);
        }

        .role-card.active {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.2) 0%, rgba(168, 85, 247, 0.2) 100%);
            border-color: var(--accent-purple);
            color: #fff;
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.2);
        }

        .role-card.active i {
            color: var(--accent-purple);
            transform: scale(1.1);
        }

        .role-card.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--accent-purple);
        }

        .border-white-10 {
            border-color: rgba(255, 255, 255, 0.1) !important;
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

        /* Responsive */
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
                <h5 class="fw-semibold text-white mb-1">Securing Connection...</h5>
                <p class="text-white-50 small mb-0">Please wait while we verify your identity</p>
            </div>

            <div class="brand-logo">
                <i class="fas fa-leaf"></i>
                <h2>Greenery MS</h2>
                <p>Smart State-Level Plantation Management</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-custom">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <?php 
            $flash = getFlash();
            if ($flash): 
                $alertClass = ($flash['type'] === 'success') ? 'alert-success-custom' : 'alert-custom';
                $icon = ($flash['type'] === 'success') ? 'fa-check-circle' : 'fa-exclamation-triangle';
            ?>
                <div class="<?php echo $alertClass; ?>">
                    <i class="fas <?php echo $icon; ?> me-2"></i> <?php echo e($flash['message']); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="loginForm">
                <?php echo csrfField(); ?>
                <div class="form-label">Sign In Role</div>
                <div class="input-group-custom">
                    <select name="login_role" id="selectedRoleInput" class="form-select bg-dark border-white-10 text-white" style="border-radius: 12px; padding: 12px 15px 12px 45px; background-color: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.12);" required>
                        <?php foreach($roles as $r): ?>
                            <option value="<?php echo $r['role_slug']; ?>" style="background-color: #050511; color: #fff;"><?php echo e($r['role_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-user-shield" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.4); z-index: 5;"></i>
                </div>

                <div class="form-label">Username or Email</div>
                <div class="input-group-custom">
                    <input type="text" name="username" placeholder="Enter your identity" required autofocus>
                    <i class="fas fa-user"></i>
                </div>

                <div class="form-label">Password</div>
                <div class="input-group-custom">
                    <input type="password" name="password" id="password" placeholder="••••••••" required>
                    <i class="fas fa-lock"></i>
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-login w-100 mb-3" id="localSubmitBtn">
                    <i class="fas fa-sign-in-alt me-2"></i> SIGN IN TO DASHBOARD
                </button>


                <div class="mt-4 text-center">
                    <p style="color: rgba(255,255,255,0.6); font-size: 14px;">
                        Don't have an account? <a href="register.php" class="forgot-password fw-bold">Sign Up</a>
                    </p>
                </div>
            </form>

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

        // Parallax effect

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

        // Password Toggle Logic
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle Icon
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
            
            // Add a small scale animation on click
            gsap.from(this, {
                scale: 0.8,
                duration: 0.2,
                ease: "back.out(2)"
            });
        });

        // Trigger Loading Animation Overlay during authentications
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.getElementById('authLoader').classList.add('active');
        });
    </script>
</body>
</html>
