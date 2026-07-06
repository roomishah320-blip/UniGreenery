<?php
/**
 * Reports Page — Advanced Audit & Data Export
 * Step 14: Advanced Reporting System
 */
define('GREENERY_APP', true);
$pageTitle = 'Greenery Audit & Reports';
require_once __DIR__ . '/includes/header.php';
requireAuth();
requirePermission('reports');

// Default values
$reportType = sanitize($_GET['type'] ?? 'lawns');
$startDate = sanitize($_GET['start_date'] ?? date('Y-m-01'));
$endDate = sanitize($_GET['end_date'] ?? date('Y-m-d'));

// Fetch Preview Data (Limited to 10 for preview)
$previewData = [];
$summary = ['total' => 0, 'metric' => ''];

switch($reportType) {
    case 'lawns':
        $previewData = db()->fetchAll("SELECT name, location, area_sqm, sustainability_score, created_at FROM lawns ORDER BY sustainability_score DESC LIMIT 10");
        $summary['total'] = db()->count("SELECT COUNT(*) FROM lawns");
        $summary['metric'] = 'Total Lawns Registered';
        break;
    case 'plants':
        $previewData = db()->fetchAll("SELECT name, scientific_name, category, health_status, planted_date FROM plants ORDER BY planted_date DESC LIMIT 10");
        $summary['total'] = db()->count("SELECT COUNT(*) FROM plants");
        $summary['metric'] = 'Botanical Assets';
        break;
    case 'irrigation':
        $previewData = db()->fetchAll("SELECT zone_name, water_usage_liters, status, schedule_time FROM irrigation_zones ORDER BY water_usage_liters DESC LIMIT 10");
        $summary['total'] = db()->fetchOne("SELECT SUM(water_usage_liters) as total FROM irrigation_zones")['total'];
        $summary['metric'] = 'Liters Consumed';
        break;
    case 'maintenance':
        $previewData = db()->fetchAll("SELECT activity_type, performed_by, log_date, description FROM maintenance_logs WHERE log_date BETWEEN ? AND ? ORDER BY log_date DESC LIMIT 10", [$startDate, $endDate]);
        $summary['total'] = db()->count("SELECT COUNT(*) FROM maintenance_logs WHERE log_date BETWEEN ? AND ?", [$startDate, $endDate]);
        $summary['metric'] = 'Activities in Period';
        break;
}
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-area">
        <?php displayFlash(); ?>

        <!-- Page Header -->
        <div class="page-header" data-aos="fade-down">
            <div>
                <h1><i class="fas fa-file-invoice me-2 text-warning"></i>Greenery Reporting Suite</h1>
                <p class="text-muted mb-0">Generate executive performance audits and export university greenery data.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-glass" onclick="window.print()"><i class="fas fa-print me-2"></i>Quick Print</button>
            </div>
        </div>

        <div class="row g-4">
            <!-- Report Configuration -->
            <div class="col-xl-4" data-aos="fade-right">
                <div class="card-glass h-100">
                    <div class="card-header-custom p-4 border-bottom border-white-10">
                        <h5 class="fw-bold mb-0">Audit Configuration</h5>
                    </div>
                    <div class="card-body-custom p-4">
                        <form action="" method="GET">
                            <div class="mb-3">
                                <label class="form-label text-white-50">Report Template</label>
                                <select name="type" class="form-select" onchange="this.form.submit()">
                                    <option value="lawns" <?php echo $reportType == 'lawns' ? 'selected' : ''; ?>>Lawn Performance Audit</option>
                                    <option value="plants" <?php echo $reportType == 'plants' ? 'selected' : ''; ?>>Botanical Inventory</option>
                                    <option value="irrigation" <?php echo $reportType == 'irrigation' ? 'selected' : ''; ?>>Irrigation & Water Log</option>
                                    <option value="maintenance" <?php echo $reportType == 'maintenance' ? 'selected' : ''; ?>>Maintenance Activity Log</option>
                                </select>
                            </div>

                            <div class="row g-2 mb-4">
                                <div class="col-6">
                                    <label class="form-label text-white-50">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label text-white-50">End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                                </div>
                            </div>

                            <div class="report-summary-box p-3 rounded-4 bg-white-5 border border-white-5 mb-4">
                                <h6 class="text-success small fw-bold text-uppercase mb-1">Preview Summary</h6>
                                <h2 class="mb-0 fw-bold"><?php echo is_numeric($summary['total']) ? formatNumber($summary['total']) : $summary['total']; ?></h2>
                                <small class="text-muted"><?php echo $summary['metric']; ?></small>
                            </div>

                            <button type="submit" class="btn btn-outline-green w-100 mb-3"><i class="fas fa-sync-alt me-2"></i>Refresh Preview</button>
                            
                            <hr class="border-white-10 mb-4">

                            <h6 class="fw-bold mb-3 small text-uppercase text-white-50">Download Formats</h6>
                            <div class="d-grid gap-2">
                                <a href="modules/generate-report.php?format=pdf&type=<?php echo $reportType; ?>&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>" class="btn btn-primary-green">
                                    <i class="fas fa-file-pdf me-2"></i>Generate PDF Report
                                </a>
                                <a href="modules/generate-report.php?format=excel&type=<?php echo $reportType; ?>&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>" class="btn btn-glass text-white">
                                    <i class="fas fa-file-excel me-2"></i>Export Excel (CSV)
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Live Preview Table -->
            <div class="col-xl-8" data-aos="fade-left">
                <div class="card-glass h-100">
                    <div class="card-header-custom p-4 border-bottom border-white-10 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">Live Audit Preview</h5>
                        <span class="badge bg-white-5 text-white-50 px-3 py-2">Showing Latest 10 Records</span>
                    </div>
                    <div class="card-body-custom p-0">
                        <div class="table-responsive">
                            <table class="table-modern w-100">
                                <thead class="bg-white-5">
                                    <tr>
                                        <?php if($reportType == 'lawns'): ?>
                                            <th>Lawn Name</th>
                                            <th>Location</th>
                                            <th>Area (m²)</th>
                                            <th>Score</th>
                                            <th>Created</th>
                                        <?php elseif($reportType == 'plants'): ?>
                                            <th>Plant Name</th>
                                            <th>Scientific Name</th>
                                            <th>Category</th>
                                            <th>Health</th>
                                            <th>Planted</th>
                                        <?php elseif($reportType == 'irrigation'): ?>
                                            <th>Zone Name</th>
                                            <th>Usage (L)</th>
                                            <th>Status</th>
                                            <th>Schedule</th>
                                        <?php else: ?>
                                            <th>Activity</th>
                                            <th>By Staff</th>
                                            <th>Date</th>
                                            <th>Details</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($previewData)): ?>
                                        <tr><td colspan="5" class="text-center py-5 text-muted">No data found for the selected criteria.</td></tr>
                                    <?php else: foreach($previewData as $row): ?>
                                        <tr>
                                            <?php foreach($row as $key => $val): ?>
                                                <td class="<?php echo is_numeric($val) ? 'fw-bold' : ''; ?>">
                                                    <?php 
                                                        if($key == 'health_status' || $key == 'status') echo '<span class="badge bg-white-5 border border-white-10 text-white-50">'.ucfirst($val).'</span>';
                                                        else echo e($val); 
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white-5 p-3 text-center">
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Previews are limited to 10 rows. Download the full report to see all data.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-white-5 { background: rgba(255,255,255,0.05); }
    .border-white-10 { border-color: rgba(255,255,255,0.1) !important; }
    .report-summary-box { border-left: 4px solid var(--accent-green) !important; }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
