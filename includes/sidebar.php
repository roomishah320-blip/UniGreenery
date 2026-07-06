<?php
/**
 * Sidebar Navigation Component
 */
$user = $user ?? currentUser();
$role = $user ? $user['role_slug'] : '';

// Define menu items — access is controlled entirely by DB role permissions
$menuItems = [
    ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard',        'page' => 'dashboard',      'file' => 'dashboard.php',      'permission' => 'dashboard'],
    ['icon' => 'fa-th-large',       'label' => 'Categories',       'page' => 'categories',     'file' => 'categories.php',     'permission' => 'categories'],
    ['icon' => 'fa-leaf',           'label' => 'Lawns',            'page' => 'lawns',          'file' => 'lawns.php',          'permission' => 'lawns'],
    ['icon' => 'fa-trophy',         'label' => 'Rankings',         'page' => 'rankings',       'file' => 'rankings.php',       'permission' => 'rankings'],
    ['icon' => 'fa-exchange-alt',   'label' => 'Transformations',  'page' => 'transformations','file' => 'transformations.php','permission' => 'transformations'],
    ['icon' => 'fa-project-diagram','label' => 'Active Projects',  'page' => 'projects',       'file' => 'projects.php',       'permission' => 'projects'],
    ['icon' => 'fa-tools',          'label' => 'Maintenance',      'page' => 'maintenance',    'file' => 'maintenance.php',    'permission' => 'maintenance'],
    ['icon' => 'fa-map-marked-alt', 'label' => 'Map Management',   'page' => 'map-management', 'file' => 'map-management.php', 'permission' => 'map_management'],
    ['icon' => 'fa-tint',           'label' => 'Irrigation',       'page' => 'irrigation',     'file' => 'irrigation.php',     'permission' => 'irrigation'],
    ['icon' => 'fa-seedling',       'label' => 'Plants & Trees',   'page' => 'plants',         'file' => 'plants.php',         'permission' => 'plants'],
    ['icon' => 'fa-users',          'label' => 'Staff',            'page' => 'staff',          'file' => 'staff.php',          'permission' => 'staff'],
    ['icon' => 'fa-chart-bar',      'label' => 'Reports',          'page' => 'reports',        'file' => 'reports.php',        'permission' => 'reports'],
    ['icon' => 'fa-user-shield',    'label' => 'User Accounts',    'page' => 'users',          'file' => 'users.php',          'permission' => 'users'],
    ['icon' => 'fa-users-cog',      'label' => 'Roles & Permissions','page' => 'roles',        'file' => 'roles.php',          'permission' => 'roles'],
    ['icon' => 'fa-cog',            'label' => 'Settings',         'page' => 'settings',       'file' => 'settings.php',       'permission' => 'settings'],
];
?>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <!-- Sidebar Header / Brand -->
    <div class="sidebar-header">
        <a href="<?php echo url('dashboard.php'); ?>" class="sidebar-brand">
            <div class="brand-icon">
                <i class="fas fa-leaf"></i>
            </div>
            <div class="brand-text">
                <span class="brand-name">Greenery</span>
                <span class="brand-sub">Management System</span>
            </div>
        </a>
        <button class="sidebar-close d-lg-none" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Sidebar Menu -->
    <div class="sidebar-menu">
        <div class="menu-section">
            <span class="menu-section-title">Main Navigation</span>
        </div>

        <ul class="sidebar-nav">
            <?php foreach ($menuItems as $item):
                // Access is granted entirely by the DB permission for this module
                $hasAccess = hasPermission($item['permission']);
                if (!$hasAccess) continue;
                $isActive = isPage($item['page']);
            ?>
            <li class="nav-item-sidebar <?php echo $isActive ? 'active' : ''; ?>">
                <a href="<?php echo url($item['file']); ?>" class="nav-link-sidebar">
                    <span class="nav-icon"><i class="fas <?php echo $item['icon']; ?>"></i></span>
                    <span class="nav-text"><?php echo e($item['label']); ?></span>
                    <?php if ($isActive): ?>
                    <span class="active-indicator"></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>

        <div class="menu-section mt-3">
            <span class="menu-section-title">Account</span>
        </div>
        <ul class="sidebar-nav">
            <li class="nav-item-sidebar">
                <a href="<?php echo url('logout.php'); ?>" class="nav-link-sidebar text-danger-subtle">
                    <span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span>
                    <span class="nav-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-user-card">
            <div class="user-avatar-sm">
                <?php if ($user && $user['avatar']): ?>
                <img src="<?php echo UPLOADS_URL . '/' . e($user['avatar']); ?>" alt="">
                <?php else: ?>
                <div class="avatar-initials-sm"><?php echo $user ? strtoupper(substr($user['full_name'], 0, 1)) : 'U'; ?></div>
                <?php endif; ?>
            </div>
            <div class="user-info-sm">
                <span class="name"><?php echo $user ? e(truncate($user['full_name'], 18)) : 'User'; ?></span>
                <span class="role"><?php echo $user ? e($user['role_name']) : ''; ?></span>
            </div>
        </div>
    </div>
</aside>
