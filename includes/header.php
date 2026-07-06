<?php
/**
 * Header Component — Final Polish
 * Step 16 Re-check: UI/UX & Responsiveness
 */
if(!defined('GREENERY_APP')) exit;

// Core dependencies
require_once __DIR__ . '/functions.php';
startSecureSession();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title><?php echo $pageTitle ?? 'State Greenery Management'; ?> | SLGMS</title>
    
    <!-- Premium Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    
    <!-- Core UI Frameworks -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Custom Design System -->
    <link rel="stylesheet" href="<?php echo CSS_URL; ?>/style.css?v=<?php echo time(); ?>">
    
    <style>
        body { background-color: #050511 !important; }
        .page-loader { background-color: #050511 !important; }
    </style>
    
    <!-- Chart.js Engine -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    
    <!-- Global Page Pre-loader Removed for Performance -->

    <!-- Mobile Top Navigation Header -->
    <header class="mobile-header d-lg-none">
        <div class="container-fluid d-flex justify-content-between align-items-center h-100 px-3">
            <div class="mobile-logo d-flex align-items-center">
                <i class="fas fa-leaf text-success me-2"></i>
                <span class="fw-bold text-white small">SLGMS ERP</span>
            </div>
            <button class="btn btn-glass mobile-toggle" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </header>

    <!-- Desktop Top Navigation -->
    <?php if(isLoggedIn()): $u = currentUser(); ?>
    <header class="desktop-topbar d-none d-lg-flex">
        <div class="ms-auto d-flex align-items-center gap-3 pe-4">
            <!-- Search Bar -->
            <div class="top-search d-none d-xl-flex">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search resources...">
            </div>

            <!-- Notifications -->
            <?php 
            $unreadCount = unreadNotificationCount(); 
            $recentNotifications = getRecentNotifications(5);
            ?>
            <div class="dropdown" id="notificationDropdownContainer">
                <button class="btn btn-glass-circle position-relative" data-bs-toggle="dropdown" id="notificationBellBtn">
                    <i class="far fa-bell"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem; padding: 0.35em 0.5em; z-index: 10;">
                            <?php echo $unreadCount; ?>
                        </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end glass-dropdown p-0 overflow-hidden" style="width: 320px;">
                    <div class="dropdown-header border-bottom border-white-10 p-3 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-white">Notifications</h6>
                        <?php if ($unreadCount > 0): ?>
                            <a href="#" class="text-success small text-decoration-none" id="markAllReadBtn" style="font-size: 0.75rem;">Mark all read</a>
                        <?php endif; ?>
                    </div>
                    <div class="notification-list p-2" style="max-height: 300px; overflow-y: auto;">
                        <?php if (empty($recentNotifications)): ?>
                            <div class="text-center py-4 text-muted small">
                                <i class="far fa-bell-slash d-block fs-4 mb-2 opacity-50"></i>
                                No notifications
                            </div>
                        <?php else: ?>
                            <?php foreach ($recentNotifications as $notif): ?>
                                <div class="notification-item p-2 mb-1 rounded-3 <?php echo $notif['is_read'] ? 'opacity-75' : 'bg-white-5 fw-bold'; ?>" style="font-size: 0.82rem; border-left: 3px solid <?php echo $notif['is_read'] ? 'transparent' : '#52b788'; ?>;">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <span class="text-white"><?php echo e($notif['title']); ?></span>
                                        <small class="text-muted" style="font-size: 0.65rem;"><?php echo date('d M H:i', strtotime($notif['created_at'])); ?></small>
                                    </div>
                                    <p class="text-white-50 mb-1 small" style="line-height: 1.4;"><?php echo e($notif['message']); ?></p>
                                    <?php if (!empty($notif['link'])): ?>
                                        <a href="<?php echo url($notif['link']); ?>" class="text-success small d-inline-block mt-1 text-decoration-none">View Details →</a>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Profile Dropdown -->
            <div class="dropdown profile-dropdown">
                <button class="btn btn-glass profile-btn d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                    <div class="avatar-sm">
                        <?php if ($u['avatar']): ?>
                            <img src="<?php echo UPLOADS_URL . '/' . e($u['avatar']); ?>" alt="">
                        <?php else: ?>
                            <div class="avatar-initials-xs"><?php echo strtoupper(substr($u['full_name'], 0, 1)); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="profile-info text-start d-none d-md-block">
                        <span class="d-block fw-bold small"><?php echo e(truncate($u['full_name'], 15)); ?></span>
                        <span class="d-block text-muted" style="font-size: 0.6rem;"><?php echo e($u['role_name']); ?></span>
                    </div>
                    <i class="fas fa-chevron-down small opacity-50 ms-1"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end glass-dropdown mt-2 p-2 border-white-10">
                    <li><a class="dropdown-item rounded-3" href="<?php echo url('profile.php'); ?>"><i class="fas fa-user-circle me-2"></i>My Profile</a></li>
                    <li><a class="dropdown-item rounded-3" href="<?php echo url('settings.php'); ?>"><i class="fas fa-cog me-2"></i>Settings</a></li>
                    <li><hr class="dropdown-divider border-white-10"></li>
                    <li><a class="dropdown-item rounded-3 text-danger" href="<?php echo url('logout.php'); ?>"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>
    </header>
    <?php endif; ?>

    <div class="wrapper">
