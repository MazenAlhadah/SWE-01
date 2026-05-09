<?php
require_once __DIR__ . '/../config/Database.php';

class Backorder {

    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    /* PUML-aligned: createBackOrder() — insert a new BACKORDER row */
    public function createBackOrder($order_line_id, $item_id, $qty, $reason) {
        $stmt = $this->conn->prepare(
            "INSERT INTO BACKORDER (order_line_id, item_id, quantity_needed, reason, status, created_at)
             VALUES (?, ?, ?, ?, 'OPEN', NOW())"
        );
        $stmt->execute([$order_line_id, $item_id, $qty, $reason]);
        return $this->conn->lastInsertId();
    }

    /* PUML-aligned: resolveBackOrder() — mark fulfilled */
    public function resolveBackOrder($backorder_id, $status = 'FULFILLED') {
        $stmt = $this->conn->prepare(
            "UPDATE BACKORDER SET status = ?, fulfilled_at = NOW() WHERE backorder_id = ?"
        );
        $stmt->execute([$status, $backorder_id]);
    }

    /* UC-15: fetch all OPEN backorders */
    public function fetchOpenBackorders() {
        $stmt = $this->conn->prepare(
            "SELECT b.backorder_id, b.order_line_id, b.item_id, b.quantity_needed,
                    b.reason, b.status, b.created_at,
                    it.sku, it.name
             FROM BACKORDER b
             JOIN ITEM it ON it.item_id = b.item_id
             WHERE b.status = 'OPEN'
             ORDER BY b.created_at ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* UC-15: match incoming PO items to open backorders by item_id */
    public function matchIncomingToBackorders($incoming_items, $backorders) {
        $incoming_ids = array_column($incoming_items, 'item_id');
        return array_values(array_filter($backorders, function($b) use ($incoming_ids) {
            return in_array($b['item_id'], $incoming_ids);
        }));
    }

    /* UC-15: setBackorderStatus — used by BackorderService */
    public function setBackorderStatus($backorder_id, $status) {
        $stmt = $this->conn->prepare(
            "UPDATE BACKORDER SET status = ?, fulfilled_at = NOW() WHERE backorder_id = ?"
        );
        $stmt->execute([$status, $backorder_id]);
    }

    /* UC-15: markItemForCrossDocking — set INVENTORY.state = 'CROSS_DOCKED' */
    public function markItemForCrossDocking($item_id) {
        $stmt = $this->conn->prepare(
            "UPDATE INVENTORY SET state = 'CROSS_DOCKED' WHERE item_id = ?"
        );
        $stmt->execute([$item_id]);
    }
}
