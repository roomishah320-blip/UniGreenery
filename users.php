<?php
/**
 * User Accounts Management Control Panel
 * Step 7: Create Admin Role Management Panel
 */
define('GREENERY_APP', true);
$pageTitle = 'User Accounts Management';
require_once __DIR__ . '/includes/header.php';
requireAuth();
requireRole('super_admin');

// Fetch all users with their roles
$users = db()->fetchAll(
    "SELECT u.*, r.role_name, r.role_slug FROM users u 
     JOIN roles r ON u.role_id = r.id 
     ORDER BY u.id DESC"
);

// Fetch active roles for selection
$roles = db()->fetchAll("SELECT id, role_name FROM roles WHERE is_active = 1 ORDER BY id ASC");
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-area">
        <?php displayFlash(); ?>

        <!-- Page Header -->
        <div class="page-header" data-aos="fade-down">
            <div>
                <h1><i class="fas fa-user-shield me-2 text-success"></i>User Management</h1>
                <p class="text-muted mb-0">Control system logins, assign security scopes, and manage user statuses.</p>
            </div>
            <button class="btn btn-primary-green" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-user-plus me-2"></i>Create New User
            </button>
        </div>

        <!-- Search and stats panel -->
        <div class="row g-4 mb-4" data-aos="fade-up">
            <div class="col-md-8">
                <div class="card-glass p-3 d-flex align-items-center">
                    <div class="input-group bg-dark bg-opacity-25 rounded-3 border border-white-10">
                        <span class="input-group-text bg-transparent border-0 text-white-50"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control bg-transparent border-0 text-white table-search" data-target="#usersTable" placeholder="Search users by name, email or identity...">
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-glass p-3 text-center d-flex justify-content-around align-items-center">
                    <div>
                        <h4 class="fw-bold text-success mb-0"><?php echo count($users); ?></h4>
                        <small class="text-muted">Total Accounts</small>
                    </div>
                    <div class="border-start border-white-10 h-75"></div>
                    <div>
                        <h4 class="fw-bold text-info mb-0"><?php echo count(array_filter($users, fn($u) => $u['is_active'] == 1)); ?></h4>
                        <small class="text-muted">Active Users</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="card-glass p-0 overflow-hidden" data-aos="fade-up" data-aos-delay="100">
            <div class="table-responsive">
                <table class="table table-dark table-hover align-middle mb-0" id="usersTable">
                    <thead>
                        <tr class="text-white-50" style="border-bottom: 2px solid rgba(255,255,255,0.08);">
                            <th class="ps-4">User Details</th>
                            <th>Username</th>
                            <th>Role Scope</th>
                            <th>Auth Provider</th>
                            <th>Verification</th>
                            <th>Account Status</th>
                            <th>Last Active</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($users as $u): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-sm">
                                        <?php if ($u['profile_image']): ?>
                                            <img src="<?php echo e($u['profile_image']); ?>" class="rounded-circle" width="40" height="40">
                                        <?php elseif ($u['avatar']): ?>
                                            <img src="<?php echo UPLOADS_URL . '/' . e($u['avatar']); ?>" class="rounded-circle" width="40" height="40">
                                        <?php else: ?>
                                            <div class="avatar-placeholder-large" style="width: 40px; height: 40px; border-radius: 50%; background: #40916c; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:bold;">
                                                <?php echo strtoupper(substr($u['full_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <span class="d-block fw-semibold text-white"><?php echo e($u['full_name']); ?></span>
                                        <small class="text-muted" style="font-size: 0.8rem;"><?php echo e($u['email']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-white-50">@<?php echo e($u['username']); ?></td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">
                                    <?php echo e($u['role_name']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($u['auth_provider'] === 'Google'): ?>
                                    <span class="text-info"><i class="fab fa-google me-1.5"></i>Google SSO</span>
                                <?php else: ?>
                                    <span class="text-white-50"><i class="fas fa-lock me-1.5"></i>Secure Credentials</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($u['email_verified']): ?>
                                    <span class="text-success"><i class="fas fa-check-circle me-1"></i>Verified</span>
                                <?php else: ?>
                                    <span class="text-warning"><i class="fas fa-hourglass-half me-1"></i>Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input toggle-status" type="checkbox" data-id="<?php echo $u['id']; ?>" <?php echo $u['is_active'] ? 'checked' : ''; ?> <?php echo $u['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                    <span class="ms-1 status-label <?php echo $u['is_active'] ? 'text-success' : 'text-danger'; ?>" style="font-size: 13px; font-weight: 500;">
                                        <?php echo $u['is_active'] ? 'Active' : 'Banned'; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="text-white-50" style="font-size: 13px;">
                                <?php echo $u['last_login'] ? timeAgo($u['last_login']) : 'Never'; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="dropdown">
                                    <button class="btn btn-glass btn-sm" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end glass-dropdown">
                                        <li>
                                            <a class="dropdown-item edit-user-btn" href="#" 
                                               data-bs-toggle="modal" 
                                               data-bs-target="#editUserModal"
                                               data-json='<?php echo htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8'); ?>'>
                                                <i class="fas fa-user-edit me-2"></i>Edit Details
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider border-white-10"></li>
                                        <li>
                                            <a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $u['id']; ?>, 'user', 'user account')">
                                                <i class="fas fa-user-slash me-2"></i>Delete Account
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Create New User -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2 text-success"></i>Create System Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="modules/user-actions.php" method="POST" class="ajax-form">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Role Profile Scope</label>
                            <select name="role_id" class="form-select bg-dark border-white-10 text-white" required>
                                <?php foreach($roles as $r): ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo e($r['role_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Full Name</label>
                            <input type="text" name="full_name" class="form-control" placeholder="e.g. Dr. Arthur Green" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="e.g. arthur_g" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="e.g. arthur@univ.edu" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit System User -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-edit me-2 text-success"></i>Edit User Account</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="modules/user-actions.php" method="POST" class="ajax-form">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Role Profile Scope</label>
                            <select name="role_id" id="edit_role_id" class="form-select bg-dark border-white-10 text-white" required>
                                <?php foreach($roles as $r): ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo e($r['role_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Full Name</label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Username</label>
                            <input type="text" name="username" id="edit_username" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Email Address</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Password <small class="text-white-50">(Leave blank to keep current)</small></label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••">
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

<?php
$extraJS = '
<script>
    // Bind data parameters to Edit User Modal
    document.addEventListener("click", function(e) {
        if (e.target.closest(".edit-user-btn")) {
            const btn = e.target.closest(".edit-user-btn");
            const data = JSON.parse(btn.dataset.json);
            
            document.getElementById("edit_id").value = data.id;
            document.getElementById("edit_role_id").value = data.role_id;
            document.getElementById("edit_full_name").value = data.full_name;
            document.getElementById("edit_username").value = data.username;
            document.getElementById("edit_email").value = data.email;
        }
    });

    // AJAX Toggle Status (Activate/Ban)
    document.querySelectorAll(".toggle-status").forEach(el => {
        el.addEventListener("change", function() {
            const userId = this.dataset.id;
            const status = this.checked ? 1 : 0;
            const label = this.nextElementSibling;
            
            showToast("Updating user status...", "info");

            const formData = new FormData();
            formData.append("action", "toggle_status");
            formData.append("id", userId);
            formData.append("is_active", status);
            formData.append("csrf_token", document.querySelector("meta[name=\'csrf-token\']").content);

            fetch("modules/user-actions.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, "success");
                    if (status == 1) {
                        label.className = "ms-1 status-label text-success";
                        label.innerText = "Active";
                    } else {
                        label.className = "ms-1 status-label text-danger";
                        label.innerText = "Banned";
                    }
                } else {
                    showToast("Error: " + data.error, "danger");
                    this.checked = !this.checked; // Revert checkbox UI state
                }
            })
            .catch(() => {
                showToast("Network error. Reverting state.", "danger");
                this.checked = !this.checked;
            });
        });
    });
</script>';
include __DIR__ . '/includes/footer.php';
?>
