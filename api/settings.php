<?php
/**
 * Settings API Endpoint
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if (!hasPermission('settings')) {
    echo json_encode(['success' => false, 'error' => 'Permission denied']);
    exit;
}

$csrfToken = $_POST[CSRF_TOKEN_NAME] ?? $_GET[CSRF_TOKEN_NAME] ?? '';
if (!verifyCSRFToken($csrfToken)) {
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$action = sanitize($_GET['action'] ?? '');

if ($action === 'update') {
    $key = sanitize($_POST['key'] ?? '');
    $value = sanitize($_POST['value'] ?? '');
    
    if (empty($key)) {
        echo json_encode(['success' => false, 'error' => 'Setting key is required']);
        exit;
    }
    
    // Check if key exists in settings
    $exists = db()->fetchOne("SELECT id FROM settings WHERE setting_key = ?", [$key]);
    if (!$exists) {
        echo json_encode(['success' => false, 'error' => 'Setting key not found']);
        exit;
    }
    
    $updated = updateSetting($key, $value);
    logActivity('update', "Updated setting: $key to " . truncate($value, 50), 'settings');
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
