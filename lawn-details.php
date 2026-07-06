<?php
/**
 * Lawn Details Page — Deep Analytics & Management
 * Step 6: Lawn Detailed View & Analytics
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/includes/functions.php';
startSecureSession();
requireAuth();

$id = intval($_GET['id'] ?? 0);
if (!$id) { redirect('categories.php'); }

// Fetch Main Lawn Data
$lawn = db()->fetchOne("SELECT l.*, c.name as category_name, c.slug as category_slug FROM lawns l LEFT JOIN lawn_categories c ON l.category_id = c.id WHERE l.id = ?", [$id]);
if (!$lawn) { redirect('categories.php'); }

$pageTitle = $lawn['name'] . ' - Analytics';
require_once __DIR__ . '/includes/header.php';

// Fetch Related Data for Tabs
try {
    $maintenanceLogs = db()->fetchAll("SELECT * FROM maintenance_logs WHERE lawn_id = ? ORDER BY log_date DESC", [$id]);
    $fertilizerLogs = db()->fetchAll("SELECT * FROM fertilizer_logs WHERE lawn_id = ? ORDER BY application_date DESC", [$id]);
} catch (Exception $e) {
    // Auto-fix: Create missing tables if they don't exist
    db()->query("CREATE TABLE IF NOT EXISTS maintenance_logs (id INT AUTO_INCREMENT PRIMARY KEY, lawn_id INT NOT NULL, activity_type VARCHAR(100), description TEXT, performed_by VARCHAR(255), log_date DATE, status ENUM('completed', 'pending', 'in_progress') DEFAULT 'completed', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (lawn_id) REFERENCES lawns(id) ON DELETE CASCADE, INDEX idx_lawn (lawn_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    db()->query("CREATE TABLE IF NOT EXISTS fertilizer_logs (id INT AUTO_INCREMENT PRIMARY KEY, lawn_id INT NOT NULL, fertilizer_name VARCHAR(255), fertilizer_type VARCHAR(100), quantity_kg DECIMAL(8,2), application_date DATE, applied_by VARCHAR(255), notes TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (lawn_id) REFERENCES lawns(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $maintenanceLogs = [];
    $fertilizerLogs = [];
}
$irrigationZones = db()->fetchAll("SELECT iz.*, irs.name as system_name FROM irrigation_zones iz LEFT JOIN irrigation_systems irs ON iz.system_id = irs.id WHERE iz.lawn_id = ?", [$id]);
$plants = db()->fetchAll("SELECT * FROM plants WHERE lawn_id = ? AND is_active = 1", [$id]);
$assignedStaff = db()->fetchAll("SELECT s.*, ls.role as assignment_role FROM lawn_staff ls JOIN staff s ON ls.staff_id = s.id WHERE ls.lawn_id = ? AND ls.status = 'active'", [$id]);

// Calculate Average Health
$healthAvg = round(($lawn['sustainability_score'] + $lawn['cleanliness_score'] + $lawn['maintenance_score'] + $lawn['grass_quality_score']) / 4);
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-area">
        <?php displayFlash(); ?>

        <!-- Command Header -->
        <div class="page-header d-flex justify-content-between align-items-center mb-4" data-aos="fade-down">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="categories.php" class="text-success">Categories</a></li>
                        <li class="breadcrumb-item active text-white-50"><?php echo e($lawn['category_name']); ?></li>
                    </ol>
                </nav>
                <h1 class="fw-bold mb-0"><?php echo e($lawn['name']); ?></h1>
                <p class="text-muted"><i class="fas fa-map-marker-alt me-1 text-success"></i> <?php echo e($lawn['location']); ?></p>
            </div>
            <div class="header-actions d-flex gap-2">
                <button class="btn btn-glass" onclick="window.print()"><i class="fas fa-print"></i></button>
                <a href="<?php echo url('modules/generate-report.php?id=' . $id . '&format=pdf'); ?>" class="btn btn-outline-green"><i class="fas fa-file-pdf me-2"></i>PDF Report</a>
                <?php if(hasPermission('lawns', 'edit')): ?>
                <button class="btn btn-primary-green" data-bs-toggle="modal" data-bs-target="#editLawnModal"><i class="fas fa-edit me-2"></i>Edit Details</button>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Core Analytics & Stats -->
            <div class="col-xl-4 col-lg-5" data-aos="fade-right">
                <!-- Health Overview Card -->
                <div class="card-glass mb-4 overflow-hidden">
                    <div class="card-body-custom p-4 text-center">
                        <div class="health-gauge-wrapper mb-4">
                            <h2 class="display-4 fw-bold mb-0"><?php echo $healthAvg; ?>%</h2>
                            <p class="text-muted small text-uppercase fw-bold">Overall Health Index</p>
                        </div>
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="healthRadarChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Resources Quick Stats -->
                <div class="row g-3 mb-4">
                    <div class="col-6">
                        <div class="stat-card-premium text-center p-3" style="--card-color: #40916c; --card-bg: rgba(64,145,108,0.1);">
                            <h3 class="mb-1"><?php echo $lawn['total_trees']; ?></h3>
                            <small class="text-muted">Trees</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card-premium text-center p-3" style="--card-color: #52b788; --card-bg: rgba(82,183,136,0.1);">
                            <h3 class="mb-1"><?php echo $lawn['total_plants']; ?></h3>
                            <small class="text-muted">Plants</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card-premium text-center p-3" style="--card-color: #f4a261; --card-bg: rgba(244,162,97,0.1);">
                            <h3 class="mb-1"><?php echo formatNumber($lawn['total_flowers']); ?></h3>
                            <small class="text-muted">Flowers</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card-premium text-center p-3" style="--card-color: #457b9d; --card-bg: rgba(69,123,157,0.1);">
                            <h3 class="mb-1">4.2k L</h3>
                            <small class="text-muted">Avg. Water</small>
                        </div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card-glass p-4 mb-4">
                    <h6 class="fw-bold mb-3 border-bottom border-white-10 pb-2">Technical Specifications</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Area Size</span>
                        <span class="fw-bold"><?php echo formatNumber($lawn['area_sqm']); ?> m²</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Irrigation Type</span>
                        <span class="fw-bold text-capitalize"><?php echo e($lawn['irrigation_type']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Soil Condition</span>
                        <span class="fw-bold text-capitalize text-warning"><?php echo e($lawn['soil_condition']); ?></span>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted d-block mb-1">Description:</small>
                        <p class="small text-white-50 mb-0"><?php echo e($lawn['description'] ?: 'No description provided.'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Right Column: Tabs & Detailed Lists -->
            <div class="col-xl-8 col-lg-7" data-aos="fade-left">
                <div class="card-glass h-100">
                    <!-- Premium Tabs Navigation -->
                    <div class="card-header-custom p-0 border-bottom border-white-10">
                        <ul class="nav nav-tabs border-0" id="lawnDetailTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active py-3 px-4 border-0" id="overview-tab" data-bs-toggle="tab" href="#overview" role="tab">Gallery</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-3 px-4 border-0" id="maintenance-tab" data-bs-toggle="tab" href="#maintenance" role="tab">Maintenance Logs</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-3 px-4 border-0" id="plants-tab" data-bs-toggle="tab" href="#plants" role="tab">Plants Catalog</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-3 px-4 border-0" id="staff-tab" data-bs-toggle="tab" href="#staff" role="tab">Assigned Staff</a>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body-custom p-4">
                        <div class="tab-content" id="lawnDetailTabsContent">
                            
                            <!-- Gallery Tab -->
                            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <div class="gallery-main rounded-4 overflow-hidden mb-3">
                                            <img src="<?php echo $lawn['image'] ? UPLOADS_URL.'/'.e($lawn['image']) : 'https://images.unsplash.com/photo-1558635924-b60e7d332925?w=800&q=80'; ?>" class="img-fluid w-100" style="height: 400px; object-fit: cover;">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="row g-3">
                                            <div class="col-12"><img src="https://images.unsplash.com/photo-1584473457406-6240486418e9?w=400&q=80" class="img-fluid rounded-4" style="height: 192px; width: 100%; object-fit: cover;"></div>
                                            <div class="col-12"><img src="https://images.unsplash.com/photo-1466692476868-aef1dfb1e735?w=400&q=80" class="img-fluid rounded-4" style="height: 192px; width: 100%; object-fit: cover;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Maintenance Logs Tab -->
                            <div class="tab-pane fade" id="maintenance" role="tabpanel">
                                <?php if(empty($maintenanceLogs)): ?>
                                    <div class="p-5 text-center text-muted">No maintenance logs recorded for this lawn.</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table-modern w-100">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Activity Type</th>
                                                    <th>Performed By</th>
                                                    <th>Description</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($maintenanceLogs as $log): ?>
                                                <tr>
                                                    <td><?php echo date('d M, Y', strtotime($log['log_date'])); ?></td>
                                                    <td class="fw-bold text-success text-capitalize"><?php echo e($log['activity_type']); ?></td>
                                                    <td><?php echo e($log['performed_by']); ?></td>
                                                    <td class="small text-muted"><?php echo e($log['description']); ?></td>
                                                    <td><span class="badge bg-success-soft"><?php echo ucfirst($log['status']); ?></span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Plants Tab -->
                            <div class="tab-pane fade" id="plants" role="tabpanel">
                                <div class="row g-3">
                                    <?php if(empty($plants)): ?>
                                        <div class="col-12 p-5 text-center text-muted">No plants registered in this zone.</div>
                                    <?php else: foreach($plants as $p): ?>
                                        <div class="col-md-6 col-lg-4">
                                            <div class="plant-card p-2 border border-white-10 rounded-4 d-flex align-items-center gap-3">
                                                <div class="position-relative overflow-hidden rounded-3" style="width: 60px; height: 60px; flex-shrink: 0;">
                                                    <img src="<?php echo $p['image'] ? UPLOADS_URL.'/'.e($p['image']) : 'https://images.unsplash.com/photo-1459411552884-841db9b3cc2a?w=100&q=80'; ?>" class="w-100 h-100" style="object-fit: cover;">
                                                    <div class="plant-img-overlay d-flex align-items-center justify-content-center gap-1">
                                                        <a href="<?php echo $p['image'] ? UPLOADS_URL.'/'.e($p['image']) : 'https://images.unsplash.com/photo-1459411552884-841db9b3cc2a?w=800&q=80'; ?>" class="btn btn-xs btn-glass text-white btn-view-image" data-title="<?php echo e($p['name']); ?>" title="View Full Image"><i class="fas fa-eye" style="font-size: 0.65rem;"></i></a>
                                                        <a href="<?php echo url('plants.php?search='.urlencode($p['name'])); ?>" class="btn btn-xs btn-glass text-white" title="Edit Plant"><i class="fas fa-edit" style="font-size: 0.65rem;"></i></a>
                                                    </div>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold"><?php echo e($p['name']); ?></h6>
                                                    <small class="text-success"><?php echo e($p['scientific_name'] ?: 'N/A'); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>

                            <!-- Staff Tab -->
                            <div class="tab-pane fade" id="staff" role="tabpanel">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0">Personnel Assigned to This Zone</h6>
                                    <button class="btn btn-sm btn-primary-green" data-bs-toggle="modal" data-bs-target="#assignStaffModal">
                                        <i class="fas fa-plus-circle me-1"></i>Assign Staff
                                    </button>
                                </div>
                                <div class="row g-3">
                                    <?php if(empty($assignedStaff)): ?>
                                        <div class="col-12 p-5 text-center text-muted">No staff assigned to this lawn.</div>
                                    <?php else: foreach($assignedStaff as $s): ?>
                                        <div class="col-md-6">
                                            <div class="card-glass border-white-5 p-3 d-flex align-items-center gap-3">
                                                <div class="avatar-placeholder" style="width: 50px; height: 50px;">
                                                    <?php echo strtoupper(substr($s['full_name'],0,1)); ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold"><?php echo e($s['full_name']); ?></h6>
                                                    <p class="mb-0 small text-success"><?php echo e($s['assignment_role'] ?: $s['designation']); ?></p>
                                                    <small class="text-muted"><i class="fas fa-phone-alt me-1"></i><?php echo e($s['phone']); ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>

                        </div><!-- /.tab-content -->
                    </div><!-- /.card-body-custom -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Lawn Modal -->
<div class="modal fade" id="editLawnModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold">Edit Lawn Specifications</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="modules/lawn-actions.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="<?php echo $lawn['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Lawn Identity Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo e($lawn['name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <?php $categories = db()->fetchAll("SELECT * FROM lawn_categories WHERE is_active = 1"); ?>
                            <select name="category_id" class="form-select">
                                <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $lawn['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Geographic Location / Zone</label>
                            <input type="text" name="location" class="form-control" value="<?php echo e($lawn['location']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Area (sq meters)</label>
                            <input type="number" step="0.01" name="area_sqm" class="form-control" value="<?php echo $lawn['area_sqm']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Irrigation System</label>
                            <select name="irrigation_type" class="form-select">
                                <option value="automatic" <?php echo $lawn['irrigation_type'] == 'automatic' ? 'selected' : ''; ?>>Automatic Sprinkler</option>
                                <option value="manual" <?php echo $lawn['irrigation_type'] == 'manual' ? 'selected' : ''; ?>>Manual Watering</option>
                                <option value="drip" <?php echo $lawn['irrigation_type'] == 'drip' ? 'selected' : ''; ?>>Drip System</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">General Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo e($lawn['description']); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Update Hero Image</label>
                            <input type="file" name="image" class="form-control">
                            <small class="text-white-50">Leave blank to keep existing image</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-glass text-white" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Staff Modal -->
<div class="modal fade" id="assignStaffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold">Assign Personnel to <?php echo e($lawn['name']); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="modules/lawn-actions.php" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="assign_staff">
                <input type="hidden" name="lawn_id" value="<?php echo $lawn['id']; ?>">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-white-50">Select Staff Member</label>
                        <?php $allStaff = db()->fetchAll("SELECT id, full_name, designation FROM staff WHERE status = 'active' ORDER BY full_name"); ?>
                        <select name="staff_id" class="form-select" required>
                            <option value="">-- Choose Staff --</option>
                            <?php foreach($allStaff as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo e($s['full_name']); ?> (<?php echo e($s['designation']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50">Assignment Role</label>
                        <select name="role" class="form-select">
                            <option value="Gardener">Gardener</option>
                            <option value="Supervisor">Supervisor</option>
                            <option value="Technician">Technician</option>
                            <option value="Security">Security</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Confirm Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .nav-tabs .nav-link { color: var(--text-muted); font-weight: 600; transition: 0.3s; }
    .nav-tabs .nav-link.active { background: rgba(82, 183, 136, 0.1); color: var(--accent-green); border-bottom: 2px solid var(--accent-green) !important; }
    .nav-tabs .nav-link:hover:not(.active) { color: #fff; background: rgba(255,255,255,0.05); }

    .health-gauge-wrapper h2 { text-shadow: 0 0 20px rgba(82, 183, 136, 0.4); }
    .bg-success-soft { background: rgba(45, 106, 79, 0.2); color: #52b788; border: 1px solid rgba(45, 106, 79, 0.3); }
    
    .border-white-5 { border-color: rgba(255,255,255,0.05) !important; }
    .border-white-10 { border-color: rgba(255,255,255,0.1) !important; }
    
    .plant-card { transition: 0.3s; cursor: default; }
    .plant-card:hover { background: rgba(255,255,255,0.05); transform: translateX(5px); }

    .plant-img-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.65);
        opacity: 0;
        transition: 0.25s ease;
        z-index: 5;
    }
    .plant-card:hover .plant-img-overlay {
        opacity: 1;
    }
    .plant-img-overlay .btn-glass {
        padding: 4px !important;
        border-radius: 4px;
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .plant-img-overlay .btn-glass:hover {
        background: var(--accent-green) !important;
        border-color: var(--accent-green) !important;
    }
</style>

<?php
$extraJS = '
<script>
    // Health Radar Chart
    const radarCtx = document.getElementById("healthRadarChart").getContext("2d");
    new Chart(radarCtx, {
        type: "radar",
        data: {
            labels: ["Sustainability", "Cleanliness", "Maintenance", "Grass Quality", "Water Efficiency"],
            datasets: [{
                label: "Performance Metrics",
                data: [
                    ' . $lawn['sustainability_score'] . ', 
                    ' . $lawn['cleanliness_score'] . ', 
                    ' . $lawn['maintenance_score'] . ', 
                    ' . $lawn['grass_quality_score'] . ', 
                    85
                ],
                backgroundColor: "rgba(82, 183, 136, 0.2)",
                borderColor: "#52b788",
                borderWidth: 2,
                pointBackgroundColor: "#52b788",
                pointBorderColor: "#fff",
                pointHoverRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    angleLines: { color: "rgba(255,255,255,0.1)" },
                    grid: { color: "rgba(255,255,255,0.1)" },
                    pointLabels: { color: "rgba(255,255,255,0.6)", font: { size: 10 } },
                    ticks: { display: false, max: 100, min: 0 }
                }
            },
            plugins: { legend: { display: false } }
        }
    });

    // Initialize Tooltips & Tabs
    var triggerTabList = [].slice.call(document.querySelectorAll(\'#lawnDetailTabs a\'))
    triggerTabList.forEach(function (triggerEl) {
        var tabTrigger = new bootstrap.Tab(triggerEl)
        triggerEl.addEventListener(\'click\', function (event) {
            event.preventDefault()
            tabTrigger.show()
        })
    });

    // Lightbox Modal for Centered Image Viewing (On-Page)
    function showImageLightbox(src, title = "Specimen Image") {
        let modalEl = document.getElementById("globalImageViewerModal");
        if (!modalEl) {
            const modalHTML = `
                <div class="modal fade" id="globalImageViewerModal" tabindex="-1" aria-hidden="true" style="z-index: 1099;">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content glass-panel border-0 text-white shadow-lg overflow-hidden" style="background: rgba(15, 15, 35, 0.95); border: 1px solid rgba(255,255,255,0.1) !important;">
                            <div class="modal-header border-bottom border-white-10 py-2">
                                <h6 class="modal-title fw-bold" id="globalImageViewerTitle">${title}</h6>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0 text-center bg-black d-flex align-items-center justify-content-center" style="min-height: 200px; max-height: 80vh;">
                                <img id="globalImageViewerImg" src="" class="img-fluid" style="max-height: 75vh; object-fit: contain; width: 100%;">
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML("beforeend", modalHTML);
            modalEl = document.getElementById("globalImageViewerModal");
        }
        document.getElementById("globalImageViewerImg").src = src;
        document.getElementById("globalImageViewerTitle").innerText = title;
        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
    }

    document.addEventListener("click", function(e) {
        const viewBtn = e.target.closest(".btn-view-image");
        if (viewBtn) {
            e.preventDefault();
            const src = viewBtn.getAttribute("href");
            const title = viewBtn.getAttribute("data-title") || "Specimen Image";
            showImageLightbox(src, title);
        }
    });
</script>';
include __DIR__ . '/includes/footer.php';
?>
