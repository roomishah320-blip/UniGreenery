<?php
/**
 * Maintenance Logs — Operational Tracking (Refined)
 * Step 15 Re-check: University Maintenance Logs
 */
define('GREENERY_APP', true);
$pageTitle = 'Maintenance Activity Logs';
require_once __DIR__ . '/includes/header.php';
requireAuth();
requirePermission('maintenance');

// Ensure table exists & has staff_id
try {
    db()->query("SELECT 1 FROM maintenance_logs LIMIT 1");
    // Check if staff_id column exists
    $columns = db()->fetchAll("DESCRIBE maintenance_logs");
    $hasStaffId = false;
    foreach($columns as $col) {
        if($col['Field'] === 'staff_id') { $hasStaffId = true; break; }
    }
    if(!$hasStaffId) {
        db()->query("ALTER TABLE maintenance_logs ADD COLUMN staff_id INT NULL AFTER performed_by");
    }
} catch (Exception $e) {
    db()->query("CREATE TABLE IF NOT EXISTS maintenance_logs (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        lawn_id INT NOT NULL, 
        activity_type VARCHAR(100), 
        description TEXT, 
        performed_by VARCHAR(255), 
        staff_id INT NULL,
        log_date DATE, 
        status ENUM('completed', 'pending', 'in_progress') DEFAULT 'completed', 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        FOREIGN KEY (lawn_id) REFERENCES lawns(id) ON DELETE CASCADE, 
        INDEX idx_lawn (lawn_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// Filters logic
$typeFilter = sanitize($_GET['type'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');
$search = sanitize($_GET['search'] ?? '');

$where = "WHERE 1=1";
$params = [];

if ($typeFilter) { $where .= " AND ml.activity_type = ?"; $params[] = $typeFilter; }
if ($statusFilter) { $where .= " AND ml.status = ?"; $params[] = $statusFilter; }
if ($search) { $where .= " AND (ml.description LIKE ? OR l.name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

// Fetch Logs with Lawn & Staff Info
$logs = db()->fetchAll("SELECT ml.*, l.name as lawn_name, s.full_name as staff_name 
    FROM maintenance_logs ml 
    LEFT JOIN lawns l ON ml.lawn_id = l.id 
    LEFT JOIN staff s ON ml.staff_id = s.id
    $where ORDER BY ml.log_date DESC, ml.created_at DESC", $params);

$lawns = db()->fetchAll("SELECT id, name FROM lawns WHERE status = 'active' ORDER BY name");
$staff = db()->fetchAll("SELECT id, full_name FROM staff WHERE status = 'active' ORDER BY full_name");

// Stats for the strip
$todayCount = db()->count("SELECT COUNT(*) FROM maintenance_logs WHERE DATE(log_date) = CURDATE()");
$pendingCount = db()->count("SELECT COUNT(*) FROM maintenance_logs WHERE status = 'pending'");
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-area">
        <?php displayFlash(); ?>

        <!-- Page Header -->
        <div class="page-header" data-aos="fade-down">
            <div>
                <h1><i class="fas fa-tools me-2 text-warning"></i>Maintenance Operations</h1>
                <p class="text-muted mb-0">Unified tracking of horticultural tasks, weeding, and infrastructure maintenance.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-green" onclick="window.print()"><i class="fas fa-print me-1"></i>Print Logs</button>
                <?php if (hasPermission('maintenance', 'create')): ?>
                <button class="btn btn-primary-green" data-bs-toggle="modal" data-bs-target="#addLogModal">
                    <i class="fas fa-calendar-plus me-2"></i>Log Activity
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Stats Strip -->
        <div class="row g-4 mb-4">
            <div class="col-md-4" data-aos="fade-up">
                <div class="stat-card-premium p-3 d-flex align-items-center gap-3" style="--card-color: #52b788; --card-bg: rgba(82,183,136,0.1);">
                    <div class="stat-icon-sm"><i class="fas fa-tasks"></i></div>
                    <div><h4 class="mb-0 fw-bold"><?php echo $todayCount; ?></h4><small class="text-muted">Today's Executions</small></div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card-premium p-3 d-flex align-items-center gap-3" style="--card-color: #f4a261; --card-bg: rgba(244,162,97,0.1);">
                    <div class="stat-icon-sm"><i class="fas fa-clock"></i></div>
                    <div><h4 class="mb-0 fw-bold"><?php echo $pendingCount; ?></h4><small class="text-muted">Awaiting Action</small></div>
                </div>
            </div>
            <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-card-premium p-3 d-flex align-items-center gap-3" style="--card-color: #4361ee; --card-bg: rgba(67,97,238,0.1);">
                    <div class="stat-icon-sm"><i class="fas fa-user-check"></i></div>
                    <div><h4 class="mb-0 fw-bold"><?php echo count($staff); ?></h4><small class="text-muted">Field Staff Online</small></div>
                </div>
            </div>
        </div>

        <!-- Professional Filter Bar -->
        <div class="card-glass mb-4 p-3" data-aos="fade-up">
            <form method="GET" class="row g-3">
                <div class="col-lg-4"><input type="text" name="search" class="form-control" placeholder="Search lawn or notes..." value="<?php echo e($search); ?>"></div>
                <div class="col-lg-3">
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">All Activities</option>
                        <option value="mowing" <?php echo $typeFilter == 'mowing' ? 'selected' : ''; ?>>Mowing (Blue)</option>
                        <option value="weeding" <?php echo $typeFilter == 'weeding' ? 'selected' : ''; ?>>Weeding (Green)</option>
                        <option value="watering" <?php echo $typeFilter == 'watering' ? 'selected' : ''; ?>>Watering (Teal)</option>
                        <option value="pruning" <?php echo $typeFilter == 'pruning' ? 'selected' : ''; ?>>Pruning (Purple)</option>
                        <option value="fertilizing" <?php echo $typeFilter == 'fertilizing' ? 'selected' : ''; ?>>Fertilizing (Orange)</option>
                    </select>
                </div>
                <div class="col-lg-3">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">All Status</option>
                        <option value="completed" <?php echo $statusFilter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $statusFilter == 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    </select>
                </div>
                <div class="col-lg-2"><button type="submit" class="btn btn-outline-green w-100">Update View</button></div>
            </form>
        </div>

        <!-- Activity Table -->
        <div class="card-glass overflow-hidden" data-aos="fade-up">
            <div class="table-responsive">
                <table class="table-modern w-100">
                    <thead>
                        <tr>
                            <th>Activity Type</th>
                            <th>Target Lawn</th>
                            <th>Personnel</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($logs)): ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted">No operational logs found.</td></tr>
                        <?php else: foreach($logs as $l): 
                            // Re-verified Color Coding
                            $typeConfig = match($l['activity_type']) {
                                'mowing' => ['icon' => 'fa-cut', 'color' => '#4361ee'],       // Blue
                                'weeding' => ['icon' => 'fa-leaf', 'color' => '#2d6a4f'],      // Green
                                'watering' => ['icon' => 'fa-tint', 'color' => '#4895ef'],     // Teal
                                'pruning' => ['icon' => 'fa-hand-scissors', 'color' => '#7209b7'], // Purple
                                'fertilizing' => ['icon' => 'fa-flask', 'color' => '#f4a261'], // Orange (FIXED)
                                default => ['icon' => 'fa-tools', 'color' => '#adb5bd']
                            };
                        ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="icon-sm rounded-circle text-white d-flex align-items-center justify-content-center shadow-sm" style="background: <?php echo $typeConfig['color']; ?>; width: 32px; height: 32px;">
                                        <i class="fas <?php echo $typeConfig['icon']; ?> small"></i>
                                    </div>
                                    <span class="text-capitalize fw-bold"><?php echo e($l['activity_type']); ?></span>
                                </div>
                            </td>
                            <td><span class="text-success"><?php echo e($l['lawn_name']); ?></span></td>
                            <td><small><?php echo e($l['staff_name'] ?: $l['performed_by']); ?></small></td>
                            <td><span class="small text-white-50"><?php echo date('d M, Y', strtotime($l['log_date'])); ?></span></td>
                            <td>
                                <span class="badge bg-white-5 border border-white-10 text-uppercase" style="font-size: 0.6rem;">
                                    <?php echo e($l['status']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-glass text-white" onclick='editLog(<?php echo json_encode($l); ?>)'><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-glass text-danger ms-1" onclick="confirmDelete(<?php echo $l['id']; ?>, 'maintenance', 'log')"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Modal (Unified) -->
<div class="modal fade" id="addLogModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold" id="modalTitle">Record Maintenance Task</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo url('modules/maintenance-actions.php'); ?>" method="POST" class="ajax-form">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="logId">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label text-white-50">University Lawn / Zone *</label>
                            <select name="lawn_id" id="formLawnId" class="form-select" required>
                                <?php foreach($lawns as $ln): ?>
                                <option value="<?php echo $ln['id']; ?>"><?php echo e($ln['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Task Category *</label>
                            <select name="activity_type" id="formType" class="form-select" required>
                                <option value="mowing">Mowing</option>
                                <option value="weeding">Weeding</option>
                                <option value="watering">Watering</option>
                                <option value="pruning">Pruning</option>
                                <option value="fertilizing">Fertilizing</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Assigned Personnel</label>
                            <select name="staff_id" id="formStaffId" class="form-select">
                                <option value="">-- Select Personnel --</option>
                                <?php foreach($staff as $st): ?>
                                <option value="<?php echo $st['id']; ?>"><?php echo e($st['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Date of Execution *</label>
                            <input type="date" name="log_date" id="formDate" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Operational Status</label>
                            <select name="status" id="formStatus" class="form-select">
                                <option value="completed">Completed</option>
                                <option value="in_progress">In Progress</option>
                                <option value="pending">Scheduled / Pending</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50">Execution Notes</label>
                            <textarea name="description" id="formDesc" class="form-control" rows="2" placeholder="Detail any observations or technical notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Synchronize Log</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .stat-icon-sm { font-size: 1.4rem; opacity: 0.8; }
    .bg-white-5 { background: rgba(255,255,255,0.05); }
    .border-white-10 { border-color: rgba(255,255,255,0.1) !important; }
</style>

<?php
$extraJS = '
<script>
    function editLog(log) {
        document.getElementById("modalTitle").innerText = "Update Maintenance Task";
        document.getElementById("formAction").value = "edit";
        document.getElementById("logId").value = log.id;
        document.getElementById("formLawnId").value = log.lawn_id;
        document.getElementById("formType").value = log.activity_type;
        document.getElementById("formStaffId").value = log.staff_id || "";
        document.getElementById("formDate").value = log.log_date;
        document.getElementById("formStatus").value = log.status;
        document.getElementById("formDesc").value = log.description;
        
        new bootstrap.Modal(document.getElementById("addLogModal")).show();
    }
</script>';
include __DIR__ . '/includes/footer.php';
?>
