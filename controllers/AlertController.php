<?php
/* UC-06 — JSON endpoint only. No HTML output, ever. */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'data' => [], 'error' => 'Unauthenticated']);
    exit();
}

require_once __DIR__ . '/../models/Inventory.php';

class AlertController {

    /* UC-06: return occupancy JSON for AJAX polling in alerts.js */
    public function getCapacityStatus() {
        $inv = new Inventory();
        $occ = $inv->fetchTotalOccupancy();

        $used  = (float)($occ['used']  ?? 0);
        $total = (float)($occ['total'] ?? 1);
        $ratio = ($total > 0) ? $used / $total : 0;

        /* opt: if breach, also return zone breakdown */
        $zones = [];
        if ($ratio > 0.9) {
            $zones = $inv->fetchZoneBreakdown();
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data'    => [
                'used_m3'  => $used,
                'total_m3' => $total,
                'ratio'    => round($ratio, 4),
                'breach'   => $ratio > 0.9,
                'zones'    => $zones,
            ],
            'error' => '',
        ]);
        exit();
    }
}
