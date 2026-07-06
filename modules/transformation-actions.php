<?php
/**
 * Transformation CRUD Actions
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('transformations.php'); }
if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    setFlash('danger', 'Invalid security token.');
    redirect('transformations.php');
}

$action = sanitize($_POST['action'] ?? '');

switch ($action) {
    case 'add':
        $title = sanitize($_POST['title'] ?? '');
        $lawnId = intval($_POST['lawn_id'] ?? 0) ?: null;
        $description = sanitize($_POST['description'] ?? '');
        $transformationDate = sanitize($_POST['transformation_date'] ?? '') ?: date('Y-m-d');
        $futureRequirements = sanitize($_POST['future_requirements'] ?? '');

        $beforeImage = null;
        if (!empty($_FILES['before_image']['name'])) {
            $upload = uploadFile($_FILES['before_image'], 'uploads/transformations', ALLOWED_IMAGE_TYPES);
            if ($upload['success']) $beforeImage = $upload['filepath'];
        }

        $afterImage = null;
        if (!empty($_FILES['after_image']['name'])) {
            $upload = uploadFile($_FILES['after_image'], 'uploads/transformations', ALLOWED_IMAGE_TYPES);
            if ($upload['success']) $afterImage = $upload['filepath'];
        }

        $roadmapImage = null;
        if (!empty($_FILES['roadmap_image']['name'])) {
            $upload = uploadFile($_FILES['roadmap_image'], 'uploads/transformations', ALLOWED_IMAGE_TYPES);
            if ($upload['success']) $roadmapImage = $upload['filepath'];
        }

        // Auto-fix column if missing
        try { db()->query("ALTER TABLE transformations ADD COLUMN roadmap_image VARCHAR(500) DEFAULT NULL AFTER future_requirements"); } catch(Exception $e) {}

        $transId = db()->insert(
            "INSERT INTO transformations (title, lawn_id, description, before_image, after_image, transformation_date, future_requirements, roadmap_image, created_by) VALUES (?,?,?,?,?,?,?,?,?)",
            [$title, $lawnId, $description, $beforeImage, $afterImage, $transformationDate, $futureRequirements, $roadmapImage, $_SESSION['user_id']]
        );

        logActivity('create', "Added transformation: $title", 'transformations', $transId);
        setFlash('success', 'Transformation added successfully!');
        redirect('transformations.php');
        break;

    case 'edit':
        $id = intval($_POST['id'] ?? 0);
        $title = sanitize($_POST['title'] ?? '');
        $lawnId = intval($_POST['lawn_id'] ?? 0) ?: null;
        $description = sanitize($_POST['description'] ?? '');
        $futureRequirements = sanitize($_POST['future_requirements'] ?? '');

        $t = db()->fetchOne("SELECT * FROM transformations WHERE id = ?", [$id]);
        if (!$t) {
            setFlash('danger', 'Transformation record not found.');
            redirect('transformations.php');
        }

        $beforeImage = $t['before_image'];
        if (!empty($_FILES['before_image']['name'])) {
            $upload = uploadFile($_FILES['before_image'], 'uploads/transformations', ALLOWED_IMAGE_TYPES);
            if ($upload['success']) {
                if ($t['before_image']) deleteUploadedFile($t['before_image']);
                $beforeImage = $upload['filepath'];
            }
        }

        $afterImage = $t['after_image'];
        if (!empty($_FILES['after_image']['name'])) {
            $upload = uploadFile($_FILES['after_image'], 'uploads/transformations', ALLOWED_IMAGE_TYPES);
            if ($upload['success']) {
                if ($t['after_image']) deleteUploadedFile($t['after_image']);
                $afterImage = $upload['filepath'];
            }
        }

        $roadmapImage = $t['roadmap_image'];
        if (!empty($_FILES['roadmap_image']['name'])) {
            $upload = uploadFile($_FILES['roadmap_image'], 'uploads/transformations', ALLOWED_IMAGE_TYPES);
            if ($upload['success']) {
                if ($t['roadmap_image']) deleteUploadedFile($t['roadmap_image']);
                $roadmapImage = $upload['filepath'];
            }
        }

        db()->update(
            "UPDATE transformations SET title = ?, lawn_id = ?, description = ?, before_image = ?, after_image = ?, future_requirements = ?, roadmap_image = ? WHERE id = ?",
            [$title, $lawnId, $description, $beforeImage, $afterImage, $futureRequirements, $roadmapImage, $id]
        );

        logActivity('update', "Updated transformation: $title", 'transformations', $id);
        setFlash('success', 'Transformation updated successfully!');
        redirect('transformations.php');
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        $t = db()->fetchOne("SELECT * FROM transformations WHERE id = ?", [$id]);
        if ($t) {
            if ($t['before_image']) deleteUploadedFile($t['before_image']);
            if ($t['after_image']) deleteUploadedFile($t['after_image']);
            db()->delete("DELETE FROM transformations WHERE id = ?", [$id]);
            logActivity('delete', "Deleted transformation: " . $t['title'], 'transformations', $id);
            setFlash('success', 'Transformation deleted.');
        }
        redirect('transformations.php');
        break;

    default:
        redirect('transformations.php');
}
