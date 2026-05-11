<?php
require_once __DIR__ . '/../config/Database.php';

class Order {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function createOrder() {
        // Placeholder for PUML alignment
    }

    public function cancelOrder() {
        // Placeholder for PUML alignment
    }

    public function calculateTotal() {
        // Placeholder for PUML alignment
    }

    public function placeOrder() {
        // Placeholder for PUML alignment
    }

    public function getOrdersReadyForPicking() {
        if (!$this->hasTable('CUSTOMER_ORDER') || !$this->hasTable('ORDER_LINE_ITEM')) {
            return [];
        }

        $stmt = $this->conn->prepare(
            "SELECT co.order_id, co.status, co.urgency, co.shipping_address,
                    oli.order_line_id, oli.item_id, oli.quantity, oli.state,
                    it.sku, it.name, b.bin_id, b.location_code, z.zone_name
             FROM CUSTOMER_ORDER co
             JOIN ORDER_LINE_ITEM oli ON oli.order_id = co.order_id
             JOIN ITEM it ON it.item_id = oli.item_id
             LEFT JOIN INVENTORY inv ON inv.item_id = it.item_id
             LEFT JOIN BIN b ON b.bin_id = inv.bin_id
             LEFT JOIN WAREHOUSE_ZONE z ON z.zone_id = b.zone_id
             WHERE co.status = 'PROCESSING'
             ORDER BY co.created_at ASC, z.zone_name ASC, b.location_code ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchPickedOrders($packerId = null) {
        if (!$this->hasTable('CUSTOMER_ORDER') || !$this->hasTable('ORDER_LINE_ITEM')) {
            return [];
        }

        $sql =
            "SELECT co.order_id, co.status, co.urgency, co.shipping_address, co.created_at,
                    COUNT(oli.order_line_id) AS item_count,
                    SUM(oli.quantity) AS total_units
             FROM CUSTOMER_ORDER co
             JOIN ORDER_LINE_ITEM oli ON oli.order_id = co.order_id
             WHERE (co.status = 'PICKING'";

        $params = [];

        if ($this->hasColumn('CUSTOMER_ORDER', 'packer_id')) {
            $sql .= " OR (co.status = 'PACKING' AND (co.packer_id IS NULL";
            if ($packerId !== null) {
                $sql .= " OR co.packer_id = ?))";
                $params[] = $packerId;
            } else {
                $sql .= "))";
            }
        }

        $sql .= ")
             GROUP BY co.order_id, co.status, co.urgency, co.shipping_address, co.created_at
             ORDER BY FIELD(co.status, 'PACKING', 'PICKING'), co.created_at ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchOrderItems($orderId) {
        if (!$this->hasTable('ORDER_LINE_ITEM')) {
            return [];
        }

        $stmt = $this->conn->prepare(
            "SELECT oli.order_line_id, oli.order_id, oli.item_id, oli.quantity, oli.state,
                    it.sku, it.name, it.weight, it.height_cm, it.width_cm, it.depth_cm,
                    b.bin_id, b.location_code, z.zone_name
             FROM ORDER_LINE_ITEM oli
             JOIN ITEM it ON it.item_id = oli.item_id
             LEFT JOIN INVENTORY inv ON inv.item_id = it.item_id
             LEFT JOIN BIN b ON b.bin_id = inv.bin_id
             LEFT JOIN WAREHOUSE_ZONE z ON z.zone_id = b.zone_id
             WHERE oli.order_id = ?
             ORDER BY z.zone_name ASC, b.location_code ASC, oli.order_line_id ASC"
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchItemDimensions($orderId) {
        if (!$this->hasTable('ORDER_LINE_ITEM')) {
            return [];
        }

        $stmt = $this->conn->prepare(
            "SELECT it.item_id, it.name, oli.quantity, it.height_cm, it.width_cm, it.depth_cm
             FROM ORDER_LINE_ITEM oli
             JOIN ITEM it ON it.item_id = oli.item_id
             WHERE oli.order_id = ?
             ORDER BY oli.order_line_id ASC"
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchExpectedWeight($orderId) {
        if (!$this->hasTable('ORDER_LINE_ITEM')) {
            return 0;
        }

        $stmt = $this->conn->prepare(
            "SELECT SUM(COALESCE(it.weight, 0) * oli.quantity) AS expected_weight
             FROM ORDER_LINE_ITEM oli
             JOIN ITEM it ON it.item_id = oli.item_id
             WHERE oli.order_id = ?"
        );
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float)($row['expected_weight'] ?? 0);
    }

    public function fetchOrderAndCarrierDetails($orderId) {
        if (!$this->hasTable('CUSTOMER_ORDER')) {
            return [];
        }

        $stmt = $this->conn->prepare(
            "SELECT co.order_id, co.customer_id, co.carrier_id, co.shipping_address, co.urgency,
                    sc.carrier_name
             FROM CUSTOMER_ORDER co
             LEFT JOIN SHIPPING_CARRIER sc ON sc.carrier_id = co.carrier_id
             WHERE co.order_id = ?
             LIMIT 1"
        );
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [];
        }

        $row['items'] = $this->fetchOrderItems($orderId);
        return $row;
    }

    public function getOrdersForStaff($staffId) {
        if (!$this->hasTable('CUSTOMER_ORDER')) {
            return [];
        }

        $sql =
            "SELECT co.order_id, co.status, co.total_amount, co.urgency, co.shipping_address, co.created_at,
                    co.shipped_at, co.delivered_at";

        if ($this->hasColumn('CUSTOMER_ORDER', 'picker_id')) {
            $sql .= ", co.picker_id";
        }
        if ($this->hasColumn('CUSTOMER_ORDER', 'packer_id')) {
            $sql .= ", co.packer_id";
        }

        $sql .= " FROM CUSTOMER_ORDER co
                  WHERE co.status IN ('PROCESSING', 'PICKING', 'PACKING', 'SHIPPED')
                  ORDER BY FIELD(co.status, 'PROCESSING', 'PICKING', 'PACKING', 'SHIPPED'), co.created_at ASC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrderDetails($orderId) {
        if (!$this->hasTable('CUSTOMER_ORDER')) {
            return [];
        }

        $sql =
            "SELECT co.order_id, co.customer_id, co.carrier_id, co.status, co.total_amount,
                    co.shipping_address, co.urgency, co.created_at, co.shipped_at, co.delivered_at";

        if ($this->hasColumn('CUSTOMER_ORDER', 'picker_id')) {
            $sql .= ", co.picker_id";
        }
        if ($this->hasColumn('CUSTOMER_ORDER', 'packer_id')) {
            $sql .= ", co.packer_id";
        }

        $sql .= " FROM CUSTOMER_ORDER co WHERE co.order_id = ? LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [];
        }

        $row['items'] = $this->fetchOrderItems($orderId);
        return $row;
    }

    public function fetchOrderState($orderId) {
        if (!$this->hasTable('CUSTOMER_ORDER')) {
            return '';
        }

        $stmt = $this->conn->prepare(
            "SELECT status FROM CUSTOMER_ORDER WHERE order_id = ? LIMIT 1"
        );
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['status'] : '';
    }

    public function setOrderState($orderId, $state, $pickerId = null) {
        if (!$this->hasTable('CUSTOMER_ORDER')) {
            return;
        }

        if ($this->hasColumn('CUSTOMER_ORDER', 'picker_id') && $pickerId !== null) {
            $stmt = $this->conn->prepare(
                "UPDATE CUSTOMER_ORDER SET status = ?, picker_id = ? WHERE order_id = ?"
            );
            $stmt->execute([$state, $pickerId, $orderId]);
            return;
        }

        $stmt = $this->conn->prepare(
            "UPDATE CUSTOMER_ORDER SET status = ? WHERE order_id = ?"
        );
        $stmt->execute([$state, $orderId]);
    }

    public function markItemPickedInOrder($barcode) {
        if (!$this->hasTable('ORDER_LINE_ITEM')) {
            return;
        }

        $stmt = $this->conn->prepare(
            "UPDATE ORDER_LINE_ITEM oli
             JOIN ITEM it ON it.item_id = oli.item_id
             SET oli.state = 'PICKING'
             WHERE it.sku = ?
             ORDER BY oli.order_line_id ASC
             LIMIT 1"
        );
        $stmt->execute([$barcode]);
    }

    public function markOrderLinePicked($orderLineId) {
        if (!$this->hasTable('ORDER_LINE_ITEM')) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "UPDATE ORDER_LINE_ITEM
             SET state = 'PICKING'
             WHERE order_line_id = ?"
        );
        $stmt->execute([$orderLineId]);
        return $stmt->rowCount() > 0;
    }

    public function assignPacker($orderId, $packerId) {
        if (!$this->hasTable('CUSTOMER_ORDER') || !$this->hasColumn('CUSTOMER_ORDER', 'packer_id')) {
            return;
        }

        $stmt = $this->conn->prepare(
            "UPDATE CUSTOMER_ORDER SET packer_id = ? WHERE order_id = ?"
        );
        $stmt->execute([$packerId, $orderId]);
    }

    public function setPackingState($orderId, $packerId = null) {
        if (!$this->hasTable('CUSTOMER_ORDER')) {
            return;
        }

        if ($this->hasColumn('CUSTOMER_ORDER', 'packer_id') && $packerId !== null) {
            $stmt = $this->conn->prepare(
                "UPDATE CUSTOMER_ORDER SET status = 'PACKING', packer_id = ? WHERE order_id = ?"
            );
            $stmt->execute([$packerId, $orderId]);
            return;
        }

        $stmt = $this->conn->prepare(
            "UPDATE CUSTOMER_ORDER SET status = 'PACKING' WHERE order_id = ?"
        );
        $stmt->execute([$orderId]);
    }

    public function updateOrderState($orderId, $state) {
        if (!$this->hasTable('CUSTOMER_ORDER')) {
            return false;
        }

        $sql = "UPDATE CUSTOMER_ORDER SET status = ?";
        $params = [$state];

        if ($state === 'SHIPPED' && $this->hasColumn('CUSTOMER_ORDER', 'shipped_at')) {
            $sql .= ", shipped_at = NOW()";
        }

        if ($state === 'DELIVERED' && $this->hasColumn('CUSTOMER_ORDER', 'delivered_at')) {
            $sql .= ", delivered_at = NOW()";
        }

        $sql .= " WHERE order_id = ?";
        $params[] = $orderId;

        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function fetchCompletedOrdersOlderThan($months = 12) {
        if (!$this->hasTable('CUSTOMER_ORDER')) {
            return [];
        }

        $dateField = $this->hasColumn('CUSTOMER_ORDER', 'delivered_at') ? 'delivered_at' : 'created_at';
        $select = ['order_id', 'status'];
        $optional = [
            'customer_id',
            'picker_id',
            'packer_id',
            'carrier_id',
            'total_amount',
            'shipping_address',
            'urgency',
            'date',
            'created_at',
            'shipped_at',
            'delivered_at'
        ];

        foreach ($optional as $column) {
            if ($this->hasColumn('CUSTOMER_ORDER', $column)) {
                $select[] = $column;
            }
        }

        $stmt = $this->conn->prepare(
            "SELECT " . implode(', ', $select) . "
             FROM CUSTOMER_ORDER
             WHERE status = 'DELIVERED'
               AND {$dateField} IS NOT NULL
               AND {$dateField} < DATE_SUB(NOW(), INTERVAL ? MONTH)
             ORDER BY {$dateField} ASC"
        );
        $stmt->execute([(int)$months]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orders as &$row) {
            $row['items'] = $this->fetchArchivedLineItems($row['order_id']);
        }

        return $orders;
    }

    public function archiveOrderRecord($orderId, $archiveData) {
        if (!$this->hasTable('ARCHIVED_ORDER')) {
            $_SESSION['archived_orders'][$orderId] = [
                'order_id' => $orderId,
                'archive_data' => $archiveData,
                'archived_at' => date('Y-m-d H:i:s')
            ];
            return true;
        }

        $stmt = $this->conn->prepare(
            "SELECT archive_id FROM ARCHIVED_ORDER WHERE order_id = ? LIMIT 1"
        );
        $stmt->execute([$orderId]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO ARCHIVED_ORDER (order_id, archive_data, archived_at)
             VALUES (?, ?, NOW())"
        );
        $stmt->execute([$orderId, $archiveData]);
        return true;
    }

    public function resolvePackerId($userId) {
        if (!$this->hasTable('PACKER')) {
            return (int)$userId;
        }

        if (!$this->hasColumn('PACKER', 'user_id')) {
            return null;
        }

        $stmt = $this->conn->prepare(
            "SELECT packer_id FROM PACKER WHERE user_id = ? LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || !isset($row['packer_id'])) {
            return null;
        }

        return $row['packer_id'];
    }

    public function removeFromActiveDB($orderId) {
        if (!$this->hasTable('CUSTOMER_ORDER')) {
            return false;
        }

        if ($this->hasTable('ORDER_LINE_ITEM')) {
            $stmt = $this->conn->prepare(
                "DELETE FROM ORDER_LINE_ITEM WHERE order_id = ?"
            );
            $stmt->execute([$orderId]);
        }

        if ($this->hasTable('SHIPPING_LABEL')) {
            $stmt = $this->conn->prepare(
                "DELETE FROM SHIPPING_LABEL WHERE order_id = ?"
            );
            $stmt->execute([$orderId]);
        }

        $stmt = $this->conn->prepare(
            "DELETE FROM CUSTOMER_ORDER WHERE order_id = ?"
        );
        $stmt->execute([$orderId]);
        return true;
    }

    public function fetchFromArchive($orderId) {
        if (!$this->hasTable('ARCHIVED_ORDER')) {
            return $_SESSION['archived_orders'][$orderId] ?? [];
        }

        $stmt = $this->conn->prepare(
            "SELECT archive_id, order_id, archive_data, archived_at
             FROM ARCHIVED_ORDER
             WHERE order_id = ?
             LIMIT 1"
        );
        $stmt->execute([$orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : [];
    }

    private function hasTable($table) {
        $stmt = $this->conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->fetch() !== false;
    }

    private function hasColumn($table, $column) {
        $stmt = $this->conn->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
        $stmt->execute([$column]);
        return $stmt->fetch() !== false;
    }

    private function fetchArchivedLineItems($orderId) {
        if (!$this->hasTable('ORDER_LINE_ITEM')) {
            return [];
        }

        $stmt = $this->conn->prepare(
            "SELECT oli.order_line_id, oli.item_id, oli.quantity, oli.state, oli.unit_price,
                    it.sku, it.name
             FROM ORDER_LINE_ITEM oli
             LEFT JOIN ITEM it ON it.item_id = oli.item_id
             WHERE oli.order_id = ?
             ORDER BY oli.order_line_id ASC"
        );
        $stmt->execute([$orderId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
