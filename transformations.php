<?php
/**
 * Transformations Page — Before & After Showcase
 * Step 9: Before & After Transformation Module
 */
define('GREENERY_APP', true);
$pageTitle = 'Campus Transformations';
require_once __DIR__ . '/includes/header.php';
requireAuth();
requirePermission('transformations');

// Fetch all transformations with lawn names
$transformations = db()->fetchAll("SELECT t.*, l.name as lawn_name FROM transformations t LEFT JOIN lawns l ON t.lawn_id = l.id ORDER BY t.transformation_date DESC");
$lawns = db()->fetchAll("SELECT id, name FROM lawns WHERE status = 'active' ORDER BY name");
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-area">
        <?php displayFlash(); ?>

        <!-- Page Header -->
        <div class="page-header" data-aos="fade-down">
            <div>
                <h1><i class="fas fa-magic me-2 text-info"></i>Greenery Transformations</h1>
                <p class="text-muted mb-0">Visual documentation of the impact and progress of our university plantation drives.</p>
            </div>
            <?php if (hasPermission('transformations', 'create')): ?>
            <button class="btn btn-primary-green" data-bs-toggle="modal" data-bs-target="#addTransformModal">
                <i class="fas fa-plus me-2"></i>Add Transformation
            </button>
            <?php endif; ?>
        </div>

        <?php if(empty($transformations)): ?>
            <div class="card-glass py-5 text-center" data-aos="fade-up">
                <i class="fas fa-images fa-4x mb-3 text-muted"></i>
                <h5>No Transformations Found</h5>
                <p class="text-muted">Start by documenting your first before & after greenery project.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach($transformations as $i => $t): ?>
                <div class="col-xl-6 col-lg-12" data-aos="fade-up" data-aos-delay="<?php echo $i * 100; ?>">
                    <div class="card-glass overflow-hidden h-100">
                        <div class="card-header-custom p-3 border-bottom border-white-10 d-flex justify-content-between align-items-center">
                            <div>
                                <h5 class="fw-bold mb-0"><?php echo e($t['title']); ?></h5>
                                <small class="text-success"><i class="fas fa-leaf me-1"></i><?php echo e($t['lawn_name'] ?: 'General Campus'); ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-dark border border-white-10 text-white-50"><?php echo date('d M, Y', strtotime($t['transformation_date'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="card-body-custom p-0">
                            <!-- Interactive Before/After Slider -->
                            <div class="comparison-container" id="slider-<?php echo $t['id']; ?>">
                                <div class="image-before">
                                    <img src="<?php echo $t['before_image'] ? UPLOADS_URL.'/'.e($t['before_image']) : 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=800&q=80'; ?>" alt="Before">
                                    <div class="label-badge badge-before">BEFORE</div>
                                </div>
                                <div class="image-after" style="width: 50%;">
                                    <img src="<?php echo $t['after_image'] ? UPLOADS_URL.'/'.e($t['after_image']) : 'https://images.unsplash.com/photo-1558635924-b60e7d332925?w=800&q=80'; ?>" alt="After">
                                    <div class="label-badge badge-after">AFTER</div>
                                </div>
                                <div class="comparison-slider" style="left: 50%;">
                                    <div class="slider-handle"><i class="fas fa-arrows-alt-h"></i></div>
                                </div>
                            </div>

                            <!-- Details Section -->
                            <div class="p-4">
                                <div class="row g-4">
                                    <div class="col-md-7">
                                        <h6 class="fw-bold text-white-50 small text-uppercase mb-2">Project Impact</h6>
                                        <p class="text-secondary small mb-0"><?php echo e($t['description']); ?></p>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="future-reqs-panel p-3 rounded-4 bg-white-5 border border-white-5">
                                            <h6 class="fw-bold text-success small text-uppercase mb-2"><i class="fas fa-chart-line me-2"></i>Future Roadmap</h6>
                                            <div class="small text-white-50 mb-3">
                                                <?php echo nl2br(e($t['future_requirements'])); ?>
                                            </div>
                                            <?php if($t['roadmap_image']): ?>
                                                <div class="roadmap-preview rounded-3 overflow-hidden border border-white-10">
                                                    <img src="<?php echo UPLOADS_URL.'/'.e($t['roadmap_image']); ?>" class="img-fluid" style="cursor: pointer;" onclick="window.open(this.src)">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer bg-white-5 p-2 px-4 d-flex justify-content-between align-items-center">
                            <small class="text-muted">Managed by <?php echo e($_SESSION['role_name']); ?></small>
                            <div class="actions">
                                <script type="application/json" id="transform-data-<?php echo $t['id']; ?>">
                                    <?php echo json_encode($t); ?>
                                </script>
                                <button class="btn btn-sm btn-primary-green btn-edit-transform no-aos" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editTransformModal"
                                        data-id="<?php echo $t['id']; ?>">
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                <button class="btn btn-sm btn-glass text-danger ms-2" onclick="confirmDelete(<?php echo $t['id']; ?>, 'transformation')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Transformation Modal -->
<div class="modal fade" id="addTransformModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold"><i class="fas fa-magic me-2 text-info"></i>New Transformation Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo url('modules/transformation-actions.php'); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label text-white-50">Project Title *</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. South Campus Restoration" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Lawn Reference</label>
                            <select name="lawn_id" class="form-select">
                                <option value="">General / Other</option>
                                <?php foreach($lawns as $l): ?>
                                <option value="<?php echo $l['id']; ?>"><?php echo e($l['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Before Image *</label>
                            <input type="file" name="before_image" class="form-control" accept="image/*" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">After Image *</label>
                            <input type="file" name="after_image" class="form-control" accept="image/*" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Transformation Description</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="Describe the changes made..."></textarea>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label text-white-50">Future Requirements (Roadmap)</label>
                            <textarea name="future_requirements" class="form-control" rows="2" placeholder="e.g. 50 new trees, drip irrigation installation..."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Roadmap Visual (Image)</label>
                            <input type="file" name="roadmap_image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Save Transformation</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Edit Transformation Modal -->
<div class="modal fade" id="editTransformModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content glass-panel border-0 text-white">
            <div class="modal-header border-bottom border-white-10">
                <h5 class="modal-title fw-bold">Edit Transformation Record</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo url('modules/transformation-actions.php'); ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" value="">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label text-white-50">Project Title *</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Lawn Reference</label>
                            <select name="lawn_id" class="form-select">
                                <option value="">General / Other</option>
                                <?php foreach($lawns as $l): ?>
                                <option value="<?php echo $l['id']; ?>"><?php echo e($l['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Change Before Image</label>
                            <input type="file" name="before_image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-white-50">Change After Image</label>
                            <input type="file" name="after_image" class="form-control" accept="image/*">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-white-50">Transformation Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label text-white-50">Future Requirements (Roadmap)</label>
                            <textarea name="future_requirements" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-white-50">Update Roadmap Visual</label>
                            <input type="file" name="roadmap_image" class="form-control" accept="image/*">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white-10">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green px-4">Update Transformation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .comparison-container {
        position: relative;
        width: 100%;
        height: 350px;
        overflow: hidden;
        cursor: ew-resize;
        background: #000;
    }
    .image-before, .image-after {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }
    .image-before img, .image-after img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    .image-after {
        z-index: 2;
        border-right: 2px solid #fff;
    }
    .comparison-slider {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 2px;
        background: #fff;
        z-index: 10;
        pointer-events: none;
    }
    .slider-handle {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 40px;
        height: 40px;
        background: var(--accent-green);
        border: 4px solid #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        box-shadow: 0 0 15px rgba(0,0,0,0.5);
    }

    .label-badge {
        position: absolute;
        top: 15px;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.65rem;
        font-weight: 800;
        letter-spacing: 1px;
        color: #fff;
        z-index: 5;
    }
    .badge-before { right: 15px; background: rgba(220, 53, 69, 0.8); }
    .badge-after { left: 15px; background: rgba(45, 106, 79, 0.8); }

    .bg-white-5 { background: rgba(255,255,255,0.05); }
    .future-reqs-panel { border-left: 3px solid var(--accent-green) !important; }
    .actions { position: relative; z-index: 100; pointer-events: auto !important; }
    
    /* Robust Modal Visibility (Standard Bootstrap Compatibility) */
    .modal { z-index: 1070 !important; }
    .modal-backdrop { z-index: 1060 !important; }
    .modal-content.glass-panel {
        background: #0f0f23 !important; /* Solid premium dark background */
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
        box-shadow: 0 0 50px rgba(0,0,0,0.5) !important;
    }
</style>

<?php
$extraJS = '
<script>
    // Drag-to-Slide Before/After Comparison Slider Logic
    document.querySelectorAll(".comparison-container").forEach(container => {
        const afterImg = container.querySelector(".image-after");
        const slider = container.querySelector(".comparison-slider");
        let isDragging = false;
        
        const move = (e) => {
            if (!isDragging) return;
            const rect = container.getBoundingClientRect();
            const clientX = e.clientX || (e.touches && e.touches[0].clientX) || (e.changedTouches && e.changedTouches[0].clientX);
            if (clientX === undefined) return;

            let x = clientX - rect.left;
            
            if (x < 0) x = 0;
            if (x > rect.width) x = rect.width;
            
            const percent = (x / rect.width) * 100;
            afterImg.style.width = percent + "%";
            slider.style.left = percent + "%";
        };

        const startDragging = (e) => {
            isDragging = true;
            move(e);
        };

        const stopDragging = () => {
            isDragging = false;
        };

        container.addEventListener("mousedown", startDragging);
        window.addEventListener("mousemove", move);
        window.addEventListener("mouseup", stopDragging);

        container.addEventListener("touchstart", startDragging, { passive: true });
        window.addEventListener("touchmove", move, { passive: true });
        window.addEventListener("touchend", stopDragging);
        window.addEventListener("touchcancel", stopDragging);
    });

    // Robust Event-Delegated Modal Population (Matches staff/irrigation patterns)
    document.addEventListener("click", function(e) {
        const btn = e.target.closest(".btn-edit-transform");
        if (btn) {
            const id = btn.getAttribute("data-id");
            const dataEl = document.getElementById("transform-data-" + id);
            
            if (dataEl) {
                try {
                    const d = JSON.parse(dataEl.innerHTML.trim());
                    const form = document.querySelector("#editTransformModal form");

                    if (form) {
                        form.querySelector(\'[name="id"]\').value = d.id || "";
                        form.querySelector(\'[name="title"]\').value = d.title || "";
                        form.querySelector(\'[name="lawn_id"]\').value = d.lawn_id || "";
                        form.querySelector(\'[name="description"]\').value = d.description || "";
                        form.querySelector(\'[name="future_requirements"]\').value = d.future_requirements || "";
                    }
                } catch(err) {
                    console.error("Data loading error:", err);
                }
            }
        }
    });
</script>
';
include __DIR__ . '/includes/footer.php';
?>
