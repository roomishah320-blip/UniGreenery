<?php
/**
 * Categories Page — Professional Lawn Portfolio
 * Step 5: Professional Lawn Categories Page
 */
define('GREENERY_APP', true);
$pageTitle = 'Lawn Categories & Performance';
require_once __DIR__ . '/includes/header.php';
requireAuth();
requirePermission('categories');

// Handle search and filtering
$search = sanitize($_GET['search'] ?? '');
$filter = sanitize($_GET['filter'] ?? '');

// Base query for categories with lawn counts
$categories = db()->fetchAll("SELECT c.*, 
    (SELECT COUNT(*) FROM lawns WHERE category_id = c.id) as lawn_count 
    FROM lawn_categories c WHERE c.is_active = 1 ORDER BY c.sort_order ASC");

// Fetch lawns based on search/filter
$where = "WHERE l.status = 'active'";
$params = [];
if ($search) { $where .= " AND (l.name LIKE ? OR l.location LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($filter) { $where .= " AND c.slug = ?"; $params[] = $filter; }

$lawns = db()->fetchAll("SELECT l.*, c.name as cat_name, c.slug as cat_slug FROM lawns l 
    LEFT JOIN lawn_categories c ON l.category_id = c.id 
    $where ORDER BY l.sustainability_score DESC", $params);
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-area">
        <?php displayFlash(); ?>

        <!-- Page Header -->
        <div class="page-header" data-aos="fade-down">
            <div>
                <h1><i class="fas fa-leaf me-2 text-success"></i>Lawn Performance Categories</h1>
                <p class="text-muted mb-0">Monitor lawn rankings, health scores, and maintenance status across campus.</p>
            </div>
            <?php if (hasPermission('lawns', 'create')): ?>
            <button class="btn btn-primary-green px-4 py-2" data-bs-toggle="modal" data-bs-target="#addLawnModal">
                <i class="fas fa-plus me-2"></i>Add New Lawn
            </button>
            <?php endif; ?>
        </div>

        <!-- Filter Bar -->
        <div class="card-glass mb-4 p-3" data-aos="fade-up">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-white-10 text-white-50"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by lawn name or location..." value="<?php echo e($search); ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="filter" class="form-select" onchange="this.form.submit()">
                        <option value="">All Rankings</option>
                        <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['slug']; ?>" <?php echo $filter == $cat['slug'] ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-green w-100">Apply Filters</button>
                </div>
            </form>
        </div>

        <!-- Lawns Masonry Grid -->
        <?php if(empty($lawns)): ?>
            <div class="empty-state py-5 card-glass">
                <i class="fas fa-leaf fa-4x mb-3 text-success"></i>
                <h5>No Lawns Found</h5>
                <p class="text-white">No lawns match your search or filter criteria.</p>
            </div>
        <?php else: ?>
            <div class="row g-4" id="lawnGrid">
                <?php foreach($lawns as $i => $lawn): 
                    $healthScore = round(($lawn['sustainability_score'] + $lawn['cleanliness_score'] + $lawn['maintenance_score'] + $lawn['grass_quality_score']) / 4);
                    $badgeClass = match($lawn['cat_slug']) {
                        'best' => 'bg-success',
                        'average' => 'bg-warning',
                        'worst' => 'bg-danger',
                        default => 'bg-primary'
                    };
                ?>
                <div class="col-xl-4 col-lg-6 col-md-6" data-aos="zoom-in" data-aos-delay="<?php echo $i * 50; ?>">
                    <div class="lawn-card-premium">
                        <!-- Image & Ranking Badge -->
                        <div class="lawn-card-img-wrapper">
                            <img src="<?php echo $lawn['image'] ? UPLOADS_URL.'/'.e($lawn['image']) : 'https://images.unsplash.com/photo-1558635924-b60e7d332925?w=600&q=80'; ?>" alt="<?php echo e($lawn['name']); ?>">
                            <div class="rank-badge <?php echo $badgeClass; ?>">
                                <?php echo e($lawn['cat_name']); ?>
                            </div>
                            <div class="image-overlay-tools">
                                <a href="<?php echo url('lawn-details.php?id=' . $lawn['id']); ?>" class="btn btn-sm btn-glass text-white no-aos" title="Quick View"><i class="fas fa-eye"></i></a>
                                <a href="<?php echo url('maintenance.php?search=' . urlencode($lawn['name'])); ?>" class="btn btn-sm btn-glass text-white no-aos" title="Maintenance Log"><i class="fas fa-tools"></i></a>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="lawn-card-body p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h5 class="fw-bold mb-0"><?php echo e($lawn['name']); ?></h5>
                                    <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?php echo e($lawn['location']); ?></small>
                                </div>
                                <div class="health-percent text-success fw-bold"><?php echo $healthScore; ?>%</div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="health-progress-wrapper mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Health Index</small>
                                    <small class="text-muted">Maintenance Score</small>
                                </div>
                                <div class="progress" style="height: 6px; border-radius: 3px; background: rgba(255,255,255,0.05);">
                                    <div class="progress-bar <?php echo $badgeClass; ?>" style="width: <?php echo $healthScore; ?>%;"></div>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="row g-2 mb-3">
                                <div class="col-4">
                                    <div class="stat-mini text-center p-2 rounded bg-white-5">
                                        <i class="fas fa-tree text-success d-block mb-1"></i>
                                        <small class="fw-bold"><?php echo $lawn['total_trees']; ?></small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-mini text-center p-2 rounded bg-white-5">
                                        <i class="fas fa-seedling text-primary d-block mb-1"></i>
                                        <small class="fw-bold"><?php echo $lawn['total_plants']; ?></small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="stat-mini text-center p-2 rounded bg-white-5">
                                        <i class="fas fa-vector-square text-warning d-block mb-1"></i>
                                        <small class="fw-bold"><?php echo formatNumber($lawn['area_sqm']); ?>m²</small>
                                    </div>
                                </div>
                            </div>

                            <a href="<?php echo url('lawn-details.php?id=' . $lawn['id']); ?>" class="btn btn-outline-green w-100 py-2 no-aos" style="font-size: 0.85rem; border-radius: 12px;">
                                Explore Deep Analytics <i class="fas fa-arrow-right ms-2"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Lawn Modal -->
<div class="modal fade" id="addLawnModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white shadow-lg">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2 text-success"></i>Register New Lawn Zone</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo url('modules/lawn-actions.php'); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Lawn Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Administrative Block Lawn" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <?php foreach($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo e($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Location / Zone *</label>
                            <input type="text" name="location" class="form-control" placeholder="e.g. Sector 4, North Campus" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-white-50">Area (sq.m)</label>
                            <input type="number" name="area_sqm" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-white-50">Irrigation Type</label>
                            <select name="irrigation_type" class="form-select">
                                <option value="sprinkler">Sprinkler</option>
                                <option value="drip">Drip Irrigation</option>
                                <option value="manual">Manual Hose</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50">Brief Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50">Lawn Showcase Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Register Lawn</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .lawn-card-premium {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 24px;
        overflow: hidden;
        transition: 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
    }
    .lawn-card-premium:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        border-color: var(--accent-green);
    }

    .lawn-card-img-wrapper {
        position: relative;
        height: 200px;
        overflow: hidden;
    }
    .lawn-card-img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: 0.6s;
    }
    .lawn-card-premium:hover .lawn-card-img-wrapper img {
        transform: scale(1.1);
    }

    .rank-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #fff;
        z-index: 2;
    }

    .image-overlay-tools {
        position: absolute;
        bottom: -50px;
        left: 0;
        width: 100%;
        padding: 15px;
        background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
        transition: 0.3s;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }
    .lawn-card-premium:hover .image-overlay-tools {
        bottom: 0;
    }

    .bg-white-5 { background: rgba(255,255,255,0.05); }
    .stat-mini i { font-size: 0.9rem; }
    .btn-xs { padding: 4px 8px; font-size: 0.75rem; }
    .no-aos { pointer-events: auto !important; transition: transform 0.2s !important; }
    .no-aos:hover { transform: scale(1.1); }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
