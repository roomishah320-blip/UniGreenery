<?php
/**
 * Staff Management — Workforce Hub
 * Step 13: Staff Management Module
 */
define('GREENERY_APP', true);
$pageTitle = 'Staff & Workforce Management';
require_once __DIR__ . '/includes/header.php';
requireAuth();
requirePermission('staff');

// Filters
$search = sanitize($_GET['search'] ?? '');
$deptFilter = sanitize($_GET['department'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');

$where = "WHERE 1=1";
$params = [];

if ($search) { $where .= " AND (s.full_name LIKE ? OR s.employee_id LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($deptFilter) { $where .= " AND s.department = ?"; $params[] = $deptFilter; }
if ($statusFilter) { $where .= " AND s.status = ?"; $params[] = $statusFilter; }

// Fetch Staff with Assignment Counts
$staffList = db()->fetchAll("SELECT s.*, 
    (SELECT COUNT(*) FROM lawn_staff ls WHERE ls.staff_id = s.id AND ls.status = 'active') as assignment_count 
    FROM staff s $where ORDER BY s.full_name ASC", $params);

$departments = db()->fetchAll("SELECT DISTINCT department FROM staff WHERE department IS NOT NULL AND department != ''");
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-area">
        <?php displayFlash(); ?>

        <!-- Page Header -->
        <div class="page-header" data-aos="fade-down">
            <div>
                <h1><i class="fas fa-users me-2 text-primary"></i>Workforce Management</h1>
                <p class="text-muted mb-0">Manage university gardeners, field officers, and technical staff assignments.</p>
            </div>
            <?php if (hasPermission('staff', 'create')): ?>
            <button class="btn btn-primary-green" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                <i class="fas fa-user-plus me-2"></i>Register New Staff
            </button>
            <?php endif; ?>
        </div>

        <!-- Workforce Filter Suite -->
        <div class="card-glass mb-4 p-3" data-aos="fade-up">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-lg-4 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-white-10 text-white-50"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by name or Employee ID..." value="<?php echo e($search); ?>">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <select name="department" class="form-select" onchange="this.form.submit()">
                        <option value="">All Departments</option>
                        <?php foreach($departments as $dept): ?>
                         <option value="<?php echo $dept['department']; ?>" <?php echo $deptFilter == $dept['department'] ? 'selected' : ''; ?>><?php echo e($dept['department']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Any Status</option>
                        <option value="active" <?php echo $statusFilter == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="on_leave" <?php echo $statusFilter == 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                        <option value="inactive" <?php echo $statusFilter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <button type="submit" class="btn btn-outline-green w-100">Filter Team</button>
                </div>
            </form>
        </div>

        <!-- Staff Profiles Grid -->
        <?php if(empty($staffList)): ?>
            <div class="card-glass py-5 text-center" data-aos="fade-up">
                <i class="fas fa-user-slash fa-4x mb-3 text-muted"></i>
                <h5>No Personnel Found</h5>
                <p class="text-muted">No staff members match the current search or filters.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach($staffList as $i => $s): 
                    $score = $s['performance_score'];
                    $scoreColor = $score >= 80 ? 'bg-success' : ($score >= 50 ? 'bg-warning' : 'bg-danger');
                    $statusColor = match($s['status']) {
                        'active' => 'bg-success',
                        'on_leave' => 'bg-warning',
                        'inactive' => 'bg-danger',
                        default => 'bg-secondary'
                    };
                ?>
                <div class="col-xl-3 col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="<?php echo $i * 50; ?>">
                    <div class="staff-card-premium">
                        <!-- Profile Header -->
                        <div class="staff-header p-4 text-center">
                            <div class="staff-avatar-wrapper mb-3 mx-auto">
                                <?php if($s['image']): ?>
                                    <img src="<?php echo UPLOADS_URL.'/'.e($s['image']); ?>" alt="<?php echo e($s['full_name']); ?>">
                                <?php else: ?>
                                    <div class="avatar-initials"><?php echo strtoupper(substr($s['full_name'],0,1)); ?></div>
                                <?php endif; ?>
                                <span class="status-indicator <?php echo $statusColor; ?>"></span>
                            </div>
                            <h6 class="fw-bold mb-1"><?php echo e($s['full_name']); ?></h6>
                            <small class="text-success text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">
                                <?php echo e($s['designation'] ?: 'Horticulture Team'); ?>
                            </small>
                        </div>

                        <!-- Staff Body -->
                        <div class="staff-body p-3 px-4 bg-white-5 border-top border-bottom border-white-5">
                            <div class="d-flex justify-content-between mb-2">
                                <small class="text-muted">Employee ID</small>
                                <small class="fw-bold">#<?php echo e($s['employee_id'] ?: 'EXT-'.$s['id']); ?></small>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <small class="text-muted">Lawns Managed</small>
                                <span class="badge bg-primary-green px-2"><?php echo $s['assignment_count']; ?> Assigned</span>
                            </div>
                            
                            <div class="performance-metric">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Performance KPI</small>
                                    <small class="fw-bold"><?php echo $score; ?>%</small>
                                </div>
                                <div class="progress" style="height: 5px; background: rgba(0,0,0,0.2);">
                                    <div class="progress-bar <?php echo $scoreColor; ?>" style="width: <?php echo $score; ?>%;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="staff-footer p-3 px-4 d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="fas fa-briefcase me-1"></i><?php echo e($s['department'] ?: 'General'); ?></small>
                            <div class="btn-group">
                                <button class="btn btn-xs btn-glass text-info" title="Assign Task" onclick="quickAssignTask(<?php echo $s['id']; ?>, '<?php echo addslashes($s['full_name']); ?>')">
                                    <i class="fas fa-tasks"></i>
                                </button>
                                <?php if($s['email']): ?>
                                    <a href="mailto:<?php echo e($s['email']); ?>" class="btn btn-xs btn-glass text-white" title="Email: <?php echo e($s['email']); ?>"><i class="fas fa-envelope"></i></a>
                                <?php else: ?>
                                    <button class="btn btn-xs btn-glass text-white-50" title="No Email Provided" onclick="showToast('No email address available for this staff member.', 'warning')"><i class="fas fa-envelope"></i></button>
                                <?php endif; ?>
                                <button class="btn btn-xs btn-glass text-white ms-1 btn-edit-staff" 
                                        title="Edit Profile"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editStaffModal"
                                        data-id="<?php echo $s['id']; ?>"
                                        data-json="<?php echo e(json_encode($s)); ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-xs btn-glass text-danger ms-1" 
                                        title="Delete"
                                        onclick="confirmDelete(<?php echo $s['id']; ?>, 'staff-actions.php')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Staff Modal -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-plus me-2 text-primary"></i>Onboard New Personnel</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo url('modules/staff-actions.php'); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Full Name *</label>
                            <input type="text" name="full_name" class="form-control" placeholder="e.g. Ramesh Kumar" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Employee ID</label>
                            <input type="text" name="employee_id" class="form-control" placeholder="e.g. EMP1024">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Designation</label>
                            <input type="text" name="designation" class="form-control" placeholder="e.g. Senior Gardener">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Department</label>
                            <select name="department" class="form-select">
                                <option value="Horticulture">Horticulture</option>
                                <option value="Irrigation">Irrigation</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Security">Security</option>
                                <option value="Administration">Administration</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Work Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="on_leave">On Leave</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Phone Number</label>
                            <input type="text" name="phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Email Address</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50">Profile Picture</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Register Personnel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Staff Modal -->
<div class="modal fade" id="editStaffModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold"><i class="fas fa-user-edit me-2 text-warning"></i>Update Personnel Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo url('modules/staff-actions.php'); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Full Name *</label>
                            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Employee ID</label>
                            <input type="text" name="employee_id" id="edit_employee_id" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Designation</label>
                            <input type="text" name="designation" id="edit_designation" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Department</label>
                            <select name="department" id="edit_department" class="form-select">
                                <option value="Horticulture">Horticulture</option>
                                <option value="Irrigation">Irrigation</option>
                                <option value="Maintenance">Maintenance</option>
                                <option value="Security">Security</option>
                                <option value="Administration">Administration</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Work Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="on_leave">On Leave</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Phone Number</label>
                            <input type="text" name="phone" id="edit_phone" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Email Address</label>
                            <input type="email" name="email" id="edit_email" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Performance Score (%)</label>
                            <input type="range" name="performance_score" id="edit_performance_score" class="form-range" min="0" max="100">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50">Update Profile Picture</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Update Records</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .staff-card-premium {
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 24px;
        overflow: hidden;
        transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .staff-card-premium:hover {
        transform: translateY(-8px);
        border-color: var(--accent-green);
        box-shadow: 0 15px 30px rgba(0,0,0,0.3);
    }

    .staff-avatar-wrapper {
        position: relative;
        width: 85px;
        height: 85px;
    }
    .staff-avatar-wrapper img, .avatar-initials {
        width: 100%;
        height: 100%;
        border-radius: 20px;
        object-fit: cover;
        border: 3px solid rgba(255,255,255,0.1);
    }
    .avatar-initials {
        background: var(--primary-green);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 700;
    }
    
    .status-indicator {
        position: absolute;
        bottom: -2px;
        right: -2px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        border: 4px solid #0f0f23;
    }

    .bg-white-5 { background: rgba(255,255,255,0.05); }
    .border-white-5 { border-color: rgba(255,255,255,0.05) !important; }
</style>

<!-- Quick Task Assignment Modal -->
<div class="modal fade" id="quickTaskModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold">Assign Task to <span id="quickTaskStaffName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="modules/maintenance-actions.php" method="POST" class="ajax-form">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="staff_id" id="quickTaskStaffId">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label text-white-50">Target Lawn / Zone *</label>
                            <?php $lawns = db()->fetchAll("SELECT id, name FROM lawns WHERE status = 'active' ORDER BY name"); ?>
                            <select name="lawn_id" class="form-select" required>
                                <option value="">-- Select Lawn --</option>
                                <?php foreach($lawns as $ln): ?>
                                <option value="<?php echo $ln['id']; ?>"><?php echo e($ln['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Task Category *</label>
                            <select name="activity_type" class="form-select" required>
                                <option value="mowing">Mowing</option>
                                <option value="weeding">Weeding</option>
                                <option value="watering">Watering</option>
                                <option value="pruning">Pruning</option>
                                <option value="fertilizing">Fertilizing</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Scheduled Date</label>
                            <input type="date" name="log_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50">Task Details</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Instructions for the staff..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Assign Task</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener("click", function(e) {
        if (e.target.closest(".btn-edit-staff")) {
            const btn = e.target.closest(".btn-edit-staff");
            const data = JSON.parse(btn.dataset.json);
            
            document.getElementById("edit_id").value = data.id;
            document.getElementById("edit_full_name").value = data.full_name;
            document.getElementById("edit_employee_id").value = data.employee_id || "";
            document.getElementById("edit_designation").value = data.designation || "";
            document.getElementById("edit_department").value = data.department;
            document.getElementById("edit_status").value = data.status;
            document.getElementById("edit_phone").value = data.phone || "";
            document.getElementById("edit_email").value = data.email || "";
            document.getElementById("edit_performance_score").value = data.performance_score || 0;
        }
    });

    function quickAssignTask(id, name) {
        document.getElementById("quickTaskStaffId").value = id;
        document.getElementById("quickTaskStaffName").innerText = name;
        new bootstrap.Modal(document.getElementById("quickTaskModal")).show();
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
