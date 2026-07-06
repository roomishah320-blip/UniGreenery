<?php
/**
 * Maintenance Actions Handler (AJAX)
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();

header('Content-Type: application/json');

// Check if POST data exists
if (empty($_POST)) {
    echo json_encode(['success' => false, 'error' => 'No data received by the server.']);
    exit;
}

// Check Authentication
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Session expired. Please log in again.']);
    exit;
}

// Check CSRF
$token = $_POST['csrf_token'] ?? $_POST[CSRF_TOKEN_NAME] ?? '';
if (!verifyCSRFToken($token)) {
    echo json_encode(['success' => false, 'error' => 'Security token invalid. Please refresh the page.']);
    exit;
}

$action = sanitize($_POST['action'] ?? '');
$userId = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'add':
            $lawnId = intval($_POST['lawn_id'] ?? 0);
            $type = sanitize($_POST['activity_type'] ?? '');
            $staffId = intval($_POST['staff_id'] ?? 0);
            $date = sanitize($_POST['log_date'] ?? '');
            $status = sanitize($_POST['status'] ?? 'completed');
            $desc = sanitize($_POST['description'] ?? '');

            if (!$lawnId || !$type || !$date) {
                throw new Exception("Required fields (Lawn, Task, Date) are missing.");
            }

            $id = db()->insert(
                "INSERT INTO maintenance_logs (lawn_id, activity_type, staff_id, log_date, status, description) VALUES (?, ?, ?, ?, ?, ?)",
                [$lawnId, $type, $staffId ?: null, $date, $status, $desc]
            );

            logActivity('create', "Recorded maintenance task: $type", 'maintenance', $id);

            // Fetch lawn name for notification details
            $lawn = db()->fetchOne("SELECT name FROM lawns WHERE id = ?", [$lawnId]);
            $lawnName = $lawn['name'] ?? 'University Lawn';

            if ($staffId) {
                notifyStaffOfTask($staffId, 'Maintenance Activity', $type, $desc ?: "Category: $type", $date, $lawnName);
            }

            echo json_encode(['success' => true, 'message' => 'Maintenance activity logged.', 'reload' => true]);
            exit;

        case 'edit':
            $id = intval($_POST['id'] ?? 0);
            $lawnId = intval($_POST['lawn_id'] ?? 0);
            $type = sanitize($_POST['activity_type'] ?? '');
            $staffId = intval($_POST['staff_id'] ?? 0);
            $date = sanitize($_POST['log_date'] ?? '');
            $status = sanitize($_POST['status'] ?? 'completed');
            $desc = sanitize($_POST['description'] ?? '');

            if (!$id || !$lawnId || !$type) {
                throw new Exception("Missing data for update.");
            }

            db()->update(
                "UPDATE maintenance_logs SET lawn_id = ?, activity_type = ?, staff_id = ?, log_date = ?, status = ?, description = ? WHERE id = ?",
                [$lawnId, $type, $staffId ?: null, $date, $status, $desc, $id]
            );

            logActivity('update', "Updated log #$id", 'maintenance', $id);

            // Fetch lawn name for notification details
            $lawn = db()->fetchOne("SELECT name FROM lawns WHERE id = ?", [$lawnId]);
            $lawnName = $lawn['name'] ?? 'University Lawn';

            if ($staffId) {
                notifyStaffOfTask($staffId, 'Maintenance Activity (Updated)', $type, $desc ?: "Category: $type", $date, $lawnName);
            }

            echo json_encode(['success' => true, 'message' => 'Log updated successfully.', 'reload' => true]);
            exit;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            db()->delete("DELETE FROM maintenance_logs WHERE id = ?", [$id]);
            logActivity('delete', "Removed log #$id", 'maintenance', $id);
            echo json_encode(['success' => true, 'message' => 'Log entry removed.']);
            exit;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action requested.']);
            exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
    exit;
}
