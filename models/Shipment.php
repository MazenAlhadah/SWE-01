<?php
require_once __DIR__ . '/../config/Database.php';

class Shipment {
    private $conn;
    private $shipmentColumns = null;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function shipOrder() {
        // Placeholder for PUML alignment
    }

    public function trackShipment() {
        // Placeholder for PUML alignment
    }

    public function updateLeadTime() {
        // Placeholder for PUML alignment
    }

    public function setState($state) {
        // Placeholder for PUML alignment
    }

    public function persistShipmentDetails($poId, $dispatchDate, $arrivalDate, $items) {
        $trackingNumber = 'TRK-' . $poId . '-' . date('His');

        $stmt = $this->conn->prepare(
            "SELECT shipment_id FROM SHIPMENT WHERE po_id = ? ORDER BY shipment_id DESC LIMIT 1"
        );
        $stmt->execute([$poId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $shipmentId = (int)$existing['shipment_id'];
            $sql = "UPDATE SHIPMENT SET dispatch_date = ?";
            $params = [$dispatchDate];

            if ($this->hasShipmentColumn('estimated_arrival')) {
                $sql .= ", estimated_arrival = ?";
                $params[] = $arrivalDate;
            }
            if ($this->hasShipmentColumn('tracking_number')) {
                $sql .= ", tracking_number = ?";
                $params[] = $trackingNumber;
            }

            $sql .= " WHERE shipment_id = ?";
            $params[] = $shipmentId;

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
        } else {
            $columns = ['po_id', 'carrier_id', 'dispatch_date', 'state'];
            $values = ['?', 'NULL', '?', "'EXPECTED'"];
            $params = [$poId, $dispatchDate];

            if ($this->hasShipmentColumn('tracking_number')) {
                $columns[] = 'tracking_number';
                $values[] = '?';
                $params[] = $trackingNumber;
            }
            if ($this->hasShipmentColumn('estimated_arrival')) {
                $columns[] = 'estimated_arrival';
                $values[] = '?';
                $params[] = $arrivalDate;
            }

            $stmt = $this->conn->prepare(
                "INSERT INTO SHIPMENT (" . implode(', ', $columns) . ")
                 VALUES (" . implode(', ', $values) . ")"
            );
            $stmt->execute($params);
            $shipmentId = (int)$this->conn->lastInsertId();
        }

        if (!empty($items)) {
            $stmtLine = $this->conn->prepare(
                "UPDATE PO_LINE_ITEM
                 SET quantity_received = ?
                 WHERE po_id = ? AND item_id = ?"
            );

            foreach ($items as $itemId => $qty) {
                $stmtLine->execute([(int)$qty, $poId, (int)$itemId]);
            }
        }

        return $shipmentId;
    }

    public function getShipmentById($shipmentId) {
        $select = ['shipment_id', 'po_id', 'carrier_id', 'dispatch_date', 'state'];
        if ($this->hasShipmentColumn('tracking_number')) {
            $select[] = 'tracking_number';
        }
        if ($this->hasShipmentColumn('estimated_arrival')) {
            $select[] = 'estimated_arrival';
        }
        if ($this->hasShipmentColumn('actual_arrival')) {
            $select[] = 'actual_arrival';
        }

        $stmt = $this->conn->prepare(
            "SELECT " . implode(', ', $select) . "
             FROM SHIPMENT
             WHERE shipment_id = ?"
        );
        $stmt->execute([$shipmentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return $row;
        }

        if (!isset($row['tracking_number'])) {
            $row['tracking_number'] = '';
        }
        if (!isset($row['estimated_arrival'])) {
            $row['estimated_arrival'] = '';
        }
        if (!isset($row['actual_arrival'])) {
            $row['actual_arrival'] = '';
        }

        return $row;
    }

    public function setShipmentState($shipmentId, $state) {
        $stmt = $this->conn->prepare(
            "UPDATE SHIPMENT SET state = ? WHERE shipment_id = ?"
        );
        $stmt->execute([$state, $shipmentId]);
    }

    public function fetchSupplierDeliveryHistory($supplierId) {
        $select = ['sh.dispatch_date'];
        if ($this->hasShipmentColumn('estimated_arrival')) {
            $select[] = 'sh.estimated_arrival';
        }
        if ($this->hasShipmentColumn('actual_arrival')) {
            $select[] = 'sh.actual_arrival';
        }

        $stmt = $this->conn->prepare(
            "SELECT " . implode(', ', $select) . "
             FROM SHIPMENT sh
             JOIN PURCHASE_ORDER po ON po.po_id = sh.po_id
             WHERE po.supplier_id = ?
             ORDER BY sh.dispatch_date DESC"
        );
        $stmt->execute([$supplierId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            if (!isset($row['estimated_arrival'])) {
                $row['estimated_arrival'] = '';
            }
            if (!isset($row['actual_arrival'])) {
                $row['actual_arrival'] = '';
            }
        }

        return $rows;
    }

    public function checkCarrierAssignment($shipmentId) {
        $stmt = $this->conn->prepare(
            "SELECT carrier_id FROM SHIPMENT WHERE shipment_id = ?"
        );
        $stmt->execute([$shipmentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && !empty($row['carrier_id']);
    }

    public function updateShipmentCarrier($shipmentId, $carrierId) {
        $stmt = $this->conn->prepare(
            "UPDATE SHIPMENT SET carrier_id = ? WHERE shipment_id = ?"
        );
        $stmt->execute([$carrierId, $shipmentId]);
    }

    public function fetchOrderIdForShipment($shipmentId) {
        if (!$this->hasTable('CUSTOMER_ORDER') || !$this->hasTable('ORDER_LINE_ITEM')) {
            return 0;
        }

        $stmt = $this->conn->prepare(
            "SELECT co.order_id
             FROM SHIPMENT sh
             JOIN PO_LINE_ITEM pli ON pli.po_id = sh.po_id
             JOIN ORDER_LINE_ITEM oli ON oli.item_id = pli.item_id
             JOIN CUSTOMER_ORDER co ON co.order_id = oli.order_id
             WHERE sh.shipment_id = ?
             ORDER BY co.created_at ASC
             LIMIT 1"
        );
        $stmt->execute([$shipmentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['order_id'] : 0;
    }

    public function detectBackorderedItemsInShipment($shipmentId) {
        $stmt = $this->conn->prepare(
            "SELECT DISTINCT pli.item_id, pli.quantity_received
             FROM SHIPMENT sh
             JOIN PO_LINE_ITEM pli ON pli.po_id = sh.po_id
             WHERE sh.shipment_id = ?
               AND pli.quantity_received > 0"
        );
        $stmt->execute([$shipmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchOpenBackorders($items) {
        if (empty($items)) {
            return [];
        }
        if (!$this->hasTable('CUSTOMER_ORDER') || !$this->hasTable('ORDER_LINE_ITEM')) {
            return [];
        }

        $itemIds = array_column($items, 'item_id');
        $placeholders = str_repeat('?,', count($itemIds) - 1) . '?';

        $stmt = $this->conn->prepare(
            "SELECT b.backorder_id, b.item_id, b.quantity_needed, b.created_at,
                    oli.order_id, co.customer_id
             FROM BACKORDER b
             JOIN ORDER_LINE_ITEM oli ON oli.order_line_id = b.order_line_id
             JOIN CUSTOMER_ORDER co ON co.order_id = oli.order_id
             WHERE b.status = 'OPEN'
               AND b.item_id IN ($placeholders)
             ORDER BY b.created_at ASC"
        );
        $stmt->execute($itemIds);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function allocateStockToCustomer($customerId, $itemId, $quantity) {
        $stmt = $this->conn->prepare(
            "UPDATE INVENTORY
             SET quantity_reserved = quantity_reserved + ?,
                 quantity_available = GREATEST(quantity_available - ?, 0)
             WHERE item_id = ?
             ORDER BY inventory_id ASC
             LIMIT 1"
        );
        $stmt->execute([$quantity, $quantity, $itemId]);
    }

    public function updateBackorderRecord($backorderId, $status) {
        $stmt = $this->conn->prepare(
            "UPDATE BACKORDER
             SET status = ?, fulfilled_at = NOW()
             WHERE backorder_id = ?"
        );
        $stmt->execute([$status, $backorderId]);
    }

    public function updateInventory($items) {
        if (empty($items)) {
            return;
        }

        $stmtSelect = $this->conn->prepare(
            "SELECT inventory_id FROM INVENTORY WHERE item_id = ? ORDER BY inventory_id ASC LIMIT 1"
        );
        $stmtUpdate = $this->conn->prepare(
            "UPDATE INVENTORY SET quantity_available = quantity_available + ? WHERE inventory_id = ?"
        );

        foreach ($items as $row) {
            $stmtSelect->execute([$row['item_id']]);
            $inv = $stmtSelect->fetch(PDO::FETCH_ASSOC);
            if ($inv) {
                $qty = isset($row['quantity_received']) ? (int)$row['quantity_received'] : (int)$row['quantity_needed'];
                $stmtUpdate->execute([$qty, $inv['inventory_id']]);
            }
        }
    }

    private function hasShipmentColumn($column) {
        if ($this->shipmentColumns === null) {
            $stmt = $this->conn->query("SHOW COLUMNS FROM SHIPMENT");
            $this->shipmentColumns = [];
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $this->shipmentColumns[] = $row['Field'];
            }
        }

        return in_array($column, $this->shipmentColumns);
    }

    private function hasTable($table) {
        $stmt = $this->conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->fetch() !== false;
    }
}
