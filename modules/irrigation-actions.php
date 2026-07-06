<?php
/**
 * Irrigation Zone CRUD Actions
 */
define('GREENERY_APP', true);
require_once __DIR__ . '/../includes/functions.php';
startSecureSession();
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('irrigation.php'); }
if (!verifyCSRFToken($_POST[CSRF_TOKEN_NAME] ?? '')) {
    setFlash('danger', 'Invalid security token.');
    redirect('irrigation.php');
}

$action = sanitize($_POST['action'] ?? '');

// Granular permission enforcement
$actionMap = ['add' => 'add', 'edit' => 'edit', 'delete' => 'delete'];
if (isset($actionMap[$action])) {
    canPerformAction('irrigation', $actionMap[$action]);
}

switch ($action) {
    case 'add':
        $zoneName         = sanitize($_POST['zone_name'] ?? '');
        $systemId         = intval($_POST['system_id'] ?? 0) ?: null;
        $lawnId           = intval($_POST['lawn_id'] ?? 0) ?: null;
        $scheduleTime     = sanitize($_POST['schedule_time'] ?? '') ?: null;
        $durationMinutes  = intval($_POST['duration_minutes'] ?? 30);
        $isAutomated      = isset($_POST['is_automated']) ? 1 : 0;
        $waterUsageLiters = max(0, floatval($_POST['water_usage_liters'] ?? 0));

        $zoneId = db()->insert(
            "INSERT INTO irrigation_zones (zone_name, system_id, lawn_id, schedule_time, duration_minutes, is_automated, water_usage_liters) VALUES (?,?,?,?,?,?,?)",
            [$zoneName, $systemId, $lawnId, $scheduleTime, $durationMinutes, $isAutomated, $waterUsageLiters]
        );

        logActivity('create', "Added irrigation zone: $zoneName", 'irrigation', $zoneId);
        setFlash('success', 'Irrigation zone added!');
        redirect('irrigation.php');
        break;

    case 'edit':
        $id               = intval($_POST['id'] ?? 0);
        $zoneName         = sanitize($_POST['zone_name'] ?? '');
        $systemId         = intval($_POST['system_id'] ?? 0) ?: null;
        $lawnId           = intval($_POST['lawn_id'] ?? 0) ?: null;
        $scheduleTime     = sanitize($_POST['schedule_time'] ?? '') ?: null;
        $durationMinutes  = intval($_POST['duration_minutes'] ?? 30);
        $isAutomated      = isset($_POST['is_automated']) ? 1 : 0;
        $status           = sanitize($_POST['status'] ?? 'active');
        $waterUsageLiters = max(0, floatval($_POST['water_usage_liters'] ?? 0));

        db()->update(
            "UPDATE irrigation_zones SET zone_name = ?, system_id = ?, lawn_id = ?, schedule_time = ?, duration_minutes = ?, is_automated = ?, status = ?, water_usage_liters = ? WHERE id = ?",
            [$zoneName, $systemId, $lawnId, $scheduleTime, $durationMinutes, $isAutomated, $status, $waterUsageLiters, $id]
        );

        logActivity('update', "Updated irrigation zone: $zoneName", 'irrigation', $id);
        setFlash('success', 'Zone updated!');
        redirect('irrigation.php');
        break;

    case 'delete':
        $id = intval($_POST['id'] ?? 0);
        db()->delete("DELETE FROM irrigation_zones WHERE id = ?", [$id]);
        logActivity('delete', "Deleted irrigation zone", 'irrigation', $id);
        setFlash('success', 'Zone deleted.');
        redirect('irrigation.php');
        break;

    default:
        redirect('irrigation.php');
}
