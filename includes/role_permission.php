<?php
/**
 * Role-Based Access Control & Permission Verification Middleware
 * Step 5: Dynamic Access Control System
 */
if (!defined('GREENERY_APP')) {
    define('GREENERY_APP', true);
}
require_once __DIR__ . '/functions.php';

/**
 * Checks if the current user is authorized to view the requested page.
 * If unauthorized, redirects to the access-denied page or shows an AJAX error.
 */
function protectPageByPermissions() {
    // Only protect pages if user is logged in
    if (!isLoggedIn()) {
        // Handled by requireAuth() elsewhere or here
        return;
    }

    $currentPage = currentPage();
    
    // Pages universally accessible to all authenticated users (no module permission needed)
    $publicPages = ['profile', 'logout', 'index'];
    if (in_array($currentPage, $publicPages)) {
        return;
    }

    // Page to permission module mapping
    $pagePermissionMap = [
        'dashboard'        => 'dashboard',
        'categories'       => 'categories',
        'lawns'            => 'lawns',
        'lawn-details'     => 'lawns',
        'rankings'         => 'rankings',
        'transformations'  => 'transformations',
        'projects'         => 'projects',
        'maintenance'      => 'maintenance',
        'map-management'   => 'map_management',
        'irrigation'       => 'irrigation',
        'plants'           => 'plants',
        'staff'            => 'staff',
        'reports'          => 'reports',
        'roles'            => 'roles',
        'settings'         => 'settings',
        'users'            => 'users',
    ];

    if (isset($pagePermissionMap[$currentPage])) {
        $requiredModule = $pagePermissionMap[$currentPage];
        
        // Define action depending on script
        $action = 'view';
        
        // Check dynamic permission
        if (!hasPermission($requiredModule, $action)) {
            // Log access violation attempt
            $username = $_SESSION['full_name'] ?? 'Unknown User';
            logActivity('unauthorized_access', "User tried to view unauthorized page: $currentPage.php", 'auth');
            
            // Check if AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Access Denied. You do not have permission to view this resource.']);
                exit;
            }
            
            // Redirect to premium Access Denied page
            header('Location: ' . BASE_URL . '/access-denied.php');
            exit;
        }
    }
}

/**
 * Checks if the user has permission to perform a specific action on a backend API/Handler.
 */
function protectBackendAPI($module, $action) {
    if (!hasPermission($module, $action)) {
        logActivity('unauthorized_api_call', "User tried unauthorized API action: {$action} on module: {$module}", 'auth');
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Security Violation: You do not have permissions to perform this action.']);
        exit;
    }
}
