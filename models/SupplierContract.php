<?php
require_once __DIR__ . '/../config/Database.php';

class SupplierContract {
    private $conn;

    public function __construct() {
        $this->conn = Database::getInstance()->getConnection();
    }

    public function getActiveVersion() {
        // Placeholder for PUML alignment
    }

    public function updatePricing() {
        // Placeholder for PUML alignment
    }

    // DB logic for fetchSupplierContracts and getContractVersions
    public function fetchSupplierContracts() {
        $stmt = $this->conn->prepare(
            "SELECT sc.contract_id, sc.supplier_id, sc.item_id, i.name AS item_name,
                    sc.version, sc.unit_price, sc.discount_threshold,
                    sc.discount_percentage, sc.valid_from, sc.valid_to
             FROM SUPPLIER_CONTRACT sc
             JOIN ITEM i ON i.item_id = sc.item_id
             ORDER BY sc.supplier_id, sc.item_id, sc.version"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getContractVersions() {
        return $this->fetchSupplierContracts();
    }
}
