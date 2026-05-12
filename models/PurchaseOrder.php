<?php
require_once __DIR__ . '/../config/Database.php';

class PurchaseOrder {
    private $conn;
    private const VALID_STATUSES = ['PENDING', 'CONFIRMED', 'MODIFICATION_REQUESTED', 'FULFILLED'];

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function createPO($poData) {
        $stmt = $this->conn->prepare(
            "INSERT INTO PURCHASE_ORDER (supplier_id, manager_id, contract_id, status, total_cost, digital_signature, generated_at)
             VALUES (?, ?, ?, 'PENDING', ?, ?, NOW())"
        );
        $stmt->execute([
            $poData['supplierId'],
            $poData['managerId'],
            $poData['contractId'],
            $poData['totalCost'],
            $poData['signature']
        ]);
        $poId = $this->conn->lastInsertId();

        // Insert line items
        $stmtLine = $this->conn->prepare(
            "INSERT INTO PO_LINE_ITEM (po_id, item_id, quantity_ordered, quantity_received, unit_price)
             VALUES (?, ?, ?, 0, ?)"
        );
        foreach ($poData['skus'] as $i => $sku) {
            $stmtLine->execute([
                $poId,
                $poData['item_ids'][$i],
                $poData['quantities'][$i],
                $poData['unitPrices'][$i]
            ]);
        }
        return $poId;
    }

    public function approvePO($poId) {
        return $this->updatePOStatus($poId, 'CONFIRMED');
    }

    public function receivePO() {
        // Method placeholder for PUML alignment
    }

    public function generatePO($sku, $prices, $signature) {
        // Method placeholder for PUML alignment
    }

    public function sendOP() {
        // Method placeholder for PUML alignment
    }

    // Additional methods for UC-05 and UC-16
    public function logPOSent($poId) {
        // Handled by ProcurementController or here?
        // Sequence diagram says PC -> DB : logPOSent(poId, timestamp)
        $stmt = $this->conn->prepare(
            "INSERT INTO AUDIT_LOG (user_id, sensor_id, event_type, event_detail, reason, discrepancy_rate, timestamp)
             VALUES (NULL, NULL, 'PO_SENT', ?, 'Purchase order delivered to supplier portal', 0, NOW())"
        );
        $stmt->execute(["PO $poId sent to supplier"]);
    }

    public function getPO($poId) {
        $stmt = $this->conn->prepare(
            "SELECT po.po_id, po.supplier_id, po.status, po.total_cost, po.digital_signature,
                    po.generated_at, po.confirmed_at, s.company_name
             FROM PURCHASE_ORDER po
             JOIN SUPPLIER s ON s.supplier_id = po.supplier_id
             WHERE po.po_id = ?"
        );
        $stmt->execute([$poId]);
        $po = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($po) {
            $stmtLine = $this->conn->prepare(
                "SELECT pli.item_id, pli.quantity_ordered, pli.unit_price, i.sku, i.name
                 FROM PO_LINE_ITEM pli
                 JOIN ITEM i ON i.item_id = pli.item_id
                 WHERE pli.po_id = ?"
            );
            $stmtLine->execute([$poId]);
            $po['items'] = $stmtLine->fetchAll(PDO::FETCH_ASSOC);
        }
        return $po;
    }

    public function updatePOStatus($poId, $status) {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            return false;
        }

        $stmt = $this->conn->prepare("SELECT status FROM PURCHASE_ORDER WHERE po_id = ?");
        $stmt->execute([$poId]);
        $currentStatus = $stmt->fetchColumn();

        if ($currentStatus === false || !$this->isAllowedStatusTransition($currentStatus, $status)) {
            return false;
        }

        if ($status === 'CONFIRMED') {
            $stmt = $this->conn->prepare(
                "UPDATE PURCHASE_ORDER
                 SET status = ?, confirmed_at = COALESCE(confirmed_at, NOW())
                 WHERE po_id = ?"
            );
            return $stmt->execute([$status, $poId]);
        }

        $stmt = $this->conn->prepare(
            "UPDATE PURCHASE_ORDER SET status = ? WHERE po_id = ?"
        );
        return $stmt->execute([$status, $poId]);
    }

    public function logConfirmation($poId) {
        $stmt = $this->conn->prepare(
            "INSERT INTO AUDIT_LOG (user_id, sensor_id, event_type, event_detail, reason, discrepancy_rate, timestamp)
             VALUES (NULL, NULL, 'PO_CONFIRMED', ?, 'Supplier confirmed purchase order', 0, NOW())"
        );
        $stmt->execute(["Supplier confirmed PO $poId"]);
    }

    public function logModificationRequest($poId, $details) {
        $stmt = $this->conn->prepare(
            "INSERT INTO AUDIT_LOG (user_id, sensor_id, event_type, event_detail, reason, discrepancy_rate, timestamp)
             VALUES (NULL, NULL, 'PO_MODIFIED', ?, ?, 0, NOW())"
        );
        $stmt->execute([
            "Supplier requested modification for PO $poId",
            empty($details) ? 'Modification requested' : $details
        ]);
    }

    public function getPOsBySupplier($supplierId) {
        $stmt = $this->conn->prepare(
            "SELECT po_id, status, total_cost, generated_at 
             FROM PURCHASE_ORDER 
             WHERE supplier_id = ?
             ORDER BY generated_at DESC"
        );
        $stmt->execute([$supplierId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPOsForDashboard() {
        $stmt = $this->conn->prepare(
            "SELECT po.po_id,
                    po.status,
                    po.total_cost,
                    po.generated_at,
                    s.company_name,
                    sh.shipment_id,
                    sh.state AS shipment_state,
                    sh.estimated_arrival,
                    sh.carrier_id
             FROM PURCHASE_ORDER po
             JOIN SUPPLIER s ON s.supplier_id = po.supplier_id
             LEFT JOIN SHIPMENT sh
               ON sh.shipment_id = (
                    SELECT sh2.shipment_id
                    FROM SHIPMENT sh2
                    WHERE sh2.po_id = po.po_id
                    ORDER BY sh2.shipment_id DESC
                    LIMIT 1
               )
             ORDER BY
                 CASE
                     WHEN po.status = 'FULFILLED' THEN 0
                     WHEN po.status = 'MODIFICATION_REQUESTED' THEN 0
                     WHEN po.status = 'PENDING' THEN 1
                     WHEN po.status = 'CONFIRMED' THEN 2
                     ELSE 3
                 END,
                 po.generated_at DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function isAllowedStatusTransition($currentStatus, $nextStatus) {
        if ($currentStatus === $nextStatus) {
            return true;
        }

        $allowedTransitions = [
            'PENDING' => ['CONFIRMED'],
            'CONFIRMED' => ['MODIFICATION_REQUESTED', 'FULFILLED'],
            'MODIFICATION_REQUESTED' => [],
            'FULFILLED' => [],
        ];

        return in_array($nextStatus, $allowedTransitions[$currentStatus] ?? [], true);
    }
}
