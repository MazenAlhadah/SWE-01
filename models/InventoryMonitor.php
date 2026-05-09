<?php
require_once __DIR__ . '/../config/Database.php';

/* Singleton — StockSubject in class_diagram_v3.puml */
class InventoryMonitor {

    private static $instance = null;
    private $conn;
    private $observers = [];

    private function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new InventoryMonitor();
        }
        return self::$instance;
    }

    /* StockSubject interface */
    public function subscribe($observer) {
        $this->observers[] = $observer;
    }

    public function unsubscribe($observer) {
        $this->observers = array_filter($this->observers, function($o) use ($observer) {
            return $o !== $observer;
        });
    }

    public function notifyAll() {
        foreach ($this->observers as $o) {
            $o->update('STOCK_BELOW_SAFETY');
        }
    }

    /* Check if any item is below safety stock */
    public function stockBelowSafety() {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS cnt
             FROM INVENTORY
             WHERE quantity_available < safety_stock"
        );
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['cnt'] > 0;
    }

    /* Trigger observer chain */
    public function notifyObserver() {
        $this->notifyAll();
    }

    /* Read latest weight from a zone's sensors */
    public function readWeight($zone_id) {
        $stmt = $this->conn->prepare(
            "SELECT AVG(s.last_reading) AS avg_weight
             FROM IOT_SENSOR s
             JOIN BIN b ON b.bin_id = s.bin_id
             WHERE b.zone_id = ?"
        );
        $stmt->execute([$zone_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['avg_weight'];
    }

    /* Get expected weight for a given SKU */
    public function getExpectedWeight($sku) {
        $stmt = $this->conn->prepare(
            "SELECT s.expected_weight_kg
             FROM IOT_SENSOR s
             JOIN BIN b ON b.bin_id = s.bin_id
             JOIN INVENTORY inv ON inv.bin_id = b.bin_id
             JOIN ITEM it ON it.item_id = inv.item_id
             WHERE it.sku = ?
             LIMIT 1"
        );
        $stmt->execute([$sku]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (float)$row['expected_weight_kg'] : 0.0;
    }

    /* Compare actual vs expected — returns deviation */
    public function compareWeights($actual, $expected) {
        return abs($actual - $expected);
    }
}
