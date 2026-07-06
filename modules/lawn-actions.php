<?php
/**
 * Lawn CRUD Actions Handler
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('lawns.php'); }
if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    setFlash('danger', 'Invalid security token. Please try again.');
    redirect('categories.php');
}

$action = sanitize($_POST['action'] ?? '');

// Granular permission enforcement
$actionMap = ['add' => 'add', 'edit' => 'edit', 'delete' => 'delete', 'assign_staff' => 'edit'];
if (isset($actionMap[$action])) {
    canPerformAction('lawns', $actionMap[$action]);
}

switch ($action) {
    case 'add':
        $name = sanitize($_POST['name'] ?? '');
        $slug = generateSlug($name);
        $categoryId = intval($_POST['category_id'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $areaSqm = floatval($_POST['area_sqm'] ?? 0);
        $location = sanitize($_POST['location'] ?? '');
        $irrigationType = sanitize($_POST['irrigation_type'] ?? '');
        $soilCondition = sanitize($_POST['soil_condition'] ?? '');

        // Handle image upload
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $upload = uploadFile($_FILES['image'], 'uploads/lawns', ALLOWED_IMAGE_TYPES);
            if ($upload['success']) {
                $imagePath = $upload['filepath'];
            } else {
                setFlash('danger', $upload['error']);
                redirect('categories.php');
            }
        }

        // Check duplicate slug
        $existing = db()->fetchOne("SELECT id FROM lawns WHERE slug = ?", [$slug]);
        if ($existing) { $slug .= '-' . time(); }

        db()->insert(
            "INSERT INTO lawns (name, slug, category_id, description, area_sqm, location, irrigation_type, soil_condition, image, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [$name, $slug, $categoryId ?: null, $description, $areaSqm, $location, $irrigationType, $soilCondition, $imagePath, $_SESSION['user_id']]
        );

        logActivity('create', "Added new lawn: $name", 'lawns');
        setFlash('success', 'Lawn added successfully!');
        redirect('categories.php');
        break;

    case 'edit':
        $id = intval($_POST['id'] ?? 0);
        $name = sanitize($_POST['name'] ?? '');
        $categoryId = intval($_POST['category_id'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $areaSqm = floatval($_POST['area_sqm'] ?? 0);
        $location = sanitize($_POST['location'] ?? '');
        $irrigationType = sanitize($_POST['irrigation_type'] ?? '');
        $soilCondition = sanitize($_POST['soil_condition'] ?? '');

        $updateFields = "name=?, category_id=?, description=?, area_sqm=?, location=?, irrigation_type=?, soil_condition=?";
        $params = [$name, $categoryId ?: null, $description, $areaSqm, $location, $irrigationType, $soilCondition];

        if (!empty($_FILES['image']['name'])) {
            $upload = uploadFile($_FILES['image'], 'uploads/lawns', ALLOWED_IMAGE_TYPES);
            if ($upload['success']) {
                // Delete old image
                $old = db()->fetchOne("SELECT image FROM lawns WHERE id = ?", [$id]);
                if ($old && $old['image']) deleteUploadedFile($old['image']);
                $updateFields .= ", image=?";
                $params[] = $upload['filepath'];
            }
        }

        $params[] = $id;
        db()->update("UPDATE lawns SET $updateFields WHERE id = ?", $params);
        logActivity('update', "Updated lawn: $name", 'lawns', $id);
        setFlash('success', 'Lawn updated successfully!');
        redirect('lawn-details.php?id=' . $id);
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        $lawn = db()->fetchOne("SELECT * FROM lawns WHERE id = ?", [$id]);
        if ($lawn) {
            if ($lawn['image']) deleteUploadedFile($lawn['image']);
            // Cascade handled by DB foreign keys, but let's also update counts
            db()->delete("DELETE FROM lawns WHERE id = ?", [$id]);
            logActivity('delete', "Deleted lawn: " . $lawn['name'], 'lawns', $id);
            setFlash('success', 'Lawn deleted successfully.');
        }
        redirect('lawns.php');
        break;

    case 'assign_staff':
        $lawnId = intval($_POST['lawn_id'] ?? 0);
        $staffId = intval($_POST['staff_id'] ?? 0);
        $role = sanitize($_POST['role'] ?? 'Gardener');

        if (!$lawnId || !$staffId) {
            setFlash('danger', 'Invalid assignment data.');
            redirect('lawn-details.php?id=' . $lawnId);
        }

        try {
            db()->insert(
                "INSERT INTO lawn_staff (lawn_id, staff_id, role, assigned_date, status) VALUES (?, ?, ?, CURDATE(), 'active') ON DUPLICATE KEY UPDATE role = ?, status = 'active'",
                [$lawnId, $staffId, $role, $role]
            );
            logActivity('assign', "Assigned staff member to lawn", 'staff', $staffId);
            
            // Fetch lawn name for notification details
            $lawn = db()->fetchOne("SELECT name FROM lawns WHERE id = ?", [$lawnId]);
            $lawnName = $lawn['name'] ?? 'University Lawn';
            notifyStaffOfTask($staffId, 'Lawn Duty Assignment', "Lawn Duty ($role)", "You have been assigned to maintain this lawn as a $role.", date('Y-m-d'), $lawnName);

            setFlash('success', 'Staff member assigned successfully.');
        } catch (Exception $e) {
            setFlash('danger', 'Database Error: ' . $e->getMessage());
        }
        redirect('lawn-details.php?id=' . $lawnId);
        break;

    default:
        redirect('lawns.php');
}
