<?php
/**
 * Role Actions Handler
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAuth();
requireRole('super_admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('roles.php'); }
if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    setFlash('danger', 'Invalid security token.');
    redirect('roles.php');
}

$action = sanitize($_POST['action'] ?? '');

switch ($action) {
    case 'add':
        $name = sanitize($_POST['role_name'] ?? '');
        $slug = sanitize($_POST['role_slug'] ?? '');
        $desc = sanitize($_POST['description'] ?? '');

        // Default permissions (empty JSON)
        $perms = json_encode([]);

        $roleId = db()->insert(
            "INSERT INTO roles (role_name, role_slug, description, permissions) VALUES (?,?,?,?)",
            [$name, $slug, $desc, $perms]
        );

        logActivity('create', "Created new role: $name", 'roles', $roleId);
        setFlash('success', 'Role created successfully!');
        redirect('roles.php');
        break;

    case 'edit':
        $id = intval($_POST['id'] ?? 0);
        $name = sanitize($_POST['role_name'] ?? '');
        $slug = sanitize($_POST['role_slug'] ?? '');
        $desc = sanitize($_POST['description'] ?? '');
        $active = isset($_POST['is_active']) ? 1 : 0;

        db()->update(
            "UPDATE roles SET role_name = ?, role_slug = ?, description = ?, is_active = ? WHERE id = ?",
            [$name, $slug, $desc, $active, $id]
        );

        logActivity('update', "Updated role: $name", 'roles', $id);
        setFlash('success', 'Role updated!');
        redirect('roles.php');
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        
        // Prevent deleting Super Admin
        $role = db()->fetchOne("SELECT role_slug FROM roles WHERE id = ?", [$id]);
        if ($role && $role['role_slug'] === 'super_admin') {
            setFlash('danger', 'Cannot delete the Super Admin role.');
            redirect('roles.php');
        }

        // Check if users are assigned to this role
        $count = db()->count("SELECT COUNT(*) FROM users WHERE role_id = ?", [$id]);
        if ($count > 0) {
            setFlash('danger', 'Cannot delete role while users are assigned to it.');
            redirect('roles.php');
        }

        db()->delete("DELETE FROM roles WHERE id = ?", [$id]);
        logActivity('delete', "Deleted role ID: $id", 'roles', $id);
        setFlash('success', 'Role removed.');
        redirect('roles.php');
        break;

    case 'save_permissions':
        $id = intval($_POST['id'] ?? 0);
        $postedPerms = $_POST['permissions'] ?? [];
        
        if (empty($id)) {
            echo json_encode(['success' => false, 'error' => 'Missing target role identifier.']);
            exit;
        }

        // Prevent modifying permissions for Super Admin
        $role = db()->fetchOne("SELECT role_slug, role_name FROM roles WHERE id = ?", [$id]);
        if ($role && $role['role_slug'] === 'super_admin') {
            echo json_encode(['success' => false, 'error' => 'Security policy restriction: Super Admin permissions are permanent and cannot be modified.']);
            exit;
        }

        $formattedPerms = [];
        if (isset($postedPerms['all']) && $postedPerms['all'] == '1') {
            $formattedPerms = ['all' => true];
        } else {
            // Loop through posted modules and actions to build standard JSON format
            foreach ($postedPerms as $module => $actions) {
                if ($module === 'all') continue;
                if (is_array($actions)) {
                    foreach ($actions as $actionName => $value) {
                        if ($value == '1') {
                            $formattedPerms[$module][$actionName] = true;
                        }
                    }
                }
            }
        }

        $permsJson = json_encode($formattedPerms);
        db()->update("UPDATE roles SET permissions = ? WHERE id = ?", [$permsJson, $id]);

        logActivity('update_permissions', "Updated security permissions for role: {$role['role_name']}", 'roles', $id);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Security permissions synchronized successfully!', 
            'reload' => true
        ]);
        exit;

    default:
        redirect('roles.php');
}
