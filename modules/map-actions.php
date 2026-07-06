<?php
/**
 * Map Upload/Delete Actions
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('map-management.php'); }
if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    setFlash('danger', 'Invalid security token.');
    redirect('map-management.php');
}

$action = sanitize($_POST['action'] ?? '');

// Granular permission enforcement
$actionMap = ['upload' => 'add', 'delete' => 'delete', 'set_primary' => 'edit'];
if (isset($actionMap[$action])) {
    canPerformAction('map_management', $actionMap[$action]);
}

switch ($action) {
    case 'upload':
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $mapType = sanitize($_POST['map_type'] ?? 'campus');
        $isPrimary = isset($_POST['is_primary']) ? 1 : 0;

        if (empty($_FILES['map_file']['name'])) {
            setFlash('danger', 'Please select a file to upload.');
            redirect('map-management.php');
        }

        $upload = uploadFile($_FILES['map_file'], 'uploads/maps', ALLOWED_MAP_TYPES);
        if (!$upload['success']) {
            setFlash('danger', $upload['error']);
            redirect('map-management.php');
        }

        // If setting as primary, unset other primaries
        if ($isPrimary) {
            db()->update("UPDATE maps SET is_primary = 0 WHERE is_primary = 1");
        }

        db()->insert(
            "INSERT INTO maps (title, description, file_path, file_type, file_size, map_type, is_primary, uploaded_by) VALUES (?,?,?,?,?,?,?,?)",
            [$title, $description, $upload['filepath'], $upload['type'], $_FILES['map_file']['size'], $mapType, $isPrimary, $_SESSION['user_id']]
        );

        logActivity('upload', "Uploaded map: $title", 'maps');
        setFlash('success', 'Map uploaded successfully!');
        redirect('map-management.php');
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        $map = db()->fetchOne("SELECT * FROM maps WHERE id = ?", [$id]);
        if ($map) {
            deleteUploadedFile($map['file_path']);
            db()->delete("DELETE FROM maps WHERE id = ?", [$id]);
            logActivity('delete', "Deleted map: " . $map['title'], 'maps', $id);
            setFlash('success', 'Map deleted.');
        }
        redirect('map-management.php');
        break;

    case 'set_primary':
        $id = intval($_POST['id'] ?? 0);
        db()->update("UPDATE maps SET is_primary = 0 WHERE is_primary = 1");
        db()->update("UPDATE maps SET is_primary = 1 WHERE id = ?", [$id]);
        logActivity('update', "Set map ID $id as primary", 'maps', $id);
        setFlash('success', 'Map marked as primary campus plan.');
        redirect('map-management.php');
        break;

    default:
        redirect('map-management.php');
}
