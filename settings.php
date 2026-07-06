<?php
/**
 * Settings Page — System Configuration
 * Step 7: Advanced Application Settings
 */
define('GREENERY_APP', true);
$pageTitle = 'System Settings';
require_once __DIR__ . '/includes/header.php';
requireAuth();
requirePermission('settings');

// Fetch Settings Grouped by Category
$allSettings = db()->fetchAll("SELECT * FROM settings ORDER BY setting_group, id");
$groupedSettings = [];
foreach ($allSettings as $s) {
    $groupedSettings[$s['setting_group']][] = $s;
}

$groups = [
    'general' => ['icon' => 'fa-cog', 'label' => 'General Config'],
    'theme' => ['icon' => 'fa-palette', 'label' => 'Design & UI'],
    'dashboard' => ['icon' => 'fa-tachometer-alt', 'label' => 'Dashboard Settings'],
    'notifications' => ['icon' => 'fa-bell', 'label' => 'Alerts & Email'],
    'system' => ['icon' => 'fa-server', 'label' => 'Database & Logs']
];

// Fetch Recent Logs for the Logs Panel
$systemLogs = db()->fetchAll("SELECT * FROM activities ORDER BY created_at DESC LIMIT 50");
?>

<?php include __DIR__ . '/includes/sidebar.php'; ?>

<div class="main-content">
    <div class="content-area">
        <?php displayFlash(); ?>

        <!-- Page Header -->
        <div class="page-header" data-aos="fade-down">
            <div>
                <h1><i class="fas fa-sliders-h me-2 text-success"></i>System Configuration</h1>
                <p class="text-muted mb-0">Fine-tune the application parameters, security, and visual aesthetics.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-glass" id="backupBtn"><i class="fas fa-database me-2"></i>Backup DB</button>
                <button class="btn btn-primary-green" onclick="location.reload()"><i class="fas fa-sync-alt me-1"></i>Refresh</button>
            </div>
        </div>

        <div class="row g-4">
            <!-- Sidebar Navigation -->
            <div class="col-xl-3 col-lg-4" data-aos="fade-right">
                <div class="card-glass p-0 sticky-top" style="top: 100px; z-index: 10;">
                    <div class="list-group list-group-flush settings-nav">
                        <?php foreach($groups as $key => $g): ?>
                        <a href="#section-<?php echo $key; ?>" class="list-group-item list-group-item-action bg-transparent text-white border-white-5 p-3 d-flex align-items-center gap-3">
                            <div class="icon-box text-center" style="width: 30px;"><i class="fas <?php echo $g['icon']; ?> text-success"></i></div>
                            <span><?php echo $g['label']; ?></span>
                            <i class="fas fa-chevron-right ms-auto small opacity-25"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="col-xl-9 col-lg-8" data-aos="fade-left">
                <?php foreach($groups as $key => $g): ?>
                <div id="section-<?php echo $key; ?>" class="settings-section mb-5">
                    <h5 class="fw-bold mb-3 d-flex align-items-center">
                        <i class="fas <?php echo $g['icon']; ?> me-2 text-success"></i>
                        <?php echo $g['label']; ?>
                    </h5>
                    
                    <div class="card-glass p-0 overflow-hidden">
                        <div class="card-body-custom p-0">
                            <?php if($key === 'system'): ?>
                                <!-- Special System Tools -->
                                <div class="p-4 border-bottom border-white-5">
                                    <h6 class="fw-bold mb-3">Database Management</h6>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="p-3 border border-white-10 rounded-3 bg-white-5">
                                                <p class="small text-muted mb-2">Download a complete SQL dump of the greenery management database.</p>
                                                <button class="btn btn-sm btn-outline-green w-100">Export SQL Database</button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="p-3 border border-white-10 rounded-3 bg-white-5">
                                                <p class="small text-muted mb-2">Restore database from a previous backup file (.sql).</p>
                                                <button class="btn btn-sm btn-outline-danger w-100">Import/Restore DB</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <h6 class="fw-bold mb-3">System Activity Logs</h6>
                                    <div class="log-terminal bg-black p-3 rounded-3" style="max-height: 300px; overflow-y: auto;">
                                        <?php foreach($systemLogs as $log): ?>
                                            <div class="log-entry small mb-1">
                                                <span class="text-success">[<?php echo date('H:i:s', strtotime($log['created_at'])); ?>]</span>
                                                <span class="text-white-50"><?php echo e($log['description']); ?></span>
                                                <span class="text-info float-end"><?php echo e($log['activity_type']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Loop through dynamic settings -->
                                <?php 
                                $settings = $groupedSettings[$key] ?? [];
                                if(empty($settings)): echo '<p class="p-4 text-muted">No settings found in this category.</p>'; 
                                else: foreach($settings as $s): ?>
                                    <div class="setting-row p-4 border-bottom border-white-5 d-flex align-items-center justify-content-between">
                                        <div class="setting-info me-4">
                                            <label class="fw-bold mb-0 d-block"><?php echo e($s['label']); ?></label>
                                            <small class="text-muted"><?php echo e($s['description']); ?></small>
                                        </div>
                                        <div class="setting-control" style="min-width: 200px;">
                                            <?php if($s['setting_type'] == 'boolean'): ?>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input ajax-setting" type="checkbox" data-key="<?php echo $s['setting_key']; ?>" <?php echo $s['setting_value'] == '1' ? 'checked' : ''; ?>>
                                                </div>
                                            <?php elseif($s['setting_type'] == 'color'): ?>
                                                <input type="color" class="form-control form-control-color border-0 bg-transparent ajax-setting" data-key="<?php echo $s['setting_key']; ?>" value="<?php echo e($s['setting_value']); ?>">
                                            <?php elseif($s['setting_type'] == 'number'): ?>
                                                <input type="number" class="form-control bg-dark border-white-10 text-white ajax-setting" data-key="<?php echo $s['setting_key']; ?>" value="<?php echo e($s['setting_value']); ?>">
                                            <?php else: ?>
                                                <input type="text" class="form-control bg-dark border-white-10 text-white ajax-setting" data-key="<?php echo $s['setting_key']; ?>" value="<?php echo e($s['setting_value']); ?>">
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .settings-nav .list-group-item { transition: 0.3s; }
    .settings-nav .list-group-item:hover { background: rgba(255,255,255,0.05) !important; color: var(--accent-green) !important; }
    .settings-section { scroll-margin-top: 100px; }
    
    .setting-row:last-child { border-bottom: none !important; }
    .setting-row:hover { background: rgba(255,255,255,0.02); }

    .log-terminal { font-family: 'Courier New', Courier, monospace; border: 1px solid rgba(255,255,255,0.1); }
    .log-terminal::-webkit-scrollbar { width: 5px; }
    .log-terminal::-webkit-scrollbar-thumb { background: rgba(82, 183, 136, 0.3); border-radius: 10px; }
    
    .form-control-color { width: 45px; height: 40px; padding: 0; cursor: pointer; }
    .form-check-input { width: 45px; height: 22px; cursor: pointer; }
    .form-check-input:checked { background-color: var(--accent-green); border-color: var(--accent-green); }
</style>

<?php
$extraJS = '
<script>
    // AJAX Setting Update
    document.querySelectorAll(".ajax-setting").forEach(el => {
        el.addEventListener("change", function() {
            const key = this.dataset.key;
            let value = this.type === "checkbox" ? (this.checked ? "1" : "0") : this.value;
            
            // Show subtle saving indicator
            showToast("Saving " + key + "...", "info");

            fetch("api/settings.php?action=update", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `key=${key}&value=${value}&' . CSRF_TOKEN_NAME . '=' . generateCSRFToken() . '`
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    showToast("Setting updated successfully!", "success");
                } else {
                    showToast("Error: " + data.error, "danger");
                }
            })
            .catch(() => showToast("Network error. Try again.", "danger"));
        });
    });

    // Smooth Scroll for Nav
    document.querySelectorAll(".settings-nav a").forEach(anchor => {
        anchor.addEventListener("click", function(e) {
            e.preventDefault();
            const targetId = this.getAttribute("href");
            document.querySelector(targetId).scrollIntoView({ behavior: "smooth" });
            
            // Update active state
            document.querySelectorAll(".settings-nav a").forEach(a => a.classList.remove("active", "text-success"));
            this.classList.add("active", "text-success");
        });
    });
</script>';
include __DIR__ . '/includes/footer.php';
?>
