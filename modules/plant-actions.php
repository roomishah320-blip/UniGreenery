<?php
/**
 * Plant CRUD Actions Handler
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('plants.php'); }
if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    setFlash('danger', 'Invalid security token.');
    redirect('plants.php');
}

$action = sanitize($_POST['action'] ?? '');

// Granular permission enforcement
$actionMap = ['add' => 'add', 'edit' => 'edit', 'delete' => 'delete'];
if (isset($actionMap[$action])) {
    canPerformAction('plants', $actionMap[$action]);
}

switch ($action) {
    case 'add':
        $name = sanitize($_POST['name'] ?? '');
        $scientificName = sanitize($_POST['scientific_name'] ?? '');
        $category = sanitize($_POST['category'] ?? 'plant');
        $lawnId = intval($_POST['lawn_id'] ?? 0) ?: null;
        $healthStatus = sanitize($_POST['health_status'] ?? 'good');
        $ageYears = floatval($_POST['age_years'] ?? 0);
        $heightCm = floatval($_POST['height_cm'] ?? 0);
        $plantedDate = sanitize($_POST['planted_date'] ?? '') ?: null;
        $notes = sanitize($_POST['notes'] ?? '');

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $upload = uploadFile($_FILES['image'], 'uploads/plants', ALLOWED_IMAGE_TYPES);
            if ($upload['success']) $imagePath = $upload['filepath'];
        }

        $plantId = db()->insert(
            "INSERT INTO plants (name, scientific_name, category, lawn_id, health_status, age_years, height_cm, planted_date, notes, image, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
            [$name, $scientificName, $category, $lawnId, $healthStatus, $ageYears, $heightCm, $plantedDate, $notes, $imagePath, $_SESSION['user_id']]
        );

        // Update lawn counts
        if ($lawnId) {
            $countField = $category === 'tree' ? 'total_trees' : ($category === 'flower' ? 'total_flowers' : 'total_plants');
            db()->update("UPDATE lawns SET $countField = $countField + 1 WHERE id = ?", [$lawnId]);
        }

        logActivity('create', "Added plant: $name", 'plants', $plantId);
        setFlash('success', 'Plant added successfully!');
        redirect('plants.php');
        break;

    case 'edit':
        $id = intval($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $scientificName = sanitize($_POST['scientific_name'] ?? '');
        $category = sanitize($_POST['category'] ?? 'plant');
        $lawnId = intval($_POST['lawn_id'] ?? 0) ?: null;
        $healthStatus = sanitize($_POST['health_status'] ?? 'good');
        $ageYears = floatval($_POST['age_years'] ?? 0);
        $heightCm = floatval($_POST['height_cm'] ?? 0);
        $plantedDate = sanitize($_POST['planted_date'] ?? '') ?: null;

        $plant = db()->fetchOne("SELECT * FROM plants WHERE id = ?", [$id]);
        if (!$plant) {
            setFlash('danger', 'Plant not found.');
            redirect('plants.php');
        }

        $imagePath = $plant['image'];
        if (!empty($_FILES['image']['name'])) {
            $upload = uploadFile($_FILES['image'], 'uploads/plants', ALLOWED_IMAGE_TYPES);
            if ($upload['success']) {
                if ($imagePath) deleteUploadedFile($imagePath);
                $imagePath = $upload['filepath'];
            }
        }

        db()->update(
            "UPDATE plants SET name=?, scientific_name=?, category=?, lawn_id=?, health_status=?, age_years=?, height_cm=?, planted_date=?, image=? WHERE id=?",
            [$name, $scientificName, $category, $lawnId, $healthStatus, $ageYears, $heightCm, $plantedDate, $imagePath, $id]
        );

        logActivity('update', "Updated plant: $name", 'plants', $id);
        setFlash('success', 'Plant details updated successfully!');
        redirect('plants.php');
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        $plant = db()->fetchOne("SELECT * FROM plants WHERE id = ?", [$id]);
        if ($plant) {
            if ($plant['image']) deleteUploadedFile($plant['image']);
            if ($plant['lawn_id']) {
                $countField = $plant['category'] === 'tree' ? 'total_trees' : ($plant['category'] === 'flower' ? 'total_flowers' : 'total_plants');
                db()->update("UPDATE lawns SET $countField = GREATEST($countField - 1, 0) WHERE id = ?", [$plant['lawn_id']]);
            }
            db()->delete("DELETE FROM plants WHERE id = ?", [$id]);
            logActivity('delete', "Deleted plant: " . $plant['name'], 'plants', $id);
            setFlash('success', 'Plant deleted.');
        }
        redirect('plants.php');
        break;

    default:
        redirect('plants.php');
}
