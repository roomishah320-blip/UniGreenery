<?php
/**
 * User Account Operations Action Handler
 * Step 7: Create Admin Role Management Panel
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAuth();
requireRole('super_admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$action = sanitize($_POST['action'] ?? '');

switch ($action) {
    case 'add':
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = intval($_POST['role_id'] ?? 0);

        if (empty($fullName) || empty($email) || empty($username) || empty($password) || empty($roleId)) {
            echo json_encode(['success' => false, 'error' => 'Please fill in all required fields.']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email address syntax.']);
            exit;
        }

        // Verify username/email availability
        $existing = db()->fetchOne("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
        if ($existing) {
            echo json_encode(['success' => false, 'error' => 'Username or Email address is already taken.']);
            exit;
        }

        // Create new user account
        $userId = db()->insert(
            "INSERT INTO users (username, email, password, full_name, role_id, is_active) VALUES (?, ?, ?, ?, ?, 1)",
            [$username, $email, password_hash($password, PASSWORD_DEFAULT), $fullName, $roleId]
        );

        if ($userId) {
            logActivity('create_user', "Created system account for: $username", 'users', $userId);
            echo json_encode(['success' => true, 'message' => 'User account created successfully!', 'reload' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to create user record. Please try again.']);
        }
        break;

    case 'edit':
        $id = intval($_POST['id'] ?? 0);
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $username = sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = intval($_POST['role_id'] ?? 0);

        if (empty($id) || empty($fullName) || empty($email) || empty($username) || empty($roleId)) {
            echo json_encode(['success' => false, 'error' => 'Required parameters are missing.']);
            exit;
        }

        // Verify unique records
        $existing = db()->fetchOne("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?", [$username, $email, $id]);
        if ($existing) {
            echo json_encode(['success' => false, 'error' => 'Username or Email address is already assigned to another account.']);
            exit;
        }

        // Perform updates
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            db()->update(
                "UPDATE users SET username = ?, email = ?, password = ?, full_name = ?, role_id = ? WHERE id = ?",
                [$username, $email, $hashedPassword, $fullName, $roleId, $id]
            );
        } else {
            db()->update(
                "UPDATE users SET username = ?, email = ?, full_name = ?, role_id = ? WHERE id = ?",
                [$username, $email, $fullName, $roleId, $id]
            );
        }

        logActivity('edit_user', "Modified system profile details for: $username", 'users', $id);
        echo json_encode(['success' => true, 'message' => 'User profile updated successfully!', 'reload' => true]);
        break;

    case 'toggle_status':
        $id = intval($_POST['id'] ?? 0);
        $isActive = intval($_POST['is_active'] ?? 0);

        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'Missing target user identity parameter.']);
            exit;
        }

        // Prevent self banning
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'error' => 'Security policy violation: You cannot restrict/ban your own active session account.']);
            exit;
        }

        db()->update("UPDATE users SET is_active = ? WHERE id = ?", [$isActive, $id]);
        
        $stateLabel = $isActive ? 'activated' : 'deactivated/banned';
        logActivity('toggle_status', "User ID {$id} has been {$stateLabel}", 'users', $id);
        echo json_encode(['success' => true, 'message' => "User account successfully {$stateLabel}!"]);
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);

        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'Missing target user identity parameter.']);
            exit;
        }

        // Prevent self deletion
        if ($id == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'error' => 'Security policy violation: You cannot delete your own active session account.']);
            exit;
        }

        db()->delete("DELETE FROM users WHERE id = ?", [$id]);
        
        logActivity('delete_user', "Deleted user account ID: $id", 'users', $id);
        echo json_encode(['success' => true, 'message' => 'User account deleted completely!']);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action requested.']);
        exit;
}
