<?php
/**
 * Registration Page — Premium Animated Signup
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/includes/functions.php';
startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) { redirect('dashboard.php'); }

$error = '';
$roles = db()->fetchAll("SELECT * FROM roles WHERE is_active = 1 AND role_slug != 'super_admin' ORDER BY id ASC");

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = sanitize($_POST['full_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $username = sanitize($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $roleSlug = sanitize($_POST['register_role'] ?? 'public_user');
    
    if (empty($fullName) || empty($email) || empty($username) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check availability
        $existing = db()->fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
        if ($existing) {
            $error = 'Username or email is already taken.';
        } else {
            // Get role ID
            $role = db()->fetchOne("SELECT id, role_slug, role_name FROM roles WHERE role_slug = ?", [$roleSlug]);
            $roleId = $role ? $role['id'] : 12; // Fallback to User if role not found
            $roleSlugActual = $role ? $role['role_slug'] : 'public_user';

            // Sensitive/Administrative roles requiring Super Admin verification
            $sensitiveRoles = ['super_admin', 'vice_chancellor', 'registrar', 'dean', 'state_officer', 'security_officer', 'treasurer_officer', 'irrigation_officer', 'plantation_officer', 'staff_mgmt_officer', 'dept_user'];
            
            // Set account to inactive (pending) if registering as a sensitive administrative/officer role
            $isActive = in_array($roleSlugActual, $sensitiveRoles) ? 0 : 1;

            $userId = db()->insert(
                "INSERT INTO users (username, email, password, full_name, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?)",
                [$username, $email, password_hash($password, PASSWORD_DEFAULT), $fullName, $roleId, $isActive]
            );

            if ($userId) {
                logActivity('register', "New user registered: $username with role $roleSlugActual (Active: $isActive)", 'auth', $userId);
                
                if ($isActive === 0) {
                    // Fetch all Super Admins
                    $superAdmins = db()->fetchAll(
                        "SELECT u.id FROM users u 
                         JOIN roles r ON u.role_id = r.id 
                         WHERE r.role_slug = 'super_admin'"
                    );
                    
                    $roleName = $role ? $role['role_name'] : 'Unknown Role';
                    foreach ($superAdmins as $admin) {
                        createNotification(
                            $admin['id'],
                            '🆕 Pending Account Approval',
                            "New registration: {$fullName} (@{$username}) requested the '{$roleName}' role.",
                            'warning',
                            'users.php'
                        );
                    }
                    
                    setFlash('warning', 'Registration request submitted. Since you registered with an administrative/officer role, your account is pending verification and activation by the Super Admin.');
                } else {
                    setFlash('success', 'Registration successful! You can now sign in.');
                }
                redirect('login.php');
            } else {
                $error = 'Something went wrong. Please try again.';
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
    <title>Sign Up | State Level Greenery Management System</title>
    
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


        .border-white-10 {
            border-color: rgba(255, 255, 255, 0.1) !important;
        }

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

        body, html {
            height: 100%;
            margin: 0;
            font-family: 'Outfit', sans-serif;
            background: #050511;
            overflow-x: hidden;
        }

        .hero-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1518531933037-91b2f5f229cc?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
            z-index: -2;
            transform: scale(1.1);
        }

        .hero-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(5, 5, 17, 0.9) 0%, rgba(30, 27, 75, 0.7) 100%);
            z-index: -1;
        }

        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
            z-index: 10;
            position: relative;
        }

        .login-card {
            background: var(--glass-bg);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
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
            font-size: 40px;
            color: var(--accent-purple);
            filter: drop-shadow(0 0 10px rgba(168, 85, 247, 0.5));
            margin-bottom: 10px;
        }

        .brand-logo h2 {
            color: #fff;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .brand-logo p {
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
        }

        .form-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .input-group-custom {
            position: relative;
            margin-bottom: 18px;
        }

        .input-group-custom i:not(.toggle-password) {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.4);
            transition: 0.3s;
        }

        .input-group-custom input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 10px 15px 10px 45px;
            color: #fff;
            transition: 0.3s;
            font-size: 14px;
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

        .toggle-password:hover { color: var(--accent-purple); }

        .role-selector {
            margin-bottom: 25px;
        }

        .role-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 14px;
            padding: 12px 5px;
            text-align: center;
            cursor: pointer;
            transition: 0.3s;
            color: rgba(255, 255, 255, 0.5);
        }

        .role-card i {
            display: block;
            font-size: 18px;
            margin-bottom: 5px;
        }

        .role-card span {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .role-card:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }

        .role-card.active {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.2) 0%, rgba(168, 85, 247, 0.2) 100%);
            border-color: var(--accent-purple);
            color: #fff;
            box-shadow: 0 0 15px rgba(168, 85, 247, 0.2);
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary-purple) 0%, var(--accent-purple) 100%);
            border: none;
            border-radius: 12px;
            padding: 12px;
            color: #fff;
            font-weight: 600;
            width: 100%;
            margin-top: 10px;
            transition: 0.3s;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.3);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
            box-shadow: 0 15px 30px rgba(79, 70, 229, 0.4);
        }

        .alert-custom {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #ff8a94;
            border-radius: 10px;
            padding: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            text-align: center;
        }

        .forgot-password {
            color: var(--accent-purple);
            text-decoration: none;
            transition: 0.3s;
        }

        .forgot-password:hover { color: #fff; }

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
                <h5 class="fw-semibold text-white mb-1">Creating Account...</h5>
                <p class="text-white-50 small mb-0">Please wait while we set up your account</p>
            </div>

            <div class="brand-logo">
                <i class="fas fa-leaf"></i>
                <h2>Create Account</h2>
                <p>Join the Smart Greenery Management System</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-custom">
                    <i class="fas fa-exclamation-triangle me-2"></i> <?php echo e($error); ?>
                </div>
            <?php endif; ?>

            <form action="" method="POST" id="registerForm">
                <?php echo csrfField(); ?>
                <div class="form-label">Join As Role</div>
                <div class="input-group-custom">
                    <select name="register_role" id="registerRoleInput" class="form-select bg-dark border-white-10 text-white" style="border-radius: 12px; padding: 12px 15px 12px 45px; background-color: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.12);" required>
                        <?php foreach($roles as $r): ?>
                            <option value="<?php echo $r['role_slug']; ?>" style="background-color: #050511; color: #fff;"><?php echo e($r['role_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-user-tag" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.4); z-index: 5;"></i>
                </div>

                <div class="form-label">Full Name</div>
                <div class="input-group-custom">
                    <input type="text" name="full_name" placeholder="John Doe" required autofocus>
                    <i class="fas fa-id-card"></i>
                </div>

                <div class="form-label">Email Address</div>
                <div class="input-group-custom">
                    <input type="email" name="email" placeholder="john@example.com" required>
                    <i class="fas fa-envelope"></i>
                </div>

                <div class="form-label">Username</div>
                <div class="input-group-custom">
                    <input type="text" name="username" placeholder="johndoe123" required>
                    <i class="fas fa-user"></i>
                </div>

                <div class="form-label">Password</div>
                <div class="input-group-custom">
                    <input type="password" name="password" id="password" placeholder="••••••••" required>
                    <i class="fas fa-lock"></i>
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                </div>

                <button type="submit" class="btn-register w-100 mb-3" id="localRegisterBtn">
                    <i class="fas fa-user-plus me-2"></i> CREATE ACCOUNT
                </button>

                <div class="mt-4 text-center">
                    <p style="color: rgba(255,255,255,0.6); font-size: 14px;">
                        Already have an account? <a href="login.php" class="forgot-password fw-bold">Sign In</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Role Selection
        function selectRole(role, element) {
            document.getElementById('registerRoleInput').value = role;
            document.querySelectorAll('.role-card').forEach(c => c.classList.remove('active'));
            element.classList.add('active');
            gsap.from(element, { scale: 0.95, duration: 0.2 });
        }

        // Password Toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const input = document.getElementById('password');
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Leaf Animation
        function createLeaf() {
            const container = document.getElementById('leafContainer');
            const leaf = document.createElement('i');
            leaf.className = `fas fa-leaf leaf`;
            container.appendChild(leaf);
            const startX = Math.random() * window.innerWidth;
            gsap.set(leaf, { x: startX, y: -50, opacity: 0, rotate: Math.random() * 360 });
            gsap.to(leaf, {
                y: window.innerHeight + 50,
                x: startX + (Math.random() - 0.5) * 400,
                rotate: 720,
                opacity: 0.3,
                duration: 15 + Math.random() * 10,
                ease: "none",
                onComplete: () => leaf.remove()
            });
        }
        setInterval(createLeaf, 3000);

        // Parallax
        document.addEventListener('mousemove', (e) => {
            const moveX = (e.clientX - window.innerWidth / 2) / 60;
            const moveY = (e.clientY - window.innerHeight / 2) / 60;
            gsap.to('.hero-bg', { x: moveX, y: moveY, duration: 1 });
        });

        // Loading Screen Trigger
        document.getElementById('registerForm').addEventListener('submit', function() {
            document.getElementById('authLoader').classList.add('active');
        });
    </script>
</body>
</html>
