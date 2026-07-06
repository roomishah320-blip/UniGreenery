<?php
/**
 * Active Projects (Plantation Drives) Management
 */
define('GREENERY_APP', true);
$pageTitle = 'Active Projects';
require_once __DIR__ . '/includes/header.php';
requireAuth();
requirePermission('projects');

// Fetch Projects with Lawn Info & Filter
$statusFilter = sanitize($_GET['status'] ?? '');
$where = "WHERE 1=1";
$params = [];

if ($statusFilter && in_array($statusFilter, ['ongoing', 'upcoming', 'completed'])) {
    $where .= " AND p.status = ?";
    $params[] = $statusFilter;
}

$query = "SELECT p.*, l.name as lawn_name, u.full_name as organizer_name 
          FROM plantation_drives p 
          LEFT JOIN lawns l ON p.lawn_id = l.id 
          LEFT JOIN users u ON p.organized_by = u.id 
          $where
          ORDER BY p.drive_date DESC";
$projects = db()->fetchAll($query, $params);

// Fetch Lawns for Assignment
$lawns = db()->fetchAll("SELECT id, name FROM lawns WHERE status = 'active'");
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-area">
        <?php displayFlash(); ?>

        <!-- Page Header -->
        <div class="page-header d-flex justify-content-between align-items-center mb-4" data-aos="fade-down">
            <div>
                <h1 class="display-6 fw-bold mb-1">Active Projects</h1>
                <p class="text-muted mb-0">Manage plantation drives and assign specific greenery tasks to lawns.</p>
            </div>
            <?php if(hasRole(['super_admin', 'state_officer', 'plantation_officer'])): ?>
            <button class="btn btn-primary-green" data-bs-toggle="modal" data-bs-target="#addProjectModal">
                <i class="fas fa-plus-circle me-2"></i>Initiate New Project
            </button>
            <?php endif; ?>
        </div>

        <!-- Project Status Filters -->
        <div class="row g-4 mb-4" data-aos="fade-up">
            <div class="col-md-12">
                <div class="card-glass p-2">
                    <div class="d-flex gap-2">
                        <a href="?status=" class="btn btn-glass <?php echo empty($statusFilter) ? 'active' : ''; ?>">All Projects</a>
                        <a href="?status=ongoing" class="btn btn-glass <?php echo $statusFilter === 'ongoing' ? 'active' : ''; ?>">Ongoing</a>
                        <a href="?status=upcoming" class="btn btn-glass <?php echo $statusFilter === 'upcoming' ? 'active' : ''; ?>">Upcoming</a>
                        <a href="?status=completed" class="btn btn-glass <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">Completed</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Grid -->
        <div class="row g-4">
            <?php if(empty($projects)): ?>
                <div class="col-12 text-center py-5" data-aos="zoom-in">
                    <div class="card-glass p-5">
                        <i class="fas fa-project-diagram fa-4x text-white-10 mb-4"></i>
                        <h3>No projects found</h3>
                        <p class="text-muted">Start by initiating a new plantation project or task assignment.</p>
                    </div>
                </div>
            <?php else: foreach($projects as $p): 
                $statusColor = match($p['status']) {
                    'ongoing' => 'primary',
                    'upcoming' => 'warning',
                    'completed' => 'success',
                    'cancelled' => 'danger',
                    default => 'secondary'
                };
                $progress = $p['target_plants'] > 0 ? ($p['planted_count'] / $p['target_plants']) * 100 : 0;
            ?>
            <div class="col-xl-4 col-lg-6" data-aos="fade-up">
                <div class="card-glass h-100 project-card overflow-hidden">
                    <div class="project-header p-4 pb-0 d-flex justify-content-between align-items-start">
                        <div>
                            <span class="badge bg-<?php echo $statusColor; ?>-subtle text-<?php echo $statusColor; ?> mb-2 text-uppercase">
                                <i class="fas fa-circle small me-1"></i> <?php echo $p['status']; ?>
                            </span>
                            <h5 class="fw-bold text-white mb-1"><?php echo e($p['title']); ?></h5>
                            <p class="text-white-50 small mb-0"><i class="fas fa-map-marker-alt me-1"></i> <?php echo e($p['lawn_name'] ?: 'General Location'); ?></p>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-glass btn-sm" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end glass-dropdown">
                                <li><a class="dropdown-item btn-edit-project" href="#" 
                                       data-bs-toggle="modal" 
                                       data-bs-target="#editProjectModal"
                                       data-json="<?php echo e(json_encode($p)); ?>">
                                    <i class="fas fa-edit me-2"></i>Edit Project</a></li>
                                <li><hr class="dropdown-divider border-white-10"></li>
                                <li><a class="dropdown-item text-danger" href="#" onclick="confirmDelete(<?php echo $p['id']; ?>, 'project-actions.php')">
                                    <i class="fas fa-trash me-2"></i>Terminate</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card-body p-4">
                        <p class="text-white-50 small mb-4"><?php echo truncate($p['description'], 120); ?></p>
                        
                        <div class="project-stats bg-white-5 rounded-4 p-3 mb-4">
                            <div class="row text-center">
                                <div class="col-6 border-end border-white-10">
                                    <small class="text-white-50 d-block mb-1 text-uppercase" style="font-size: 0.6rem;">Target</small>
                                    <span class="fw-bold"><?php echo formatNumber($p['target_plants']); ?></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-white-50 d-block mb-1 text-uppercase" style="font-size: 0.6rem;">Completed</small>
                                    <span class="fw-bold text-success"><?php echo formatNumber($p['planted_count']); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="progress-wrapper mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="text-white-50">Completion Progress</small>
                                <small class="fw-bold"><?php echo round($progress); ?>%</small>
                            </div>
                            <div class="progress" style="height: 6px; background: rgba(255,255,255,0.05);">
                                <div class="progress-bar bg-<?php echo $statusColor; ?>" style="width: <?php echo $progress; ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer bg-white-5 border-0 p-3 px-4 d-flex justify-content-between align-items-center">
                        <div class="organizer-info d-flex align-items-center gap-2">
                            <i class="fas fa-user-circle text-white-50"></i>
                            <small class="text-white-50"><?php echo e($p['organizer_name'] ?: 'System'); ?></small>
                        </div>
                        <small class="text-white-50"><i class="far fa-calendar-alt me-1"></i> <?php echo formatDate($p['drive_date']); ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- Add Project Modal -->
<div class="modal fade" id="addProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2 text-success"></i>Initiate Plantation Task</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="modules/project-actions.php" method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Project Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. Monsoon Tree Plantation Drive 2024" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Assigned Lawn *</label>
                            <select name="lawn_id" class="form-select" required>
                                <option value="">Select a lawn...</option>
                                <?php foreach($lawns as $l): ?>
                                    <option value="<?php echo $l['id']; ?>"><?php echo e($l['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Target Date *</label>
                            <input type="date" name="drive_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Target Plant Count</label>
                            <input type="number" name="target_plants" class="form-control" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Initial Status</label>
                            <select name="status" class="form-select">
                                <option value="upcoming">Upcoming</option>
                                <option value="ongoing">Ongoing</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50">Project Description & Instructions</label>
                            <textarea name="description" class="form-control" rows="4" placeholder="Detailed tasks and requirements..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Create Project</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Project Modal -->
<div class="modal fade" id="editProjectModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2 text-warning"></i>Update Project Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="modules/project-actions.php" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Project Title</label>
                            <input type="text" name="title" id="edit_title" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Assigned Lawn</label>
                            <select name="lawn_id" id="edit_lawn_id" class="form-select" required>
                                <?php foreach($lawns as $l): ?>
                                    <option value="<?php echo $l['id']; ?>"><?php echo e($l['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="upcoming">Upcoming</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Target Date</label>
                            <input type="date" name="drive_date" id="edit_drive_date" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Target Plants</label>
                            <input type="number" name="target_plants" id="edit_target_plants" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Completed Count</label>
                            <input type="number" name="planted_count" id="edit_planted_count" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="4"></textarea>
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

<style>
    .project-card {
        transition: var(--transition-standard);
        border: 1px solid var(--border-color);
    }
    .project-card:hover {
        transform: translateY(-8px);
        border-color: var(--accent-green);
        box-shadow: 0 15px 35px rgba(0,0,0,0.4);
    }
    .bg-primary-subtle { background: rgba(79, 70, 229, 0.1) !important; }
    .bg-warning-subtle { background: rgba(245, 158, 11, 0.1) !important; }
    .bg-success-subtle { background: rgba(16, 185, 129, 0.1) !important; }
    .bg-danger-subtle { background: rgba(239, 68, 68, 0.1) !important; }
</style>

<script>
    document.addEventListener("click", function(e) {
        if (e.target.closest(".btn-edit-project")) {
            const btn = e.target.closest(".btn-edit-project");
            const data = JSON.parse(btn.dataset.json);
            
            document.getElementById("edit_id").value = data.id;
            document.getElementById("edit_title").value = data.title;
            document.getElementById("edit_lawn_id").value = data.lawn_id;
            document.getElementById("edit_status").value = data.status;
            document.getElementById("edit_drive_date").value = data.drive_date;
            document.getElementById("edit_target_plants").value = data.target_plants;
            document.getElementById("edit_planted_count").value = data.planted_count;
            document.getElementById("edit_description").value = data.description || "";
        }
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
