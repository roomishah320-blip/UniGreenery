<?php
/**
 * Session, CSRF and Security Handler
 * Step 8: Session & Security System
 */
if (!defined('GREENERY_APP')) {
    define('GREENERY_APP', true);
}
require_once __DIR__ . '/functions.php';

/**
 * Validate CSRF token on all POST requests
 */
function validateCSRFOnPost() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Skip CSRF check for public authentication pages — they establish the session
        $currentPage = basename($_SERVER['SCRIPT_FILENAME'], '.php');
        $publicAuthPages = ['login', 'register', 'google-callback'];
        if (in_array($currentPage, $publicAuthPages)) {
            return;
        }

        startSecureSession();
        $token = $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!verifyCSRFToken($token)) {
            logActivity('security_violation', 'CSRF verification failed on POST request', 'auth');
            
            // Check if AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Security Error: CSRF token mismatch or expired.']);
                exit;
            }
            
            die("Security Error: Invalid or missing CSRF token.");
        }
    }
}

/**
 * Records a login attempt in the database log
 */
function recordLoginAttempt($email, $status, $userId = null) {
    try {
        db()->insert(
            "INSERT INTO login_logs (user_id, email, status, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
            [$userId, $email, $status, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', $_SERVER['HTTP_USER_AGENT'] ?? '']
        );
    } catch (Exception $e) {
        // Fallback silently if table not synced yet
        error_log("Login log error: " . $e->getMessage());
    }
}

/**
 * Checks if an email/identity is currently locked out due to excessive failed attempts.
 * Returns remaining lock time in seconds, or 0 if not locked out.
 */
function checkLoginLockout($identity) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // Find if the IP or email has consecutive failed attempts recently without a success
        $lastSuccess = db()->fetchOne(
            "SELECT created_at FROM login_logs 
             WHERE (email = ? OR ip_address = ?) AND status = 'success' 
             ORDER BY created_at DESC LIMIT 1",
            [$identity, $ip]
        );
        
        $successTime = $lastSuccess ? $lastSuccess['created_at'] : '1970-01-01 00:00:00';
        
        // Count failures since the last success, within the lockout window
        $lockoutWindow = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_TIME);
        $sinceTime = max($successTime, $lockoutWindow);
        
        $failures = db()->count(
            "SELECT COUNT(*) FROM login_logs 
             WHERE (email = ? OR ip_address = ?) AND status = 'failed' AND created_at > ?",
            [$identity, $ip, $sinceTime]
        );
        
        if ($failures >= MAX_LOGIN_ATTEMPTS) {
            // Get the time of the last failure to determine remaining lock time
            $lastFailure = db()->fetchOne(
                "SELECT created_at FROM login_logs 
                 WHERE (email = ? OR ip_address = ?) AND status = 'failed' 
                 ORDER BY created_at DESC LIMIT 1",
                [$identity, $ip]
            );
            
            if ($lastFailure) {
                $lastFailureTime = strtotime($lastFailure['created_at']);
                $elapsed = time() - $lastFailureTime;
                $remaining = LOGIN_LOCKOUT_TIME - $elapsed;
                
                if ($remaining > 0) {
                    return $remaining;
                }
            }
        }
    } catch (Exception $e) {
        // Silently skip if table not found or connected
        error_log("Lockout check error: " . $e->getMessage());
    }
    
    return 0;
}

// Automatically enforce CSRF protection on POST operations
validateCSRFOnPost();
