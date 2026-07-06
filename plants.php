<?php
/**
 * Plants Management — Digital Herbarium
 * Step 12: Plants Management Module
 */
define('GREENERY_APP', true);
$pageTitle = 'Plants & Botanical Catalog';
require_once __DIR__ . '/includes/header.php';
requireAuth();
requirePermission('plants');

// Pagination Configuration
$itemsPerPage = 12;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $itemsPerPage;

// Filters
$search = sanitize($_GET['search'] ?? '');
$catFilter = sanitize($_GET['category'] ?? '');
$healthFilter = sanitize($_GET['health'] ?? '');

$where = "WHERE p.is_active = 1";
$params = [];

if ($search) { $where .= " AND (p.name LIKE ? OR p.scientific_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($catFilter) { $where .= " AND p.category = ?"; $params[] = $catFilter; }
if ($healthFilter) { $where .= " AND p.health_status = ?"; $params[] = $healthFilter; }

// Fetch Total Count for Pagination
$totalPlants = db()->count("SELECT COUNT(*) FROM plants p $where", $params);
$totalPages = ceil($totalPlants / $itemsPerPage);

// Fetch Paginated Plants with Lawn Info
$plants = db()->fetchAll("SELECT p.*, l.name as lawn_name FROM plants p 
    LEFT JOIN lawns l ON p.lawn_id = l.id 
    $where ORDER BY p.created_at DESC LIMIT $itemsPerPage OFFSET $offset", $params);

$lawns = db()->fetchAll("SELECT id, name FROM lawns WHERE status = 'active' ORDER BY name");
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-area">
        <?php displayFlash(); ?>

        <!-- Page Header -->
        <div class="page-header" data-aos="fade-down">
            <div>
                <h1><i class="fas fa-seedling me-2 text-success"></i>Botanical Asset Registry</h1>
                <p class="text-muted mb-0">Detailed tracking of campus flora, scientific categorization, and health monitoring.</p>
            </div>
            <?php if (hasPermission('plants', 'create')): ?>
            <button class="btn btn-primary-green" data-bs-toggle="modal" data-bs-target="#addPlantModal">
                <i class="fas fa-plus me-2"></i>Register New Specimen
            </button>
            <?php endif; ?>
        </div>

        <!-- Advanced Filter Suite -->
        <div class="card-glass mb-4 p-3" data-aos="fade-up">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-lg-4 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-white-10 text-white-50"><i class="fas fa-search"></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by name or scientific ID..." value="<?php echo e($search); ?>">
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <option value="tree" <?php echo $catFilter == 'tree' ? 'selected' : ''; ?>>Trees</option>
                        <option value="flower" <?php echo $catFilter == 'flower' ? 'selected' : ''; ?>>Flowers</option>
                        <option value="shrub" <?php echo $catFilter == 'shrub' ? 'selected' : ''; ?>>Shrubs</option>
                        <option value="herb" <?php echo $catFilter == 'herb' ? 'selected' : ''; ?>>Herbs</option>
                        <option value="plant" <?php echo $catFilter == 'plant' ? 'selected' : ''; ?>>Other Plants</option>
                    </select>
                </div>
                <div class="col-lg-3 col-md-6">
                    <select name="health" class="form-select" onchange="this.form.submit()">
                        <option value="">Any Vitality Status</option>
                        <option value="excellent" <?php echo $healthFilter == 'excellent' ? 'selected' : ''; ?>>Excellent</option>
                        <option value="good" <?php echo $healthFilter == 'good' ? 'selected' : ''; ?>>Good</option>
                        <option value="fair" <?php echo $healthFilter == 'fair' ? 'selected' : ''; ?>>Fair</option>
                        <option value="poor" <?php echo $healthFilter == 'poor' ? 'selected' : ''; ?>>Poor</option>
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <button type="submit" class="btn btn-outline-green w-100">Apply Filter</button>
                </div>
            </form>
        </div>

        <!-- Plants Catalog Grid -->
        <?php if(empty($plants)): ?>
            <div class="card-glass py-5 text-center" data-aos="fade-up">
                <i class="fas fa-seedling fa-4x mb-3 text-muted"></i>
                <h5>No Specimens Found</h5>
                <p class="text-muted">Adjust your filters or register a new plant to begin tracking.</p>
            </div>
        <?php else: ?>
            <div class="row g-4 mb-4">
                <?php foreach($plants as $i => $p): 
                    $healthColor = match($p['health_status']) {
                        'excellent' => '#2d6a4f',
                        'good' => '#52b788',
                        'fair' => '#f4a261',
                        'poor' => '#e63946',
                        default => '#95d5b2'
                    };
                ?>
                <div class="col-xl-3 col-lg-4 col-md-6" data-aos="zoom-in" data-aos-delay="<?php echo $i * 50; ?>">
                    <div class="plant-card-premium">
                        <div class="plant-img-wrapper">
                            <img src="<?php echo $p['image'] ? UPLOADS_URL.'/'.e($p['image']) : 'https://images.unsplash.com/photo-1459411552884-841db9b3cc2a?w=400&q=80'; ?>" alt="<?php echo e($p['name']); ?>">
                            <div class="vitality-badge" style="background: <?php echo $healthColor; ?>;">
                                <?php echo strtoupper($p['health_status']); ?>
                            </div>
                            <div class="plant-img-overlay d-flex align-items-center justify-content-center">
                                <a href="<?php echo $p['image'] ? UPLOADS_URL.'/'.e($p['image']) : 'https://images.unsplash.com/photo-1459411552884-841db9b3cc2a?w=800&q=80'; ?>" class="btn btn-sm btn-glass text-white px-3 btn-view-image" data-title="<?php echo e($p['name']); ?>" title="View Image"><i class="fas fa-eye me-2"></i>View Image</a>
                            </div>
                        </div>
                        <div class="plant-content p-3">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="fw-bold mb-0 text-truncate"><?php echo e($p['name']); ?></h6>
                                <span class="badge bg-white-5 text-muted small px-2"><?php echo e($p['category']); ?></span>
                            </div>
                            <small class="text-success fst-italic d-block mb-3"><?php echo e($p['scientific_name'] ?: 'N/A'); ?></small>
                            
                            <div class="row g-2 mb-3 border-top border-bottom border-white-5 py-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Age</small>
                                    <span class="fw-bold small"><?php echo $p['age_years']; ?> Years</span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Height</small>
                                    <span class="fw-bold small"><?php echo $p['height_cm']; ?> CM</span>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between">
                                <small class="text-muted"><i class="fas fa-map-marker-alt me-1 text-success"></i><?php echo e($p['lawn_name'] ?: 'Not Assigned'); ?></small>
                                <div class="dropdown">
                                    <button class="btn btn-xs btn-glass text-white" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-h"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow">
                                        <li><a class="dropdown-item btn-view-image" href="<?php echo $p['image'] ? UPLOADS_URL.'/'.e($p['image']) : 'https://images.unsplash.com/photo-1459411552884-841db9b3cc2a?w=800&q=80'; ?>" data-title="<?php echo e($p['name']); ?>">
                                               <i class="fas fa-eye me-2"></i> View Image</a></li>
                                        <li><a class="dropdown-item btn-edit-plant" href="#" 
                                               data-bs-toggle="modal" 
                                               data-bs-target="#editPlantModal"
                                               data-json="<?php echo e(json_encode($p)); ?>">
                                               <i class="fas fa-edit me-2"></i> Edit Record</a></li>
                                        <li><a class="dropdown-item" href="#"><i class="fas fa-heartbeat me-2"></i> Vitality Log</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-trash me-2"></i> Delete</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if($totalPages > 1): ?>
            <nav aria-label="Plants Pagination">
                <ul class="pagination justify-content-center gap-2">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link glass-page" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $catFilter; ?>&health=<?php echo $healthFilter; ?>"><i class="fas fa-chevron-left"></i></a>
                    </li>
                    <?php for($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link glass-page" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $catFilter; ?>&health=<?php echo $healthFilter; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link glass-page" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo $catFilter; ?>&health=<?php echo $healthFilter; ?>"><i class="fas fa-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Plant Modal -->
<div class="modal fade" id="addPlantModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold"><i class="fas fa-seedling me-2 text-success"></i>Register Botanical Specimen</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo url('modules/plant-actions.php'); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Specimen Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Royal Palm" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Scientific Name</label>
                            <input type="text" name="scientific_name" class="form-control" placeholder="e.g. Roystonea regia">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Botanical Category</label>
                            <select name="category" class="form-select">
                                <option value="tree">Tree</option>
                                <option value="flower">Flower</option>
                                <option value="shrub">Shrub</option>
                                <option value="herb">Herb</option>
                                <option value="plant">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Location (Lawn)</label>
                            <select name="lawn_id" class="form-select">
                                <option value="">Not Assigned</option>
                                <?php foreach($lawns as $l): ?>
                                <option value="<?php echo $l['id']; ?>"><?php echo e($l['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Vitality Health Status</label>
                            <select name="health_status" class="form-select">
                                <option value="excellent">Excellent</option>
                                <option value="good" selected>Good</option>
                                <option value="fair">Fair</option>
                                <option value="poor">Poor</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-white-50">Age (Years)</label>
                            <input type="number" step="0.1" name="age_years" class="form-control" value="1">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-white-50">Height (CM)</label>
                            <input type="number" step="1" name="height_cm" class="form-control" value="50">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Planted Date</label>
                            <input type="date" name="planted_date" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50">Specimen Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Register Specimen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Plant Modal -->
<div class="modal fade" id="editPlantModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2 text-primary"></i>Update Specimen Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo url('modules/plant-actions.php'); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Specimen Name *</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Scientific Name</label>
                            <input type="text" name="scientific_name" id="edit_scientific_name" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Botanical Category</label>
                            <select name="category" id="edit_category" class="form-select">
                                <option value="tree">Tree</option>
                                <option value="flower">Flower</option>
                                <option value="shrub">Shrub</option>
                                <option value="herb">Herb</option>
                                <option value="plant">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Location (Lawn)</label>
                            <select name="lawn_id" id="edit_lawn_id" class="form-select">
                                <option value="">Not Assigned</option>
                                <?php foreach($lawns as $l): ?>
                                <option value="<?php echo $l['id']; ?>"><?php echo e($l['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Vitality Health Status</label>
                            <select name="health_status" id="edit_health_status" class="form-select">
                                <option value="excellent">Excellent</option>
                                <option value="good">Good</option>
                                <option value="fair">Fair</option>
                                <option value="poor">Poor</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-white-50">Age (Years)</label>
                            <input type="number" step="0.1" name="age_years" id="edit_age_years" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-white-50">Height (CM)</label>
                            <input type="number" step="1" name="height_cm" id="edit_height_cm" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Planted Date</label>
                            <input type="date" name="planted_date" id="edit_planted_date" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-white-50">Update Specimen Image (Optional)</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to keep current image</small>
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
    .plant-card-premium {
        background: var(--bg-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        overflow: hidden;
        transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .plant-card-premium:hover {
        transform: translateY(-8px);
        border-color: var(--accent-green);
        box-shadow: 0 15px 30px rgba(0,0,0,0.3);
    }

    .plant-img-wrapper {
        position: relative;
        height: 180px;
        overflow: hidden;
    }
    .plant-img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: 0.5s;
    }
    .plant-card-premium:hover .plant-img-wrapper img { transform: scale(1.1); }

    .vitality-badge {
        position: absolute;
        top: 12px;
        left: 12px;
        padding: 4px 10px;
        border-radius: 50px;
        font-size: 0.65rem;
        font-weight: 800;
        color: #fff;
        z-index: 2;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    .glass-page {
        background: rgba(255,255,255,0.05) !important;
        border: 1px solid rgba(255,255,255,0.1) !important;
        color: #fff !important;
        border-radius: 8px !important;
    }
    .page-item.active .glass-page {
        background: var(--accent-green) !important;
        border-color: var(--accent-green) !important;
    }

    .bg-white-5 { background: rgba(255,255,255,0.05); }
    .border-white-5 { border-color: rgba(255,255,255,0.05) !important; }

    .plant-img-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.65);
        opacity: 0;
        transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 5;
    }
    .plant-card-premium:hover .plant-img-overlay {
        opacity: 1;
    }
    .plant-img-overlay .btn-glass:hover {
        background: var(--accent-green) !important;
        border-color: var(--accent-green) !important;
    }
</style>

<?php
$extraJS = '
<script>
    document.addEventListener("click", function(e) {
        const editBtn = e.target.closest(".btn-edit-plant");
        if (editBtn) {
            const data = JSON.parse(editBtn.dataset.json);
            
            document.getElementById("edit_id").value = data.id;
            document.getElementById("edit_name").value = data.name;
            document.getElementById("edit_scientific_name").value = data.scientific_name || "";
            document.getElementById("edit_category").value = data.category;
            document.getElementById("edit_lawn_id").value = data.lawn_id || "";
            document.getElementById("edit_health_status").value = data.health_status;
            document.getElementById("edit_age_years").value = data.age_years;
            document.getElementById("edit_height_cm").value = data.height_cm;
            document.getElementById("edit_planted_date").value = data.planted_date || "";
        }
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
