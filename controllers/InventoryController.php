<?php
/* Raw session check - manager only */
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
        $stock         = $inv->fetchStockLevels();
        $sensors       = $inv->getSensorReadings();
        $env_alerts    = $inv->fetchEnvironmentalAlerts();
        $discrepancies = $this->compareActualVsExpected($sensors, $stock);

        /* compareActualVsExpected: log any sensor with deviation */
        foreach ($discrepancies as $sensor) {
            $inv->logDiscrepancyAlert(
                $sensor['zone_name'],
                $sensor['sensor_id'],
                $sensor['deviation_kg']
            );
        }

        /* opt: capacity alert: occupancy > 90% */
        $occ         = $inv->fetchTotalOccupancy();
        $occ_pct     = ($occ['total'] > 0) ? ($occ['used'] / $occ['total']) : 0;
        $zone_detail = [];
        if ($occ_pct > 0.9) {
            $zone_detail = $inv->fetchZoneBreakdown();
            $_SESSION['notifications'][] = 'Warehouse occupancy exceeded 90%.';
        }

        /* opt: expiry check - items expiring within 7 days */
        $expiring = $inv->fetchItemsNearExpiry(7);

        require_once __DIR__ . '/../views/dashboard/index.php';
    }

    private function compareActualVsExpected($sensorReadings, $stockLevels) {
        $stockByZone = [];

        foreach ($stockLevels as $row) {
            $zone = $row['zone_name'] ?? '';
            if ($zone === '') {
                continue;
            }

            if (!isset($stockByZone[$zone])) {
                $stockByZone[$zone] = 0;
            }

            $stockByZone[$zone] += (float)($row['quantity_available'] ?? 0);
        }

        return array_values(array_filter($sensorReadings, function($reading) use ($stockByZone) {
            if (!empty($reading['has_discrepancy'])) {
                return true;
            }

            $zone = $reading['zone_name'] ?? '';
            if ($zone === '' || !isset($stockByZone[$zone])) {
                return false;
            }

            $expectedWeight = isset($reading['expected_weight_kg'])
                ? (float)$reading['expected_weight_kg']
                : $stockByZone[$zone];
            $actualWeight = (float)($reading['last_reading'] ?? 0);

            return abs($actualWeight - $expectedWeight) > 0.001;
        }));
    }
}
