<?php
require_once __DIR__ . '/../config/Database.php';

class Item {

    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /* UC-02: full item catalog for zonal optimizer */
    public function fetchItemCatalog() {
        $stmt = $this->conn->prepare(
            "SELECT item_id, sku, name, weight, expiry_date, unit_price, sales_velocity,
                    is_sensitive, safety_stock_qty, height_cm, width_cm, depth_cm
             FROM ITEM
             ORDER BY sales_velocity DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* UC-02: all zone data for optimizer */
    public function fetchZoneData() {
        $stmt = $this->conn->prepare(
            "SELECT zone_id, zone_name, zone_type, total_capacity_m3,
                    current_occupancy_m3, temperature, humidity
             FROM WAREHOUSE_ZONE
             ORDER BY zone_name"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* UC-02: commit a single zone assignment — move item's inventory record to new bin/zone */
    public function updateZoneAssignment($item_id, $zone_id) {
        /* Simulate: pick first available bin in target zone */
        $stmt = $this->conn->prepare(
            "SELECT bin_id FROM BIN WHERE zone_id = ?
             ORDER BY (max_capacity_m3 - current_usage_m3) DESC LIMIT 1"
        );
        $stmt->execute([$zone_id]);
        $bin = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$bin) return;

        $stmt2 = $this->conn->prepare(
            "UPDATE INVENTORY SET bin_id = ? WHERE item_id = ?"
        );
        $stmt2->execute([$bin['bin_id'], $item_id]);
    }

    /* UC-03: fetch all items that have an expiry_date set */
    public function fetchAllItemsWithExpiry() {
        $stmt = $this->conn->prepare(
            "SELECT it.item_id, it.sku, it.name, it.expiry_date,
                    i.inventory_id, i.quantity_available, b.location_code, z.zone_name
             FROM ITEM it
             JOIN INVENTORY i ON i.item_id = it.item_id
             JOIN BIN b       ON b.bin_id   = i.bin_id
             JOIN WAREHOUSE_ZONE z ON z.zone_id = b.zone_id
             WHERE it.expiry_date IS NOT NULL
             ORDER BY it.expiry_date ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* UC-03: filter to items expiring within $window days (in-memory, post-fetch) */
    public function filterItemsExpiringSoon($items, $window = 7) {
        $cutoff = date('Y-m-d', strtotime("+{$window} days"));
        return array_filter($items, function($row) use ($cutoff) {
            return $row['expiry_date'] <= $cutoff;
        });
    }

    /* UC-03: update picking priority to FEFO for expiring items */
    public function updatePickingPriority($expiring_items) {
        /* Simulate FEFO: mark items as RESERVED so they are picked first */
        foreach ($expiring_items as $row) {
            $stmt = $this->conn->prepare(
                "UPDATE INVENTORY SET state = 'RESERVED' WHERE inventory_id = ?"
            );
            $stmt->execute([$row['inventory_id']]);
        }
    }

    /* UC-02 opt: open backorders for cross-dock matching */
    public function fetchOpenBackorders() {
        $stmt = $this->conn->prepare(
            "SELECT b.backorder_id, b.item_id, b.quantity_needed, b.reason,
                    it.sku, it.name
             FROM BACKORDER b
             JOIN ITEM it ON it.item_id = b.item_id
             WHERE b.status = 'OPEN'
             ORDER BY b.created_at ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchIncomingShipmentItems() {
        $stmt = $this->conn->prepare(
            "SELECT DISTINCT it.item_id, it.sku, it.name, pli.quantity_ordered, sh.state
             FROM SHIPMENT sh
             JOIN PURCHASE_ORDER po ON po.po_id = sh.po_id
             JOIN PO_LINE_ITEM pli ON pli.po_id = po.po_id
             JOIN ITEM it ON it.item_id = pli.item_id
             WHERE sh.state IN ('EXPECTED', 'AT_DOCK', 'BEING_INSPECTED')
             ORDER BY sh.dispatch_date DESC, sh.shipment_id DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* UC-02 opt: match incoming items to open backorders by item_id */
    public function matchIncomingToBackorders($incoming_items, $backorders) {
        $incoming_ids = array_column($incoming_items, 'item_id');
        return array_filter($backorders, function($b) use ($incoming_ids) {
            return in_array($b['item_id'], $incoming_ids);
        });
    }

    /* PUML-aligned: updateDetails() */
    public function updateDetails($item_id, $field, $value) {
        $stmt = $this->conn->prepare("UPDATE ITEM SET {$field} = ? WHERE item_id = ?");
        $stmt->execute([$value, $item_id]);
    }

    /* PUML-aligned: isExpired() */
    public function isExpired($expiry_date) {
        return $expiry_date !== null && $expiry_date < date('Y-m-d');
    }

    /* PUML-aligned: isNearExpiry() */
    public function isNearExpiry($expiry_date, $days = 7) {
        $cutoff = date('Y-m-d', strtotime("+{$days} days"));
        return $expiry_date !== null && $expiry_date <= $cutoff && $expiry_date >= date('Y-m-d');
    }

    /* PUML-aligned: checkStockLevel() — returns qty_available for item */
    public function checkStockLevel($item_id) {
        $stmt = $this->conn->prepare(
            "SELECT SUM(quantity_available) AS total FROM INVENTORY WHERE item_id = ?"
        );
        $stmt->execute([$item_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    }
}
