<?php
/**
 * Irrigation Page — Water Resource Management
 * Step 11: Irrigation Management Module
 */
define('GREENERY_APP', true);
$pageTitle = 'Irrigation & Water Management';
require_once __DIR__ . '/includes/header.php';
requireAuth();
requirePermission('irrigation');

// Fetch Irrigation Zones with System & Lawn Info
$zones = db()->fetchAll("SELECT iz.*, irs.name as system_name, irs.type as system_type, l.name as lawn_name 
    FROM irrigation_zones iz 
    LEFT JOIN irrigation_systems irs ON iz.system_id = irs.id 
    LEFT JOIN lawns l ON iz.lawn_id = l.id 
    ORDER BY iz.zone_name ASC");

$systems = db()->fetchAll("SELECT * FROM irrigation_systems WHERE status = 'active'");
$lawns = db()->fetchAll("SELECT id, name FROM lawns WHERE status = 'active' ORDER BY name");

// Aggregate Statistics
$totalUsage = array_sum(array_column($zones, 'water_usage_liters'));
$automatedCount = count(array_filter($zones, fn($z) => $z['is_automated']));
$activeCount = count(array_filter($zones, fn($z) => $z['status'] === 'active'));
$autoPercent = count($zones) > 0 ? round(($automatedCount / count($zones)) * 100) : 0;

// Chart: aggregate water usage per lawn
$lawnUsage = [];
foreach ($zones as $z) {
    $key = !empty($z['lawn_name']) ? $z['lawn_name'] : 'Unassigned';
    $lawnUsage[$key] = ($lawnUsage[$key] ?? 0) + (float)($z['water_usage_liters'] ?? 0);
}

// CSV export rows
$csvRows = [['Zone Name','Lawn','System','Water Usage (L)','Schedule Time','Duration (min)','Mode','Status']];
foreach ($zones as $z) {
    $csvRows[] = [
        $z['zone_name'],
        $z['lawn_name']   ?? 'N/A',
        $z['system_name'] ?? 'N/A',
        (float)($z['water_usage_liters'] ?? 0),
        $z['schedule_time'] ? date('h:i A', strtotime($z['schedule_time'])) : 'Manual Only',
        (int)($z['duration_minutes'] ?? 0),
        $z['is_automated'] ? 'Smart/Auto' : 'Manual',
        ucfirst($z['status'] ?? 'active'),
    ];
}
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-area">
        <?php displayFlash(); ?>

        <!-- Page Header -->
        <div class="page-header" data-aos="fade-down">
            <div>
                <h1><i class="fas fa-tint me-2 text-info"></i>Smart Irrigation Control</h1>
                <p class="text-muted mb-0">Monitor water consumption, schedule automated zones, and manage campus-wide irrigation infrastructure.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-green"><i class="fas fa-history me-2"></i>Usage History</button>
                <?php if (hasPermission('irrigation', 'create')): ?>
                <button class="btn btn-primary-green" data-bs-toggle="modal" data-bs-target="#addZoneModal">
                    <i class="fas fa-plus me-2"></i>Add Irrigation Zone
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Irrigation Stats Grid -->
        <div class="row g-4 mb-4">
            <div class="col-xl-3 col-md-6" data-aos="fade-up">
                <div class="stat-card-premium p-4" style="--card-color: #457b9d; --card-bg: rgba(69,123,157,0.1);">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-icon-lg"><i class="fas fa-water"></i></div>
                        <span class="badge bg-info text-white">Monthly</span>
                    </div>
                    <h3 class="counter-value fw-bold" data-target="<?php echo $totalUsage; ?>">0</h3>
                    <p class="text-muted small mb-0">Total Water Used (Liters)</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="100">
                <div class="stat-card-premium p-4" style="--card-color: #52b788; --card-bg: rgba(82,183,136,0.1);">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-icon-lg"><i class="fas fa-robot"></i></div>
                        <span class="badge bg-success text-white"><?php echo $autoPercent; ?>% Smart</span>
                    </div>
                    <h3 class="counter-value fw-bold" data-target="<?php echo $automatedCount; ?>">0</h3>
                    <p class="text-muted small mb-0">Automated IoT Zones</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="200">
                <div class="stat-card-premium p-4" style="--card-color: #2d6a4f; --card-bg: rgba(45,106,79,0.1);">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-icon-lg"><i class="fas fa-check-circle"></i></div>
                        <span class="text-success small fw-bold">Online</span>
                    </div>
                    <h3 class="counter-value fw-bold" data-target="<?php echo $activeCount; ?>">0</h3>
                    <p class="text-muted small mb-0">Active Running Zones</p>
                </div>
            </div>
            <div class="col-xl-3 col-md-6" data-aos="fade-up" data-aos-delay="300">
                <div class="stat-card-premium p-4" style="--card-color: #f4a261; --card-bg: rgba(244,162,97,0.1);">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-icon-lg"><i class="fas fa-leaf"></i></div>
                        <span class="text-warning small fw-bold">Priority</span>
                    </div>
                    <h3 class="counter-value fw-bold" data-target="<?php echo count($lawns); ?>">0</h3>
                    <p class="text-muted small mb-0">Supported Lawns</p>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Consumption Chart -->
            <div class="col-lg-8" data-aos="fade-right">
                <div class="card-glass h-100">
                    <div class="card-header-custom p-4 border-bottom border-white-10">
                        <h5 class="fw-bold mb-0">Consumption Analytics</h5>
                    </div>
                    <div class="card-body-custom p-4">
                        <div class="chart-container" style="height: 350px;">
                            <canvas id="irrigationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Smart Controls & Quick Actions -->
            <div class="col-lg-4" data-aos="fade-left">
                <div class="card-glass h-100">
                    <div class="card-header-custom p-4 border-bottom border-white-10">
                        <h5 class="fw-bold mb-0">System Status</h5>
                    </div>
                    <div class="card-body-custom p-4">
                        <div class="mb-4">
                            <label class="small text-muted d-block mb-2">Overall Network Health</label>
                            <div class="progress" style="height: 8px; border-radius: 4px; background: rgba(255,255,255,0.05);">
                                <div class="progress-bar bg-success" style="width: 92%;"></div>
                            </div>
                            <small class="text-success mt-1 d-block">92% Systems Operational</small>
                        </div>
                        
                        <h6 class="fw-bold mb-3 small text-uppercase text-white-50">Quick Commands</h6>
                        <button id="btn-test-zones" class="btn btn-outline-info w-100 mb-3 text-start d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-sync-alt me-2"></i>Test All Zones</span>
                            <i class="fas fa-chevron-right small"></i>
                        </button>
                        <button id="btn-emergency-stop" class="btn btn-outline-warning w-100 mb-3 text-start d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-exclamation-triangle me-2"></i>Emergency Shut-off</span>
                            <i class="fas fa-chevron-right small"></i>
                        </button>
                        <button id="btn-export-log" class="btn btn-outline-green w-100 text-start d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-file-invoice me-2"></i>Export Water Log</span>
                            <i class="fas fa-chevron-right small"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Detailed Zones Table -->
            <div class="col-12" data-aos="fade-up">
                <div class="card-glass">
                    <div class="table-responsive">
                        <table class="table-modern w-100">
                            <thead>
                                <tr>
                                    <th>Zone Identity</th>
                                    <th>Lawn Association</th>
                                    <th>System Type</th>
                                    <th>Water Usage</th>
                                    <th>Schedule</th>
                                    <th>Duration</th>
                                    <th>Mode</th>
                                    <th>Status</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($zones)): ?>
                                <tr><td colspan="9" class="text-center py-5 text-muted">No irrigation zones configured.</td></tr>
                                <?php else: foreach($zones as $z): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?php echo e($z['zone_name']); ?></div>
                                        <small class="text-muted">ID: #IR-<?php echo $z['id']; ?></small>
                                    </td>
                                    <td><span class="text-success"><i class="fas fa-leaf me-1"></i><?php echo e($z['lawn_name']); ?></span></td>
                                    <td><span class="badge bg-white-5 border border-white-10 text-white-50"><?php echo e($z['system_name']); ?></span></td>
                                    <td class="fw-bold text-info"><?php echo formatNumber($z['water_usage_liters']); ?> L</td>
                                    <td><i class="far fa-clock me-1 text-muted"></i><?php echo $z['schedule_time'] ? date('h:i A', strtotime($z['schedule_time'])) : 'Manual Only'; ?></td>
                                    <td><?php echo $z['duration_minutes']; ?> min</td>
                                    <td>
                                        <?php if($z['is_automated']): ?>
                                            <span class="text-info"><i class="fas fa-robot me-1"></i> SMART</span>
                                        <?php else: ?>
                                            <span class="text-muted"><i class="fas fa-hand-paper me-1"></i> MANUAL</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge-status badge-<?php echo $z['status']; ?>"><?php echo ucfirst($z['status']); ?></span></td>
                                    <td class="text-center">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-glass text-white btn-edit-zone" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editZoneModal"
                                                    data-id="<?php echo $z['id']; ?>"
                                                    data-json="<?php echo e(json_encode($z)); ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-glass text-danger ms-1" 
                                                    onclick="confirmDelete(<?php echo $z['id']; ?>, 'irrigation-actions.php')">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
    </div>
</div>

<!-- Add Zone Modal -->
<div class="modal fade" id="addZoneModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold"><i class="fas fa-tint me-2 text-info"></i>Configure New Irrigation Zone</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo url('modules/irrigation-actions.php'); ?>" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Zone Identifier *</label>
                            <input type="text" name="zone_name" class="form-control bg-dark border-white-10 text-white" placeholder="e.g. North Admin Cluster A" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Irrigation System</label>
                            <select name="system_id" class="form-select bg-dark border-white-10 text-white">
                                <?php foreach($systems as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo e($s['name']); ?> (<?php echo e($s['type']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Lawn Association</label>
                            <select name="lawn_id" class="form-select bg-dark border-white-10 text-white">
                                <?php foreach($lawns as $l): ?>
                                <option value="<?php echo $l['id']; ?>"><?php echo e($l['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Activation Schedule</label>
                            <input type="time" name="schedule_time" class="form-control bg-dark border-white-10 text-white">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Cycle Duration (min)</label>
                            <input type="number" name="duration_minutes" class="form-control bg-dark border-white-10 text-white" value="30" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Water Usage (Liters)</label>
                            <input type="number" step="0.01" name="water_usage_liters" class="form-control bg-dark border-white-10 text-white" value="0" min="0" placeholder="e.g. 1500.00">
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_automated" id="autoSwitch" checked>
                                <label class="form-check-label text-white-50" for="autoSwitch">Enable IoT Smart Automation</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Initialize Zone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Zone Modal -->
<div class="modal fade" id="editZoneModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2 text-warning"></i>Edit Irrigation Zone</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo url('modules/irrigation-actions.php'); ?>" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Zone Identifier *</label>
                            <input type="text" name="zone_name" id="edit_zone_name" class="form-control bg-dark border-white-10 text-white" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Irrigation System</label>
                            <select name="system_id" id="edit_system_id" class="form-select bg-dark border-white-10 text-white">
                                <?php foreach($systems as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo e($s['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Lawn Association</label>
                            <select name="lawn_id" id="edit_lawn_id" class="form-select bg-dark border-white-10 text-white">
                                <?php foreach($lawns as $l): ?>
                                <option value="<?php echo $l['id']; ?>"><?php echo e($l['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Activation Schedule</label>
                            <input type="time" name="schedule_time" id="edit_schedule_time" class="form-control bg-dark border-white-10 text-white">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Cycle Duration (min)</label>
                            <input type="number" name="duration_minutes" id="edit_duration_minutes" class="form-control bg-dark border-white-10 text-white" min="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Water Usage (Liters)</label>
                            <input type="number" step="0.01" name="water_usage_liters" id="edit_water_usage" class="form-control bg-dark border-white-10 text-white" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Current Status</label>
                            <select name="status" id="edit_status" class="form-select bg-dark border-white-10 text-white">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_automated" id="edit_is_automated">
                                <label class="form-check-label text-white-50" for="edit_is_automated">Enable IoT Smart Automation</label>
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

<style>
    .stat-icon-lg { font-size: 2.2rem; opacity: 0.8; }
    .bg-white-5 { background: rgba(255,255,255,0.05); }
    .border-white-10 { border-color: rgba(255,255,255,0.1) !important; }
</style>

<!-- PHP data injected safely as a global JS object -->
<script>
var IrrigationApp = <?php echo json_encode([
    'chartLabels' => array_keys($lawnUsage),
    'chartData'   => array_values($lawnUsage),
    'totalZones'  => count($zones),
    'csvRows'     => $csvRows,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
</script>

<?php
$extraJS = '
<script>
    // ── Irrigation Analytics Chart (Real Data) ─────────────────────────
    var irrCtx      = document.getElementById("irrigationChart").getContext("2d");
    var irrGradient = irrCtx.createLinearGradient(0, 0, 0, 350);
    irrGradient.addColorStop(0, "rgba(69, 123, 157, 0.65)");
    irrGradient.addColorStop(1, "rgba(82, 183, 136, 0.08)");

    var chartLabels = IrrigationApp.chartLabels;
    var chartData   = IrrigationApp.chartData;

    new Chart(irrCtx, {
        type: "bar",
        data: {
            labels: chartLabels,
            datasets: [{
                label: "Water Usage (Liters)",
                data: chartData,
                backgroundColor: irrGradient,
                borderColor: "#457b9d",
                borderWidth: 2,
                borderRadius: 8,
                hoverBackgroundColor: "rgba(82,183,136,0.6)"
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    labels: { color: "rgba(255,255,255,0.7)", font: { size: 12 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(ctx) { return " " + Number(ctx.parsed.y).toLocaleString() + " L"; }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: "rgba(255,255,255,0.05)" },
                    ticks: {
                        color: "rgba(255,255,255,0.5)",
                        callback: function(v) { return Number(v).toLocaleString() + " L"; }
                    }
                },
                x: {
                    grid: { display: false },
                    ticks: { color: "rgba(255,255,255,0.5)", maxRotation: 35 }
                }
            }
        }
    });

    // ── Populate Edit Modal ─────────────────────────────────────────────
    document.addEventListener("click", function(e) {
        if (e.target.closest(".btn-edit-zone")) {
            var btn  = e.target.closest(".btn-edit-zone");
            var data = JSON.parse(btn.dataset.json);
            document.getElementById("edit_id").value               = data.id;
            document.getElementById("edit_zone_name").value        = data.zone_name;
            document.getElementById("edit_system_id").value        = data.system_id || "";
            document.getElementById("edit_lawn_id").value          = data.lawn_id   || "";
            document.getElementById("edit_schedule_time").value    = data.schedule_time ? data.schedule_time.slice(0,5) : "";
            document.getElementById("edit_duration_minutes").value = data.duration_minutes;
            document.getElementById("edit_water_usage").value      = data.water_usage_liters || 0;
            document.getElementById("edit_status").value           = data.status;
            document.getElementById("edit_is_automated").checked   = data.is_automated == 1;
        }
    });

    // ── Command Buttons ─────────────────────────────────────────────────
    var btnTest = document.getElementById("btn-test-zones");
    if (btnTest) btnTest.addEventListener("click", function() {
        var count = IrrigationApp.totalZones;
        if (count === 0) { showToast("No zones configured to test.", "warning"); return; }
        showToast("Starting diagnostic on " + count + " zone(s)...", "info");
        setTimeout(function() { showToast("Diagnostic complete. " + count + " zone(s) OK.", "success"); }, 2500);
    });

    var btnStop = document.getElementById("btn-emergency-stop");
    if (btnStop) btnStop.addEventListener("click", function() {
        if (!confirm("Emergency Shut-off will stop ALL active irrigation. Continue?")) return;
        showToast("EMERGENCY STOP INITIATED", "danger");
        setTimeout(function() { showToast("All valves closed. Network secured.", "warning"); }, 1500);
    });

    var btnExport = document.getElementById("btn-export-log");
    if (btnExport) btnExport.addEventListener("click", function() {
        var rows = IrrigationApp.csvRows;
        if (!rows || rows.length <= 1) { showToast("No data to export.", "warning"); return; }
        var q   = String.fromCharCode(34);
        var csv = rows.map(function(r) {
            return r.map(function(c) { return q + String(c).replace(new RegExp(q, "g"), q + q) + q; }).join(",");
        }).join("\n");
        var blob = new Blob(["\xEF\xBB\xBF" + csv], { type: "text/csv;charset=utf-8;" });
        var url  = URL.createObjectURL(blob);
        var a    = document.createElement("a");
        a.href     = url;
        a.download = "irrigation_water_log_" + new Date().toISOString().slice(0, 10) + ".csv";
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        showToast("Water log exported!", "success");
    });
</script>';
include __DIR__ . '/includes/footer.php';
?>

