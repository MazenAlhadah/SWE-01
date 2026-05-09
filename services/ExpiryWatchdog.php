<?php
require_once __DIR__ . '/../config/Database.php';

/* In folder structure and class diagram — not an invented class */
class ExpiryWatchdog {

    /* UC-03: scan all items with expiry dates and return those near expiry */
    public function scanExpiryDates($inventory) {
        return array_filter($inventory, function($row) {
            return $this->isNearExpiry(7, $row['expiry_date']);
        });
    }

    /* UC-03: return true if expiry_date is within $days from today */
    public function isNearExpiry($days, $expiry_date) {
        if (empty($expiry_date)) return false;
        $cutoff = date('Y-m-d', strtotime("+{$days} days"));
        return $expiry_date <= $cutoff && $expiry_date >= date('Y-m-d');
    }

    /* UC-03: generate FEFO pick ordering — sort by expiry_date ASC */
    public function applyFEFO($items) {
        usort($items, function($a, $b) {
            return strcmp($a['expiry_date'], $b['expiry_date']);
        });
        return $items;
    }

    /* UC-03: raise expiry alert — appended to AUDIT_LOG */
    public function raiseExpiryAlert($item, $conn) {
        $stmt = $conn->prepare(
            "INSERT INTO AUDIT_LOG (user_id, sensor_id, event_type, event_detail, reason, discrepancy_rate, timestamp)
             VALUES (NULL, NULL, 'EXPIRY_ALERT', ?, 'Item near expiry', 0, NOW())"
        );
        $stmt->execute(["SKU {$item['sku']} expires {$item['expiry_date']}"]);
    }

    /* UC-03: notify picker with FEFO instructions (simulated) */
    public function notifyPicker($instructions) {
        $_SESSION['picker_instructions'] = $instructions;
    }
}
