<?php
/* Raw session check — manager only */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?page=auth&action=login");
    exit();
}
if ($_SESSION['role'] !== 'manager') {
    http_response_code(403);
    die("Access denied.");
}

require_once __DIR__ . '/../models/Inventory.php';
require_once __DIR__ . '/../models/InventoryMonitor.php';

class InventoryController {

    /* UC-01: assemble full dashboard data and render view */
    public function requestDashboardData() {
        $inv     = new Inventory();
        $monitor = InventoryMonitor::getInstance();

        /* Main flow */
        $stock        = $inv->fetchStockLevels();
        $sensors      = $inv->getSensorReadings();
        $env_alerts   = $inv->fetchEnvironmentalAlerts();

        /* compareActualVsExpected — log any sensor with deviation */
        foreach ($sensors as $s) {
            if ($s['has_discrepancy']) {
                $inv->logDiscrepancyAlert($s['zone_name'], $s['sensor_id'], $s['deviation_kg']);
            }
        }

        /* opt: capacity alert — occupancy > 90% */
        $occ         = $inv->fetchTotalOccupancy();
        $occ_pct     = ($occ['total'] > 0) ? ($occ['used'] / $occ['total']) : 0;
        $zone_detail = [];
        if ($occ_pct > 0.9) {
            $zone_detail = $inv->fetchZoneBreakdown();
        }

        /* opt: expiry check — items expiring within 7 days */
        $expiring = $inv->fetchItemsNearExpiry(7);

        require_once __DIR__ . '/../views/dashboard/index.php';
    }
}
