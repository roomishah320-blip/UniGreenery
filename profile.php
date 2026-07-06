<?php
/**
 * User Profile Page
 */
define('GREENERY_APP', true);
$pageTitle = 'My Profile';
require_once __DIR__ . '/includes/header.php';
requireAuth();

$u = currentUser();
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-area">
        <?php displayFlash(); ?>

        <!-- Profile Header -->
        <div class="profile-header-banner mb-4" data-aos="fade-down">
            <div class="profile-cover"></div>
            <div class="profile-meta d-flex align-items-end p-4">
                <div class="profile-avatar-large me-4">
                    <?php if ($u['avatar']): ?>
                        <img src="<?php echo UPLOADS_URL . '/' . e($u['avatar']); ?>" alt="">
                    <?php else: ?>
                        <div class="avatar-initials-lg"><?php echo strtoupper(substr($u['full_name'], 0, 1)); ?></div>
                    <?php endif; ?>
                    <button class="btn-change-avatar" data-bs-toggle="modal" data-bs-target="#avatarModal">
                        <i class="fas fa-camera"></i>
                    </button>
                </div>
                <div class="profile-titles">
                    <h2 class="fw-bold mb-1 text-white"><?php echo e($u['full_name']); ?></h2>
                    <p class="text-white-50 mb-0 d-flex align-items-center">
                        <span class="badge bg-primary-green me-2"><?php echo e($u['role_name']); ?></span>
                        <i class="fas fa-building me-1 ms-2"></i> <?php echo e($u['department'] ?: 'System Administration'); ?>
                    </p>
                </div>
                <div class="ms-auto pb-2">
                    <button class="btn btn-primary-green px-4" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                        <i class="fas fa-edit me-2"></i>Edit Profile
                    </button>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Information Side -->
            <div class="col-xl-4 col-lg-5" data-aos="fade-right">
                <div class="card-glass h-100">
                    <div class="card-header-custom p-3 border-bottom border-white-10">
                        <h6 class="fw-bold mb-0">Contact Information</h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="info-row mb-4">
                            <label class="text-white-50 small text-uppercase fw-bold mb-1 d-block">Email Address</label>
                            <p class="mb-0 text-white"><i class="fas fa-envelope text-success me-2"></i><?php echo e($u['email']); ?></p>
                        </div>
                        <div class="info-row mb-4">
                            <label class="text-white-50 small text-uppercase fw-bold mb-1 d-block">Phone Number</label>
                            <p class="mb-0 text-white"><i class="fas fa-phone text-success me-2"></i><?php echo e($u['phone'] ?: 'Not provided'); ?></p>
                        </div>
                        <div class="info-row mb-4">
                            <label class="text-white-50 small text-uppercase fw-bold mb-1 d-block">Username</label>
                            <p class="mb-0 text-white"><i class="fas fa-user-tag text-success me-2"></i>@<?php echo e($u['username']); ?></p>
                        </div>
                        <div class="info-row">
                            <label class="text-white-50 small text-uppercase fw-bold mb-1 d-block">Member Since</label>
                            <p class="mb-0 text-white"><i class="fas fa-calendar-alt text-success me-2"></i><?php echo date('F j, Y', strtotime($u['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity / Settings Side -->
            <div class="col-xl-8 col-lg-7" data-aos="fade-left">
                <div class="card-glass h-100">
                    <div class="card-header-custom p-0 border-bottom border-white-10">
                        <nav class="nav nav-tabs border-0 p-2 gap-2" id="profileTabs">
                            <button class="nav-link active border-0 rounded-3 text-white" data-bs-toggle="tab" data-bs-target="#security">Security Settings</button>
                            <button class="nav-link border-0 rounded-3 text-white" data-bs-toggle="tab" data-bs-target="#activity">Recent Activity</button>
                        </nav>
                    </div>
                    <div class="card-body p-4">
                        <div class="tab-content">
                            <!-- Security Tab -->
                            <div class="tab-pane fade show active" id="security">
                                <h6 class="fw-bold mb-4">Update Password</h6>
                                <form action="modules/profile-actions.php" method="POST">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="update_password">
                                    <div class="row g-3">
                                        <div class="col-md-12">
                                            <label class="form-label text-white-50">Current Password</label>
                                            <input type="password" name="current_password" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-white-50">New Password</label>
                                            <input type="password" name="new_password" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-white-50">Confirm New Password</label>
                                            <input type="password" name="confirm_password" class="form-control" required>
                                        </div>
                                        <div class="col-12 mt-4 text-end">
                                            <button type="submit" class="btn btn-outline-green">Update Password</button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Activity Tab -->
                            <div class="tab-pane fade" id="activity">
                                <div class="timeline-compact">
                                    <?php 
                                    $logs = db()->fetchAll("SELECT * FROM activities WHERE user_id = ? ORDER BY created_at DESC LIMIT 10", [$u['id']]);
                                    if(empty($logs)): ?>
                                        <div class="text-center py-5 text-muted">No recent activity logs.</div>
                                    <?php else: foreach($logs as $log): ?>
                                        <div class="timeline-item-sm pb-3 mb-3 border-bottom border-white-5">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-success small fw-bold"><?php echo e(strtoupper($log['activity_type'])); ?></span>
                                                <span class="text-white-50" style="font-size: 0.7rem;"><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></span>
                                            </div>
                                            <p class="mb-0 small"><?php echo e($log['description']); ?></p>
                                        </div>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold">Update Profile Info</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="modules/profile-actions.php" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_profile">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-white-50">Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?php echo e($u['full_name']); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo e($u['email']); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo e($u['phone']); ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Avatar Modal -->
<div class="modal fade" id="avatarModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold">Change Avatar</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="modules/profile-actions.php" method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_avatar">
                <div class="modal-body p-4 text-center">
                    <div class="avatar-preview mb-3 mx-auto">
                        <?php if ($u['avatar']): ?>
                            <img src="<?php echo UPLOADS_URL . '/' . e($u['avatar']); ?>" class="rounded-pill" style="width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                            <div class="avatar-initials-lg mx-auto"><?php echo strtoupper(substr($u['full_name'], 0, 1)); ?></div>
                        <?php endif; ?>
                    </div>
                    <input type="file" name="avatar" class="form-control" accept="image/*" required>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="submit" class="btn btn-primary-green w-100">Upload New Photo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
