<?php
/**
 * Roles & Permissions Management
 */
define('GREENERY_APP', true);
$pageTitle = 'Roles & Permissions';
require_once __DIR__ . '/includes/header.php';
requireAuth();
requirePermission('roles');

// Fetch all roles
$roles = db()->fetchAll("SELECT * FROM roles ORDER BY id ASC");
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-area">
        <?php displayFlash(); ?>

        <!-- Page Header -->
        <div class="page-header" data-aos="fade-down">
            <div>
                <h1><i class="fas fa-users-cog me-2 text-primary"></i>Access Control</h1>
                <p class="text-muted mb-0">Define system roles and manage granular permissions for each user group.</p>
            </div>
            <button class="btn btn-primary-green" data-bs-toggle="modal" data-bs-target="#addRoleModal">
                <i class="fas fa-plus me-2"></i>Create New Role
            </button>
        </div>

        <!-- Roles Grid -->
        <div class="row g-4">
            <?php foreach($roles as $r): ?>
            <div class="col-xl-4 col-lg-6" data-aos="zoom-in">
                <div class="card-glass h-100 border-white-10">
                    <div class="card-header-custom p-4 d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="fw-bold mb-1"><?php echo e($r['role_name']); ?></h5>
                            <span class="badge bg-white-10 text-white-50 small"><?php echo e($r['role_slug']); ?></span>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-glass btn-sm" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end glass-dropdown">
                                <li><a class="dropdown-item btn-edit-role" href="#" 
                                       data-bs-toggle="modal" 
                                       data-bs-target="#editRoleModal"
                                       data-json="<?php echo e(json_encode($r)); ?>">
                                       <i class="fas fa-edit me-2"></i>Edit Role</a></li>
                                <li><a class="dropdown-item btn-manage-perms" href="#" 
                                       data-bs-toggle="modal" 
                                       data-bs-target="#managePermissionsModal"
                                       data-json="<?php echo e(json_encode($r)); ?>">
                                       <i class="fas fa-key me-2 text-warning"></i>Manage Permissions</a></li>
                                <li><hr class="dropdown-divider border-white-10"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $r['id']; ?>, 'role-actions.php')">
                                    <i class="fas fa-trash me-2"></i>Delete</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body p-4 pt-0">
                        <p class="text-muted small mb-4"><?php echo e($r['description'] ?: 'No description provided.'); ?></p>
                        
                        <h6 class="fw-bold small text-uppercase text-white-50 mb-3">Permissions Preview</h6>
                        <div class="permissions-preview d-flex flex-wrap gap-2">
                            <?php 
                            $perms = json_decode($r['permissions'], true);
                            if($perms && isset($perms['all']) && $perms['all']) {
                                echo '<span class="badge bg-success-subtle text-success border border-success-subtle">Full Access</span>';
                            } elseif($perms) {
                                $count = 0;
                                foreach($perms as $module => $actions) {
                                    if($count >= 5) {
                                        echo '<span class="badge bg-white-5 text-white-50 border border-white-10">+'.(count($perms)-$count).' more</span>';
                                        break;
                                    }
                                    echo '<span class="badge bg-white-5 text-white-50 border border-white-10">'.ucfirst($module).'</span>';
                                    $count++;
                                }
                            } else {
                                echo '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">No Permissions</span>';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="card-footer bg-white-5 p-3 px-4 d-flex justify-content-between align-items-center">
                        <small class="text-muted">ID: #<?php echo $r['id']; ?></small>
                        <span class="badge <?php echo $r['is_active'] ? 'bg-success' : 'bg-danger'; ?>-subtle text-<?php echo $r['is_active'] ? 'success' : 'danger'; ?>">
                            <?php echo $r['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Role Modals would go here -->
<!-- For now, I'll provide a simplified Add Role Modal -->
<div class="modal fade" id="addRoleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold">Create New System Role</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="modules/role-actions.php" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Role Name</label>
                            <input type="text" name="role_name" class="form-control" placeholder="e.g. Campus Manager" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Role Slug</label>
                            <input type="text" name="role_slug" class="form-control" placeholder="e.g. campus_manager" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Create Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editRoleModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold">Edit System Role</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="modules/role-actions.php" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Role Name</label>
                            <input type="text" name="role_name" id="edit_role_name" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Role Slug</label>
                            <input type="text" name="role_slug" id="edit_role_slug" class="form-control" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active" checked>
                                <label class="form-check-label text-white-50" for="edit_is_active">Role is Active</label>
                            </div>
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

<!-- Modal: Granular Permissions Matrix -->
<div class="modal fade" id="managePermissionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold"><i class="fas fa-key me-2 text-warning"></i>Granular Permissions Matrix</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="modules/role-actions.php" method="POST" class="ajax-form">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="save_permissions">
                <input type="hidden" name="id" id="perm_role_id">
                
                <div class="modal-body p-4 overflow-auto" style="max-height: 70vh;">
                    <div class="mb-4 p-3 rounded-3 bg-white-5 border border-white-10">
                        <div class="form-check form-switch m-0">
                            <input class="form-check-input" type="checkbox" name="permissions[all]" id="perm_all" value="1">
                            <label class="form-check-label fw-bold text-success" for="perm_all">
                                <i class="fas fa-crown me-1.5 text-warning"></i>Full System Administrator Access (Bypasses all rules)
                            </label>
                        </div>
                    </div>

                    <table class="table table-dark table-hover align-middle mb-0 text-center" style="font-size: 14px;">
                        <thead>
                            <tr class="text-white-50" style="border-bottom: 2px solid rgba(255,255,255,0.08);">
                                <th class="text-start ps-3">System Module</th>
                                <th>View</th>
                                <th>Create</th>
                                <th>Edit</th>
                                <th>Delete</th>
                                <th>Approve</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $modules = [
                                'dashboard'       => 'Dashboard Analytics',
                                'categories'      => 'Lawn Categories',
                                'lawns'           => 'Lawn Management',
                                'plants'          => 'Plants & Trees',
                                'irrigation'      => 'Irrigation Modules',
                                'staff'           => 'Staff Profiles',
                                'transformations' => 'Transformations',
                                'projects'        => 'Active Projects/Drives',
                                'maintenance'     => 'Maintenance Logs',
                                'map_management'  => 'Map Management',
                                'reports'         => 'System Reports',
                                'rankings'        => 'Greenery Rankings',
                                'settings'        => 'System Settings',
                                'roles'           => 'Access Control Roles',
                                'users'           => 'User Accounts'
                            ];
                            foreach ($modules as $modSlug => $modName):
                            ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <td class="text-start fw-semibold text-white ps-3"><?php echo $modName; ?></td>
                                <td>
                                    <input class="form-check-input perm-chk" type="checkbox" name="permissions[<?php echo $modSlug; ?>][view]" id="perm_<?php echo $modSlug; ?>_view" value="1">
                                </td>
                                <td>
                                    <input class="form-check-input perm-chk" type="checkbox" name="permissions[<?php echo $modSlug; ?>][create]" id="perm_<?php echo $modSlug; ?>_create" value="1">
                                </td>
                                <td>
                                    <input class="form-check-input perm-chk" type="checkbox" name="permissions[<?php echo $modSlug; ?>][edit]" id="perm_<?php echo $modSlug; ?>_edit" value="1">
                                </td>
                                <td>
                                    <input class="form-check-input perm-chk" type="checkbox" name="permissions[<?php echo $modSlug; ?>][delete]" id="perm_<?php echo $modSlug; ?>_delete" value="1">
                                </td>
                                <td>
                                    <input class="form-check-input perm-chk" type="checkbox" name="permissions[<?php echo $modSlug; ?>][approve]" id="perm_<?php echo $modSlug; ?>_approve" value="1">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Save Permissions</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("click", function(e) {
        if (e.target.closest(".btn-edit-role")) {
            const btn = e.target.closest(".btn-edit-role");
            const data = JSON.parse(btn.dataset.json);
            
            document.getElementById("edit_id").value = data.id;
            document.getElementById("edit_role_name").value = data.role_name;
            document.getElementById("edit_role_slug").value = data.role_slug;
            document.getElementById("edit_description").value = data.description || "";
            document.getElementById("edit_is_active").checked = data.is_active == 1;
        }

        if (e.target.closest(".btn-manage-perms")) {
            const btn = e.target.closest(".btn-manage-perms");
            const data = JSON.parse(btn.dataset.json);
            
            document.getElementById("perm_role_id").value = data.id;
            
            // Clear all checkboxes
            document.querySelectorAll(".perm-chk, #perm_all").forEach(chk => {
                chk.checked = false;
                chk.disabled = false;
            });

            // Parse existing permissions
            try {
                const perms = JSON.parse(data.permissions);
                
                if (perms && perms.all === true) {
                    document.getElementById("perm_all").checked = true;
                    toggleAllPermissions(true);
                } else if (perms) {
                    Object.keys(perms).forEach(mod => {
                        if (perms[mod] === true) {
                            // Checked all actions for this module
                            const actions = ["view", "create", "edit", "delete", "approve"];
                            actions.forEach(act => {
                                const chk = document.getElementById(`perm_${mod}_${act}`);
                                if (chk) chk.checked = true;
                            });
                        } else if (typeof perms[mod] === "object") {
                            Object.keys(perms[mod]).forEach(act => {
                                if (perms[mod][act] === true) {
                                    const chk = document.getElementById(`perm_${mod}_${act}`);
                                    if (chk) chk.checked = true;
                                }
                            });
                        }
                    });
                }
            } catch (err) {
                console.error("Failed to parse role permissions:", err);
            }
        }
    });

    // Handle perm_all crown switch logic
    const permAll = document.getElementById("perm_all");
    if (permAll) {
        permAll.addEventListener("change", function() {
            toggleAllPermissions(this.checked);
        });
    }

    function toggleAllPermissions(checked) {
        document.querySelectorAll(".perm-chk").forEach(chk => {
            chk.checked = checked;
            chk.disabled = checked;
        });
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
