<?php
require_once __DIR__ . '/../config/Database.php';

class Supplier {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function supplyItems() {
        // Placeholder for PUML alignment
    }

    public function confirmOrder() {
        // Placeholder for PUML alignment
    }

    public function getPerformance() {
        // Placeholder for PUML alignment
    }

    public function notifyObserver() {
        // Placeholder for PUML alignment
    }

    public function applyTieredLogic($price, $speed) {
        // Placeholder for PUML alignment
    }

    public function sendOP() {
        // Placeholder for PUML alignment
    }

    public function getDeliveryHistory() {
        // Placeholder for PUML alignment
    }

    // Methods for UC-05
    public function fetchSupplierOptions($skuList) {
        if (empty($skuList)) return [];
        $placeholders = str_repeat('?,', count($skuList) - 1) . '?';
        $stmt = $this->conn->prepare(
            "SELECT DISTINCT s.supplier_id, s.company_name, s.accuracy_score, s.tier_rank,
                    sc.contract_id, sc.unit_price, sc.discount_threshold 
             FROM SUPPLIER s
             JOIN SUPPLIER_CONTRACT sc ON sc.supplier_id = s.supplier_id
             JOIN ITEM i ON i.item_id = sc.item_id
             WHERE i.sku IN ($placeholders)"
        );
        $stmt->execute($skuList);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function fetchUnitPrices($skuList, $supplierId) {
        if (empty($skuList)) return [];
        $placeholders = str_repeat('?,', count($skuList) - 1) . '?';
        $stmt = $this->conn->prepare(
            "SELECT i.item_id, i.sku, sc.unit_price
             FROM ITEM i
             LEFT JOIN SUPPLIER_CONTRACT sc ON sc.item_id = i.item_id AND sc.supplier_id = ?
             WHERE i.sku IN ($placeholders)"
        );
        $params = [$supplierId];
        foreach ($skuList as $sku) {
            $params[] = $sku;
        }
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function fetchAllSuppliers() {
        $stmt = $this->conn->prepare("SELECT supplier_id, company_name, accuracy_score, tier_rank FROM SUPPLIER");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchDeliveryHistory($supplierId) {
        $stmt = $this->conn->prepare(
            "SELECT po.po_id, i.name AS item_name,
                    pli.quantity_ordered, pli.quantity_received
             FROM PO_LINE_ITEM pli
             JOIN PURCHASE_ORDER po ON po.po_id = pli.po_id
             JOIN ITEM i ON i.item_id = pli.item_id
             WHERE po.supplier_id = ? AND po.status = 'FULFILLED'
             ORDER BY po.po_id"
        );
        $stmt->execute([$supplierId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByUserId($userId) {
        $stmt = $this->conn->prepare(
            "SELECT supplier_id, user_id, company_name, contact_info, accuracy_score, tier_rank
             FROM SUPPLIER
             WHERE user_id = ?"
        );
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
