<?php
/**
 * State Level Greenery Management System
 * Configuration File
 * 
 * Database credentials and global settings
 */

// Prevent direct access
if (!defined('GREENERY_APP')) {
    define('GREENERY_APP', true);
}

// ============================================================
// DATABASE CONFIGURATION
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'greenery_management');
define('DB_CHARSET', 'utf8mb4');

// ============================================================
// APPLICATION CONFIGURATION
// ============================================================
define('SITE_NAME', 'State Level Greenery Management System');
define('SITE_TAGLINE', 'Smart Plantation & Environmental Management');
define('SITE_VERSION', '1.0.0');

// Base URL - dynamically detected for localhost and live hosting
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_path = str_replace('\\', '/', dirname(__DIR__));
$doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$relative_path = '';
if (!empty($doc_root) && stripos($base_path, $doc_root) === 0) {
    $relative_path = substr($base_path, strlen($doc_root));
}
$relative_path = rtrim(str_replace('\\', '/', $relative_path), '/');
define('BASE_URL', $protocol . '://' . $host . $relative_path);
define('BASE_PATH', dirname(__DIR__));

// ============================================================
// DIRECTORY PATHS
// ============================================================
define('ASSETS_URL', BASE_URL . '/assets');
define('CSS_URL', ASSETS_URL . '/css');
define('JS_URL', ASSETS_URL . '/js');
define('IMAGES_URL', ASSETS_URL . '/images');
define('UPLOADS_URL', ASSETS_URL);
define('BACKGROUNDS_URL', ASSETS_URL . '/backgrounds');

define('ASSETS_PATH', BASE_PATH . '/assets');
define('UPLOADS_PATH', ASSETS_PATH . '/uploads');
define('IMAGES_PATH', ASSETS_PATH . '/images');

// ============================================================
// SESSION CONFIGURATION
// ============================================================
define('SESSION_LIFETIME', 3600); // 1 hour
define('REMEMBER_ME_LIFETIME', 2592000); // 30 days
define('CSRF_TOKEN_NAME', 'csrf_token');

// ============================================================
// UPLOAD CONFIGURATION
// ============================================================
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOC_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx']);
define('ALLOWED_MAP_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png']);

// ============================================================
// PAGINATION
// ============================================================
define('ITEMS_PER_PAGE', 12);

// ============================================================
// SECURITY
// ============================================================
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// ============================================================
// GOOGLE OAUTH CONFIGURATION
// ============================================================
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', BASE_URL . '/google-callback.php');

// ============================================================
// EMAIL / MAILER CONFIGURATION
// ============================================================
// Sender address shown in task assignment emails
define('MAIL_FROM',      'noreply@greenery.gov.pk');
define('MAIL_FROM_NAME', SITE_NAME);

// ============================================================
// TIMEZONE
// ============================================================
date_default_timezone_set('Asia/Karachi'); // Pakistan Standard Time (UTC+5)


// ============================================================
// ERROR REPORTING (set to 0 in production)
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
