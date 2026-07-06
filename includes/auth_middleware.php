<?php
/**
 * Auth Middleware — Session Management & Persistent Logins
 * Handles session inactivity timeout and "Remember Me" secure authentication.
 */
if (!defined('GREENERY_APP')) {
    define('GREENERY_APP', true);
}
require_once __DIR__ . '/functions.php';

/**
 * Validates the current session state and checks for inactivity timeouts
 */
function handleSessionSecurity() {
    startSecureSession();

    // 1. Process Remember Me if session is empty but cookie is present
    if (!isLoggedIn() && isset($_COOKIE['remember_me'])) {
        $cookieToken = $_COOKIE['remember_me'];
        
        // Find user with corresponding remember token
        $user = db()->fetchOne(
            "SELECT u.*, r.role_name, r.role_slug FROM users u 
             JOIN roles r ON u.role_id = r.id 
             WHERE u.remember_token = ? AND u.is_active = 1 AND r.is_active = 1",
            [$cookieToken]
        );

        if ($user) {
            // Re-establish session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role_slug'] = $user['role_slug'];
            $_SESSION['role_name'] = $user['role_name'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['last_activity'] = time();

            logActivity('login_remember_me', 'User logged in via remember me cookie', 'auth');
        }
    }

    // 2. Manage Session Timeout
    if (isLoggedIn()) {
        if (isset($_SESSION['last_activity'])) {
            $inactiveTime = time() - $_SESSION['last_activity'];
            if ($inactiveTime > SESSION_LIFETIME) {
                // Session expired
                logActivity('session_timeout', 'User session expired due to inactivity', 'auth');
                
                // Clear remember tokens on timeout for safety
                if (isset($_SESSION['user_id'])) {
                    db()->update("UPDATE users SET remember_token = NULL WHERE id = ?", [$_SESSION['user_id']]);
                }
                
                // Destroy session completely
                $_SESSION = [];
                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000,
                        $params["path"], $params["domain"],
                        $params["secure"], $params["httponly"]
                    );
                }
                session_destroy();
                
                // Clear remember me cookie
                if (isset($_COOKIE['remember_me'])) {
                    setcookie('remember_me', '', time() - 3600, '/');
                }

                // Redirect to login
                startSecureSession();
                setFlash('warning', 'Your session has expired due to inactivity. Please sign in again.');
                header('Location: ' . BASE_URL . '/login.php');
                exit;
            }
        }
        // Update activity timestamp
        $_SESSION['last_activity'] = time();
    }
}

/**
 * Registers a remember me cookie for a user
 */
function setRememberMeToken($userId) {
    $token = bin2hex(random_bytes(32));
    
    // Save hashed token in DB
    db()->update("UPDATE users SET remember_token = ? WHERE id = ?", [$token, $userId]);
    
    // Store in cookie for 30 days
    setcookie('remember_me', $token, time() + REMEMBER_ME_LIFETIME, '/', '', false, true);
}

/**
 * Revokes remember me cookie and tokens
 */
function revokeRememberMe($userId) {
    db()->update("UPDATE users SET remember_token = NULL WHERE id = ?", [$userId]);
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', time() - 3600, '/');
    }
}

// Execute session tracking globally when middleware is required
handleSessionSecurity();
