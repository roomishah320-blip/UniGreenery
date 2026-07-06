<?php
/**
 * Map Management Page — Professional Campus GIS Hub
 * Step 4: University Map Management System
 */
define('GREENERY_APP', true);
$pageTitle = 'University Map Management';
require_once __DIR__ . '/includes/header.php';
requireAuth();
requirePermission('map_management');

// Fetch all maps
$maps = db()->fetchAll("SELECT m.*, u.full_name as uploader FROM maps m LEFT JOIN users u ON m.uploaded_by = u.id ORDER BY m.is_primary DESC, m.created_at DESC");

// Get Primary Map for the Explorer
$primaryMap = null;
foreach ($maps as $m) { if ($m['is_primary']) { $primaryMap = $m; break; } }

// Fallback: If no primary set, use the first available map
if (!$primaryMap && !empty($maps)) {
    $primaryMap = $maps[0];
}
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-area">
        <?php displayFlash(); ?>

        <!-- Page Header -->
        <div class="page-header" data-aos="fade-down">
            <div>
                <h1><i class="fas fa-map-marked-alt me-2 text-success"></i>Campus Map Management</h1>
                <p class="text-muted mb-0">Manage university master plans, irrigation zones, and GIS mapping documents.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-green" onclick="window.print()"><i class="fas fa-print me-1"></i>Print View</button>
                <?php if (hasPermission('maps', 'upload')): ?>
                <button class="btn btn-primary-green" data-bs-toggle="modal" data-bs-target="#uploadMapModal">
                    <i class="fas fa-upload me-2"></i>Upload New Map
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Map Explorer (Primary View) -->
            <div class="col-xl-8 col-lg-7" data-aos="fade-right">
                <div class="card-glass h-100 overflow-hidden">
                    <div class="card-header-custom bg-white bg-opacity-10 d-flex justify-content-between align-items-center p-3">
                        <h5 class="mb-0 fw-bold">Map Explorer <?php echo $primaryMap ? "— " . e($primaryMap['title']) : ''; ?></h5>
                        <div class="explorer-tools d-flex gap-2">
                            <button class="btn btn-sm btn-glass" onclick="toggleZoom()" title="Zoom In/Out"><i class="fas fa-search-plus"></i></button>
                            <button class="btn btn-sm btn-glass" onclick="toggleFullscreen()" title="Fullscreen"><i class="fas fa-expand"></i></button>
                            <?php if($primaryMap): ?>
                            <a href="<?php echo UPLOADS_URL.'/'.e($primaryMap['file_path']); ?>" download class="btn btn-sm btn-glass" title="Download"><i class="fas fa-download"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body-custom p-0 position-relative map-explorer-container" id="mapExplorer">
                        <?php if(!$primaryMap): ?>
                            <div class="empty-state py-5">
                                <i class="fas fa-map-signs fa-4x mb-3 text-muted"></i>
                                <h5>No Primary Map Set</h5>
                                <p class="text-muted">Upload and mark a map as "Primary" to view it here.</p>
                            </div>
                        <?php else: 
                            $ext = strtolower(pathinfo($primaryMap['file_path'], PATHINFO_EXTENSION));
                            if(in_array($ext, ['jpg','jpeg','png','webp'])): ?>
                                <div class="map-image-wrapper" id="zoomWrapper">
                                    <img src="<?php echo UPLOADS_URL.'/'.e($primaryMap['file_path']); ?>" id="mapMainImage" alt="Campus Map" class="img-fluid">
                                    
                                    <!-- GIS Smart Zones (Demonstration) -->
                                    <div class="gis-marker" style="top: 30%; left: 45%;" data-bs-toggle="tooltip" title="Admin Lawn: Click for details"></div>
                                    <div class="gis-marker" style="top: 55%; left: 20%;" data-bs-toggle="tooltip" title="Main Sports Complex"></div>
                                    <div class="gis-marker" style="top: 40%; left: 75%;" data-bs-toggle="tooltip" title="Irrigation Zone B"></div>
                                </div>
                            <?php elseif($ext === 'pdf'): ?>
                                <iframe src="<?php echo UPLOADS_URL.'/'.e($primaryMap['file_path']); ?>#toolbar=0" class="w-100" style="height: 600px; border:none;"></iframe>
                            <?php else: ?>
                                <div class="p-5 text-center">
                                    <i class="fas fa-file-<?php echo ($ext == 'xlsx' || $ext == 'xls') ? 'excel' : 'word'; ?> fa-5x text-success opacity-25 mb-4"></i>
                                    <h4><?php echo strtoupper($ext); ?> Document</h4>
                                    <p class="text-muted">Visual preview is not available for this file type.</p>
                                    <a href="<?php echo UPLOADS_URL.'/'.e($primaryMap['file_path']); ?>" download class="btn btn-outline-green mt-3">Download to View Content</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Interactive Info Panel (Hidden by default) -->
                        <div id="gisInfoPanel" class="gis-info-overlay shadow-lg p-3 d-none">
                            <div class="d-flex justify-content-between mb-2">
                                <h6 class="fw-bold mb-0">Zone Information</h6>
                                <button class="btn-close btn-close-white" onclick="closeGisPanel()" style="font-size: 0.7rem;"></button>
                            </div>
                            <div id="gisContent">
                                <small class="text-white-50">Loading zone details...</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-dark bg-opacity-25 p-2 text-center">
                        <small class="text-white-50"><i class="fas fa-info-circle me-1"></i> Use mouse to drag and zoom. Click markers for zone details.</small>
                    </div>
                </div>
            </div>

            <!-- Documents & Revision History -->
            <div class="col-xl-4 col-lg-5" data-aos="fade-left">
                <div class="card-glass h-100">
                    <div class="card-header-custom p-3 border-bottom">
                        <h5 class="mb-0 fw-bold">Document Repository</h5>
                    </div>
                    <div class="card-body-custom p-0" style="max-height: 650px; overflow-y: auto;">
                        <?php if(empty($maps)): ?>
                            <div class="p-4 text-center text-muted">No documents found.</div>
                        <?php else: foreach($maps as $m): 
                            $ext = strtolower(pathinfo($m['file_path'], PATHINFO_EXTENSION));
                            $icon = match($ext) {
                                'pdf' => 'fa-file-pdf text-danger',
                                'xlsx', 'xls' => 'fa-file-excel text-success',
                                'docx', 'doc' => 'fa-file-word text-primary',
                                'jpg', 'jpeg', 'png' => 'fa-file-image text-warning',
                                default => 'fa-file text-secondary'
                            };
                        ?>
                            <div class="map-item p-3 border-bottom d-flex align-items-center gap-3 transition-all hover:bg-white-5">
                                <div class="file-icon fs-2">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <h6 class="mb-1 text-truncate fw-bold"><?php echo e($m['title']); ?></h6>
                                    <div class="d-flex gap-2 align-items-center">
                                        <span class="badge bg-secondary opacity-75" style="font-size: 0.6rem;"><?php echo strtoupper($ext); ?></span>
                                        <?php if($m['is_primary']): ?>
                                            <span class="badge bg-success" style="font-size: 0.6rem;">PRIMARY</span>
                                        <?php endif; ?>
                                        <small class="text-muted" style="font-size: 0.7rem;"><?php echo round($m['file_size']/1024); ?> KB</small>
                                    </div>
                                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">Uploaded: <?php echo date('d M, Y', strtotime($m['created_at'])); ?></small>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-glass p-1 px-2" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end shadow">
                                        <li><a class="dropdown-item" href="<?php echo UPLOADS_URL.'/'.e($m['file_path']); ?>" target="_blank"><i class="fas fa-eye me-2"></i> Preview</a></li>
                                        <li><a class="dropdown-item" href="<?php echo UPLOADS_URL.'/'.e($m['file_path']); ?>" download><i class="fas fa-download me-2"></i> Download</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i> Replace File</a></li>
                                        <?php if(!$m['is_primary']): ?>
                                        <li>
                                            <form action="modules/map-actions.php" method="POST" id="setPrimaryForm_<?php echo $m['id']; ?>">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="set_primary">
                                                <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
                                                <a class="dropdown-item text-success" href="javascript:void(0)" onclick="document.getElementById('setPrimaryForm_<?php echo $m['id']; ?>').submit();">
                                                    <i class="fas fa-star me-2"></i> Set as Primary
                                                </a>
                                            </form>
                                        </li>
                                        <li><a class="dropdown-item text-danger" href="#"><i class="fas fa-trash me-2"></i> Delete</a></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Map Modal -->
<div class="modal fade" id="uploadMapModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold">Upload University Map / Document</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo url('modules/map-actions.php'); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="upload">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-white-50">Document Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Master Plan 2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50">Map Category</label>
                        <select name="map_type" class="form-select">
                            <option value="campus">Campus Master Plan</option>
                            <option value="zone">Irrigation Zone Map</option>
                            <option value="plantation">Plantation Grid</option>
                            <option value="gis">GIS Scanned Document</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-white-50">Select File (PDF, DOCX, XLSX, JPG, PNG)</label>
                        <input type="file" name="map_file" class="form-control" required>
                        <div class="form-text text-white-50 small mt-1">Supports scanned documents and prints up to 10MB.</div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_primary" id="setPrimary">
                            <label class="form-check-label text-white-50" for="setPrimary">Set as Primary Campus Map</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Upload Document</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .map-explorer-container {
        background: #1a1a1a;
        min-height: 500px;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
    }
    .map-image-wrapper {
        position: relative;
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: grab;
    }
    .map-image-wrapper:active { cursor: grabbing; }
    .map-image-wrapper.zoomed { transform: scale(2); }

    /* GIS Markers */
    .gis-marker {
        position: absolute;
        width: 20px;
        height: 20px;
        background: rgba(82, 183, 136, 0.6);
        border: 2px solid #fff;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 0 15px rgba(82, 183, 136, 0.8);
        transition: 0.3s;
        z-index: 5;
    }
    .gis-marker:hover {
        transform: scale(1.5);
        background: var(--accent-green);
        box-shadow: 0 0 25px rgba(82, 183, 136, 1);
    }

    .gis-info-overlay {
        position: absolute;
        bottom: 20px;
        right: 20px;
        width: 250px;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        color: #fff;
        z-index: 100;
        animation: slideUp 0.3s ease;
    }

    @keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

    .hover\:bg-white-5:hover { background: rgba(255,255,255,0.05); }
    .transition-all { transition: all 0.2s ease; }
</style>

<?php
$extraJS = '
<script>
    // Zoom Toggle
    let isZoomed = false;
    function toggleZoom() {
        const wrapper = document.getElementById("zoomWrapper");
        if(!wrapper) return;
        isZoomed = !isZoomed;
        wrapper.classList.toggle("zoomed", isZoomed);
        if(isZoomed) {
            showToast("Zoomed in. Drag to explore.", "info");
        }
    }

    // Fullscreen View
    function toggleFullscreen() {
        const explorer = document.getElementById("mapExplorer");
        if (explorer.requestFullscreen) explorer.requestFullscreen();
        else if (explorer.webkitRequestFullscreen) explorer.webkitRequestFullscreen();
    }

    // GIS Interactions (Simulated)
    document.querySelectorAll(".gis-marker").forEach(marker => {
        marker.addEventListener("click", function(e) {
            e.stopPropagation();
            const title = this.getAttribute("title") || "University Zone";
            const panel = document.getElementById("gisInfoPanel");
            const content = document.getElementById("gisContent");
            
            panel.classList.remove("d-none");
            content.innerHTML = `
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="fas fa-leaf text-success"></i>
                    <span class="fw-bold">${title}</span>
                </div>
                <div class="small">
                    <p class="mb-1 text-white-50"><i class="fas fa-tree me-1"></i> Trees: 45</p>
                    <p class="mb-1 text-white-50"><i class="fas fa-tint me-1"></i> Irrigation: Automatic</p>
                    <p class="mb-2 text-white-50"><i class="fas fa-user me-1"></i> Manager: Rajesh Kumar</p>
                    <a href="categories.php" class="btn btn-xs btn-primary-green w-100 py-1" style="font-size: 0.7rem;">View Zone Dashboard</a>
                </div>
            `;
        });
    });

    function closeGisPanel() {
        document.getElementById("gisInfoPanel").classList.add("d-none");
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll(\'[data-bs-toggle="tooltip"]\'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });
</script>';
include __DIR__ . '/includes/footer.php';
?>
