<?php
/**
 * Staff CRUD Actions Handler
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('staff.php'); }
if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    setFlash('danger', 'Invalid security token.');
    redirect('staff.php');
}

$action = sanitize($_POST['action'] ?? '');

// Granular permission enforcement
$actionMap = ['add' => 'add', 'edit' => 'edit', 'delete' => 'delete'];
if (isset($actionMap[$action])) {
    canPerformAction('staff', $actionMap[$action]);
}

switch ($action) {
    case 'add':
        $fullName = sanitize($_POST['full_name'] ?? '');
        $employeeId = sanitize($_POST['employee_id'] ?? '') ?: null;
        $designation = sanitize($_POST['designation'] ?? '');
        $department = sanitize($_POST['department'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $joinDate = sanitize($_POST['join_date'] ?? '') ?: null;
        $address = sanitize($_POST['address'] ?? '');

        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $upload = uploadFile($_FILES['image'], 'uploads/staff', ALLOWED_IMAGE_TYPES);
            if ($upload['success']) $imagePath = $upload['filepath'];
        }

        $staffId = db()->insert(
            "INSERT INTO staff (full_name, employee_id, designation, department, phone, email, join_date, address, image) VALUES (?,?,?,?,?,?,?,?,?)",
            [$fullName, $employeeId, $designation, $department, $phone, $email, $joinDate, $address, $imagePath]
        );

        logActivity('create', "Added staff: $fullName", 'staff', $staffId);
        setFlash('success', 'Staff member added successfully!');
        redirect('staff.php');
        break;

    case 'edit':
        $id = intval($_POST['id'] ?? 0);
        $fullName = sanitize($_POST['full_name'] ?? '');
        $employeeId = sanitize($_POST['employee_id'] ?? '') ?: null;
        $designation = sanitize($_POST['designation'] ?? '');
        $department = sanitize($_POST['department'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $joinDate = sanitize($_POST['join_date'] ?? '') ?: null;
        $address = sanitize($_POST['address'] ?? '');
        $status = sanitize($_POST['status'] ?? 'active');
        $performanceScore = intval($_POST['performance_score'] ?? 0);

        // Fetch current to handle image replacement
        $staff = db()->fetchOne("SELECT image FROM staff WHERE id = ?", [$id]);
        $imagePath = $staff['image'] ?? null;

        if (!empty($_FILES['image']['name'])) {
            $upload = uploadFile($_FILES['image'], 'uploads/staff', ALLOWED_IMAGE_TYPES);
            if ($upload['success']) {
                if ($imagePath) deleteUploadedFile($imagePath);
                $imagePath = $upload['filepath'];
            }
        }

        db()->update(
            "UPDATE staff SET full_name = ?, employee_id = ?, designation = ?, department = ?, phone = ?, email = ?, join_date = ?, address = ?, status = ?, performance_score = ?, image = ? WHERE id = ?",
            [$fullName, $employeeId, $designation, $department, $phone, $email, $joinDate, $address, $status, $performanceScore, $imagePath, $id]
        );

        logActivity('update', "Updated staff: $fullName", 'staff', $id);
        setFlash('success', 'Staff information updated.');
        redirect('staff.php');
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        $staff = db()->fetchOne("SELECT * FROM staff WHERE id = ?", [$id]);
        if ($staff) {
            if ($staff['image']) deleteUploadedFile($staff['image']);
            db()->delete("DELETE FROM staff WHERE id = ?", [$id]);
            logActivity('delete', "Deleted staff: " . $staff['full_name'], 'staff', $id);
            setFlash('success', 'Staff member removed.');
        }
        redirect('staff.php');
        break;

    default:
        redirect('staff.php');
}
