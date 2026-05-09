<?php
require_once __DIR__ . '/../config/Database.php';

class PickList {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function generateRoute() {
        // Placeholder for PUML alignment
    }

    public function confirmPick($picklistId, $barcode = '') {
        if (!$this->hasTable('PICK_LIST_ITEM')) {
            return;
        }

        if (!empty($barcode)) {
            $stmt = $this->conn->prepare(
                "UPDATE PICK_LIST_ITEM pli
                 JOIN ORDER_LINE_ITEM oli ON oli.order_line_id = pli.order_line_id
                 JOIN ITEM it ON it.item_id = oli.item_id
                 SET pli.is_picked = 1, pli.picked_at = NOW()
                 WHERE pli.picklist_id = ? AND it.sku = ?
                 ORDER BY pli.pl_item_id ASC
                 LIMIT 1"
            );
            $stmt->execute([$picklistId, $barcode]);
        }
    }

    public function generatePickList($pickerId, $pickList) {
        if (!$this->hasTable('PICK_LIST')) {
            return 0;
        }

        $route = implode(' -> ', $pickList['route']);
        $stmt = $this->conn->prepare(
            "INSERT INTO PICK_LIST (picker_id, optimized_route, batch_size, status, created_at, completed_at)
             VALUES (?, ?, ?, 'ACTIVE', NOW(), NULL)"
        );
        $stmt->execute([$pickerId, $route, count($pickList['items'])]);
        $picklistId = (int)$this->conn->lastInsertId();

        if ($this->hasTable('PICK_LIST_ITEM') && !empty($pickList['items'])) {
            $stmtItem = $this->conn->prepare(
                "INSERT INTO PICK_LIST_ITEM (picklist_id, order_line_id, bin_id, is_picked, picked_at)
                 VALUES (?, ?, ?, 0, NULL)"
            );
            foreach ($pickList['items'] as $row) {
                $stmtItem->execute([
                    $picklistId,
                    $row['order_line_id'],
                    $row['bin_id']
                ]);
            }
        }

        return $picklistId;
    }

    public function applyFEFO($item) {
        return $item;
    }

    public function resolvePickerId($userId) {
        if (!$this->hasTable('PICKER')) {
            return (int)$userId;
        }

        $stmt = $this->conn->prepare(
            "SELECT picker_id FROM PICKER WHERE user_id = ? LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['picker_id'] : (int)$userId;
    }

    public function setCompleted($picklistId) {
        if (!$this->hasTable('PICK_LIST')) {
            return;
        }

        $stmt = $this->conn->prepare(
            "UPDATE PICK_LIST SET status = 'COMPLETED', completed_at = NOW() WHERE picklist_id = ?"
        );
        $stmt->execute([$picklistId]);
    }

    public function findOpenPickListByPicker($pickerId) {
        if (!$this->hasTable('PICK_LIST')) {
            return [];
        }

        $stmt = $this->conn->prepare(
            "SELECT picklist_id, optimized_route, batch_size, status, created_at, completed_at
             FROM PICK_LIST
             WHERE picker_id = ?
               AND status IN ('ACTIVE', 'PAUSED')
             ORDER BY created_at DESC
             LIMIT 1"
        );
        $stmt->execute([$pickerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row : [];
    }

    public function getPickListItems($picklistId) {
        if (!$this->hasTable('PICK_LIST_ITEM')) {
            return [];
        }

        $stmt = $this->conn->prepare(
            "SELECT pli.pl_item_id, pli.picklist_id, pli.order_line_id, pli.bin_id, pli.is_picked, pli.picked_at,
                    oli.order_id, oli.item_id, oli.quantity,
                    it.sku, it.name,
                    b.location_code, z.zone_name
             FROM PICK_LIST_ITEM pli
             JOIN ORDER_LINE_ITEM oli ON oli.order_line_id = pli.order_line_id
             JOIN ITEM it ON it.item_id = oli.item_id
             LEFT JOIN BIN b ON b.bin_id = pli.bin_id
             LEFT JOIN WAREHOUSE_ZONE z ON z.zone_id = b.zone_id
             WHERE pli.picklist_id = ?
             ORDER BY pli.pl_item_id ASC"
        );
        $stmt->execute([$picklistId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function hasTable($table) {
        $stmt = $this->conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return $stmt->fetch() !== false;
    }
}
