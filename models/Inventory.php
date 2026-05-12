<?php
require_once __DIR__ . '/../config/Database.php';

class Inventory {

    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /* UC-01: stock levels per item with bin and zone context */
    public function fetchStockLevels() {
        $stmt = $this->conn->prepare(
            "SELECT i.inventory_id, it.sku, it.name, it.safety_stock_qty,
                    i.quantity_available, i.quantity_reserved, i.state,
                    b.location_code, z.zone_name
             FROM INVENTORY i
             JOIN ITEM it ON it.item_id = i.item_id
             JOIN BIN  b  ON b.bin_id   = i.bin_id
             JOIN WAREHOUSE_ZONE z ON z.zone_id = b.zone_id
             ORDER BY z.zone_name, b.location_code"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* UC-05: fetch only SKUs below safety stock for reorder handling */
    public function fetchAffectedSKUs() {
        $stmt = $this->conn->prepare(
            "SELECT it.sku
             FROM INVENTORY i
             JOIN ITEM it ON it.item_id = i.item_id
             WHERE i.quantity_available < it.safety_stock_qty
               AND NOT EXISTS (
                   SELECT 1
                   FROM PO_LINE_ITEM pli
                   JOIN PURCHASE_ORDER po ON po.po_id = pli.po_id
                   WHERE pli.item_id = it.item_id
                     AND po.status IN ('PENDING', 'APPROVED', 'CONFIRMED', 'MODIFICATION_REQUESTED')
                     AND COALESCE(pli.quantity_received, 0) < COALESCE(pli.quantity_ordered, 0)
               )
             ORDER BY it.sku"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /* UC-01 / UC-06: total occupancy ratio across all zones */
    public function fetchTotalOccupancy() {
        $stmt = $this->conn->prepare(
            "SELECT SUM(current_occupancy_m3) AS used, SUM(total_capacity_m3) AS total
             FROM WAREHOUSE_ZONE"
        );
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /* UC-06: per-zone occupancy breakdown */
    public function fetchZoneBreakdown() {
        $stmt = $this->conn->prepare(
            "SELECT zone_id, zone_name, zone_type, total_capacity_m3,
                    current_occupancy_m3,
                    ROUND(current_occupancy_m3 / total_capacity_m3 * 100, 1) AS pct
             FROM WAREHOUSE_ZONE
             ORDER BY pct DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* UC-01: IoT sensor readings with discrepancy flag */
    public function getSensorReadings() {
        $stmt = $this->conn->prepare(
            "SELECT s.sensor_id, s.type, s.last_reading, s.expected_weight_kg,
                    s.deviation_kg, s.has_discrepancy, s.timestamp,
                    b.location_code, z.zone_name
             FROM IOT_SENSOR s
             JOIN BIN b ON b.bin_id = s.bin_id
             JOIN WAREHOUSE_ZONE z ON z.zone_id = b.zone_id
             ORDER BY s.has_discrepancy DESC, s.timestamp DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* UC-01 opt branch: environmental alerts (temp/humidity out of range) */
    public function fetchEnvironmentalAlerts() {
        $stmt = $this->conn->prepare(
            "SELECT zone_id, zone_name, temperature, humidity
             FROM WAREHOUSE_ZONE
             WHERE temperature > 25 OR humidity > 70
             ORDER BY zone_name"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* UC-01 opt branch: items expiring within window days */
    public function fetchItemsNearExpiry($window = 7) {
        $stmt = $this->conn->prepare(
            "SELECT it.item_id, it.sku, it.name, it.expiry_date,
                    i.quantity_available, b.location_code
             FROM ITEM it
             JOIN INVENTORY i ON i.item_id = it.item_id
             JOIN BIN b       ON b.bin_id   = i.bin_id
             WHERE it.expiry_date IS NOT NULL
               AND it.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
               AND it.expiry_date >= CURDATE()
             ORDER BY it.expiry_date ASC"
        );
        $stmt->execute([$window]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* UC-01: log a discrepancy alert into AUDIT_LOG (append-only) */
    public function logDiscrepancyAlert($zone_id, $sensor_id, $deviation) {
        $stmt = $this->conn->prepare(
            "INSERT INTO AUDIT_LOG (user_id, sensor_id, event_type, event_detail, reason, discrepancy_rate, timestamp)
             VALUES (NULL, ?, 'DISCREPANCY_ALERT', ?, 'Sensor weight deviation', ?, NOW())"
        );
        $stmt->execute([$sensor_id, "Zone {$zone_id} deviation {$deviation} kg", $deviation]);
    }

    /* PUML-aligned: updateStock() — adjust quantity_available for a given inventory_id */
    public function updateStock($inventory_id, $qty_delta) {
        $stmt = $this->conn->prepare(
            "UPDATE INVENTORY SET quantity_available = quantity_available + ? WHERE inventory_id = ?"
        );
        $stmt->execute([$qty_delta, $inventory_id]);
    }

    /* PUML-aligned: checkAvailability() — return true if qty_available > 0 */
    public function checkAvailability($inventory_id) {
        $stmt = $this->conn->prepare(
            "SELECT quantity_available FROM INVENTORY WHERE inventory_id = ?"
        );
        $stmt->execute([$inventory_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $row['quantity_available'] > 0;
    }
}
