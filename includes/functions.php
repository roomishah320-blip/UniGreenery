<?php
/**
 * Global Helper Functions
 */
require_once __DIR__ . '/db.php';

// ---- SECURITY ----
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrfField() {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . e(generateCSRFToken()) . '">';
}

function sanitize($data) {
    if (is_array($data)) return array_map('sanitize', $data);
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// ---- SESSION & AUTH ----
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        session_start();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $user = db()->fetchOne(
            "SELECT u.*, r.role_name, r.role_slug, r.permissions FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ? AND u.is_active = 1",
            [$_SESSION['user_id']]
        );
    }
    return $user;
}

function hasRole($roleSlug) {
    $user = currentUser();
    if (!$user) return false;
    if ($user['role_slug'] === 'super_admin') return true;
    if (is_array($roleSlug)) return in_array($user['role_slug'], $roleSlug);
    return $user['role_slug'] === $roleSlug;
}

function hasPermission($module, $action = null) {
    $user = currentUser();
    if (!$user) return false;
    if ($user['role_slug'] === 'super_admin') return true;
    $permissions = json_decode($user['permissions'], true);
    if (!$permissions) return false;
    if (isset($permissions['all']) && $permissions['all']) return true;
    if ($action === null) {
        if (!isset($permissions[$module])) return false;
        if ($permissions[$module] === true) return true;
        if (is_array($permissions[$module])) {
            return !empty(array_filter($permissions[$module]));
        }
        return false;
    }
    if (isset($permissions[$module]) && is_array($permissions[$module])) {
        return isset($permissions[$module][$action]) && $permissions[$module][$action];
    }
    return isset($permissions[$module]) && $permissions[$module] === true;
}

function requireAuth() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('login.php');
    }
}

function requireRole($roleSlug) {
    requireAuth();
    if (!hasRole($roleSlug)) {
        setFlash('danger', 'You do not have permission to access this page.');
        redirect('dashboard.php');
    }
}

function requirePermission($module, $action = null) {
    requireAuth();
    if (!hasPermission($module, $action)) {
        setFlash('danger', 'You do not have permission to access this resource.');
        redirect('dashboard.php');
    }
}

function canPerformAction($module, $action) {
    if (!hasPermission($module, $action)) {
        setFlash('danger', 'You do not have permission to perform this action.');
        redirect(str_replace('_', '-', $module) . '.php');
    }
}

// ---- URL & REDIRECT ----
function redirect($page) {
    header('Location: ' . BASE_URL . '/' . ltrim($page, '/'));
    exit;
}

function currentPage() {
    return basename($_SERVER['PHP_SELF'], '.php');
}

function isPage($page) {
    return currentPage() === $page;
}

function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

// ---- FLASH MESSAGES ----
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $icons = ['success'=>'fa-check-circle','danger'=>'fa-exclamation-circle','warning'=>'fa-exclamation-triangle','info'=>'fa-info-circle'];
        $icon = $icons[$flash['type']] ?? 'fa-bell';
        echo '<div class="alert alert-' . e($flash['type']) . ' alert-dismissible fade show shadow-sm" role="alert">
                <i class="fas ' . $icon . ' me-2"></i>' . e($flash['message']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    }
}

// ---- FILE UPLOAD ----
function uploadFile($file, $directory = 'uploads', $allowedTypes = null) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload failed.'];
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'File too large.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($allowedTypes === null) $allowedTypes = array_merge(ALLOWED_IMAGE_TYPES, ALLOWED_DOC_TYPES);
    if (!in_array($ext, $allowedTypes)) {
        return ['success' => false, 'error' => 'File type not allowed.'];
    }
    $uploadDir = ASSETS_PATH . '/' . $directory;
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $filename = uniqid('file_') . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'filepath' => $directory . '/' . $filename, 'url' => ASSETS_URL . '/' . $directory . '/' . $filename, 'size' => $file['size'], 'type' => $ext];
    }
    return ['success' => false, 'error' => 'Failed to move file.'];
}

function deleteUploadedFile($filepath) {
    $fullPath = ASSETS_PATH . '/' . $filepath;
    return file_exists($fullPath) ? unlink($fullPath) : false;
}

// ---- FORMATTING ----
function formatNumber($n) { return number_format($n ?? 0); }
function formatDate($d, $f = 'd M Y') { return $d ? date($f, strtotime($d)) : 'N/A'; }
function formatDateTime($d, $f = 'd M Y, h:i A') { return $d ? date($f, strtotime($d)) : 'N/A'; }

function timeAgo($datetime) {
    $now = new DateTime(); $past = new DateTime($datetime); $d = $now->diff($past);
    if ($d->y > 0) return $d->y . 'y ago'; if ($d->m > 0) return $d->m . 'mo ago';
    if ($d->d > 0) return $d->d . 'd ago'; if ($d->h > 0) return $d->h . 'h ago';
    if ($d->i > 0) return $d->i . 'min ago'; return 'Just now';
}

function generateSlug($s) { return trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9-]/', '-', strtolower(trim($s)))), '-'); }
function truncate($t, $l = 100) { return strlen($t) <= $l ? $t : substr($t, 0, $l) . '...'; }

// ---- ACTIVITY LOG ----
function logActivity($type, $desc, $module = null, $refId = null) {
    if (!isLoggedIn()) return;
    db()->insert("INSERT INTO activities (user_id, activity_type, description, module, reference_id, ip_address) VALUES (?, ?, ?, ?, ?, ?)",
        [$_SESSION['user_id'], $type, $desc, $module, $refId, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
}

// ---- NOTIFICATIONS ----
function createNotification($userId, $title, $msg, $type = 'info', $link = null) {
    db()->insert("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)", [$userId, $title, $msg, $type, $link]);
}

function unreadNotificationCount() {
    if (!isLoggedIn()) return 0;
    return db()->count("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [$_SESSION['user_id']]);
}

function getRecentNotifications($limit = 5) {
    if (!isLoggedIn()) return [];
    return db()->fetchAll("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?", [$_SESSION['user_id'], $limit]);
}

// ---- DASHBOARD STATS ----
function getDashboardStats() {
    return [
        'total_lawns' => db()->count("SELECT COUNT(*) FROM lawns WHERE status = 'active'"),
        'total_trees' => db()->count("SELECT COUNT(*) FROM plants WHERE category = 'tree' AND is_active = 1"),
        'total_plants' => db()->count("SELECT COUNT(*) FROM plants WHERE is_active = 1"),
        'irrigation_zones' => db()->count("SELECT COUNT(*) FROM irrigation_zones WHERE status = 'active'"),
        'staff_members' => db()->count("SELECT COUNT(*) FROM staff WHERE status = 'active'"),
        'active_projects' => db()->count("SELECT COUNT(*) FROM plantation_drives WHERE status IN ('upcoming','ongoing')")
    ];
}

// ---- SETTINGS ----
function getSetting($key, $default = null) {
    $r = db()->fetchOne("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
    return $r ? $r['setting_value'] : $default;
}

function updateSetting($key, $value) {
    return db()->update("UPDATE settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
}

// ---- PAGINATION ----
function paginate($total, $current, $perPage = ITEMS_PER_PAGE, $baseUrl = '') {
    $pages = ceil($total / $perPage);
    if ($pages <= 1) return '';
    $html = '<nav><ul class="pagination justify-content-center">';
    $html .= '<li class="page-item ' . ($current <= 1 ? 'disabled' : '') . '"><a class="page-link" href="' . $baseUrl . '?page=' . ($current - 1) . '"><i class="fas fa-chevron-left"></i></a></li>';
    $start = max(1, $current - 2); $end = min($pages, $current + 2);
    if ($start > 1) { $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=1">1</a></li>'; if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>'; }
    for ($i = $start; $i <= $end; $i++) { $html .= '<li class="page-item ' . ($i === $current ? 'active' : '') . '"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>'; }
    if ($end < $pages) { if ($end < $pages - 1) $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>'; $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $pages . '">' . $pages . '</a></li>'; }
    $html .= '<li class="page-item ' . ($current >= $pages ? 'disabled' : '') . '"><a class="page-link" href="' . $baseUrl . '?page=' . ($current + 1) . '"><i class="fas fa-chevron-right"></i></a></li>';
    $html .= '</ul></nav>';
    return $html;
}
    
// ---- TASK NOTIFICATION TRIGGER ----
/**
 * Notify a staff member about a task assignment (via DB notification & Email if offline).
 */
function notifyStaffOfTask($staffId, $taskType, $taskTitle, $taskDesc, $taskDate, $lawnName) {
    if (!$staffId) return false;

    // Fetch staff member info
    $staff = db()->fetchOne("SELECT * FROM staff WHERE id = ?", [$staffId]);
    if (!$staff) return false;

    $assignedBy = $_SESSION['full_name'] ?? 'System Administrator';
    $emailSent = false;
    $dbNotified = false;

    // Determine target email
    $toEmail = $staff['email'] ?? '';
    $userId = $staff['user_id'] ?? null;

    // Self-healing database mapping: link staff member to user account if empty
    if (empty($userId)) {
        if (!empty($toEmail)) {
            $user = db()->fetchOne("SELECT id FROM users WHERE email = ?", [$toEmail]);
            if ($user) {
                $userId = $user['id'];
                db()->update("UPDATE staff SET user_id = ? WHERE id = ?", [$userId, $staffId]);
            }
        }
        if (empty($userId) && !empty($staff['full_name'])) {
            $user = db()->fetchOne("SELECT id FROM users WHERE full_name = ?", [$staff['full_name']]);
            if ($user) {
                $userId = $user['id'];
                db()->update("UPDATE staff SET user_id = ? WHERE id = ?", [$userId, $staffId]);
            }
        }
    }
    
    // Check if staff has a user account linked now
    if (!empty($userId)) {
        // Fetch user email if staff email is not set
        if (empty($toEmail)) {
            $user = db()->fetchOne("SELECT email FROM users WHERE id = ?", [$userId]);
            $toEmail = $user['email'] ?? '';
        }

        // Add DB Notification (bell)
        $title = "📋 New Task: " . $taskTitle;
        $message = "You have been assigned a new $taskType: " . $taskTitle . " at " . $lawnName . " scheduled for " . $taskDate . ".";
        $link = ($taskType === 'Lawn Duty Assignment') ? 'lawn-details.php?id=' . $staffId : 'maintenance.php';
        createNotification($userId, $title, $message, 'success', $link);
        $dbNotified = true;

        // Check if user is online
        $isOnline = false;
        $timeLimit = time() - 300; // 5 minutes threshold
        $activeSession = db()->fetchOne("SELECT id FROM sessions WHERE user_id = ? AND last_activity > ?", [$userId, $timeLimit]);
        if ($activeSession) {
            $isOnline = true;
        }

        // If user is offline, send email
        if (!$isOnline && !empty($toEmail)) {
            require_once __DIR__ . '/mailer.php';
            sendTaskAssignmentEmail($toEmail, $staff['full_name'], $taskType, $taskTitle, $taskDesc, $taskDate, $assignedBy);
            $emailSent = true;
        }
    } else {
        // No user account, always offline. Send email if email address is available.
        if (!empty($toEmail)) {
            require_once __DIR__ . '/mailer.php';
            sendTaskAssignmentEmail($toEmail, $staff['full_name'], $taskType, $taskTitle, $taskDesc, $taskDate, $assignedBy);
            $emailSent = true;
        }
    }

    return [
        'db' => $dbNotified,
        'email' => $emailSent
    ];
}

// ---- SECURE MIDDLEWARE INTEGRATION ----
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/auth_middleware.php';
require_once __DIR__ . '/role_permission.php';

// Trigger page-level RBAC route protection dynamically on inclusion
protectPageByPermissions();

