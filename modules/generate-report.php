<?php
/**
 * Report Generator (placeholder)
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAuth();

$type = sanitize($_GET['type'] ?? 'general');
$format = sanitize($_GET['format'] ?? 'pdf');

// For now, generate a simple HTML report that can be printed
$reportData = [];

switch ($type) {
    case 'plantation':
        $reportData['title'] = 'Plantation Report';
        $reportData['data'] = db()->fetchAll("SELECT l.name, l.total_trees, l.total_plants, l.total_flowers, l.area_sqm, lc.name as category FROM lawns l LEFT JOIN lawn_categories lc ON l.category_id = lc.id WHERE l.status = 'active' ORDER BY l.name");
        break;
    case 'irrigation':
        $reportData['title'] = 'Irrigation Report';
        $reportData['data'] = db()->fetchAll("SELECT iz.zone_name, irs.name as system_name, l.name as lawn_name, iz.water_usage_liters, iz.status FROM irrigation_zones iz LEFT JOIN irrigation_systems irs ON iz.system_id = irs.id LEFT JOIN lawns l ON iz.lawn_id = l.id ORDER BY iz.zone_name");
        break;
    case 'ranking':
        $reportData['title'] = 'Rankings Report';
        $reportData['data'] = db()->fetchAll("SELECT l.name, lc.name as category, l.sustainability_score, l.cleanliness_score, l.maintenance_score, l.grass_quality_score FROM lawns l LEFT JOIN lawn_categories lc ON l.category_id = lc.id WHERE l.status = 'active' ORDER BY (l.sustainability_score+l.cleanliness_score+l.maintenance_score+l.grass_quality_score)/4 DESC");
        break;
    case 'staff':
        $reportData['title'] = 'Staff Report';
        $reportData['data'] = db()->fetchAll("SELECT s.full_name, s.employee_id, s.designation, s.department, s.status, s.performance_score FROM staff s ORDER BY s.full_name");
        break;
    default:
        $reportData['title'] = 'General Report';
        $reportData['data'] = [];
}

// Log report generation
logActivity('generate', "Generated {$reportData['title']} ($format)", 'reports');

// Store report record
db()->insert("INSERT INTO reports (title, report_type, format, generated_by) VALUES (?,?,?,?)",
    [$reportData['title'] . ' - ' . date('d M Y'), $type, $format, $_SESSION['user_id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo e($reportData['title']); ?> - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', Arial, sans-serif; padding: 30px; }
        .report-header { text-align: center; margin-bottom: 30px; border-bottom: 3px solid #2d6a4f; padding-bottom: 20px; }
        .report-header h1 { color: #2d6a4f; font-size: 1.6rem; }
        .report-header p { color: #666; font-size: 0.85rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #2d6a4f; color: #fff; padding: 10px 14px; font-size: 0.82rem; text-align: left; }
        td { padding: 10px 14px; border-bottom: 1px solid #e0e0e0; font-size: 0.85rem; }
        tr:nth-child(even) { background: #f8f9fa; }
        @media print { .no-print { display: none; } body { padding: 10px; } }
    </style>
</head>
<body>
    <div class="no-print mb-3">
        <button onclick="window.print()" class="btn btn-success btn-sm"><i class="fas fa-print"></i> Print Report</button>
        <button onclick="window.close()" class="btn btn-secondary btn-sm">Close</button>
    </div>
    <div class="report-header">
        <h1><?php echo e($reportData['title']); ?></h1>
        <p><?php echo SITE_NAME; ?> | Generated: <?php echo date('d M Y, h:i A'); ?></p>
    </div>
    <?php if (!empty($reportData['data'])): ?>
    <table>
        <thead><tr>
            <?php foreach (array_keys($reportData['data'][0]) as $col): ?>
            <th><?php echo ucwords(str_replace('_', ' ', $col)); ?></th>
            <?php endforeach; ?>
        </tr></thead>
        <tbody>
            <?php foreach ($reportData['data'] as $row): ?>
            <tr><?php foreach ($row as $val): ?><td><?php echo e($val ?? 'N/A'); ?></td><?php endforeach; ?></tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p class="text-center text-muted mt-5">No data available for this report.</p>
    <?php endif; ?>
    <div style="margin-top:40px;text-align:center;color:#999;font-size:0.75rem">
        <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?> — Official Report</p>
    </div>
</body>
</html>
