<?php
/**
 * Lawns Listing Page
 */
define('GREENERY_APP', true);
$pageTitle = 'Lawns Management';
require_once __DIR__ . '/includes/header.php';
requireAuth();
requirePermission('lawns');

$page = max(1, intval($_GET['page'] ?? 1));
$search = sanitize($_GET['search'] ?? '');
$catFilter = intval($_GET['category'] ?? 0);
$offset = ($page - 1) * ITEMS_PER_PAGE;

$where = "WHERE l.status != 'inactive'";
$params = [];
if ($search) { $where .= " AND (l.name LIKE ? OR l.location LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($catFilter) { $where .= " AND l.category_id = ?"; $params[] = $catFilter; }

$total = db()->count("SELECT COUNT(*) FROM lawns l $where", $params);
$lawns = db()->fetchAll("SELECT l.*, lc.name as category_name, lc.slug as category_slug FROM lawns l LEFT JOIN lawn_categories lc ON l.category_id = lc.id $where ORDER BY l.created_at DESC LIMIT " . ITEMS_PER_PAGE . " OFFSET $offset", $params);
$categories = db()->fetchAll("SELECT * FROM lawn_categories WHERE is_active = 1 ORDER BY sort_order");
?>
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="main-content"><div class="content-area">
    <?php displayFlash(); ?>
    <div class="page-header" data-aos="fade-down">
        <div><h1><i class="fas fa-leaf me-2"></i>Lawns Management</h1></div>
        <?php if (hasPermission('lawns', 'create') || hasRole('super_admin')): ?>
        <a href="<?php echo url('categories.php'); ?>" class="btn btn-primary-green"><i class="fas fa-plus me-2"></i>Add Lawn</a>
        <?php endif; ?>
    </div>

    <!-- Filters -->
    <div class="card-glass mb-4" data-aos="fade-up">
        <div class="card-body-custom">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5"><label class="form-label">Search</label><input type="text" name="search" class="form-control form-control-modern" placeholder="Search lawns..." value="<?php echo e($search); ?>"></div>
                <div class="col-md-4"><label class="form-label">Category</label><select name="category" class="form-select form-control-modern"><option value="">All Categories</option><?php foreach($categories as $c): ?><option value="<?php echo $c['id']; ?>" <?php echo $catFilter==$c['id']?'selected':''; ?>><?php echo e($c['name']); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><button type="submit" class="btn btn-primary-green w-100"><i class="fas fa-search me-1"></i>Filter</button></div>
            </form>
        </div>
    </div>

    <!-- Lawns Grid -->
    <div class="row g-4">
        <?php if (empty($lawns)): ?>
        <div class="col-12"><div class="card-glass"><div class="card-body-custom"><div class="empty-state"><i class="fas fa-leaf"></i><h5>No lawns found</h5><p>Try adjusting your filters</p></div></div></div></div>
        <?php else: ?>
        <?php foreach ($lawns as $i => $lawn):
            $badge = match($lawn['category_slug'] ?? '') { 'best'=>'badge-best','average'=>'badge-average','worst'=>'badge-worst',default=>'badge-best' };
        ?>
        <div class="col-xl-3 col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="<?php echo $i * 60; ?>">
            <div class="lawn-card">
                <div class="lawn-card-img">
                    <img src="<?php echo $lawn['image'] ? UPLOADS_URL.'/'.e($lawn['image']) : 'https://images.unsplash.com/photo-1558635924-b60e7d332925?w=600&q=80'; ?>" alt="<?php echo e($lawn['name']); ?>">
                    <span class="lawn-card-badge <?php echo $badge; ?>"><?php echo e($lawn['category_name'] ?? 'Uncategorized'); ?></span>
                </div>
                <div class="lawn-card-body">
                    <h5><?php echo e($lawn['name']); ?></h5>
                    <p><?php echo truncate(e($lawn['description'] ?? ''), 70); ?></p>
                    <div class="lawn-card-meta">
                        <span><i class="fas fa-tree"></i><?php echo formatNumber($lawn['total_trees']); ?></span>
                        <span><i class="fas fa-seedling"></i><?php echo formatNumber($lawn['total_plants']); ?></span>
                        <span><i class="fas fa-ruler-combined"></i><?php echo formatNumber($lawn['area_sqm']); ?> m²</span>
                    </div>
                    <a href="<?php echo url('lawn-details.php?id='.$lawn['id']); ?>" class="btn btn-outline-green btn-sm w-100 mt-2"><i class="fas fa-eye me-1"></i>View Details</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="mt-4"><?php echo paginate($total, $page, ITEMS_PER_PAGE, 'lawns.php'); ?></div>
</div></div>
<?php include __DIR__ . '/includes/footer.php'; ?>
