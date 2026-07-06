<?php
/**
 * Project Actions Handler
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('projects.php'); }
if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    setFlash('danger', 'Invalid security token.');
    redirect('projects.php');
}

$action = sanitize($_POST['action'] ?? '');
$userId = $_SESSION['user_id'];

// Granular permission enforcement
$actionMap = ['add' => 'add', 'edit' => 'edit', 'delete' => 'delete'];
if (isset($actionMap[$action])) {
    canPerformAction('projects', $actionMap[$action]);
}

switch ($action) {
    case 'add':
        $title = sanitize($_POST['title'] ?? '');
        $lawnId = intval($_POST['lawn_id'] ?? 0);
        $date = sanitize($_POST['drive_date'] ?? '');
        $target = intval($_POST['target_plants'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'upcoming');
        $desc = sanitize($_POST['description'] ?? '');

        $projId = db()->insert(
            "INSERT INTO plantation_drives (title, lawn_id, drive_date, target_plants, status, description, organized_by) VALUES (?, ?, ?, ?, ?, ?, ?)",
            [$title, $lawnId, $date, $target, $status, $desc, $userId]
        );

        logActivity('create', "Initiated new project: $title", 'projects', $projId);
        setFlash('success', 'New project has been initiated and assigned.');
        redirect('projects.php');
        break;

    case 'edit':
        $id = intval($_POST['id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $lawnId = intval($_POST['lawn_id'] ?? 0);
        $date = sanitize($_POST['drive_date'] ?? '');
        $target = intval($_POST['target_plants'] ?? 0);
        $planted = intval($_POST['planted_count'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'upcoming');
        $desc = sanitize($_POST['description'] ?? '');

        db()->update(
            "UPDATE plantation_drives SET title = ?, lawn_id = ?, drive_date = ?, target_plants = ?, planted_count = ?, status = ?, description = ? WHERE id = ?",
            [$title, $lawnId, $date, $target, $planted, $status, $desc, $id]
        );

        logActivity('update', "Updated project: $title", 'projects', $id);
        setFlash('success', 'Project details updated successfully.');
        redirect('projects.php');
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        db()->delete("DELETE FROM plantation_drives WHERE id = ?", [$id]);
        logActivity('delete', "Terminated project ID: $id", 'projects', $id);
        setFlash('success', 'Project has been terminated.');
        redirect('projects.php');
        break;

    default:
        redirect('projects.php');
}
