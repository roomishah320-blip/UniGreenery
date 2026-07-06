<?php
/**
 * Dashboard Page — Premium Admin Dashboard
 * Step 3: Modern Admin Dashboard
 */
define('GREENERY_APP', true);
$pageTitle = 'Dashboard Analytics';
require_once __DIR__ . '/includes/header.php';
requireAuth();

// Setup dynamic Chart JS configurations depending on role (moved up for conditional queries)
$roleSlug = $_SESSION['role_slug'] ?? 'public_user';

// Only fetch activity logs for administrative roles
$adminRoles = ['super_admin', 'vice_chancellor', 'registrar', 'state_officer', 'plantation_officer', 'staff_mgmt_officer'];

// Fetch Real-time Statistics
$stats = getDashboardStats();
$recentActivities = in_array($roleSlug, $adminRoles)
    ? db()->fetchAll("SELECT a.*, u.full_name, u.avatar FROM activities a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 10")
    : [];
$upcomingDrives = db()->fetchAll("SELECT * FROM plantation_drives WHERE status != 'completed' ORDER BY drive_date ASC LIMIT 4");

// Setup dynamic Chart JS configurations depending on role
$roleSlug = $_SESSION['role_slug'] ?? 'public_user';
$currentYear = date('Y');

// === LINE CHART: Cumulative trees & plants added per month (current year) ===
$treeRows = db()->fetchAll(
    "SELECT MONTH(created_at) as mo, COUNT(*) as cnt
     FROM plants WHERE category = 'tree' AND is_active = 1 AND YEAR(created_at) = ?
     GROUP BY MONTH(created_at)",
    [$currentYear]
);
$plantRows = db()->fetchAll(
    "SELECT MONTH(created_at) as mo, COUNT(*) as cnt
     FROM plants WHERE category != 'tree' AND is_active = 1 AND YEAR(created_at) = ?
     GROUP BY MONTH(created_at)",
    [$currentYear]
);
$treeMonthly = array_fill(1, 12, 0);
$plantMonthly = array_fill(1, 12, 0);
foreach ($treeRows  as $r) $treeMonthly[(int)$r['mo']]  = (int)$r['cnt'];
foreach ($plantRows as $r) $plantMonthly[(int)$r['mo']] = (int)$r['cnt'];
$treeCumulative = []; $plantCumulative = [];
$tSum = 0; $pSum = 0;
for ($m = 1; $m <= 12; $m++) {
    $tSum += $treeMonthly[$m];  $treeCumulative[]  = $tSum;
    $pSum += $plantMonthly[$m]; $plantCumulative[] = $pSum;
}

// === DOUGHNUT CHART: Active irrigation zones by system type ===
$irrTypeRows = db()->fetchAll(
    "SELECT COALESCE(s.type, 'manual') as sys_type, COUNT(z.id) as cnt
     FROM irrigation_zones z
     LEFT JOIN irrigation_systems s ON z.system_id = s.id
     WHERE z.status = 'active'
     GROUP BY COALESCE(s.type, 'manual')"
);
$typeMap = [
    'sprinkler'  => ['label' => 'Sprinkler',  'color' => '#4f46e5'],
    'drip'       => ['label' => 'Drip',       'color' => '#8b5cf6'],
    'smart'      => ['label' => 'IoT Smart',  'color' => '#d946ef'],
    'manual'     => ['label' => 'Manual',     'color' => '#f43f5e'],
    'surface'    => ['label' => 'Surface',    'color' => '#f59e0b'],
    'subsurface' => ['label' => 'Subsurface', 'color' => '#06b6d4'],
];
$dLabels = []; $dData = []; $dColors = [];
foreach ($irrTypeRows as $r) {
    $t = $r['sys_type'];
    $dLabels[] = $typeMap[$t]['label'] ?? ucfirst($t);
    $dData[]   = (int)$r['cnt'];
    $dColors[] = $typeMap[$t]['color'] ?? '#94a3b8';
}
if (empty($dData)) { $dLabels = ['No Zones Yet']; $dData = [1]; $dColors = ['#334155']; }

// Default chart variables (all roles except overrides below)
$lineLabel1    = "Trees (Cumulative)";
$lineLabel2    = "Plants (Cumulative)";
$lineData1     = json_encode($treeCumulative);
$lineData2     = json_encode($plantCumulative);
$chartTitle    = "Plantation Growth Trends " . $currentYear;
$chartSubtitle = "Cumulative trees & plants added month-by-month this year";
$doughnutLabels = json_encode($dLabels);
$doughnutData   = json_encode($dData);
$doughnutColors = json_encode($dColors);
$doughnutTitle  = "Water Zone Distribution";

if ($roleSlug === 'treasurer_officer') {
    $lineLabel1    = "Allocated Budget (k\u20b9)";
    $lineLabel2    = "Actual Expenses (k\u20b9)";
    $lineData1     = "[50, 50, 50, 50, 60, 60, 60, 60, 60, 70, 70, 70]";
    $lineData2     = "[35, 42, 38, 45, 52, 58, 48, 55, 50, 62, 65, 68]";
    $chartTitle    = "Budget & Expenses Management";
    $chartSubtitle = "Monthly budget allocation vs operational expenses";
    $doughnutLabels = '["Staff Salaries", "Irrigation Projects", "Plant Purchases", "Maintenance Tools"]';
    $doughnutData   = '[40, 25, 20, 15]';
    $doughnutColors = '["#2a9d8f", "#e76f51", "#f4a261", "#e9c46a"]';
    $doughnutTitle  = "Expense Distribution";
} elseif ($roleSlug === 'irrigation_officer') {
    // Real monthly water usage in kL
    $waterRows = db()->fetchAll(
        "SELECT MONTH(updated_at) as mo, ROUND(SUM(water_usage_liters)/1000, 2) as kl
         FROM irrigation_zones WHERE status = 'active' AND YEAR(updated_at) = ?
         GROUP BY MONTH(updated_at)",
        [$currentYear]
    );
    $waterMonthly = array_fill(1, 12, 0);
    foreach ($waterRows as $r) $waterMonthly[(int)$r['mo']] = (float)$r['kl'];
    $waterArr = [];
    for ($m = 1; $m <= 12; $m++) $waterArr[] = $waterMonthly[$m];
    $totalZones = array_sum($dData);

    $lineLabel1    = "Water Consumed (kL)";
    $lineLabel2    = "Active Zones";
    $lineData1     = json_encode($waterArr);
    $lineData2     = json_encode(array_fill(0, 12, $totalZones));
    $chartTitle    = "Water Consumption Logs " . $currentYear;
    $chartSubtitle = "Monthly water usage across all active irrigation zones";
    $doughnutTitle = "Active Zone Types";
}
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<!-- Main Content Wrapper -->
<div class="main-content">
    <div class="content-area">

        <?php displayFlash(); ?>

        <!-- Welcome & Weather Header -->
        <div class="row g-4 mb-4 align-items-end" data-aos="fade-down">
            <div class="col-lg-8">
                <div class="welcome-banner p-4">
                    <h1 class="display-6 fw-bold mb-1">Hello, <?php echo e(explode(' ', $_SESSION['full_name'])[0]); ?>! 👋</h1>
                    <p class="text-secondary mb-0">Here's what's happening across the state greenery project today.</p>
                </div>
            </div>
            <div class="col-lg-4">
                <!-- Smart Weather Widget (Real-time & Dynamic) -->
                <div class="card-glass p-3 weather-widget" id="weatherWidget"
                     data-lat="<?php echo e(getSetting('weather_latitude', '30.0511')); ?>"
                     data-lng="<?php echo e(getSetting('weather_longitude', '70.6372')); ?>">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <i id="weatherIcon" class="fas fa-sun fa-2x text-warning"></i>
                            <div>
                                <h4 class="mb-0 fw-bold" id="weatherTemp">--°C</h4>
                                <small class="text-muted" id="weatherDesc">Loading weather...</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold" id="weatherCity"><?php echo e(getSetting('weather_location', 'Dera Ghazi Khan')); ?></div>
                            <small class="text-muted"><span id="weatherTime">--:--</span> · Local Time</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Statistics Cards with Gradient Borders -->
        <div class="row g-4 mb-4">
            <?php
            $statCards = [];

            if (in_array($roleSlug, ['super_admin', 'vice_chancellor', 'state_officer', 'registrar'])) {
                $statCards = [
                    ['icon'=>'fa-leaf','label'=>'Total Lawns','value'=>$stats['total_lawns'],'color'=>'#2d6a4f','bg'=>'rgba(45,106,79,0.1)', 'link'=>'lawns.php'],
                    ['icon'=>'fa-tree','label'=>'Total Trees','value'=>$stats['total_trees'],'color'=>'#40916c','bg'=>'rgba(64,145,108,0.1)', 'link'=>'plants.php?category=tree'],
                    ['icon'=>'fa-seedling','label'=>'Total Plants','value'=>$stats['total_plants'],'color'=>'#0ea5e9','bg'=>'rgba(14,165,233,0.1)', 'link'=>'plants.php?category=plant'],
                    ['icon'=>'fa-tint','label'=>'Irrigation Zones','value'=>$stats['irrigation_zones'],'color'=>'#f59e0b','bg'=>'rgba(245,158,11,0.1)', 'link'=>'irrigation.php'],
                    ['icon'=>'fa-users','label'=>'Staff Members','value'=>$stats['staff_members'],'color'=>'#ec4899','bg'=>'rgba(236,72,153,0.1)', 'link'=>'staff.php'],
                    ['icon'=>'fa-project-diagram','label'=>'Active Projects','value'=>$stats['active_projects'],'color'=>'#06b6d4','bg'=>'rgba(6,182,212,0.1)', 'link'=>'projects.php'],
                ];
            } elseif ($roleSlug === 'treasurer_officer') {
                $statCards = [
                    ['icon'=>'fa-leaf','label'=>'Total Lawns','value'=>$stats['total_lawns'],'color'=>'#2d6a4f','bg'=>'rgba(45,106,79,0.1)', 'link'=>'lawns.php'],
                    ['icon'=>'fa-wallet','label'=>'Total Budget','value'=>'450000','color'=>'#2a9d8f','bg'=>'rgba(42,157,143,0.1)', 'link'=>'#'],
                    ['icon'=>'fa-receipt','label'=>'Spent Budget','value'=>'310000','color'=>'#e76f51','bg'=>'rgba(231,111,81,0.1)', 'link'=>'#'],
                    ['icon'=>'fa-vault','label'=>'Available Funds','value'=>'140000','color'=>'#e9c46a','bg'=>'rgba(233,196,106,0.1)', 'link'=>'#'],
                    ['icon'=>'fa-users','label'=>'Staff Expenses','value'=>'85000','color'=>'#ec4899','bg'=>'rgba(236,72,153,0.1)', 'link'=>'staff.php'],
                    ['icon'=>'fa-chart-line','label'=>'Active Projects','value'=>$stats['active_projects'],'color'=>'#06b6d4','bg'=>'rgba(6,182,212,0.1)', 'link'=>'projects.php'],
                ];
            } elseif ($roleSlug === 'irrigation_officer') {
                $statCards = [
                    ['icon'=>'fa-leaf','label'=>'Total Lawns','value'=>$stats['total_lawns'],'color'=>'#2d6a4f','bg'=>'rgba(45,106,79,0.1)', 'link'=>'lawns.php'],
                    ['icon'=>'fa-tint','label'=>'Irrigation Zones','value'=>$stats['irrigation_zones'],'color'=>'#0ea5e9','bg'=>'rgba(14,165,233,0.1)', 'link'=>'irrigation.php'],
                    ['icon'=>'fa-shower','label'=>'Water Used (L)','value'=>'12450','color'=>'#f59e0b','bg'=>'rgba(245,158,11,0.1)', 'link'=>'irrigation.php'],
                    ['icon'=>'fa-toggle-on','label'=>'Automated','value'=>'8','color'=>'#2a9d8f','bg'=>'rgba(42,157,143,0.1)', 'link'=>'irrigation.php'],
                    ['icon'=>'fa-server','label'=>'Active Systems','value'=>'5','color'=>'#ec4899','bg'=>'rgba(236,72,153,0.1)', 'link'=>'irrigation.php'],
                    ['icon'=>'fa-project-diagram','label'=>'Water Reports','value'=>'14','color'=>'#06b6d4','bg'=>'rgba(6,182,212,0.1)', 'link'=>'reports.php'],
                ];
            } elseif (in_array($roleSlug, ['plantation_officer', 'dean', 'faculty'])) {
                $statCards = [
                    ['icon'=>'fa-leaf','label'=>'Total Lawns','value'=>$stats['total_lawns'],'color'=>'#2d6a4f','bg'=>'rgba(45,106,79,0.1)', 'link'=>'lawns.php'],
                    ['icon'=>'fa-tree','label'=>'Total Trees','value'=>$stats['total_trees'],'color'=>'#40916c','bg'=>'rgba(64,145,108,0.1)', 'link'=>'plants.php?category=tree'],
                    ['icon'=>'fa-seedling','label'=>'Total Plants','value'=>$stats['total_plants'],'color'=>'#0ea5e9','bg'=>'rgba(14,165,233,0.1)', 'link'=>'plants.php?category=plant'],
                    ['icon'=>'fa-crown','label'=>'Top Lawn Rank','value'=>'1','color'=>'#f59e0b','bg'=>'rgba(245,158,11,0.1)', 'link'=>'rankings.php'],
                    ['icon'=>'fa-heartbeat','label'=>'Lawn Health','value'=>'92','color'=>'#ec4899','bg'=>'rgba(236,72,153,0.1)', 'link'=>'lawns.php'],
                    ['icon'=>'fa-project-diagram','label'=>'Planned Drives','value'=>$stats['active_projects'],'color'=>'#06b6d4','bg'=>'rgba(6,182,212,0.1)', 'link'=>'projects.php'],
                ];
            } else { // Security, Staff, Public / Users
                $statCards = [
                    ['icon'=>'fa-leaf','label'=>'Total Lawns','value'=>$stats['total_lawns'],'color'=>'#2d6a4f','bg'=>'rgba(45,106,79,0.1)', 'link'=>'lawns.php'],
                    ['icon'=>'fa-trophy','label'=>'Ranked Lawns','value'=>$stats['total_lawns'],'color'=>'#40916c','bg'=>'rgba(64,145,108,0.1)', 'link'=>'rankings.php'],
                    ['icon'=>'fa-tree','label'=>'Local Trees','value'=>$stats['total_trees'],'color'=>'#0ea5e9','bg'=>'rgba(14,165,233,0.1)', 'link'=>'plants.php?category=tree'],
                    ['icon'=>'fa-seedling','label'=>'Local Plants','value'=>$stats['total_plants'],'color'=>'#f59e0b','bg'=>'rgba(245,158,11,0.1)', 'link'=>'plants.php?category=plant'],
                    ['icon'=>'fa-project-diagram','label'=>'Active Drives','value'=>$stats['active_projects'],'color'=>'#ec4899','bg'=>'rgba(236,72,153,0.1)', 'link'=>'projects.php'],
                    ['icon'=>'fa-bell','label'=>'My Alerts','value'=>'0','color'=>'#06b6d4','bg'=>'rgba(6,182,212,0.1)', 'link'=>'#'],
                ];
            }

            foreach ($statCards as $i => $card): ?>
            <div class="col-xl-2 col-lg-4 col-md-4 col-sm-6" data-aos="fade-up" data-aos-delay="<?php echo $i * 50; ?>">
                <a href="<?php echo $card['link']; ?>" class="text-decoration-none">
                    <div class="stat-card-premium" style="--card-color: <?php echo $card['color']; ?>; --card-bg: <?php echo $card['bg']; ?>;">
                        <div class="stat-icon">
                            <i class="fas <?php echo $card['icon']; ?>"></i>
                        </div>
                        <div class="stat-details">
                            <?php if (is_numeric($card['value'])): ?>
                                <h3 class="counter-value" data-target="<?php echo $card['value']; ?>">0</h3>
                            <?php else: ?>
                                <h3 class="text-white fw-bold"><?php echo e($card['value']); ?></h3>
                            <?php endif; ?>
                            <p><?php echo $card['label']; ?></p>
                        </div>
                        <div class="stat-progress">
                            <div class="progress" style="height: 4px;">
                                <div class="progress-bar" role="progressbar" style="width: 75%; background-color: <?php echo $card['color']; ?>;"></div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Charts Row -->
        <div class="row g-4 mb-4">
            <!-- Growth Chart -->
            <div class="col-lg-8" data-aos="fade-up" data-aos-delay="100">
                <div class="card-glass">
                    <div class="card-header-custom">
                        <div>
                            <h5 class="mb-0 fw-bold"><?php echo $chartTitle; ?></h5>
                            <small class="text-white"><?php echo $chartSubtitle; ?></small>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-green active">Yearly</button>
                            <button class="btn btn-sm btn-outline-green">Monthly</button>
                        </div>
                    </div>
                    <div class="card-body-custom">
                        <div class="chart-container" style="height:350px">
                            <canvas id="growthTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Irrigation Doughnut -->
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="150">
                <div class="card-glass">
                    <div class="card-header-custom">
                        <h5 class="mb-0 fw-bold"><?php echo $doughnutTitle; ?></h5>
                    </div>
                    <div class="card-body-custom">
                        <div class="chart-container" style="height:300px">
                            <canvas id="waterDistChart"></canvas>
                        </div>
                        <div class="mt-4">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-white small"><?php echo $roleSlug === 'treasurer_officer' ? 'Budget Efficiency Goal' : 'Water Conservation Goal'; ?></span>
                                <span class="fw-bold small">85%</span>
                            </div>
                            <div class="progress" style="height: 8px; border-radius: 4px;">
                                <div class="progress-bar bg-info" style="width: 85%;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Row: Activities & Drives -->
        <div class="row g-4">
            <?php 
            $showActivities = in_array($roleSlug, $adminRoles);
            if ($showActivities): 
            ?>
            <!-- Notifications & Activities -->
            <div class="col-lg-7" data-aos="fade-up" data-aos-delay="300">
                <div class="card-glass h-100">
                    <div class="card-header-custom">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-stream me-2 text-primary"></i>Recent Activities</h5>
                        <button class="btn btn-sm btn-link text-success p-0">Clear Logs</button>
                    </div>
                    <div class="card-body-custom">
                        <div class="activity-timeline">
                            <?php if(empty($recentActivities)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-ghost fa-3x text-light mb-3"></i>
                                    <p class="text-muted">No recent activity logs.</p>
                                </div>
                            <?php else: foreach($recentActivities as $act):
                                // Module color-coding
                                $modIconMap = [
                                    'maintenance'  => ['icon'=>'fa-tools',          'color'=>'#f4a261'],
                                    'lawns'        => ['icon'=>'fa-leaf',            'color'=>'#52b788'],
                                    'staff'        => ['icon'=>'fa-user-tie',        'color'=>'#4361ee'],
                                    'plants'       => ['icon'=>'fa-seedling',        'color'=>'#2d6a4f'],
                                    'irrigation'   => ['icon'=>'fa-tint',            'color'=>'#0ea5e9'],
                                    'projects'     => ['icon'=>'fa-project-diagram', 'color'=>'#7209b7'],
                                    'categories'   => ['icon'=>'fa-tags',            'color'=>'#06b6d4'],
                                    'auth'         => ['icon'=>'fa-shield-alt',      'color'=>'#ef4444'],
                                    'users'        => ['icon'=>'fa-users',           'color'=>'#ec4899'],
                                ];
                                $mod = $act['module'] ?? 'other';
                                $actIcon = $modIconMap[$mod] ?? ['icon'=>'fa-circle', 'color'=>'#adb5bd'];
                            ?>
                                <div class="activity-item d-flex gap-3 mb-3 pb-3" style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                                    <div class="flex-shrink-0">
                                        <?php if($act['avatar']): ?>
                                            <img src="<?php echo UPLOADS_URL.'/'.$act['avatar']; ?>" class="rounded-circle" width="38" height="38" style="object-fit:cover;border:2px solid rgba(255,255,255,0.1);">
                                        <?php else: ?>
                                            <div style="width:38px;height:38px;border-radius:50%;background:<?php echo $actIcon['color']; ?>22;border:2px solid <?php echo $actIcon['color']; ?>44;display:flex;align-items:center;justify-content:center;">
                                                <i class="fas <?php echo $actIcon['icon']; ?>" style="color:<?php echo $actIcon['color']; ?>;font-size:0.85rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-content flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                            <span class="fw-semibold" style="font-size:0.85rem;color:<?php echo $actIcon['color']; ?>"><?php echo e($act['full_name'] ?? 'System'); ?></span>
                                            <small class="text-muted ms-2" style="font-size:0.7rem;white-space:nowrap;"><?php echo timeAgo($act['created_at']); ?></small>
                                        </div>
                                        <p class="mb-0 text-white-50" style="font-size:0.8rem;line-height:1.4;"><?php echo e($act['description']); ?></p>
                                        <span class="badge mt-1" style="font-size:0.6rem;background:<?php echo $actIcon['color']; ?>22;color:<?php echo $actIcon['color']; ?>;border:1px solid <?php echo $actIcon['color']; ?>33;"><?php echo ucfirst($mod); ?></span>
                                    </div>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Plantation Drives Timeline -->
            <div class="<?php echo $showActivities ? 'col-lg-5' : 'col-lg-12'; ?>" data-aos="fade-up" data-aos-delay="400">
                <div class="card-glass h-100">
                    <div class="card-header-custom">
                        <h5 class="mb-0 fw-bold"><i class="fas fa-calendar-alt me-2 text-warning"></i>Upcoming Drives</h5>
                    </div>
                    <div class="card-body-custom">
                        <?php if(empty($upcomingDrives)): ?>
                            <div class="alert alert-light border-0 text-center">
                                No upcoming plantation drives scheduled.
                            </div>
                        <?php else: foreach($upcomingDrives as $drive): ?>
                            <div class="drive-card p-3 mb-3 border rounded-3 bg-light bg-opacity-10 d-flex align-items-center gap-3">
                                <div class="date-badge text-center">
                                    <span class="d-block fw-bold fs-4"><?php echo date('d', strtotime($drive['drive_date'])); ?></span>
                                    <small class="text-uppercase"><?php echo date('M', strtotime($drive['drive_date'])); ?></small>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold"><?php echo e($drive['title']); ?></h6>
                                    <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?php echo e($drive['location']); ?></small>
                                </div>
                                <div class="drive-status">
                                    <span class="badge bg-<?php echo $drive['status'] == 'ongoing' ? 'primary' : 'warning'; ?> text-white p-2">
                                        <?php echo ucfirst($drive['status']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                        <button class="btn btn-primary-green w-100 mt-2">Schedule New Drive</button>
                    </div>
                </div>
            </div>
        </div>

    </div><!-- /.content-area -->

    <!-- Dashboard CSS Enhancements -->
    <style>
        .stat-card-premium {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: 0.3s;
            cursor: pointer;
        }
        .stat-card-premium:hover {
            transform: translateY(-5px);
            border-color: var(--card-color);
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.1);
        }
        .stat-card-premium .stat-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: var(--card-bg);
            color: var(--card-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 15px;
        }
        .stat-card-premium h3 {
            font-weight: 800;
            margin-bottom: 2px;
            color: var(--text-main);
        }
        .stat-card-premium p {
            color: var(--text-muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .avatar-placeholder {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--accent-green);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
        .date-badge {
            background: var(--primary-green);
            color: #fff;
            padding: 10px;
            border-radius: 12px;
            min-width: 65px;
        }
        .weather-widget {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(168, 85, 247, 0.1) 100%);
            color: #ffffff !important;
        }
        .weather-widget .text-muted {
            color: #ffffff !important;
        }
    </style>

<?php

$extraJS = '
<script>
// Growth Trends Chart
const growthCtx = document.getElementById("growthTrendChart").getContext("2d");
const growthGradient = growthCtx.createLinearGradient(0, 0, 0, 400);
growthGradient.addColorStop(0, "rgba(79, 70, 229, 0.4)");
growthGradient.addColorStop(1, "rgba(79, 70, 229, 0)");

new Chart(growthCtx, {
    type: "line",
    data: {
        labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        datasets: [{
            label: "' . $lineLabel1 . '",
            data: ' . $lineData1 . ',
            borderColor: "#4f46e5",
            backgroundColor: growthGradient,
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: "#4f46e5",
            pointBorderColor: "#fff",
            pointHoverRadius: 6
        }, {
            label: "' . $lineLabel2 . '",
            data: ' . $lineData2 . ',
            borderColor: "#f4a261",
            backgroundColor: "transparent",
            borderWidth: 2,
            borderDash: [5, 5],
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { 
                position: "top", 
                labels: { 
                    usePointStyle: true, 
                    font: { family: "Outfit" },
                    color: "#ffffff"
                } 
            }
        },
        scales: {
            y: { 
                grid: { borderDash: [3, 3], color: "rgba(255,255,255,0.05)" },
                ticks: { color: "#ffffff" }
            },
            x: { 
                grid: { display: false },
                ticks: { color: "#ffffff" }
            }
        }
    }
});

// Water Distribution Chart
const waterCtx = document.getElementById("waterDistChart").getContext("2d");
new Chart(waterCtx, {
    type: "doughnut",
    data: {
        labels: ' . $doughnutLabels . ',
        datasets: [{
            data: ' . $doughnutData . ',
            backgroundColor: ' . $doughnutColors . ',
            hoverOffset: 15,
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "75%",
        plugins: {
            legend: { 
                position: "bottom", 
                labels: { 
                    padding: 20, 
                    usePointStyle: true,
                    color: "#ffffff"
                } 
            }
        }
    }
});

// Dynamic Weather & Time Widget Client Script
(function() {
    const weatherWidget = document.getElementById("weatherWidget");
    if (!weatherWidget) return;
    
    const lat = weatherWidget.dataset.lat || "30.0511";
    const lng = weatherWidget.dataset.lng || "70.6372";
    
    const tempEl = document.getElementById("weatherTemp");
    const descEl = document.getElementById("weatherDesc");
    const iconEl = document.getElementById("weatherIcon");
    const timeEl = document.getElementById("weatherTime");
    
    function updateClock() {
        try {
            const timeString = new Date().toLocaleTimeString("en-US", {
                timeZone: "Asia/Karachi",
                hour: "2-digit",
                minute: "2-digit",
                hour12: false
            });
            timeEl.textContent = timeString;
        } catch(e) {
            const now = new Date();
            const hrs = String(now.getHours()).padStart(2, "0");
            const mins = String(now.getMinutes()).padStart(2, "0");
            timeEl.textContent = hrs + ":" + mins;
        }
    }
    updateClock();
    setInterval(updateClock, 1000);
    
    const weatherMapping = {
        0: { desc: "Sunny, Clear Sky", icon: "fa-sun text-warning" },
        1: { desc: "Mainly Clear", icon: "fa-cloud-sun text-warning" },
        2: { desc: "Partly Cloudy", icon: "fa-cloud text-light" },
        3: { desc: "Overcast", icon: "fa-cloud text-secondary" },
        45: { desc: "Foggy", icon: "fa-smog text-light" },
        48: { desc: "Depositing Rime Fog", icon: "fa-smog text-light" },
        51: { desc: "Light Drizzle", icon: "fa-cloud-rain text-info" },
        53: { desc: "Moderate Drizzle", icon: "fa-cloud-rain text-info" },
        55: { desc: "Dense Drizzle", icon: "fa-cloud-rain text-info" },
        56: { desc: "Light Freezing Drizzle", icon: "fa-snowflake text-info" },
        57: { desc: "Dense Freezing Drizzle", icon: "fa-snowflake text-info" },
        61: { desc: "Slight Rain", icon: "fa-cloud-showers-heavy text-info" },
        63: { desc: "Moderate Rain", icon: "fa-cloud-showers-heavy text-info" },
        65: { desc: "Heavy Rain", icon: "fa-cloud-showers-heavy text-info" },
        66: { desc: "Light Freezing Rain", icon: "fa-snowflake text-info" },
        67: { desc: "Heavy Freezing Rain", icon: "fa-snowflake text-info" },
        71: { desc: "Slight Snowfall", icon: "fa-snowflake text-light" },
        73: { desc: "Moderate Snowfall", icon: "fa-snowflake text-light" },
        75: { desc: "Heavy Snowfall", icon: "fa-snowflake text-light" },
        77: { desc: "Snow Grains", icon: "fa-snowflake text-light" },
        80: { desc: "Slight Rain Showers", icon: "fa-cloud-sun-rain text-info" },
        81: { desc: "Moderate Rain Showers", icon: "fa-cloud-sun-rain text-info" },
        82: { desc: "Violent Rain Showers", icon: "fa-cloud-showers-heavy text-info" },
        85: { desc: "Slight Snow Showers", icon: "fa-snowflake text-light" },
        86: { desc: "Heavy Snow Showers", icon: "fa-snowflake text-light" },
        95: { desc: "Thunderstorm", icon: "fa-bolt text-warning" },
        96: { desc: "Thunderstorm with Hail", icon: "fa-bolt text-warning" },
        99: { desc: "Heavy Thunderstorm with Hail", icon: "fa-bolt text-danger" }
    };
    
    fetch("https://api.open-meteo.com/v1/forecast?latitude=" + lat + "&longitude=" + lng + "&current_weather=true")
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data && data.current_weather) {
                const cw = data.current_weather;
                const temp = Math.round(cw.temperature);
                const code = cw.weathercode;
                
                tempEl.textContent = temp + "°C";
                const match = weatherMapping[code] || { desc: "Clear Sky", icon: "fa-sun text-warning" };
                descEl.textContent = match.desc;
                iconEl.className = "fas " + match.icon + " fa-2x";
            }
        })
        .catch(function(err) {
            console.error("Weather API call failed", err);
            tempEl.textContent = "28°C";
            descEl.textContent = "Sunny, Clear Sky";
            iconEl.className = "fas fa-sun fa-2x text-warning";
        });
})();
</script>';
include __DIR__ . '/includes/footer.php';
?>
