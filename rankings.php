<?php
/**
 * Rankings Page — Lawn Performance Leaderboard
 * Step 8: University Lawn Ranking System
 */
define('GREENERY_APP', true);
$pageTitle = 'Lawn Performance Rankings';
require_once __DIR__ . '/includes/header.php';
requireAuth();
requirePermission('rankings');

// Handle Search and Category Filter
$search = sanitize($_GET['search'] ?? '');
$catFilter = intval($_GET['category'] ?? 0);

$where = "WHERE l.status = 'active'";
$params = [];

if ($search) { $where .= " AND l.name LIKE ?"; $params[] = "%$search%"; }
if ($catFilter) { $where .= " AND l.category_id = ?"; $params[] = $catFilter; }

// Fetch Lawns Ranked by Weighted Performance Score
$lawns = db()->fetchAll("SELECT l.*, c.name as category_name, c.slug as category_slug,
    (l.sustainability_score + l.cleanliness_score + l.maintenance_score + l.grass_quality_score) / 4 as avg_score
    FROM lawns l 
    LEFT JOIN lawn_categories c ON l.category_id = c.id 
    $where 
    ORDER BY c.sort_order ASC, avg_score DESC", $params);

$categories = db()->fetchAll("SELECT * FROM lawn_categories WHERE is_active = 1 ORDER BY sort_order");
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-area">
        <?php displayFlash(); ?>

        <!-- Page Header -->
        <div class="page-header" data-aos="fade-down">
            <div>
                <h1><i class="fas fa-trophy me-2 text-warning"></i>University Lawn Rankings</h1>
                <p class="text-muted mb-0">The official leaderboard for campus greenery maintenance and sustainability.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-green" onclick="window.print()"><i class="fas fa-print me-1"></i>Export List</button>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="card-glass mb-4 p-3" data-aos="fade-up">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-white-10 text-white-50"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search lawn name..." value="<?php echo e($search); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $catFilter == $cat['id'] ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary-green w-100">Update Leaderboard</button>
                </div>
            </form>
        </div>

        <!-- Rankings Table -->
        <div class="card-glass overflow-hidden" data-aos="fade-up" data-aos-delay="100">
            <div class="table-responsive">
                <table class="table-modern w-100">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 80px;">Rank</th>
                            <th>Lawn Identity</th>
                            <th>Category</th>
                            <th class="text-center">Density (T/P)</th>
                            <th class="text-center">Area (m²)</th>
                            <th>Performance Score</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($lawns)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No ranking data available for the selected filters.</td>
                        </tr>
                        <?php else: foreach($lawns as $i => $l): 
                            $rank = $i + 1;
                            $score = round($l['avg_score'], 1);
                            
                            // Score Color Logic
                            $barClass = $score >= 80 ? 'bg-success' : ($score >= 60 ? 'bg-info' : ($score >= 40 ? 'bg-warning' : 'bg-danger'));
                            
                            // Medal Logic
                            $medal = match($rank) {
                                1 => '<i class="fas fa-medal fa-lg text-warning" title="Gold"></i>',
                                2 => '<i class="fas fa-medal fa-lg text-light opacity-75" title="Silver"></i>',
                                3 => '<i class="fas fa-medal fa-lg text-danger opacity-75" title="Bronze"></i>',
                                default => '<span class="fw-bold text-muted">#'.$rank.'</span>'
                            };
                        ?>
                        <tr class="rank-row <?php echo $rank <= 3 ? 'top-tier' : ''; ?>">
                            <td class="text-center"><?php echo $medal; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?php echo $l['image'] ? UPLOADS_URL.'/'.e($l['image']) : 'https://images.unsplash.com/photo-1558635924-b60e7d332925?w=100&q=80'; ?>" class="rounded-3" width="45" height="45" style="object-fit: cover;">
                                    <div>
                                        <h6 class="mb-0 fw-bold"><?php echo e($l['name']); ?></h6>
                                        <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?php echo e($l['location']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php echo $l['category_slug'] == 'best' ? 'bg-success' : ($l['category_slug'] == 'worst' ? 'bg-danger' : 'bg-warning'); ?> opacity-75">
                                    <?php echo e($l['category_name']); ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center">
                                    <span class="small fw-bold"><?php echo $l['total_trees']; ?> Trees</span>
                                    <span class="small text-muted"><?php echo $l['total_plants']; ?> Plants</span>
                                </div>
                            </td>
                            <td class="text-center fw-bold"><?php echo formatNumber($l['area_sqm']); ?></td>
                            <td style="width: 200px;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="flex-grow-1">
                                        <div class="progress" style="height: 6px; background: rgba(255,255,255,0.05); border-radius: 10px;">
                                            <div class="progress-bar <?php echo $barClass; ?>" style="width: <?php echo $score; ?>%;"></div>
                                        </div>
                                    </div>
                                    <span class="fw-bold"><?php echo $score; ?>%</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-glass text-white" onclick="showScoreDetails(<?php echo htmlspecialchars(json_encode($l)); ?>)">
                                    <i class="fas fa-chart-pie me-1"></i> Details
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Score Details Modal -->
<div class="modal fade" id="scoreDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold" id="modalLawnName">Lawn Scoring Breakdown</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4 mb-4 text-center">
                    <div class="col-6">
                        <div class="p-3 bg-white-5 rounded-4">
                            <h2 class="mb-0 fw-bold text-success" id="modalFinalScore">0%</h2>
                            <small class="text-muted">Final Rank Score</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 bg-white-5 rounded-4">
                            <h2 class="mb-0 fw-bold text-info" id="modalDensity">0</h2>
                            <small class="text-muted">Plant Density</small>
                        </div>
                    </div>
                </div>

                <div class="scores-list">
                    <?php 
                    $metrics = [
                        ['id' => 'sustainability_score', 'label' => 'Sustainability Index', 'icon' => 'fa-leaf'],
                        ['id' => 'cleanliness_score', 'label' => 'Cleanliness & Hygiene', 'icon' => 'fa-broom'],
                        ['id' => 'maintenance_score', 'label' => 'Maintenance Effort', 'icon' => 'fa-tools'],
                        ['id' => 'grass_quality_score', 'label' => 'Grass & Soil Quality', 'icon' => 'fa-seedling']
                    ];
                    foreach($metrics as $m): ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <label class="small text-white-50"><i class="fas <?php echo $m['icon']; ?> me-2"></i><?php echo $m['label']; ?></label>
                            <span class="fw-bold" id="score-<?php echo $m['id']; ?>">0%</span>
                        </div>
                        <div class="progress" style="height: 4px; background: rgba(255,255,255,0.05);">
                            <div class="progress-bar bg-success" id="bar-<?php echo $m['id']; ?>" style="width: 0%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .rank-row { transition: 0.3s; }
    .rank-row:hover { background: rgba(82, 183, 136, 0.05); }
    .rank-row.top-tier { background: rgba(255, 215, 0, 0.03); }
    .bg-white-5 { background: rgba(255,255,255,0.05); }
    
    .table-modern th { border-bottom: 2px solid rgba(255,255,255,0.05); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; color: var(--text-muted); }
    .table-modern td { vertical-align: middle; padding: 18px 12px; }
    
    .glass-panel { background: rgba(15, 15, 35, 0.98); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px); }
</style>

<?php
$extraJS = '
<script>
    function showScoreDetails(lawn) {
        const modal = new bootstrap.Modal(document.getElementById("scoreDetailsModal"));
        document.getElementById("modalLawnName").innerText = lawn.name + " - Performance Breakdown";
        document.getElementById("modalFinalScore").innerText = Math.round(lawn.avg_score) + "%";
        document.getElementById("modalDensity").innerText = parseInt(lawn.total_trees) + parseInt(lawn.total_plants);
        
        const metrics = ["sustainability_score", "cleanliness_score", "maintenance_score", "grass_quality_score"];
        metrics.forEach(m => {
            const score = lawn[m];
            document.getElementById("score-" + m).innerText = score + "%";
            document.getElementById("bar-" + m).style.width = score + "%";
        });
        
        modal.show();
    }
</script>';
include __DIR__ . '/includes/footer.php';
?>
